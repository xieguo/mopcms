<?php
/**
 * 附件类
 * @copyright			(C) 2016-2099 MOPCMS.COM
 * @license				http://www.mopcms.com/license/
 */
if (!defined('IN_MOPCMS')) {
    exit('Access failed');
}

class table_uploads extends base_table
{

    public $mediatype = array(1 => '图片', 2 => 'flash', 3 => '视频', 4 => '音频', 5 => '压缩文件', 6 => '其它文档');

    public function fetchLikeUrl($pic)
    {
        global $_M;
        if (empty($pic) || preg_match('#http:\/\/#i', $pic) || !preg_match('#\.(' . $_M['sys']['imgtype'] . '|' . $_M['sys']['softtype'] . '|' . $_M['sys']['mediatype'] . ')#', $pic)) {
            return false;
        }
        if (strpos($pic, '_')) {
            $pic = preg_replace('#(.*)(_)?(\d+)?(x)?(\d+)?(\.('.$_M['sys']['imgtype'].')){1}#isU', '\\1', $pic);
        }
        else {
            $pic = preg_replace('#(.*)(\.(' . $_M['sys']['imgtype'] . '|' . $_M['sys']['softtype'] . '|' . $_M['sys']['mediatype'] . ')){1}#isU', '\\1', $pic);
        }
        $pic = str_replace("'", '', $pic);
        return db::fetch_all("SELECT * FROM " . db::table($this->tname) . " where url like '{$pic}%'");
    }

    /**
     * 上传附件记录入库
     * @param array $row
     */
    public function save($row)
    {
        global $_M;
        $data = array();
        if(!empty($row['src'])){
            $p = (array)$this->fetchFields(array('url' => $row['src']), 'arcid,title,mid');
            $row = array_merge($row,$p);
        }
        $data['url'] = preg_replace("'(.*)?(/uploads/(.*)){1}'isU", "\\2",$row['url']);
        $data['title'] = !empty($row['title'])?$row['title']:$data['url'];
        if (!empty($row['arcid'])){
            $data['arcid'] = $row['arcid'];
        }
        $p = pathinfo($row['url']);
        if(preg_match("/(".$_M['sys']['imgtype'].")/i", $p['extension'])){
            $data['mediatype'] = 1;
        }
        elseif($p['extension']=='swf'){
            $data['mediatype'] = 2;
        }
        elseif(preg_match("/(mp4|rmvb|rm||wmv|flv|mpg)/i", $p['extension'])){
            $data['mediatype'] = 3;
        }
        elseif(preg_match("/(wav|mp3|wma|mov|amr|mid)/i", $p['extension'])){
            $data['mediatype'] = 4;
        }
        elseif(preg_match("/(zip|gz|rar)/i", $p['extension'])){
            $data['mediatype'] = 5;
        }else{
            $data['mediatype'] = 6;
        }
        $p = @getimagesize(MOPROOT . $row['url']);
        $data['width'] = $p[0];
        $data['height'] = $p[1];
        $data['filesize'] = @filesize(MOPROOT . $row['url']);
        $data['uptime'] = TIME;
        $data['isfirst'] = !empty($row['isfirst'])?1:0;
        if(!empty($_M['admin']['id'])){
            $data['adminid'] = $_M['admin']['id'];
        }else{
            $data['mid'] = !empty($row['mid'])?$row['mid']:$_M['mid'];
        }

        $fid = $this->insert($data,true);
        if(empty($row['src']) && empty($data['arcid'])){
            require_once loadlib('archives');
            setAttachCache($fid, $data['url']);
        }
    }
}