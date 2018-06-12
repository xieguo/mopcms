<?php
!defined('IN_MOPCMS') && exit('Access failed');
class control_admin extends base_control
{
    protected $admin = array();
    public function __construct()
    {
        global $_M;
        parent::__construct();
        $this->token_login();
        if (empty ($_M['admin']['id'])) {
            header("location:?mod=logining&ac=login&referer=" . urlencode($_M['cururl']));
            exit();
        }
        $this->admin = $_M['admin'];
        //权限检测
        if(!in_array($_M['mod'], array('main','ueditor','ajax'))){
            $this->check_allow();
        }
    }

    /**
     * 输出数据
     * @param $result 数据
     * @param $template 调用模板名称
     * @param $referer 跳转URL
     */
    protected function output($result='',$template='',$referer='')
    {
        if(empty($result['code'])){
            $result = getResult(100, $result);
        }
        $this->savelog($result);
        $template = empty($template) && $template!==false?$this->mod.($this->ac?'_'.$this->ac:'').($this->do?'_'.$this->do:'').($this->op?'_'.$this->op:''):$template;
        if($template){
            if(empty($result['data'])){
                $result['data'] = array();
            }
            $data = &$result['data'];
            $data = (array)$data;
            $data['menuid'] = _gp('menuid')?(int)_gp('menuid'):1;
            $data += loadm('admin')->leftmenu($data['menuid']);
            $data['template'] = $template;
        }
        if(!empty($referer)){
            $result['referer'] = $referer;
        }
        return $result;
    }

    /**
     * 检验用户是否有权使用某功能
     * @param unknown_type $n
     */
    private function allow($n = 's')
    {
        //如果所属管理组不存在，权限也不存在
        if(empty($this->admin['_groupid'])) {
            return false;
        }
        if(empty($n) || empty($this->admin['_groupid']['purviews'])) {
            return true;
        }
        $ns = explode(',', $n);
        foreach($ns as $n) {
            if(!empty($n)) {
                if(in_array($n, $this->admin['_groupid']['_purviews'])) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * 某权限检测
     * @param $n 某权限
     */
    protected function check_allow($n = '')
    {
        global $_M;
        if(empty($n)) {
            $pluginid = preg_replace('/[^\w]/i','',_gp('pluginid'));
            $n = ($pluginid?$pluginid.':':'').$_M['mod'].'_'.$_M['ac'];
        }
        $isallow = $this->allow($n);
        if(!$isallow) {
            setPromptMsg(10009, '-1', 'error');
        }
    }

    /**
     * 检测用户是否有操作某栏目权限
     */
    protected function column_allow_check($columnid)
    {
        $row = t('column')->fetchFields($columnid, 'parentid,topid');
        if (empty($this->admin['columnids']) || empty($this->admin['_groupid']['purviews']) || in_array($columnid, $this->admin['_columnids']) || in_array($row['parentid'], $this->admin['_columnids']) || in_array($row['topid'], $this->admin['_columnids'])) {
            return getResult(100);
        }
        return getResult(10201,'','','?mod=column&ac=list&menuid=2');
    }

    /**
     * 检测用户是否有操作所属某栏目的文档权限
     */
    protected function archive_allow_check($ids)
    {
        $data = array();
        foreach((array)$ids as $v){
            if(empty($v)){
                continue;
            }
            $row = t('archives')->fetchFields($v,'id,status,columnid');
            if(empty($row)){
                continue;
            }
            $result = $this->column_allow_check($row['columnid']);
            if($result['code']!=100){
                return getResult(10405);
            }
            $data[] = $v;
        }
        return getResult(100,$data);
    }

    /**
     *记录后台日志
     * @param unknown_type $arr
     */
    protected function savelog($arr)
    {
        global $_M;
        if($_M['sys']['admin_log'] == 1) {
            $data = array();
            $data['method'] = !empty($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : '';
            if($data['method'] == 'GET') {
                $data['query'] = filterString($_M['cururl']);
            } else {
                $data['query'] = filterString(http_build_query($_GET));
            }
            $data['mod'] = $_M['mod'];
            $data['ac'] = $_M['ac'];
            $data['do'] = $_M['do'];
            $data['op'] = $_M['op'];
            $data['prompt_code'] = $arr['code'];
            $data['prompt_msg'] = $arr['msg'];
            $data['ip'] = $_M['ip'];
            $data['dateline'] = TIME;
            if(!empty($this->admin['id'])){
                $data['adminid'] = $this->admin['id'];
            }
            t('admin_log')->insert($data);
        }
    }

    /**
     * 生成用于下载应用的token
     */
    protected function appToken()
    {
        global $_M;
        $val = array();
        $val['sitekey'] = $_M['config']['sitekey'];
        $val['siteurl'] = $_M['sys']['basehost'];
        $val['sitename'] = $_M['sys']['webname'];
        $val['timestamp'] = TIME;
        return urlencode(urlencode(xxtea(json_encode($val),'en','mopcms')));
    }

    /**
     * 生成token
     */
    protected function create_token()
    {
        global $_M;
        $val = serialize(array('adminid'=>$this->admin['id'],'timestamp'=>TIME));
        return urlencode(xxtea($val,'en',$_M['config']['authkey']));
    }

    /**
     * 通过token登陆
     */
    protected function token_login()
    {
        global $_M;
        $token = str_replace(' ', '+', urldecode(_gp('token')));
        if($token){
            $token = xxtea($token,'de',$_M['config']['authkey']);
            $data = unserialize($token);
            if(!empty($data['adminid']) && ($data['timestamp']+3600)>TIME){
                $_M['admin'] = t('admin')->get_cache('fetch', $data['adminid']);
            }
        }
    }

    /**
     * 获取有效的ID
     */
    protected function valid_ids()
    {
        $id = preg_replace('/[^\d,]/i','',_gp('id'));
        if(strpos($id, ',')!==false){
            $ids = array();
            foreach (explode(',', $id) as $v){
                if($v){
                    $ids[] = $v;
                }
            }
            $id = $ids;
        }
        return $id;
    }
}