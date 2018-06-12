<?php
/**
 * webuploader上传模型类
 * @copyright			(C) 2016-2099 MOPCMS.COM
 * @license				http://www.mopcms.com/license/
 */
!defined('IN_MOPCMS') && exit('Access failed');
class model_main_webuploader extends base_model
{
    /**
     * 上传
     */
    public function upload($post)
    {
        global $_M;
        session_start();
        if (!empty($_SESSION['bigfile_info']) && count($_SESSION['bigfile_info']) >= $_M['sys']['webuploader_maxupload']) {
            return getResult(10608);
        }
        $img = new mop_image;
        $result = $img->uploadAttachment('file',1000,1000,$_M['sys']['watermark']);
        if($result['code']!=100){
            return $result;
        }
        $fileurl = $result['data'];
        //保存信息到 session
        if (!isset($_SESSION['file_info'])) {
            $_SESSION['file_info'] = array();
        }

        if (!isset($_SESSION['bigfile_info'])) {
            $_SESSION['bigfile_info'] = array();
        }

        $_SESSION['fileid'] = $post['fileid'];
        $_SESSION['bigfile_info'][$post['fileid']] = $fileurl;
        return getResult(100,$post['fileid']);
    }
    
    /**
     * 删除已经上传的图片
     */
    public function delpic($post)
    {
        if(empty($post['id'])||empty($post['pic'])||(empty($post['mfid']) && empty($post['cfid']))){
            return getResult(10001);
        }
        $row = t('uploads')->fetchFields(array('url'=>$post['pic']), 'url,mid');
        if(empty($row)){
            return getResult(10004);
        }
        if (empty($post['adminid']) && (empty($row['mid'])||$row['mid'] != $post['mid'])) {
            return getResult(10009);
        }
        if(!empty($post['mfid'])){
            $f = t('models_fields')->fetchFields($post['mfid'], 'fieldname,modelid');
            if(empty($f)){
                return getResult(10004);
            }
            $tablename = t('models')->fetchFields($f['modelid'], 'tablename');
        }
        if(!empty($post['cfid'])){
            $f = t('customform_fields')->fetchFields($post['cfid'], 'fieldname,formid');
            if(empty($f)){
                return getResult(10004);
            }
            $tablename = t('customform')->fetchFields($f['formid'], 'tablename');
        }
        if(empty($tablename)){
            return getResult(10017);
        }
        $imgs = t($tablename)->fetchFields($post['id'],$f['fieldname']);
        if (empty($imgs)) {
            return getResult(10009);
        }
        $imgs = unserialize($imgs);
        unset($imgs[md5($post['pic'])]);
        $this->_del_image($post['pic']);
        t($tablename)->update($post['id'], array($f['fieldname'] => serialize($imgs)));
        return getResult(100);
    }
}