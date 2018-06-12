<?php
!defined('IN_MOPCMS') && exit('Access failed');

/**
 * 获取验证码的session值
 */
function get_seccode()
{
    @session_id($_COOKIE['PHPSESSID']);
    isset($_SESSION)?'':@session_start();
    return isset($_SESSION['securimage_code_value']['default']) ? $_SESSION['securimage_code_value']['default'] : '';
}

function convertip($ip)
{
    $return = '';
    if(preg_match("/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/", $ip)) {

        $iparray = explode('.', $ip);

        if($iparray[0] == 10 || $iparray[0] == 127 || ($iparray[0] == 192 && $iparray[1] == 168) || ($iparray[0] == 172 && ($iparray[1] >= 16 && $iparray[1] <= 31))) {
            $return = '- LAN';
        } elseif($iparray[0] > 255 || $iparray[1] > 255 || $iparray[2] > 255 || $iparray[3] > 255) {
            $return = '- Invalid IP Address';
        } else {
            $tinyipfile = MOPDATA.'data/tinyipdata.dat';
            if(@is_file($tinyipfile)) {
                $return = convertipTiny($ip, $tinyipfile);
            }
        }
    }
    return $return;
}

function convertipTiny($ip, $ipdatafile)
{

    static $fp = NULL, $offset = array(), $index = NULL;

    $ipdot = explode('.', $ip);
    $ip    = pack('N', ip2long($ip));

    $ipdot[0] = (int)$ipdot[0];
    $ipdot[1] = (int)$ipdot[1];

    if($fp === NULL && $fp = @fopen($ipdatafile, 'rb')) {
        $offset = unpack('Nlen', fread($fp, 4));
        $index  = fread($fp, $offset['len'] - 4);
    } elseif($fp == FALSE) {
        return  '- Invalid IP data file';
    }

    $length = $offset['len'] - 1028;
    $start  = unpack('Vlen', $index[$ipdot[0] * 4] . $index[$ipdot[0] * 4 + 1] . $index[$ipdot[0] * 4 + 2] . $index[$ipdot[0] * 4 + 3]);

    for ($start = $start['len'] * 8 + 1024; $start < $length; $start += 8) {

        if ($index{$start} . $index{$start + 1} . $index{$start + 2} . $index{$start + 3} >= $ip) {
            $index_offset = unpack('Vlen', $index{$start + 4} . $index{$start + 5} . $index{$start + 6} . "\x0");
            $index_length = unpack('Clen', $index{$start + 7});
            break;
        }
    }

    fseek($fp, $offset['len'] + $index_offset['len'] - 1024);
    if($index_length['len']) {
        return '- '.fread($fp, $index_length['len']);
    } else {
        return '- Unknown';
    }
}

/**
 * 获取ueditor编辑器
 * @param string $content 内容
 * @param string $fieldname 字段名称
 * @param array $config 配置参数
 */
function ueditor($fieldname='content',$content='',$config = array())
{
    global $_M;
    static $loadjs = 0;
    $loadjs++;
    $ue = array();
    $identity = '';
    if(!empty($config['identity'])){
        $identity = $config['identity'];
        unset($config['identity']);
    }
    if($identity=='member'){
        $ue[] = "toolbars: [[
            'simpleupload', 'insertimage', 'bold', 'italic', 'underline', 'removeformat', '|', 'forecolor', 'backcolor', '|',
            'fontfamily', 'fontsize', '|',
            'justifyleft', 'justifycenter', 'justifyright', 'justifyjustify', '|', 
            'link', 'unlink', '|',
            'undo', 'redo',
            ]]";
    }
    elseif($identity=='simple'){
        $ue[] = "toolbars: [[
            'fullscreen','undo', 'redo', '|',
            'bold', 'italic', 'underline', 'fontborder', 'strikethrough', 'removeformat', 'formatmatch', 'autotypeset', 'pasteplain', '|', 'forecolor', 'backcolor','selectall', 'cleardoc',
            'justifyleft', 'justifycenter', 'justifyright', 'justifyjustify', '|', 'touppercase', 'tolowercase', '|',
            'link', 'unlink','drafts']]";
    }
    if(empty($config['initialFrameWidth'])){
        $ue[] = 'initialFrameWidth:900';
    }
    if(empty($config['initialFrameHeight'])){
        $ue[] = 'initialFrameHeight:350';
    }
    if(empty($config['catchRemoteImageEnable'])){
        $ue[] = 'catchRemoteImageEnable:false';
    }
    foreach($config as $k=>$v){
        $ue[] = $k.':'.$v;
    }
    if(defined('ISMANAGE') && ISMANAGE===true && empty($config['serverUrl'])){
        $ue[] = 'serverUrl:"'.$_M['sys']['cmspath'].$_M['filename'].'?mod=ueditor"';
    }else{
        if(empty($config['serverUrl'])){
            $ue[] = 'serverUrl:"'.pseudoUrl($_M['sys']['cmspath'].$_M['filename'].'?mod=ueditor').'"';
        }
    }
    $ue[] = 'pageBreakTag:"#page#副标题#title#"';
    $str = '<script id="'.$fieldname.'" name="'.$fieldname.'" type="text/plain">'.$content.'</script>';
    if($loadjs==1){
        $str .= '<script type="text/javascript" src="'.$_M['sys']['basehost'].'/supplier/ueditor/ueditor.config.js"></script>
            <script type="text/javascript" src="'.$_M['sys']['basehost'].'/supplier/ueditor/ueditor.all.min.js"></script>';
    }
    $str .= '<script type="text/javascript">var ue = UE.getEditor("'.$fieldname.'", {'.implode(',',$ue).'});</script>';
    return $str;
}