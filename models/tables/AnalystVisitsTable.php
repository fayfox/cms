<?php
namespace cms\models\tables;

use fay\core\db\Table;
use fay\core\Loader;

/**
 * Analyst Visits table model
 *
 * @property int $id Id
 * @property int $mac Mac
 * @property int $ip_int Ip Int
 * @property string $refer Refer
 * @property string $url Url
 * @property string $short_url Short Url
 * @property string $trackid Trackid
 * @property int $user_id User Id
 * @property int $create_time 创建时间
 * @property string $create_date 创建日期
 * @property int $hour Hour
 * @property int $site Site
 * @property int $views Views
 * @property string $HTTP_CLIENT_IP HTTP CLIENT IP
 * @property string $HTTP_X_FORWARDED_FOR HTTP X FORWARDED FOR
 * @property string $REMOTE_ADDR REMOTE ADDR
 */
class AnalystVisitsTable extends Table{
    protected $_name = 'analyst_visits';
    
    /**
     * @return $this
     */
    public static function model(){
        return Loader::singleton(__CLASS__);
    }
    
    public function rules(){
        return array(
            array(array('ip_int'), 'int', array('min'=>-2147483648, 'max'=>2147483647)),
            array(array('id', 'mac', 'create_time'), 'int', array('min'=>0, 'max'=>4294967295)),
            array(array('user_id'), 'int', array('min'=>0, 'max'=>16777215)),
            array(array('site'), 'int', array('min'=>0, 'max'=>65535)),
            array(array('hour', 'views'), 'int', array('min'=>0, 'max'=>255)),
            array(array('refer', 'url', 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'), 'string', array('max'=>255)),
            array(array('short_url'), 'string', array('max'=>6)),
            array(array('trackid'), 'string', array('max'=>30)),
            
            array(array('url'), 'url'),
        );
    }

    public function labels(){
        return array(
            'id'=>'Id',
            'mac'=>'Mac',
            'ip_int'=>'IP',
            'refer'=>'Refer',
            'url'=>'Url',
            'short_url'=>'Short Url',
            'trackid'=>'Trackid',
            'user_id'=>'User Id',
            'create_time'=>'创建时间',
            'create_date'=>'创建日期',
            'hour'=>'Hour',
            'site'=>'Site',
            'views'=>'Views',
            'HTTP_CLIENT_IP'=>'HTTP CLIENT IP',
            'HTTP_X_FORWARDED_FOR'=>'HTTP X FORWARDED FOR',
            'REMOTE_ADDR'=>'REMOTE ADDR',
        );
    }

    public function filters(){
        return array(
            'mac'=>'intval',
            'refer'=>'trim',
            'url'=>'trim',
            'short_url'=>'trim',
            'trackid'=>'trim',
            'user_id'=>'intval',
            'create_date'=>'',
            'hour'=>'intval',
            'site'=>'intval',
            'views'=>'intval',
            'HTTP_CLIENT_IP'=>'trim',
            'HTTP_X_FORWARDED_FOR'=>'trim',
            'REMOTE_ADDR'=>'trim',
        );
    }
}