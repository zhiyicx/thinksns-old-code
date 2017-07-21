<?php
/**
 * 用户认证模型 - 数据对象模型.
 *
 * @author zivss <guolee226@gmail.com>
 *
 * @version TS3.0
 */
class UserVerifiedModel extends Model
{
    protected $tableName = 'user_verified';
    protected $fields = array('id', 'uid', 'usergroup_id', 'user_verified_category_id', 'company', 'realname', 'idcard', 'phone', 'info', 'verified', 'attach_id');

    /**
     * 获取指定用户的认证信息.
     *
     * @param array $uids 用户ID
     *
     * @return array 指定用户的认证信息
     */
    public function getUserVerifiedInfo($uids)
    {
        if (empty($uids)) {
            return array();
        }
        $map['uid'] = array('IN', $uids);
        $map['verified'] = 1;
        $data = $this->where($map)->getHashList('uid', 'info');

        return $data;
    }

    public function rmVerifiedGroup($gid)
    {
        if (empty($gid)) {
            return false;
        }
        $map['usergroup_id'] = $gid;
        // $uids = $this->where($map)->getAsFieldArray('uid');
        $result = $this->where($map)->delete();

        // $this->cleanCache($uids);

        return (bool) $result;
    }

    public function isVerify($uid)
    {
        if (empty($uid)) {
            return false;
        }
        $map['uid'] = $uid;
        $map['verified'] = 1;
        $data = $this->where($map)->find();

        return (bool) $data;
    }

    public function cleanCache($uids)
    {
        !is_array($uids) && $uids = explode(',', $uids);
        foreach ($uids as $uid) {
            S('user_verified_'.$uid, null);
        }
    }

    /**
     * 获取用户认证状态
     *
     * @param $uids
     * @return array
     * @author zsy
     */
    public function getVerified($uids)
    {
        !is_array($uids) && $uids = explode(',', $uids);
        $verifieds = $this->where(['uid' => ['in', $uids]])->field('id, uid, usergroup_id, verified')->findAll();

        $data = [];
        foreach ($verifieds as $verified) {
            $data[$verified['uid']] = $verified;
        }

        $return = [];
        foreach ($uids as $v) {
            if (!$data[$v]) {
                $return[$v] = [
                    'id' => 0,
                    'usergroup_id' => 0,
                    'verified' => -1
                ];
            } else {
                $return[$v] = [
                    'id' => $data[$v]['id'],
                    'usergroup_id' => $data[$v]['usergroup_id'],
                    'verified' => ($data[$v]['verified'] == 1 ? 1 : ($data[$v]['verified'] == -1 ? 2: 0))
                ];
            }
        }

       return $return;
    }

    /**
     * 获取用户认证详情
     *
     * @param $uids
     * @return array
     * @author zsy
     */
    public function getVerifyInfo($uids)
    {
        !is_array($uids) && $uids = explode(',', $uids);
        if (empty($uids)) {

            return [];
        }
        $map['uid'] = array('IN', $uids);
        $verifieds = $this->where($map)->findAll();

        $data = [];
        foreach ($verifieds as $verified) {
            $data[$verified['uid']] = $verified;
        }

        $return = [];
        foreach ($uids as $v) {
            if (!$data[$v]) {
                $return[$v] = [
                    'id' => 0,
                    'usergroup_id' => 0,
                    'verified' => -1
                ];
            } else {
                // 获取认证组
                $group_ = \Ts\Models\UserGroup::where('user_group_id', $data[$v]['usergroup_id'])->select('user_group_id', 'user_group_name', 'user_group_icon', 'is_authenticate')->first();
                $group = [
                    'user_group_id' => $group_->user_group_id,
                    'user_group_name' => $group_->user_group_name,
                    'user_group_icon' => $group_->icon,
                    'is_authenticate' => $group_->is_authenticate,
                ];

                // 获取认证附件
                $attachIds = array_filter(explode('|', $data[$v]['attach_id']));
                if (!$attachIds) {
                    $attach = [];
                } else {
                    $attach_ = \Ts\Models\Attach::whereIn('attach_id', $attachIds)->select('save_path', 'save_name')->get();
                    foreach ($attach_ as $kk => $vv) {
                        $attach[$kk] = $vv->path;
                    }
                }

                $return[$v] = [
                    'id' => $data[$v]['id'],
                    'verified' => ($data[$v]['verified'] == 1 ? 1 : ($data[$v]['verified'] == -1 ? 2: 0)),
                    'usergroup' => $group,
                    'user_verified_category' => D('user_verified_category')->where('user_verified_category_id='.$data[$v]['user_verified_category_id'])->field('user_verified_category_id,title')->find(),
                    'company' => $data[$v]['company'],
                    'realname' => $data[$v]['realname'],
                    'idcard' => $data[$v]['idcard'],
                    'phone' => $data[$v]['phone'],
                    'info' => $data[$v]['info'],
                    'attach_id' => array_values($attach),
                    'reason' => $data[$v]['reason'],
                ];

                unset($attach, $attach_, $attachIds);
            }
        }


        return $return;
    }
}
