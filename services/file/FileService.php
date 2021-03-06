<?php
namespace cms\services\file;

use cms\models\tables\FilesTable;
use cms\services\CategoryService;
use cms\services\OptionService;
use fay\common\Upload;
use fay\exceptions\ValidationException;
use fay\core\Loader;
use fay\core\Service;
use fay\helpers\ArrayHelper;
use fay\helpers\FieldsHelper;
use fay\helpers\LocalFileHelper;
use fay\helpers\NumberHelper;
use fay\helpers\StringHelper;
use fay\helpers\UrlHelper;

/**
 * 用户上传文件相关服务
 */
class FileService extends Service{
    /**
     * 原图
     */
    const PIC_ORIGINAL = 1;
    
    /**
     * 缩略图
     */
    const PIC_THUMBNAIL = 2;
    
    /**
     * 裁剪
     */
    const PIC_CROP = 3;
    
    /**
     * 缩放
     */
    const PIC_RESIZE = 4;
    
    /**
     * 切割
     */
    const PIC_CUT = 5;
    
    /**
     * @return $this
     */
    public static function service(){
        return Loader::singleton(__CLASS__);
    }
    
    /**
     * 根据文件的mimetype类型，获取对应的小图标
     * @param string $mimetype 例如：image/png
     * @return int|string
     */
    public static function getIconByMimetype($mimetype){
        $mimetypes = \F::config()->get('*', 'mimes');
        $types = array(
            'image'=>array('jpeg', 'jpg', 'jpe', 'png', 'bmp', 'gif'),
            'archive'=>array('rar', 'gz', 'tgz', 'zip', 'tar'),
            'audio'=>array('mp3', 'midi', 'mpga', 'mid', 'aif', 'aiff', 'aifc', 'ram', 'rm',
                'rpm', 'ra', 'rv', 'wav'),
            'code'=>array('php', 'php4', 'php3', 'phtml', 'phps', 'html', 'htm', 'shtml'),
            'document'=>array('txt', 'text', 'log'),
            'video'=>array(),
            'spreadsheet'=>array('csv', 'doc', 'docx', 'xlsx', 'word', 'xsl', 'ppt'),
        );
        foreach($types as $key=>$val){
            foreach($val as $v){
                if(is_array($mimetypes[$v]) && in_array($mimetype, $mimetypes[$v])){
                    return $key;
                }else if($mimetypes[$v] == $mimetype){
                    return $key;
                }
            }
        }
        return 'default';
    }

    /**
     * 返回一个可访问的url
     * 若指定文件不存在，返回null
     * 若是图片
     *   若是公共文件，且不裁剪，返回图片真实url（若上传到七牛，返回的是七牛的url）
     *   若是私有文件，或进行裁剪，返回图片file/pic方式的一个url（若上传到七牛，返回的是七牛的url）
     * 若不是图片，返回下载地址
     * @param int|array $file 可以是文件ID或包含文件信息的数组
     * @param int $type 返回图片类型。可选原图、缩略图、裁剪图和缩放图。（仅当指定文件是图片时有效）
     * @param array $options 图片的一些裁剪，缩放参数（仅当指定文件是图片时有效）
     * @return string url 返回文件可访问的url，若指定文件不存在且未指定替代图，则返回null
     */
    public static function getUrl($file, $type = self::PIC_ORIGINAL, $options = array()){
        if(NumberHelper::isInt($file)){
            if($file <= 0){
                if(isset($options['spare'])){
                    //若指定了默认图，则取默认图
                    $spare = \F::config()->get($options['spare'], 'noimage');
                    if($spare){
                        //若指定的默认图不存在，返回默认图
                        return UrlHelper::createUrl($spare);
                    }
                }
                return '';
            }
            $file = FilesTable::model()->find($file);
        }
        
        if(!$file){
            //指定文件不存在，返回null
            if(isset($options['spare']) && $spare = \F::config()->get($options['spare'], 'noimage')){
                return UrlHelper::createUrl($spare);
            }else{
                return '';
            }
        }
        
        if($file['weixin_server_id']){
            //微信服务器，还未下载到本地，直接返回微信服务器图片地址
            return WeixinFileService::getUrl($file['weixin_server_id']);
        }
        
        if(!$file['is_image']){
            //非图片，返回下载链接
            return UrlHelper::createUrl('file/download', array('id'=>$file['id']));
        }
        
        switch($type){
            case self::PIC_THUMBNAIL://缩略图
                return self::getThumbnailUrl($file);
            break;
            case self::PIC_CROP://裁剪
                $img_params = array(
                    't'=>self::PIC_CROP,
                );
                isset($options['x']) && $img_params['x'] = $options['x'];
                isset($options['y']) && $img_params['y'] = $options['y'];
                isset($options['dw']) && $img_params['dw'] = $options['dw'];
                isset($options['dh']) && $img_params['dh'] = $options['dh'];
                isset($options['w']) && $img_params['w'] = $options['w'];
                isset($options['h']) && $img_params['h'] = $options['h'];
                
                ksort($img_params);
                
                return UrlHelper::createUrl('file/pic', array(
                    'f'=>$file['id']
                ) + $img_params);
            break;
            case self::PIC_RESIZE://缩放
                if($file['qiniu'] && OptionService::get('qiniu:enabled')){
                    //若开启了七牛云存储，且文件已上传，则显示七牛路径
                    return QiniuService::service()->getUrl($file, array(
                        'dw'=>isset($options['dw']) ? $options['dw'] : false,
                        'dh'=>isset($options['dh']) ? $options['dh'] : false,
                    ));
                }else{
                    $img_params = array('t'=>self::PIC_RESIZE);
                    isset($options['dw']) && $img_params['dw'] = $options['dw'];
                    isset($options['dh']) && $img_params['dh'] = $options['dh'];
                    
                    return UrlHelper::createUrl('file/pic', array(
                            'f'=>$file['id']
                        ) + $img_params);
                }
            break;
            case self::PIC_CUT://缩放
                if($file['qiniu'] && OptionService::get('qiniu:enabled')){
                    //若开启了七牛云存储，且文件已上传，则显示七牛路径
                    empty($options['dw']) && $options['dw'] = $file['image_width'];
                    empty($options['dh']) && $options['dh'] = $file['image_height'];
                    
                    return QiniuService::service()->getUrl($file, array(
                        'dw'=>$options['dw'],
                        'dh'=>$options['dh'],
                    ));
                }else{
                    $img_params = array('t'=>self::PIC_RESIZE);
                    isset($options['dw']) && $img_params['dw'] = $options['dw'];
                    isset($options['dh']) && $img_params['dh'] = $options['dh'];
                    
                    return UrlHelper::createUrl('file/pic', array(
                        'f'=>$file['id']
                    ) + $img_params);
                }
                break;
            case self::PIC_ORIGINAL://原图
            default:
                if($file['qiniu'] && OptionService::get('qiniu:enabled')){
                    //若开启了七牛云存储，且文件已上传，则显示七牛路径
                    return QiniuService::service()->getUrl($file);
                }else{
                    if(substr($file['file_path'], 0, 4) == './..'){
                        //私有文件，不能直接访问文件
                        return UrlHelper::createUrl('file/pic', array(
                            'f'=>$file['id'],
                        ));
                    }else{
                        //公共文件，直接返回真实路径
                        return UrlHelper::createUrl() . ltrim($file['file_path'], './') . $file['raw_name'] . $file['file_ext'];
                    }
                }
            break;
        }
    }
    
    /**
     * 返回文件本地路径
     * @param int|array $file 可以是文件ID或包含文件信息的数组
     * @param bool $realpath 若为true，返回完整路径，若为false，返回相对路径，默认为true
     * @return string
     */
    public static function getPath($file, $realpath = true){
        if(NumberHelper::isInt($file)){
            $file = FilesTable::model()->find($file, 'raw_name,file_ext,file_path');
        }
        
        $relative_path = (defined('NO_REWRITE') ? './public/' : '') . $file['file_path'] . $file['raw_name'] . $file['file_ext'];
        return $realpath ? realpath($relative_path) : $relative_path;
    }
    
    /**
     * 返回文件缩略图链接（此方法可指定缩略图尺寸）
     * 若是图片，返回图片缩略图路径
     *   若是公共文件，直接返回图片真实路径
     *   若是私有文件，返回图片file/pic方式的一个url
     * 若是其他类型文件，返回文件图标（图标尺寸是固定的）
     * @param int|array $file 可以是文件ID或包含文件信息的数组
     * @param array $options 可以指定缩略图尺寸
     * @return string
     */
    public static function getThumbnailUrl($file, $options = array()){
        if(NumberHelper::isInt($file)){
            $file = FilesTable::model()->find($file);
        }
        
        if(!$file){
            //指定文件不存在，返回null
            return '';
        }
        
        if($file['weixin_server_id']){
            //微信服务器，还未下载到本地，直接返回微信服务器图片地址
            return WeixinFileService::getUrl($file['weixin_server_id']);
        }
        
        if(!$file['is_image']){
            //不是图片，返回一张文件类型对应的小图标
            $icon = self::getIconByMimetype($file['file_type']);
            return UrlHelper::createUrl() . 'assets/images/crystal/' . $icon . '.png';
        }
        
        if(isset($options['dw']) || isset($options['dh'])){
            return self::getUrl($file, self::PIC_RESIZE, $options);
        }else{
            if(substr($file['file_path'], 0, 4) == './..'){
                //私有文件，不能直接访问文件
                return UrlHelper::createUrl('file/pic', array(
                    't'=>FileService::PIC_THUMBNAIL,
                    'f'=>$file['id'],
                ));
            }else{
                //公共文件，直接返回真实路径
                if($file['qiniu']){
                    return QiniuService::service()->getUrl($file, array(
                        'dw'=>'100',
                        'dh'=>'100',
                    ));
                }else{
                    return UrlHelper::createUrl() . ltrim($file['file_path'], './') . $file['raw_name'] . '-100x100' . $file['file_ext'];
                }
            }
        }
    }
    
    /**
     * 获取文件缩略图路径（非图片类型没有缩略图，返回false；指定文件不存在返回null）
     * @param int|array $file 可以是文件ID或包含文件信息的数组
     * @param bool $realpath 若为true，返回完整路径，若为false，返回相对路径，默认为true
     * @return mixed 图片类型返回缩略图路径；非图片类型没有缩略图，返回false；指定文件不存在返回null
     */
    public static function getThumbnailPath($file, $realpath = true){
        if(NumberHelper::isInt($file)){
            $file = FilesTable::model()->find($file);
        }
        
        if(!$file){
            //指定文件不存在，返回null
            return '';
        }
        
        if(!$file['is_image']){
            //非图片类型返回false
            return false;
        }

        $relative_path = (defined('NO_REWRITE') ? './public/' : '') . $file['file_path'] . $file['raw_name'] . '-100x100' . $file['file_ext'];
        return $realpath ? realpath($relative_path) : $relative_path;
    }

    /**
     * 执行上传
     * @param int|string|array $cat 分类ID
     * @param bool $private 是否上传为私密文件，私密文件夹在public文件夹以外，无法直接访问
     * @param null|array $allowed_types 允许的文件类型，若为null，则根据config文件配置
     * @param null|bool $auto_orientate 是否自动判断并旋转jpg图片角度，若为null，则根据config文件配置
     * @return array
     */
    public function upload($cat = 0, $private = false, $allowed_types = null, $auto_orientate = null){
        if($cat){
            if(!is_array($cat)){
                $cat = CategoryService::service()->get($cat, 'id,alias', '_system_file');
            }
            
            if(!$cat){
                throw new \UnexpectedValueException('指定分类不存在');
            }
        }else{
            $cat = array(
                'id'=>0,
                'alias'=>'',
            );
        }

        if($cat['alias']){
            if(substr($cat['alias'], 0, 12) == '_system_file'){
                //去掉前缀
                $target = substr($cat['alias'], 13) . '/';
            }else{
                $target = $cat['alias'] . '/';
            }
        }else{
            $target = '';
        }
        
        //是否存入私有文件
        $upload_config['upload_path'] = $private ? './../uploads/' . APPLICATION . '/' . $target . date('Y/m/')
            : './uploads/' . APPLICATION . '/' . $target . date('Y/m/');
        
        //是否指定上传文件类型
        if($allowed_types !== null){
            $upload_config['allowed_types'] = $allowed_types;
        }
        if($auto_orientate !== null){
            $upload_config['auto_orientate'] = $auto_orientate;
        }
        LocalFileHelper::createFolder($upload_config['upload_path']);
        $upload = new Upload($upload_config);
        $result = $upload->run();
        if($result !== false){
            if($result['is_image']){
                $data = array(
                    'raw_name'=>$result['raw_name'],
                    'file_ext'=>$result['file_ext'],
                    'file_type'=>$result['file_type'],
                    'file_size'=>$result['file_size'],
                    'file_path'=>$result['file_path'],
                    'client_name'=>$result['client_name'],
                    'is_image'=>$result['is_image'],
                    'image_width'=>$result['image_width'],
                    'image_height'=>$result['image_height'],
                    'upload_time'=>\F::app()->current_time,
                    'user_id'=>\F::app()->current_user,
                    'cat_id'=>$cat['id'],
                );
                
                //水印逻辑
                $watermark_config = OptionService::getGroup('watermark:upload');

                if(!empty($watermark_config['enabled']) &&
                    $data['image_width'] > $watermark_config['min_width'] && $data['image_height'] > $watermark_config['min_height']){
                    //数据处理一下
                    empty($watermark_config['text']) && $watermark_config['text'] = 'faycms.com';
                    empty($watermark_config['size']) && $watermark_config['size'] = 20;
                    empty($watermark_config['color']) && $watermark_config['color'] = '#FFFFFF';
                    empty($watermark_config['line_height']) && $watermark_config['line_height'] = 1.3;
                    empty($watermark_config['max_width']) && $watermark_config['max_width'] = 0;
                    empty($watermark_config['margin']) && $watermark_config['margin'] = 10;
                    empty($watermark_config['align']) && $watermark_config['align'] = 'right';
                    empty($watermark_config['valign']) && $watermark_config['valign'] = 'bottom';
                    empty($watermark_config['opacity']) && $watermark_config['opacity'] = 60;
                    empty($watermark_config['image']) && $watermark_config['image'] = BASEPATH . 'assets/images/watermark.png';
                    
                    $file_path = self::getPath($data, true);
                    if($watermark_config == 'text'){
                        $watermark_img = new ImageTextService($file_path);
                        //文本水印
                        $watermark_img->write(
                            $watermark_config['text'],
                            $watermark_config['size'],
                            $watermark_config['color'],
                            $watermark_config['margin'],
                            BASEPATH . 'assets/fonts/msyh.ttf',
                            array(
                                $watermark_config['align'],
                                $watermark_config['valign'],
                            ),
                            $watermark_config['line_height'],
                            0,
                            $watermark_config['max_width'],
                            $watermark_config['opacity']
                        );
                    }else{
                        $watermark_img = new ImageService($file_path);
                        //图片水印
                        $watermark_img->merge(
                            $watermark_config['image'],
                            $watermark_config['margin'],
                            array(
                                $watermark_config['align'],
                                $watermark_config['valign'],
                            ),
                            $watermark_config['opacity']
                        );
                    }

                    //保存水印图
                    $watermark_img->save($file_path);
                    $data['file_size'] = filesize($file_path);
                }
                $data['id'] = FilesTable::model()->insert($data);

                $image = new ImageService($data['id']);
                $image->resize(100, 100)
                    ->save((defined('NO_REWRITE') ? './public/' : '').$data['file_path'].$data['raw_name'].'-100x100'.$data['file_ext']);

                $data['error'] = 0;
                if($private){
                    //私有文件通过file/pic访问
                    $data['url'] = UrlHelper::createUrl('file/pic', array('f'=>$data['id']));
                    $data['thumbnail'] = UrlHelper::createUrl('file/pic', array('t'=>self::PIC_THUMBNAIL, 'f'=>$data['id']));
                }else{
                    //公共文件直接给出真实路径
                    $data['url'] = UrlHelper::createUrl() . ltrim($data['file_path'], './') . $data['raw_name'] . $data['file_ext'];
                    $data['thumbnail'] = UrlHelper::createUrl() . ltrim($data['file_path'], './') . $data['raw_name'] . '-100x100' . $data['file_ext'];
                    //真实存放路径（是图片的话与url路径相同）
                    $data['src'] = UrlHelper::createUrl() . ltrim($data['file_path'], './') . $data['raw_name'] . $data['file_ext'];
                }
            }else{
                $data = array(
                    'raw_name'=>$result['raw_name'],
                    'file_ext'=>$result['file_ext'],
                    'file_type'=>$result['file_type'],
                    'file_size'=>$result['file_size'],
                    'file_path'=>$result['file_path'],
                    'client_name'=>$result['client_name'],
                    'is_image'=>$result['is_image'],
                    'upload_time'=>\F::app()->current_time,
                    'user_id'=>\F::app()->current_user,
                    'cat_id'=>$cat['id'],
                );
                $data['id'] = FilesTable::model()->insert($data);
                
                $icon = self::getIconByMimetype($data['file_type']);
                $data['thumbnail'] = UrlHelper::createUrl().'assets/images/crystal/'.$icon.'.png';
                //下载地址
                $data['url'] = UrlHelper::createUrl('file/download', array(
                    'id'=>$data['id'],
                ));
                //真实存放路径
                $data['src'] = UrlHelper::createUrl() . ltrim($data['file_path'], './') . $data['raw_name'] . $data['file_ext'];
            }
            return array(
                'status'=>1,
                'data'=>$data,
            );
        }else{
            return array(
                'status'=>0,
                'data'=>$upload->getErrorMsg(),
            );
        }
    }
    
    /**
     * @param string $url
     * @param int $cat
     * @param bool $only_image
     * @param string $client_name
     * @return array
     */
    public function uploadFromUrl($url, $cat = 0, $only_image = true, $client_name = ''){
        $remoteService = new RemoteFileService($url);
        if($client_name){
            $remoteService->setClientName($client_name);
        }
        return $remoteService->save($cat, $only_image, true);
    }
    
    /**
     * 编辑一张图片
     * @param int|array $file 可以传入文件ID或包含足够信息的数组
     * @param string $handler 处理方式。resize(缩放)和crop(裁剪)可选
     * @param array $params
     *  - $params['dw'] 输出宽度
     *  - $params['dh'] 输出高度
     *  - $params['x'] 裁剪时x坐标点
     *  - $params['y'] 裁剪时y坐标点
     *  - $params['w'] 裁剪时宽度
     *  - $params['h'] 裁剪时高度
     * @return array|bool|int
     * @throws ValidationException
     */
    public function edit($file, $handler, $params){
        if(NumberHelper::isInt($file)){
            $file = FilesTable::model()->find($file);
        }
        
        switch($handler){
            case 'resize':
                if($params['dw'] && !$params['dh']){
                    $params['dh'] = $params['dw'] * ($file['image_height'] / $file['image_width']);
                }else if($params['dh'] && !$params['dw']){
                    $params['dw'] = $params['dh'] * ($file['image_width'] / $file['image_height']);
                }else if(!$params['dw'] && !$params['dh']){
                    $params['dw'] = $file['image_width'];
                    $params['dh'] = $file['image_height'];
                }
                
                $image = new ImageService($file);
                $image->resize($params['dw'], $params['dh'])//缩放
                    ->save((defined('NO_REWRITE') ? './public/' : '').$file['file_path'].$file['raw_name'].$file['file_ext'])//保存文件
                ;
                
                $image->resize(100, 100)//缩放为缩略图
                    ->save((defined('NO_REWRITE') ? './public/' : '').$file['file_path'].$file['raw_name'].'-100x100'.$file['file_ext'])//保存缩略图
                ;
                
                $new_file_size = filesize((defined('NO_REWRITE') ? './public/' : '').$file['file_path'].$file['raw_name'].$file['file_ext']);
                
                //更新数据库字段
                FilesTable::model()->update(array(
                    'image_width'=>$params['dw'],
                    'image_height'=>$params['dh'],
                    'file_size'=>$new_file_size,
                ), $file['id']);
                
                //更新返回值字段
                $file['image_width'] = $params['dw'];
                $file['image_height'] = $params['dh'];
                $file['file_size'] = $new_file_size;
                
                break;
            case 'crop':
                if(!$params['x'] || !$params['y'] || !$params['w'] || !$params['h']){
                    throw new ValidationException('crop处理缺少必要参数');
                }

                if($params['dw'] == 0){
                    $params['dw'] = $params['w'];
                }
                if($params['dh'] == 0){
                    $params['dh'] = $params['h'];
                }

                $image = new ImageService($file);
                $image->crop($params['x'], $params['y'], $params['w'], $params['h'])//裁剪
                    ->resize($params['dw'], $params['dh'])//缩放
                    ->save((defined('NO_REWRITE') ? './public/' : '').$file['file_path'].$file['raw_name'].$file['file_ext']);//保存文件

                $image->resize(100, 100)//缩放为缩略图
                    ->save((defined('NO_REWRITE') ? './public/' : '').$file['file_path'].$file['raw_name'].'-100x100'.$file['file_ext'])//保存缩略图
                ;

                $new_file_size = filesize((defined('NO_REWRITE') ? './public/' : '').$file['file_path'].$file['raw_name'].$file['file_ext']);
                
                //更新数据库字段
                FilesTable::model()->update(array(
                    'image_width'=>$params['dw'],
                    'image_height'=>$params['dh'],
                    'file_size'=>$new_file_size,
                ), $file['id']);
                
                //更新返回值字段
                $file['image_width'] = $params['dw'];
                $file['image_height'] = $params['dh'];
                $file['file_size'] = $new_file_size;
                break;
        }
        return $file;
    }
    
    /**
     * 随机产生一个唯一的文件名<br>
     * 该方法区分大小写，若是windows系统，可修改files表结构，让raw_name字段不区分大小写<br>
     * 不过文件系统有文件夹分割，重名概率极低，一般问题不大
     * @param string $path
     * @param string $ext 扩展名
     * @return string
     */
    public static function getFileName($path, $ext){
        $filename = StringHelper::random('alnum', 5).$ext;
        if (!file_exists($path.$filename)){
            return $filename;
        }else{
            return self::getFileName($path, $ext);
        }
    }
    
    /**
     * 获取文件下载次数
     * @param int $file_id 文件ID
     */
    public static function getDownloads($file_id){
        $file = FilesTable::model()->find($file_id, 'downloads');
        return $file['downloads'];
    }
    
    /**
     * 返回指定文件是否是图片
     * @param int $file_id
     * @return int 返回0|1
     */
    public static function isImage($file_id){
        $file = FilesTable::model()->find($file_id, 'is_image');
        return $file['is_image'];
    }
    
    /**
     * 返回一个包含指定字段文件信息的数组
     * @param $file
     * @param array $options
     *  - spare 替代图片（当指定图片不存在时，使用配置的替代图）
     *  - dw 输出缩略图宽度
     *  - dh 输出缩略图高度
     * @param string|array $fields 返回字段，可指定id, url, thumbnail, is_image, width, height, description
     * @return array
     */
    public static function get($file, $options = array(), $fields = 'id,url,thumbnail'){
        //解析fields
        $fields = new FieldsHelper($fields, 'file');
        
        if(!is_array($file) && ($file <= 0 ||
            !$file = FilesTable::model()->find($file))
        ){
            //显然负数ID不存在，返回默认图数组
            if(isset($options['spare'])){
                //若指定了默认图，则取默认图
                $spare = \F::config()->get($options['spare'], 'noimage');
                if($spare === null){
                    //若指定的默认图不存在，返回默认图
                    $spare = \F::config()->get('default', 'noimage');
                }
            }else{
                //若未指定默认图，返回默认图
                $spare = \F::config()->get('default', 'noimage');
            }
            
            $return = array();
            foreach(array('id', 'is_image', 'width', 'height') as $key){
                if($fields->hasField($key)){
                    $return[$key] = '0';
                }
            }
            if($fields->hasField('url')){
                if($spare){
                    $return['url'] = UrlHelper::createUrl($spare);
                }else{
                    $return['url'] = '';
                }
            }
            if($fields->hasField('thumbnail')){
                if($spare){
                    $return['thumbnail'] = UrlHelper::createUrl($spare);
                }else{
                    $return['thumbnail'] = '';
                }
            }
            if($fields->hasField('description')){
                $return['description'] = isset($file['description']) ? $file['description'] : '';
            }
            
            return $return;
        }
        
        $return = array();
        foreach(array('id', 'is_image', 'width', 'height', 'description') as $key){
            if($fields->hasField($key)){
                $return[$key] = isset($file[$key]) ? $file[$key] : '';
            }
        }
        if($fields->hasField('url')){
            $return['url'] = self::getUrl($file);
        }
        if($fields->hasField('thumbnail')){
            //如果有头像，将头像图片ID转化为图片对象
            if($fields->getExtra('thumbnail') && preg_match('/^(\d+)x(\d+)$/', $fields->getExtra('thumbnail'), $thumbnail_params)){
                $return['thumbnail'] = self::getThumbnailUrl($file, array(
                    'dw'=>$thumbnail_params[1],
                    'dh'=>$thumbnail_params[2],
                ) + $options);
            }else{
                $return['thumbnail'] = self::getThumbnailUrl($file, $options);
            }
        }
        
        return $return;
    }
    
    /**
     * 批量获取图片对象
     * @param array $files 数组所有项必须一致（均为数字，或均为文件行数组）
     *  - 由文件ID构成的一维数组，则会根据文件ID进行搜索
     *  - 由文件信息对象（其实也是数组）构成的二维数组。至少包含id,raw_name,file_ext,file_path,is_image,image_width,image_height,qiniu,weixin_server_id字段
     * @param array $options
     * @param string $fields 返回字段，可指定id, url, thumbnail, is_image, width, height, description
     * @return array
     */
    public static function mget($files, $options, $fields = 'id,url,thumbnail'){
        if(empty($files)){
            return array();
        }
        
        $return = array();
        if(!is_array($files[0])){
            //传入的是文件ID，通过ID获取文件信息
            $file_rows = FilesTable::model()->fetchAll(array(
                'id IN (?)'=>$files,
            ));
            $file_map = ArrayHelper::column($file_rows, null, 'id');
            
            foreach($files as $f){
                if(isset($file_map[$f])){
                    $return[$f] = self::get($file_map[$f], $options, $fields);
                }else{
                    //文件ID没搜出来（理论上其实不会这样的）
                    $return[$f] = self::get(-1, $options, $fields);
                }
            }
        }else{
            //传入的是文件行数组，无需再搜索数据库
            foreach($files as $f){
                $return[$f['id']] = self::get($f, $options, $fields);
            }
        }
        
        return $return;
    }
}