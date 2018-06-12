<?php
/**
 * 全局控制类
 * @copyright			(C) 2016-2099 MOPCMS.COM
 * @license				http://www.mopcms.com/license/
 */
!defined('IN_MOPCMS') && exit('Access failed');

class control_global extends base_control
{
    public function __call($name, $arguments)
    {
        return getResult(10017,'','','?');
    }

    /**
     * Ueditor编辑器
     */
    public function ueditor()
    {
        $action = filterString(_gp('action'));
        $ueditor = new helper_ueditor;
        if(is_callable(array($ueditor, $action))){
            $result = $ueditor->$action();
            if (isset($_GET["callback"])) {
                if (preg_match("/^[\w]+$/", $_GET["callback"])) {
                    echo htmlspecialchars($_GET["callback"]) . '(' . getjson($result) . ')';
                } else {
                    getjson(array('state'=> 'callback参数不合法'));
                }
            } else {
                getjson($result);
            }
        }
    }

    public function formcode()
    {
        return getResult(100, FORMSUBMIT);
    }

    /**
     * 级联数据获取
     */
    public function ajax_multilevel()
    {
        global $_M;
        $identifier = filterString(_gp('identifier'));
        return controlCache('main_multilevel','multilevelList',array('identifier'=>$identifier),'multilevel');
    }

    /**
     * 评论列表
     */
    public function comments()
    {
        global $_M;
        $aid = (int)_gp('aid');
        $post = array('aid'=>$aid,'ischeck'=>2,'page'=>$_M['page']);
        $res = controlCache('main_comments','info_list',$post,'archives_comment');
        if($res['code']!=100){
            return $res;
        }
        include template('replyform');
        $res['data']['replyform'] = output();
        return $this->output($res);
    }

    /**
     * 评论AJAX列表
     */
    public function comments_ajaxlist()
    {
        global $_M;
        $aid = (int)_gp('aid');
        $post = array('aid'=>$aid,'ischeck'=>2,'page'=>1);
        $res = controlCache('main_comments','info_list',$post,'archives_comment');
        if($res['code']!=100){
            exit;
        }
        $result = &$res['data']['row'];
        include template('replyform');
        $replyform = output();
        include template($this->ac);
        $str = output();
        $this->output_js($str);
    }

    /**
     * 对某条评论顶踩操作
     */
    public function comments_dingcai()
    {
        global $_M;
        $id = (int)_gp('id');
        $type = filterString(_gp('type'));
        return loadm('main_comments')->comments_dingcai($id,$type);
    }

    public function ajax_upload_img()
    {
        $fieldname = filterString(_gp('fieldname'));
        $img = new mop_image;
        return $img->uploadAttachment($fieldname);
    }
}
