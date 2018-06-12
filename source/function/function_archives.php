<?php
/**
 * 文档函数库
 * @copyright           (C) 2016-2099 MOPCMS.COM
 * @license             http://www.mopcms.com/license/
 */
!defined('IN_MOPCMS') && exit('Access failed');

/**
 * 发布文档时涉及的附件保存到缓存里，完成后把它与文档关连
 * @global type $_M
 * @param type $fid 上传文件ID(uploads表)
 */
function setAttachCache($fid)
{
    if (!session_id()){
        session_start();
    }
    $file = MOPDATA . 'cache/attach/' . session_id() . '.php';
    require_once loadlib('file');
    $code = '';
    if(!file_exists($file)) {
        $code = '<?php' . "\r\n";
        $code .= "\$attachs = array();\r\n";
    }
    is_file($file) && include($file);
    $code .= "\$attachs[] = $fid;\r\n";
    $result = putFile($file, $code,'FILE_APPEND');
}

/**
 * 清理附件，如果关连的文档ID，先把上一批附件传给这个文档ID
 * @global type $_M
 * @param type $aid 文章ID
 * @param type $title 文章标题
 */
function clearAttachCache($aid = 0, $title = '')
{
    if (!session_id()){
        session_start();
    }
    $file = MOPDATA . 'cache/attach/' . session_id() . '.php';
    if(!file_exists($file)) {
        return;
    }
    //把附件与文档关连
    if(!empty($aid)) {
        include($file);
        foreach($attachs as $v) {
            $data = array('arcid' => $aid, 'title' => $title);
            if(empty($title)){
                unset($data['title']);
            }
            t('uploads')->update($v, $data);
        }
    }
    @ unlink($file);
}

/**
 * 对有flag标注或带缩略图或跳转的标出来
 */
function archivesFlags($row)
{
    global $_M;
    $flag = array();
    if(!empty($row['thumb'])){
        $flag[] = '[<font color="red"">图</font>]';
    }
    if(!empty($row['redirecturl'])){
        $flag[] = '[<font color="red"">跳</font>]';
    }
    if(!empty($row['flag'])){
        foreach($_M['sys']['arcatt'] as $k => $v) {
            $flag[] = strpos($row['flag'],$k)!==false ? '[<font color="red"">'.msubstr($v, 1).'</font>]' : '';
        }
    }
    if($flag){
        return implode(' ',$flag);
    }
    return '';
}

/**
 * 从文章中批量出指定数量的图片
 * @param unknown_type $text
 * @param unknown_type $num
 */
function filterImgs($text,$num=3)
{
    if (empty($text)){
        return '';
    }
    preg_match_all('|<img(.*)>|isU',$text,$imgcode);
    $arr = array();
    $i = 0;
    foreach($imgcode[1] as $k=>$v)
    {
        $i++;
        $v = str_replace("'","\"",$v.' ');
        preg_match_all('|src="(.*)"|isU',$v,$imgurl);
        if(isset($imgurl[1][0])){
            $arr[] = $imgurl[1][0];
            if($i==$num){
                break;
            }
        }
    }
    return $arr;
}