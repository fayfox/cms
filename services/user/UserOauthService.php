<?php
namespace cms\services\user;

use cms\models\tables\UsersTable;
use cms\services\file\FileService;
use fay\core\Loader;
use fay\core\Service;
use fayoauth\models\tables\OauthUserConnectsTable;
use fayoauth\services\OauthAppService;
use fayoauth\services\OAuthException;
use fayoauth\services\UserAbstract;

class UserOauthService extends Service{
    /**
     * @return $this
     */
    public static function service(){
        return Loader::singleton(__CLASS__);
    }
    
    /**
     * 通过App Id和Open Id判断此用户是否在本地注册过
     *  - 若注册过，返回用户ID
     *  - 若没注册过，返回字符串0
     * @param string $app_id
     * @param string $open_id
     * @return int
     */
    public function isLocalUser($app_id, $open_id){
        $user = OauthUserConnectsTable::model()->fetchRow(array(
            'open_id = ?'=>$open_id,
            'oauth_app_id = ?'=>OauthAppService::service()->getIdByAppId($app_id),
        ), 'user_id');
        
        if($user){
            return $user['user_id'];
        }else{
            return '0';
        }
    }
    
    /**
     * 用第三方用户信息，创建本地用户。返回用户ID。
     * @param UserAbstract $user 第三方用户信息
     * @param int $status 用户状态
     * @return int
     * @throws OAuthException
     * @throws UserException
     */
    public function createUser(UserAbstract $user, $status = UsersTable::STATUS_VERIFIED){
        if($this->isLocalUser($user->getAccessToken()->getAppId(), $user->getOpenId())){
            throw new OAuthException('用户已存在，不能重复创建');
        }
        
        if($user->getUnionId()){
            /**
             * 若存在Union Id，判断Union Id是否存在
             *  - 若存在，不创建新用户
             */
            $user_connect = OauthUserConnectsTable::model()->fetchRow(array(
                'unionid = ?'=>$user->getUnionId(),
            ));
            if($user_connect){
                $user_id = $user_connect['user_id'];
            }
        }
        
        if(empty($user_id)){
            //若是新用户，插用户表
            $user_id = UserService::service()->create(array(
                'status'=>$status,
                'avatar'=>$this->getLocalAvatar($user->getAvatar()),
                'nickname'=>$user->getNickName(),
            ));
        }
        
        OauthUserConnectsTable::model()->insert(array(
            'user_id'=>$user_id,
            'open_id'=>$user->getOpenId(),
            'unionid'=>$user->getUnionId(),
            'oauth_app_id'=>OauthAppService::service()->getIdByAppId($user->getAccessToken()->getAppId()),
            'access_token'=>$user->getAccessToken()->getAccessToken(),
            'expires_in'=>$user->getAccessToken()->getExpires(),
            'refresh_token'=>$user->getAccessToken()->getRefreshToken(),
            'create_time'=>\F::app()->current_time,
        ));
        
        return $user_id;
    }
    
    /**
     * 从远程将头像下载到本地后，返回本地文件ID
     * @param string $avatar_url 远程头像url
     * @return int
     */
    public function getLocalAvatar($avatar_url){
        if($avatar_url){
            $avatar_file = FileService::service()->uploadFromUrl($avatar_url);
            if($avatar_file['status']){
                return $avatar_file['data']['id'];
            }else{
                return '0';
            }
        }else{
            return '0';
        }
    }
    
    /**
     * 根据App Id获取用户的Open Id
     * @param string $app_id 第三方App Id
     * @param null|int $user_id 用户ID（默认为当前登录用户ID）
     * @return string
     * @throws OAuthException
     */
    public function getOpenId($app_id, $user_id = null){
        $user_id = UserService::makeUserID($user_id);
        
        $oauth_app_id = OauthAppService::service()->getIdByAppId($app_id);
        if(!$oauth_app_id){
            return '';
        }
        
        $connect = OauthUserConnectsTable::model()->fetchRow(array(
            'user_id = ?'=>$user_id,
            'oauth_app_id = ?'=>$oauth_app_id,
        ), 'open_id');
        
        return $connect ? $connect['open_id'] : '';
    }

    /**
     * 根据用户id，获取对应的Open Id
     * @param int|string $key app id或别名
     * @param int $user_id
     * @return string
     * @throws OAuthException
     */
    public function getOpenIdByUserId($key, $user_id){
        $app = OauthAppService::service()->get($key, 'id');
        if(!$app){
            throw new OAuthException("指定App[{$key}]不存在");
        }

        $row = OauthUserConnectsTable::model()->fetchRow(array(
            'user_id = ?'=>$user_id,
            'oauth_app_id = ' . $app['id']
        ), 'open_id');

        return $row ? $row['open_id'] : '';
    }
}