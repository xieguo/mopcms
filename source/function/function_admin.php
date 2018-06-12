<?php
/**
 * 后台相关函数库
 * @copyright           (C) 2016-2099 MOPCMS.COM
 * @license             http://www.mopcms.com/license/
 */
!defined('IN_MOPCMS') && exit ('Access failed');

/**
 * 某栏目下文档数量
 * @param int $columnid
 */
function countByColumnid($columnid)
{
    return t('archives')->countByColumnid($columnid);
}

/**
 * 字段设置等处用到,数据序列化
 * @param type $str 需要处理的数据
 * @return string
 */
function serializeData($str)
{
    if ($str = filterString(trim($str))) {
        $arr = explode("\n", $str);
        if (!preg_match('/=/', $str)) {
            return $str;
        }
        $row = array ();
        $i = 0;
        foreach ($arr as $v) {
            $i++;
            list ($keys, $value) = explode('=', $v);
            $keys = filterString(trim($keys));
            if (!empty ($row[$keys])) {
                $row[$i . '#' . $keys] = filterString(trim($value));
            } else {
                $row[$keys] = trim($value);
            }
        }
        return serialize($row);
    }
    return '';
}

/**
 * 字段设置等处用到,数据反序列化
 * @param type $str 需要处理的数据
 * @return string
 */
function unserializeData($data)
{
    $str = '';
    if (!empty ($data)) {
        $arr = unserialize($data);
        if (empty ($arr)){
            return $data;
        }
        foreach ($arr as $k => $v) {
            $str .= $k . '=' . $v . "\n";
        }
    }
    return trim($str, "\n");
}