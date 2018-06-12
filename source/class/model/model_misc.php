<?php
!defined('IN_MOPCMS') && exit('Access failed');
class model_misc extends base_model
{
    /**
     * 汉字转拼音
     * @param $words 关键词
     */
    public function to_pinyin($words)
    {
        if(empty($words)){
            return $words;
        }
        require_once loadlib('file');
        return getPinyin($words);
    }

    /**
     * 所属人员的站内图片列表
     * @param unknown_type $uid
     * @param unknown_type $page
     * @param unknown_type $identity
     */
    public function ueditor_imgs($uid,$page,$identity='adminid')
    {
        $identity = $identity=='adminid'?'adminid':'mid';
        $row = array();
        $row['where'] = array($identity => $uid, 'isfirst' => 1);
        $row['pagesize'] = 15;
        $row['fields'] = 'url';
        $rs = t('uploads')->info_list($row);
        if($rs['maxpages'] < $page) {
            return array();
        }
        foreach($rs['list'] as $k => $v) {
            $v['_url'] = imageResize($v['url'], 105, 105);
            $rs['list'][$k] = $v;
        }
        return $rs['list'];
    }

    /**
     * 通过swfupload上传图片
     * @param $fieldname
     * @param $width
     * @param $height
     */
    public function swfupload_image($fieldname,$width='',$height='')
    {
        $img = new mop_image;
        if($_FILES[$fieldname]['type']=='application/octet-stream'){
            if(strpos($_FILES[$fieldname]['name'], 'gif')){
                $_FILES[$fieldname]['type'] = 'image/gif';
            }
            elseif(strpos($_FILES[$fieldname]['name'], 'png')){
                $_FILES[$fieldname]['type'] = 'image/png';
            }else{
                $_FILES[$fieldname]['type'] = 'image/jpeg';
            }
        }
        return $img->uploadAttachment($fieldname,$width,$height);
    }

    public function imageResize($filename,$width='',$height='')
    {
        if(strpos($filename, 'http://')!==false){
            return false;
        }
        $img = new mop_image;
        return $img->imageResize(MOPROOT . $filename, $width, $height);
    }
}