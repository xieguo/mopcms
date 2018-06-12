<?php
!defined('IN_MOPCMS') && exit('Access failed');
class base_control
{
    protected $mod = '';
    protected $ac = '';
    protected $do = '';
    protected $op = '';

    public function __construct()
    {
        global $_M;
        $this->mod = $_M['mod'];
        $this->ac = $_M['ac'];
        $this->do = $_M['do'];
        $this->op = $_M['op'];
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
        if(empty($result['data'])){
            $result['data'] = array();
        }
        if(!empty($referer)){
            $result['referer'] = $referer;
        }
        $template = empty($template) && $template!==false?$this->mod.($this->ac?'_'.$this->ac:'').($this->do?'_'.$this->do:'').($this->op?'_'.$this->op:''):$template;
        if($template){
            $result['data']['template'] = $template;
        }
        return $result;
    }
    /**
     * 搜索URL拼接
     * @param unknown_type $post
     */
    protected function cururl($post)
    {
        global $_M;
        foreach($post as $k=>$v){
            if($v && !is_array($v)){
                $_M['cururl'] .= '&'.$k.'='.$v;
            }
        }
    }

    /**
     * 字符串转成js格式输出
     * @param type $str
     * @return type
     */
    protected function output_js($str){
        $str = trim ($str);
        $search = array('\\s\\s',chr(10),chr(13),'	','\\','"','\\\'',"'");
        $replace = array('\\s','','','','\\\\','\\"','\\\\\'',"\'");
        $str = str_replace($search,$replace,$str);
        echo 'document.write("' . $str . '");';
        exit;
    }

    /**
     * 发表评论
     */
    public function comments_submit()
    {
        global $_M;
        $post = array();
        $post['aid'] = (int)_gp('aid');
        $post['content'] = filterString(_gp('content'));
        return loadm('main_comments')->save($post);
    }

    /**
     * 回复某条评论
     */
    public function comments_reply_submit()
    {
        global $_M;
        $cmtid = (int)_gp('cmtid');
        $replymsg = filterString(_gp('replymsg'));
        return loadm('main_comments')->reply($cmtid,$replymsg);
    }

    /**
     * 获取validform检测时所传参数
     * @param unknown_type $name
     */
    protected function validform_getval($name){
        $val = (!empty($_POST['name']) && !empty($_POST['param']) && $_POST['name'] == $name) ? $_POST['param'] : _gp($name);
        return filterString($val,'@');
    }

    /**
     * validform通过ajaxurl检测返回结果
     */
    protected function validform_ajaxurl($res)
    {
        if($res['code']!=100){
            echo $res['msg'];
        }else{
            echo json_encode(array('status' => 'y'));
        }
        exit;
    }
}