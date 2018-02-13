<?php
/**
 * 极光推送模型.
 *
 * @version TS4.5
 * @name JpushModel
 *
 * @author Foreach
 */
class JpushModel extends Model
{
    protected static $client;
    protected static $apns_production;

    public function __construct()
    {
        $config = D('system_data')->where(array('key' => 'jpush'))->getField('value');
        $config = unserialize($config);
        static::$apns_production = (boolean)$config['apns_production'];
        static::$client = new \JPush\Client(t($config['key']), t($config['secret']));
    }

    /**
     * 推送消息.
     *
     * @param array  $uids   用户uid
     * @param string $alert  消息内容
     * @param array  $extras
     * @param int    $type
     * @param int    $rose
     *
     * @return array|bool
     */
    public function pushMessage($uids = array(), $alert = '', $extras = array())
    {
        $audience = array('alias' => $uids);
        foreach ($audience['alias'] as $k => $v) {
            $audience['alias'][$k] = (string) $v;
        }
        $audience['alias'] = array_values($audience['alias']);

        $result = static::$client->push()
            ->setPlatform('all')
            ->setNotificationAlert($alert)
            ->addAllAudience()
            ->addAndroidNotification($alert, null, null, $extras)
            ->addIosNotification($alert, null, null, null, 'iOS category', $extras)
            ->setMessage($alert, $alert, '', $extras)
            ->setOptions(0, null, null, static::$apns_production, null)//True 表示推送生产环境，False 表示要推送开发环境
            ->send();

        if ($result == null) {
            return false;
        }

        return $result;
    }

    public function push($uids = array(), $alert = '', $extras = array())
    {
        $title = t($extras['content']);
        if (!$uids || !$alert) {
            return array('status' => 0, 'msg' => '参数错误');
        }
        foreach ($uids as &$v) {
            $v = t($v);
        }

        try{
            $result = static::$client->push()
                ->setPlatform('all')
                ->addAlias($uids)
                ->iosNotification($alert, array(
                    'extras' => $extras
                ))
                ->androidNotification($alert, array(
                    'extras' => $extras
                ))
                ->message($alert, array(
                    'msg_content' => $title,
                    'extras' => $extras
                ))
                ->options(array(
                    'time_to_live' => 864000, // 离线消息保留时长(秒)
                    'apns_production' => static::$apns_production, // True 表示推送生产环境，False 表示要推送开发环境
                ))
                ->send();
            if ($result['http_code'] !== 200) {

                return false;
            }

            return true;
        } catch(\Exception $e){
            //
            return false;
        }
    }
}
