<?php
namespace cms\services\post;

use cms\models\tables\PostCommentsTable;
use cms\models\tables\PostMetaTable;
use cms\services\OptionService;
use cms\services\user\UserService;
use fay\exceptions\RecordNotFoundException;
use fay\core\Loader;
use fay\helpers\ArrayHelper;
use fay\helpers\FieldsHelper;
use fay\models\MultiTreeModel;

class PostCommentService extends MultiTreeModel{
    /**
     * 评论创建后事件
     */
    const EVENT_CREATED = 'after_post_comment_created';
    
    /**
     * 评论被删除后事件
     */
    const EVENT_DELETED = 'after_post_comment_deleted';
    
    /**
     * 评论被还原后事件
     */
    const EVENT_UNDELETE = 'after_post_comment_undelete';
    
    /**
     * 评论被永久删除事件
     */
    const EVENT_REMOVING = 'before_post_comment_removed';
    
    /**
     * 评论通过审核事件
     */
    const EVENT_APPROVED = 'after_post_comment_approved';
    
    /**
     * 评论未通过审核事件
     */
    const EVENT_DISAPPROVED = 'after_post_comment_disapproved';
    
    /**
     * @see MultiTreeModel::$model
     */
    protected $model = 'cms\models\tables\PostCommentsTable';
    
    /**
     * @see MultiTreeModel::$foreign_key
     */
    protected $foreign_key = 'post_id';
    
    /**
     * @see MultiTreeModel::$field_key
     */
    protected $field_key = 'comment';
    
    /**
     * @return $this
     */
    public static function service(){
        return Loader::singleton(__CLASS__);
    }

    /**
     * 发表一条文章评论
     * @param int $post_id 文章ID
     * @param string $content 评论内容
     * @param int $parent 父ID，若是回复评论的评论，则带上被评论的评论ID，默认为0
     * @param int $status 状态（默认为待审核）
     * @param array $extra 扩展参数，二次开发时可能会用到
     * @param int $user_id 用户ID，若不指定，默认为当前登录用户ID
     * @param int $sockpuppet 马甲信息，若是真实用户，传入0，默认为0
     * @return int
     * @throws RecordNotFoundException
     * @throws \ErrorException
     */
    public function create($post_id, $content, $parent = 0, $status = PostCommentsTable::STATUS_PENDING, $extra = array(), $user_id = null, $sockpuppet = 0){
        $user_id === null && $user_id = \F::app()->current_user;
        
        if(!PostService::isPostIdExist($post_id)){
            throw new PostNotExistException($post_id);
        }
        
        if($parent){
            $parent_comment = PostCommentsTable::model()->find($parent, 'post_id,delete_time');
            if(!$parent_comment || $parent_comment['delete_time']){
                throw new RecordNotFoundException('父节点不存在');
            }
            if($parent_comment['post_id'] != $post_id){
                throw new \ErrorException('被评论文章ID与指定父节点文章ID不一致');
            }
        }
        
        $comment_id = $this->_create(array_merge($extra, array(
            'post_id'=>$post_id,
            'content'=>$content,
            'status'=>$status,
            'user_id'=>$user_id,
            'sockpuppet'=>$sockpuppet,
            'create_time'=>\F::app()->current_time,
            'update_time'=>\F::app()->current_time,
            'ip_int'=>\F::app()->ip_int,
        )), $parent);
        
        //更新文章评论数
        $this->updatePostComments(array(array(
            'post_id'=>$post_id,
            'status'=>$status,
            'sockpuppet'=>$sockpuppet,
        )), 'create');
        
        //触发事件
        \F::event()->trigger(self::EVENT_CREATED, $comment_id);
        
        return $comment_id;
    }

    /**
     * 软删除一条评论
     * 软删除不会修改parent标识，因为删除的东西随时都有可能会被恢复，而parent如果变了是无法被恢复的。
     * @param int $comment_id 评论ID
     * @throws RecordNotFoundException
     */
    public function delete($comment_id){
        $comment = PostCommentsTable::model()->find($comment_id, 'delete_time,post_id,status,sockpuppet');
        if(!$comment){
            throw new RecordNotFoundException('指定评论ID不存在');
        }
        if($comment['delete_time']){
            throw new RecordNotFoundException('评论已删除');
        }
        
        //软删除不需要动树结构，只要把deleted字段标记一下即可
        PostCommentsTable::model()->update(array(
            'delete_time'=>\F::app()->current_time,
            'update_time'=>\F::app()->current_time,
        ), $comment_id);
        
        //更新文章评论数
        $this->updatePostComments(array($comment), 'delete');
        
        //触发事件
        \F::event()->trigger(self::EVENT_DELETED, array($comment_id));
    }
    
    /**
     * 批量删除
     * @param array $comment_ids 由评论ID构成的一维数组
     * @return int
     */
    public function batchDelete($comment_ids){
        $comments = PostCommentsTable::model()->fetchAll(array(
            'id IN (?)'=>$comment_ids,
            'delete_time = 0',
        ), 'id,post_id,sockpuppet,status');
        if(!$comments){
            //无符合条件的记录
            return 0;
        }
        
        //实际将被删除的评论（之前已被删除的评论不重复删除）
        $affected_commend_ids = ArrayHelper::column($comments, 'id');
        //更新状态
        $affected_rows = PostCommentsTable::model()->update(array(
            'delete_time'=>\F::app()->current_time,
            'update_time'=>\F::app()->current_time,
        ), array(
            'id IN (?)'=>$affected_commend_ids,
        ));
        
        //更新文章评论数
        $this->updatePostComments($comments, 'delete');
        
        //触发事件
        \F::event()->trigger(self::EVENT_DELETED, $affected_commend_ids);
        
        return $affected_rows;
    }

    /**
     * 从回收站恢复一条评论
     * @param int $comment_id 评论ID
     * @throws RecordNotFoundException
     */
    public function undelete($comment_id){
        $comment = PostCommentsTable::model()->find($comment_id, 'delete_time,post_id,status,sockpuppet');
        if(!$comment){
            throw new RecordNotFoundException('指定评论ID不存在');
        }
        if(!$comment['delete_time']){
            throw new RecordNotFoundException('指定评论ID不在回收站中');
        }
        
        //还原不需要动树结构，只是把deleted字段标记一下即可
        PostCommentsTable::model()->update(array(
            'delete_time'=>0,
            'update_time'=>\F::app()->current_time,
        ), $comment_id);
        
        //更新文章评论数
        $this->updatePostComments(array($comment), 'undelete');
        
        //触发事件
        \F::event()->trigger(self::EVENT_UNDELETE, array($comment_id));
    }
    
    /**
     * 批量还原
     * @param array $comment_ids 由评论ID构成的一维数组
     * @return int
     */
    public function batchUnelete($comment_ids){
        $comments = PostCommentsTable::model()->fetchAll(array(
            'id IN (?)'=>$comment_ids,
            'delete_time > 0',
        ), 'id,post_id,sockpuppet,status');
        if(!$comments){
            //无符合条件的记录
            return 0;
        }
        
        //实际将被删除的评论（之前已被删除的评论不重复删除）
        $affected_commend_ids = ArrayHelper::column($comments, 'id');
        //更新状态
        $affected_rows = PostCommentsTable::model()->update(array(
            'delete_time'=>0,
            'update_time'=>\F::app()->current_time,
        ), array(
            'id IN (?)'=>$affected_commend_ids,
        ));
        
        //更新文章评论数
        $this->updatePostComments($comments, 'undelete');
        
        \F::event()->trigger(self::EVENT_UNDELETE, $affected_commend_ids);
        
        return $affected_rows;
    }

    /**
     * 删除一条评论及所有回复该评论的评论
     * @param int $comment_id 评论ID
     * @return array
     * @throws RecordNotFoundException
     */
    public function deleteAll($comment_id){
        $comment = PostCommentsTable::model()->find($comment_id, 'left_value,right_value,root');
        if(!$comment){
            throw new RecordNotFoundException('指定评论ID不存在');
        }
        
        //获取所有待删除节点
        $comments = PostCommentsTable::model()->fetchAll(array(
            'root = ?'=>$comment['root'],
            'left_value >= ' . $comment['left_value'],
            'right_value <= ' . $comment['right_value'],
            'delete_time = 0',
        ), 'id,post_id,status,sockpuppet');
        
        if($comments){
            //如果存在待删除节点，则执行删除
            $comment_ids = ArrayHelper::column($comments, 'id');
            PostCommentsTable::model()->update(array(
                'delete_time'=>\F::app()->current_time,
                'update_time'=>\F::app()->current_time,
            ), array(
                'id IN (?)'=>$comment_ids,
            ));
            
            //更新文章评论数
            $this->updatePostComments($comments, 'delete');
            
            //触发事件
            \F::event()->trigger(self::EVENT_DELETED, $comment_ids);
            
            return $comment_ids;
        }else{
            return array();
        }
    }

    /**
     * 永久删除一条评论
     * @param int $comment_id 评论ID
     * @return bool
     * @throws RecordNotFoundException
     */
    public function remove($comment_id){
        $comment = PostCommentsTable::model()->find($comment_id, '!content');
        if(!$comment){
            throw new RecordNotFoundException('指定评论ID不存在');
        }
        
        //触发事件，这个不能用after，记录都没了就没法找了
        \F::event()->trigger(self::EVENT_REMOVING, array($comment_id));
        
        $this->_remove($comment);
        
        if(!$comment['delete_time']){
            //更新文章评论数
            $this->updatePostComments(array($comment), 'remove');
        }
        
        return true;
    }

    /**
     * 物理删除一条评论及所有回复该评论的评论
     * @param int $comment_id 评论ID
     * @return array
     * @throws RecordNotFoundException
     */
    public function removeAll($comment_id){
        $comment = PostCommentsTable::model()->find($comment_id, '!content');
        if(!$comment){
            throw new RecordNotFoundException('指定评论ID不存在');
        }
        
        //获取所有待删除节点
        $comments = PostCommentsTable::model()->fetchAll(array(
            'root = ?'=>$comment['root'],
            'left_value >= ' . $comment['left_value'],
            'right_value <= ' . $comment['right_value'],
        ), 'id,post_id,status,sockpuppet');
        $comment_ids = ArrayHelper::column($comments, 'id');
        //触发事件
        \F::event()->trigger(self::EVENT_REMOVING, $comment_ids);
        
        //获取所有不在回收站内的节点（已删除的显然不需要再更新评论数了）
        $undeleted_comments = array();
        foreach($comment as $c){
            if(!$c['delete_time']){
                $undeleted_comments[] = $c;
            }
        }
        //更新文章评论数
        $this->updatePostComments($undeleted_comments, 'remove');
        
        //执行删除
        $this->_removeAll($comment);
        
        return $comment_ids;
    }

    /**
     * 通过审核
     * @param int $comment_id 评论ID
     * @return bool
     * @throws RecordNotFoundException
     */
    public function approve($comment_id){
        $comment = PostCommentsTable::model()->find($comment_id, '!content');
        if(!$comment){
            throw new RecordNotFoundException('指定评论ID不存在');
        }
        if($comment['delete_time']){
            throw new RecordNotFoundException('评论已删除');
        }
        if($comment['status'] == PostCommentsTable::STATUS_APPROVED){
            throw new \RuntimeException('已通过审核，请勿重复操作');
        }
        
        $this->setStatus($comment_id, PostCommentsTable::STATUS_APPROVED);
        
        //更新文章评论数
        $this->updatePostComments(array($comment), 'approve');
        
        //触发事件
        \F::event()->trigger(self::EVENT_APPROVED, array($comment_id));
        return true;
    }
    
    /**
     * 批量通过审核
     * @param array $comment_ids 由评论ID构成的一维数组
     * @return int
     */
    public function batchApprove($comment_ids){
        $comments = PostCommentsTable::model()->fetchAll(array(
            'id IN (?)'=>$comment_ids,
            'status != ' . PostCommentsTable::STATUS_APPROVED,
        ), 'id,post_id,sockpuppet,status');
        if(!$comments){
            //无符合条件的记录
            return 0;
        }
        
        $affected_commend_ids = ArrayHelper::column($comments, 'id');
        
        //更新状态
        $affected_rows = $this->setStatus($affected_commend_ids, PostCommentsTable::STATUS_APPROVED);
        
        //更新文章评论数
        $this->updatePostComments($comments, 'approve');
        
        \F::event()->trigger(self::EVENT_APPROVED, $affected_commend_ids);
        
        return $affected_rows;
    }

    /**
     * 不通过审核
     * @param int $comment_id 评论ID
     * @return bool
     * @throws RecordNotFoundException
     */
    public function disapprove($comment_id){
        $comment = PostCommentsTable::model()->find($comment_id, '!content');
        if(!$comment){
            throw new RecordNotFoundException('指定评论ID不存在');
        }
        if($comment['delete_time']){
            throw new RecordNotFoundException('评论已删除');
        }
        if($comment['status'] == PostCommentsTable::STATUS_UNAPPROVED){
            throw new \RuntimeException('该评论已是“未通过审核”状态，请勿重复操作');
        }
        
        $this->setStatus($comment_id, PostCommentsTable::STATUS_UNAPPROVED);
        
        //更新文章评论数
        $this->updatePostComments(array($comment), 'disapprove');
        
        //触发事件
        \F::event()->trigger(self::EVENT_DISAPPROVED, array($comment_id));
        return true;
    }
    
    /**
     * 批量不通过审核
     * @param array $comment_ids 由评论ID构成的一维数组
     * @return int
     */
    public function batchDisapprove($comment_ids){
        $comments = PostCommentsTable::model()->fetchAll(array(
            'id IN (?)'=>$comment_ids,
            'status != ' . PostCommentsTable::STATUS_UNAPPROVED,
        ), 'id,post_id,sockpuppet,status');
        if(!$comments){
            //无符合条件的记录
            return 0;
        }
        
        $affected_commend_ids = ArrayHelper::column($comments, 'id');
        
        //更新状态
        $affected_rows = $this->setStatus($affected_commend_ids, PostCommentsTable::STATUS_UNAPPROVED);
        
        //更新文章评论数
        $this->updatePostComments($comments, 'disapprove');
        
        \F::event()->trigger(self::EVENT_DISAPPROVED, $affected_commend_ids);
        
        return $affected_rows;
    }
    
    /**
     * 编辑一条评论（只能编辑评论内容部分）
     * @param int $comment_id 评论ID
     * @param string $content 评论内容
     * @return int
     */
    public function update($comment_id, $content){
        return PostCommentsTable::model()->update(array(
            'content'=>$content,
        ), $comment_id);
    }
    
    /**
     * 获取一条评论
     * @param int $comment_id 评论ID
     * @param array|string $fields 返回字段
     *  - comment.*系列可指定post_comments表返回字段，若有一项为'comment.*'，则返回所有字段
     *  - user.*系列可指定作者信息，格式参照\cms\services\user\UserService::get()
     *  - parent.comment.*系列可指定父评论post_comments表返回字段，若有一项为'comment.*'，则返回所有字段
     *  - parent._user.*系列可指定父评论作者信息，格式参照\cms\services\user\UserService::get()
     * @return array
     */
    public function get($comment_id, $fields = array(
        'comment'=>array(
            'id', 'content', 'parent', 'create_time',
        ),
        'user'=>array(
            'id', 'nickname', 'avatar',
        ),
        'parent'=>array(
            'comment'=>array(
                'id', 'content', 'parent', 'create_time',
            ),
            'user'=>array(
                'id', 'nickname', 'avatar',
            ),
        )
    )){
        $fields = new FieldsHelper($fields, 'comment');
        if(!$fields->getFields() || $fields->hasField('*')){
            //若未指定返回字段，初始化
            $fields->setFields(\F::table($this->model)->getFields(array('status', 'delete_time', 'sockpuppet')));
        }
        
        $comment_fields = $fields->getFields();
        if($fields->user && !in_array('user_id', $comment_fields)){
            //如果要获取作者信息，则必须搜出user_id
            $comment_fields[] = 'user_id';
        }
        if($fields->parent && !in_array('parent', $comment_fields)){
            //如果要获取作者信息，则必须搜出parent
            $comment_fields[] = 'parent';
        }
        
        $comment = \F::table($this->model)->fetchRow(array(
            'id = ?'=>$comment_id,
            'delete_time = 0',
        ), $comment_fields);
        
        if(!$comment){
            return array();
        }
        
        $return = array(
            'comment'=>$comment,
        );
        //作者信息
        if($fields->hasField('user')){
            $return['user'] = UserService::service()->get(
                $comment['user_id'],
                $fields->user
            );
        }
        
        //父节点
        if($fields->hasField('parent')){
            $parent_comment_fields = $fields->parent->comment ? $fields->parent->comment->getFields() : array();
            if($fields->parent->hasField('user') && !in_array('user_id', $parent_comment_fields)){
                //如果要获取作者信息，则必须搜出user_id
                $parent_comment_fields[] = 'user_id';
            }
            
            $parent_comment = \F::table($this->model)->fetchRow(array(
                'id = ?'=>$comment['parent'],
                'delete_time = 0',
            ), $parent_comment_fields);
            
            if($parent_comment){
                //有父节点
                if($fields->parent->hasField('user')){
                    $return['parent']['user'] = UserService::service()->get(
                        $parent_comment['user_id'],
                        $fields->parent->user
                    );
                }
                if(!$fields->parent->user->hasField('user_id') && in_array('user_id', $parent_comment_fields)){
                    unset($parent_comment['user_id']);
                }
                
                if($parent_comment){
                    $return['parent']['comment'] = $parent_comment;
                }
            }else{
                //没有父节点，但是要求返回相关父节点字段，则返回空数组
                if($fields->parent->hasField('comment')){
                    $return['parent']['comment'] = array();
                }
                
                if($fields->parent->hasField('user')){
                    $return['parent']['user'] = array();
                }
            }
        }
        
        //过滤掉那些未指定返回，但出于某些原因先搜出来的字段
        foreach(array('user_id', 'parent') as $f){
            if(!$fields->hasField($f, true) && in_array($f, $comment_fields)){
                unset($return['comment'][$f]);
            }
        }
        
        return $return;
    }

    /**
     * 判断用户是否对该评论有删除权限
     * @param int|array $comment 评论
     *  - 若是数组，视为评论表行记录，必须包含user_id
     *  - 若是数字，视为评论ID，会根据ID搜索数据库
     * @param string $action 操作
     * @param int|null $user_id 用户ID，若为空，则默认为当前登录用户
     * @return bool
     * @throws RecordNotFoundException
     */
    public function checkPermission($comment, $action = 'delete', $user_id = null){
        if(!is_array($comment)){
            $comment = PostCommentsTable::model()->find($comment, 'user_id');
        }
        $user_id || $user_id = \F::app()->current_user;
        
        if(empty($comment['user_id'])){
            throw new RecordNotFoundException('指定文章评论不存在');
        }
        
        if($comment['user_id'] == $user_id){
            //自己的评论总是有权限操作的
            return true;
        }
        
        if(UserService::service()->isAdmin($user_id) &&
            UserService::service()->checkPermission('cms/admin/post-comment/' . $action, $user_id)){
            //是管理员，判断权限
            return true;
        }
        
        return false;
    }
    
    /**
     * 更新评论状态
     * @param int|array $comment_id 评论ID或由评论ID构成的一维数组
     * @param int $status 状态码
     * @return int
     */
    public function setStatus($comment_id, $status){
        if(is_array($comment_id)){
            return PostCommentsTable::model()->update(array(
                'status'=>$status,
                'update_time'=>\F::app()->current_time,
            ), array('id IN (?)'=>$comment_id));
        }else{
            return PostCommentsTable::model()->update(array(
                'status'=>$status,
                'update_time'=>\F::app()->current_time,
            ), $comment_id);
        }
    }
    
    /**
     * 判断一条动态的改变是否需要改变文章评论数
     * @param array $comment 单条评论，必须包含status,sockpuppet字段
     * @param string $action 操作（可选：delete/undelete/remove/create/approve/disapprove）
     * @return bool
     */
    private function needChangePostComments($comment, $action){
        $post_comment_verify = OptionService::get('system:post_comment_verify');
        if(in_array($action, array('delete', 'remove', 'undelete', 'create'))){
            if($comment['status'] == PostCommentsTable::STATUS_APPROVED || !$post_comment_verify){
                return true;
            }
        }else if($action == 'approve'){
            //只要开启了评论审核，则必然在通过审核的时候文章评论数+1
            if($post_comment_verify){
                return true;
            }
        }else if($action == 'disapprove'){
            //如果评论原本是通过审核状态，且系统开启了文章评论审核，则当评论未通过审核时，相应文章评论数-1
            if($comment['status'] == PostCommentsTable::STATUS_APPROVED && $post_comment_verify){
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * 更post_meta表comments和real_comments字段。
     * @param array $comments 相关评论（二维数组，每项必须包含post_id,status,sockpuppet字段）
     * @param string $action 操作（可选：delete/undelete/remove/create/approve/disapprove）
     */
    public function updatePostComments($comments, $action){
        $posts = array();
        foreach($comments as $c){
            if($this->needChangePostComments($c, $action)){
                //更新评论数
                if(isset($posts[$c['post_id']]['comments'])){
                    $posts[$c['post_id']]['comments']++;
                }else{
                    $posts[$c['post_id']]['comments'] = 1;
                }
                if(!$c['sockpuppet']){
                    //如果不是马甲，更新真实评论数
                    if(isset($posts[$c['post_id']]['real_comments'])){
                        $posts[$c['post_id']]['real_comments']++;
                    }else{
                        $posts[$c['post_id']]['real_comments'] = 1;
                    }
                }
            }
        }
        
        foreach($posts as $post_id => $comment_count){
            $comments = isset($comment_count['comments']) ? $comment_count['comments'] : 0;
            $real_comments = isset($comment_count['real_comments']) ? $comment_count['real_comments'] : 0;
            if(in_array($action, array('delete', 'remove', 'disapprove'))){
                //如果是删除相关的操作，取反
                $comments = - $comments;
                $real_comments = - $real_comments;
            }
            
            if($comments && $comments == $real_comments){
                //如果全部评论都是真实评论，则一起更新real_comments和comments
                PostMetaTable::model()->incr($post_id, array('comments', 'real_comments'), $comments);
            }else{
                if($comments){
                    PostMetaTable::model()->incr($post_id, array('comments'), $comments);
                }
                if($real_comments){
                    PostMetaTable::model()->incr($post_id, array('real_comments'), $real_comments);
                }
            }
        }
    }
    
    /**
     * 根据文章ID，以树的形式（体现层级结构）返回评论
     * @param int $post_id 文章ID
     * @param int $page_size 分页大小
     * @param int $page 页码
     * @param array|string $fields 字段
     * @return array
     */
    public function getTree($post_id, $page_size = 10, $page = 1, $fields = array(
        'comment'=>array(
            'id', 'content', 'parent', 'create_time',
        ),
        'user'=>array(
            'id', 'nickname', 'avatar',
        ),
    )){
        $conditions = array(
            'delete_time = 0',
        );
        if(OptionService::get('system:post_comment_verify')){
            //开启了评论审核
            if(\F::app()->current_user){
                //总是能看到自己发布的评论
                $conditions['or'] = array(
                    'status = '.PostCommentsTable::STATUS_APPROVED,
                    'user_id = ' . \F::app()->current_user,
                );       
            }else{
                $conditions[] = array(
                    'status = '.PostCommentsTable::STATUS_APPROVED,
                );
            }
        }
        
        $result = $this->_getTree($post_id,
            $page_size,
            $page,
            $fields,
            $conditions
        );
        
        return array(
            'comments'=>$result['data'],
            'pager'=>$result['pager'],
        );
    }
    
    /**
     * 根据文章ID，以列表的形式（俗称“盖楼”）返回评论
     * @param int $post_id 文章ID
     * @param int $page_size 分页大小
     * @param int $page 页码
     * @param array|string $fields 字段
     * @return array
     */
    public function getList($post_id, $page_size = 10, $page = 1, $fields = array(
        'comment'=>array(
            'id', 'content', 'parent', 'create_time',
        ),
        'user'=>array(
            'id', 'nickname', 'avatar',
        ),
        'parent'=>array(
            'user'=>array(
                'id', 'nickname',
            ),
        ),
    )){
        $conditions = array(
            't.delete_time = 0',
        );
        $join_conditions = array(
            't2.delete_time = 0',
        );
        if(OptionService::get('system:post_comment_verify')){
            //开启了评论审核
            if(\F::app()->current_user){
                //登录用户总是可以看到自己的评论
                $conditions['or'] = array(
                    't.status = ' . PostCommentsTable::STATUS_APPROVED,
                    't.user_id = ' . \F::app()->current_user,
                );
                $join_conditions['or'] = array(
                    't2.status = ' . PostCommentsTable::STATUS_APPROVED,
                    't2.user_id = ' . \F::app()->current_user,
                );
            }else{
                $conditions[] = 't.status = ' . PostCommentsTable::STATUS_APPROVED;
                $join_conditions[] = 't2.status = ' . PostCommentsTable::STATUS_APPROVED;
            }
        }
        
        $result = $this->_getList($post_id,
            $page_size,
            $page,
            $fields,
            $conditions,
            $join_conditions
        );
        
        return array(
            'comments'=>$result['data'],
            'pager'=>$result['pager'],
        );
    }
    
    /**
     * 根据文章ID，以二级树的形式（所有对评论的评论不再体现层级结构）返回评论
     * @param int $post_id 文章ID
     * @param int $page_size 分页大小
     * @param int $page 页码
     * @param array|string $fields 字段
     * @return array
     */
    public function getChats($post_id, $page_size = 10, $page = 1, $fields = array(
        'comment'=>array(
            'id', 'content', 'parent', 'create_time',
        ),
        'user'=>array(
            'id', 'nickname', 'avatar',
        ),
    )){
        $conditions = array(
            'delete_time = 0',
        );
        if(OptionService::get('system:post_comment_verify')){
            //开启了评论审核
            $conditions[] = 'status = '.PostCommentsTable::STATUS_APPROVED;
        }
        
        $result = $this->_getChats($post_id,
            $page_size,
            $page,
            $fields,
            $conditions
        );
        
        return array(
            'comments'=>$result['data'],
            'pager'=>$result['pager'],
        );
    }
    
    /**
     * 根据状态，获取文章评论数
     * @param int $status
     * @return string
     */
    public function getCount($status = null){
        $conditions = array('delete_time = 0');
        if($status !== null){
            $conditions['status = ?'] = $status;
        }
        $result = PostCommentsTable::model()->fetchRow(array(
            'delete_time = 0',
            'status = ?'=>$status ? $status : false,
        ), 'COUNT(*)');
        return $result['COUNT(*)'];
    }
    
    /**
     * 获取回收站内文章评论数
     * @return string
     */
    public function getDeletedCount(){
        $result = PostCommentsTable::model()->fetchRow(array('delete_time > 0'), 'COUNT(*)');
        return $result['COUNT(*)'];
    }
}