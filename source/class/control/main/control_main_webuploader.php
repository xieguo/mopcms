<?php
/**
 * webuploader控制类
 * @copyright			(C) 2016-2099 MOPCMS.COM
 * @license				http://www.mopcms.com/license/
 */
!defined('IN_MOPCMS') && exit('Access failed');
class control_main_webuploader extends control_main
{
    /**
     * 上传
     */
    public function webuploader_upload()
    {
        $post = array();
        $post['fileid'] = filterString(_gp('id'));
        $result = loadm('main_webuploader')->upload($post);
        if($result['code']!=100){
            echo '上传失败:' . $result['msg'];
        }else{
            echo $result['data'];
        }
        exit;
    }

    /**
     * 生成缩略图,低版本的浏览器太会用到
     */
    public function webuploader_thumbnail()
    {
        $id = filterString(_gp('id'));
        $fileurl = $_SESSION['bigfile_info'][$id];
        $fileurl = imageResize($fileurl,160,160);
        return getResult(100,$fileurl);
    }

    /**
     * 删除刚刚上传还没提交保存的图片
     */
    public function webuploader_deljustpic()
    {
        $id = filterString(_gp('id'));
        $fileurl = $_SESSION['bigfile_info'][$id];
        loadm('archives')->del_image($fileurl);
        return getResult(100,$_SESSION['bigfile_info']);
    }

    /**
     * 删除已经上传的图片
     */
    public function webuploader_delpic()
    {
        global $_M;
        $post = array();
        $post['id'] = (int) _gp('id');
        $post['pic'] = filterString(_gp('pic'));
        $post['mfid'] = (int) _gp('mfid');
        $post['cfid'] = (int) _gp('cfid');
        $post['mid'] = $_M['mid'];
        $_M['admin'] = t('admin')->loginInfo();
        if(!empty($_M['admin']['id'])){
            $post['adminid'] = $_M['admin']['id'];
        }
        return loadm('main_webuploader')->delpic($post);
    }
}