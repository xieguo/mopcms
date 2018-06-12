<?php
/**
 * 后台系统设置模型类
 * @copyright			(C) 2016-2099 MOPCMS.COM
 * @license				http://www.mopcms.com/license/
 */
!defined('IN_MOPCMS') && exit('Access failed');
class model_admin_setting extends base_model
{
    /**
     * 修改管理员列表页面
     */
    public function admins($post)
    {
        $where = array();
        if(!empty($post['words'])) {
            $where[] = db :: fbuild('concat(username,nickname,realname)', $post['words'], 'like');
        }
        if(!empty($post['groupid'])) {
            $where['groupid'] = $post['groupid'];
        }
        $result = t('admin')->info_list(array('where'=>$where));
        return getResult(100,$result);
    }

    /**
     * 修改管理员添加修改页面
     */
    public function admins_save($id='')
    {
        $_data = array();
        $_data['row'] = $id?t('admin')->fetch($id):array();
        $_data['groups'] = t('admin_group')->fetchList();
        $_data['columns'] = t('column')->columnList(0);
        return getResult(100,$_data);
    }

    /**
     * 管理员添加修改
     */
    public function admins_save_submit($post)
    {
        $data = array();
        if($post['pwd']) {
            $data['pwd'] = $this->create_pwd($post['pwd']);
        }
        if(!empty($post['columnids'])) {
            $columnids = t('column')->getSubids($post['columnids'], true);
            $data['columnids'] = implode(',', $columnids);
        }
        $data['nickname'] = $post['nickname'];
        $data['realname'] = $post['realname'];
        $data['groupid'] = $post['groupid'];
        $obj = t('admin');
        if(!empty($post['id'])) {
            $obj->update($post['id'], $data);
            $obj->lastUpdateTime();
            return getResult(100,'',10013,'?mod=setting&ac=admins&menuid=54');
        } else {
            if(empty($post['pwd'])){
                return getResult(10010);
            }
            $data['username'] = $post['username'];
            if(empty($data['username'])) {
                return getResult(10011);
            }
            if($obj->count(array('username' => $data['username']))) {
                return getResult(10012);
            }
            $obj->insert($data);
            return getResult(100,'',10014,'?mod=setting&ac=admins&menuid=54');
        }
    }

    /**
     *系统参数设置页面
     */
    public function parameter()
    {
        $tags = array(1 => '基本设置', 2 => '核心设置', 3 => '附件设置', 4 => '性能选项', 5 => '安全设置', 6=>'文档相关');
        $_data['tags'] = array();
        foreach($tags as $k=>$v){
            $_data['tags'][$k]['name'] = $v;
            $_data['tags'][$k]['list'] = t('sys_setting')->fetchList(array('groupid'=>$k),'*','','id','asc');
        }
        return getResult(100,$_data);
    }

    /**
     * 添加修改系统参数设置
     */
    public function parameter_submit($post)
    {
        $data = array();
        $data['value'] = $post['value'];
        $data['name'] = $post['name'];
        if(!empty($post['groupid'])) {
            $data['types'] = $post['types'];
            $data['groupid'] = $post['groupid'];
            $data['intro'] = $post['intro'];
            if(empty($data['name'])) {
                return getResult(10504);
            }
            if(empty($data['types']) || empty($data['intro'])) {
                return getResult(10002);
            }
            if(t('sys_setting')->count(array('name' => $data['name']))) {
                return getResult(10501);
            }
            $this->_parameter_submit_data($data['types'], $data['value']);
            t('sys_setting')->insert($data);
        } else {
            if(empty($post['parameter'])){
                return getResult(10018);
            }
            foreach($post['parameter'] as $k => $v) {
                $types = t('sys_setting')->fetchFields(array('name'=>$k),'types');
                if($types){
                    $this->_parameter_submit_data($types, $v);
                    t('sys_setting')->update(array('name' => $k), array('value' => $v));
                }
            }
            t('sys_setting')->lastUpdateTime();
        }
        return $this->_rewrite_setting();
    }
    private function _parameter_submit_data($types, &$data)
    {
        switch($types) {
            case 'bool' :
                $data = $data == 1 ? 1 : 0;
                break;
            case 'number' :
                $data = (int) $data;
                break;
            case 'serialize' :
                $data = serializeData($data);
                break;
            default :
                $data = filterString($data);
                break;
        }
    }

    /**
     * 更新系统配置函数
     */
    public function rewrite_setting()
    {
        return $this->_rewrite_setting();
    }
    private function _rewrite_setting()
    {
        $file = MOPDATA . 'cache/setting_cache.php';
        require_once loadlib('file');
        $code = "<?php\r\nif(!defined('IN_MOPCMS')) {\r\n	exit('Access failed');\r\n}\r\n\r\n";
        $row = t('sys_setting')->fetchList();
        foreach($row as $v) {
            if($v['types'] == 'number') {
                $code .= '$sys[\'' . $v['name'] . '\'] = ' . (int) $v['value'] . ";\r\n";
            }
            elseif($v['types'] == 'serialize') {
                $value = !empty($v['value']) ? var_export(unserialize($v['value']), true) : '';
                if($value) {
                    $code .= '$sys[\'' . $v['name'] . '\'] = ' . $value . ";\r\n";
                }
            } else {
                $code .= '$sys[\'' . $v['name'] . '\'] = \'' . str_replace("'", '', $v['value']) . "';\r\n";
            }
        }
        $code .= '?>';
        $result = putFile($file, $code);
        if(!$result) {
            return getResult(10502, '', array('name' => $file));
        }
        return getResult(100,'',10503);
    }

    /**
     * 修改管理员分组修改页面
     */
    public function admingroups_save($id='')
    {
        $_data = array();
        $_data['row'] = $id?t('admin_group')->fetch($id):array();
        $_data['menus'] = t('admin_menu')->menuList();
        $_data['models'] = t('models')->fetchList();
        $_data['customforms'] = t('customform')->fetchList();
        return getResult(100,$_data);
    }

    /**
     * 添加修改分组
     * @param $data
     */
    public function admingroups_save_submit($post)
    {
        $data = array();
        if(!empty($post['groupname'])) {
            $data['groupname'] = $post['groupname'];
        }
        $data['purviews'] = is_array($post['purviews'])?implode(',', $post['purviews']):'';
        $data['mpurviews'] = is_array($post['mpurviews'])?implode(',', $post['mpurviews']):'';
        $data['cpurviews'] = is_array($post['cpurviews'])?implode(',', $post['cpurviews']):'';
        if(!empty($post['id'])) {
            t('admin_group')->update($post['id'], $data);
            t('admin_group')->lastUpdateTime();
            return getResult(100,'',10013);
        } else {
            if(empty($data['groupname'])) {
                return getResult(10102);
            }
            t('admin_group')->insert($data);
            return getResult(100,'',10014);
        }
    }

    /**
     * 后台日志列表
     * @param unknown_type $post
     */
    public function logs($post)
    {
        $where = array();
        if(!empty($post['words'])) {
            if(is_numeric($post['words'])){
                $where['prompt_code'] = $post['words'];
            }else{
                $where[] = db :: fbuild('concat(query,prompt_msg)', $post['words'], 'like');
            }
        }
        if(!empty($post['adminid'])) {
            $where['adminid'] = $post['adminid'];
        }
        $result = t('admin_log')->info_list(array('where'=>$where,'pagesize'=>50));
        return getResult(100,$result);
    }

    /**
     * 删除后台日志
     * @param unknown_type $timestamp
     */
    public function logs_del($timestamp)
    {
        if(empty($timestamp)){
            return getResult(10508);
        }
        $where = array();
        $where[] = db :: fbuild('dateline', $timestamp, '<');
        t('admin_log')->delete($where);
        return getResult(100,'',10016,'?mod=setting&ac=logs&menuid=84');
    }

    /**
     * 系统升级解析XML文件
     * @param unknown_type $curv
     * @param unknown_type $finalv
     */
    public function upgradeResult($curv,$finalv)
    {
        if(empty($curv)||empty($finalv)){
            return getResult(103);
        }
        $fileurl = MOPDATA.'upgrade/'.$curv.'_'.$finalv.'.xml';
        if(!is_file($fileurl)){
            return getResult(10033);
        }
        $res = $this->takeoutFile($fileurl);
        $res['data']['replace'] = true;
        foreach($res['data']['files'] as $k=>$v){
            $arr = array();
            $arr['file'] = $v;
            $arr['iswritable'] = true;
            if(is_file(MOPROOT.$v)){
                $arr['isnew'] = false;
                if(!is_writable(MOPROOT.$v)){
                    $arr['iswritable'] = false;
                    $res['data']['replace'] = false;
                }
            }else{
                $arr['isnew'] = true;
                if(!is_writable(dirname(MOPROOT.$v))){
                    $arr['iswritable'] = false;
                    $res['data']['replace'] = false;
                }
            }
            $res['data']['files'][$k] = $arr;
        }
        return $res;
    }

    /**
     * 从XML文件中解出文件
     * @param $fileurl XML文件地址
     */
    private function takeoutFile($fileurl,$replace=false)
    {
        if(!is_file($fileurl)){
            return getResult(11211);
        }
        $xml = simplexml_load_file($fileurl);
        if(!$xml){
            return getResult(11206);
        }
        $files = array();
        foreach($xml->file as $v){
            if($v->path){
                $files[] = $v->path;
                $file = MOPROOT.$v->path;
                if($replace===true){
                    createdir(dirname($file));
                    $content = base64_decode($v->content);
                    if(!file_put_contents($file,$content)){
                        return getResult(11208,'',array('file'=>$v->path));
                    }
                }
            }
        }
        return getResult(100,array('files'=>$files));
    }

    public function upgradeResult_submit($curv,$finalv)
    {
        if(empty($curv)||empty($finalv)){
            return getResult(103);
        }
        $fileurl = MOPDATA.'upgrade/'.$curv.'_'.$finalv.'.xml';
        if(!is_file($fileurl)){
            return getResult(10033);
        }
        $res = $this->takeoutFile($fileurl,true);
        if ($res['code']!=100){
            return $res;
        }
        $config = "<?php\n\r!defined('IN_MOPCMS') && exit ('Access failed');\n\rdefine('MOPCMS_VERSION','".$finalv."');";
        if(!file_put_contents(MOPDATA.'version.php', $config)){
            return getResult(10701);
        }
        return getResult(100,'',10509);
    }
}