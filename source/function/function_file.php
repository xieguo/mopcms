<?php
/**
 * 文件操作相关函数库
 * @copyright           (C) 2016-2099 MOPCMS.COM
 * @license             http://www.mopcms.com/license/
 */
!defined('IN_MOPCMS') && exit('Access failed');
/**
 * 写文件
 * @param type $file
 * @param type $content
 * @param type $flag
 * @return boolean
 */
function putFile($file, $content, $flag = '')
{
    $pathinfo = pathinfo($file);
    if(!empty($pathinfo['dirname']) && file_exists($pathinfo['dirname']) === FALSE && @ mkdir($pathinfo['dirname'], 0777, TRUE) === FALSE) {
        return FALSE;
    }
    if($flag == 'FILE_APPEND') {
        return @ file_put_contents($file, $content, FILE_APPEND);
    }
    return @ file_put_contents($file, $content, LOCK_EX);
}

/**
 * 用递归方式删除目录
 * @param type $file
 * @return boolean
 */
function rmRecurse($file)
{
    if(is_dir($file) && !is_link($file)) {
        foreach(glob($file . '/*') as $sf) {
            if(!rmRecurse($sf)) {
                return false;
            }
        }
        return @ rmdir($file);
    } else {
        return @ unlink($file);
    }
}

/**
 * 获取中文字符的拼音
 */
function getPinyin($str)
{
    static $pinyins;
    $restr = '';
    $str = trim($str);
    $slen = mb_strlen($str,CHARSET);
    if(empty($pinyins)) {
        $fp = fopen(MOPDATA . 'data/pinyin.dat', 'r');
        while(!feof($fp)) {
            $line = trim(fgets($fp));
            $pinyins[$line[0] . $line[1] . $line[2]] = substr($line, 4);
        }
        fclose($fp);
    }
    for($i = 0; $i < $slen; $i++) {
        $c = mb_substr($str, $i,1,CHARSET);
        if(isset($pinyins[$c])) {
            $restr .= $pinyins[$c];
        } else {
            if(preg_match("/[\w]/i", $c)) {
                $restr .= $c;
            } else {
                $restr .= "_";
            }
        }
    }
    return $restr;
}

/**
 * 递归获取目录文件
 * @param string $directory
 * @param type $exempt
 * @param type $files
 * @param type $dirs
 * @return string
 */
function getAllFiles($directory,$exempt = array('.','..'),&$files = array(),&$dirs = array())
{
    $directory = preg_replace("/\/$/", '', $directory) . '/';
    if(!file_exists($directory)){
        return '';
    }
    if(is_dir($directory) && !opendir($directory)){
        mkdir($directory, 0777, TRUE);
    }
    $handle = opendir($directory);
    while(false !==($resource = readdir($handle))) {
        if(!in_array(strtolower($resource), $exempt)) {
            //排除目录
            if(is_dir($directory . $resource . '/')) {
                getAllFiles($directory . $resource . '/', $exempt, $files, $dirs);
                $dirs[] = preg_replace('/\/{1,}/', '/', $directory . '/' . $resource . '/');
            } else {
                $files[] = preg_replace('/\/{1,}/', '/', $directory . '/' . $resource);
            }
        }
    }
    closedir($handle);
    return array('dirs' => $dirs, 'files' => $files);
}

/**
 * 删除指定目录中的所有数据
 * @param type $directory
 * @param type $root_dir_del
 */
function delAllFiles($directory, $root_dir_del = true)
{
    $row = getAllFiles($directory);
    if($row) {
        if(!empty($row['files'])) {
            foreach($row['files'] as $v) {
                if(is_file($v))
                @ unlink($v);
            }
        }
        if(!empty($row['dirs'])) {
            foreach($row['dirs'] as $v) {
                if(is_dir($v))
                @ rmdir($v);
            }
        }
        if($root_dir_del) {
            @ rmdir($directory);
        }
    }
}

/**
 * 远程文件是否存在
 * @param unknown_type $url
 */
function isRemoteFileExists($url)
{
    $curl = curl_init($url);
    // 不取回数据
    if(preg_match('/^https:\/\//', $url)) {
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    }
    curl_setopt($curl, CURLOPT_NOBODY, true);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET'); //不加这个会返回403，加了才返回正确的200，原因不明
    // 发送请求
    $result = curl_exec($curl);
    $found = false;
    // 如果请求没有发送失败
    if($result !== false) {
        // 再检查http响应码是否为200
        $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if($statusCode == 200) {
            $found = true;
        }
    }
    curl_close($curl);

    return $found;
}