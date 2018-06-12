<?php
!defined('IN_MOPCMS') && exit('Access failed');
class base_db
{
    public static $db;
    public static $connect;
    private static $config = array();

    public static function init($config)
    {
        self :: $config = &$config;
        $connect = 'db_'.($config['connecttype']=='pdo'?'pdo':'mysql');
        self :: $connect = $connect;
        self :: $db = new $connect();
        self :: $db->connect($config);
    }

    /**
     * 获取表前缀
     */
    public static function prefix()
    {
        return self :: $config['tablepre'];
    }

    /**
     * 获取表名
     * @param unknown_type $table
     */
    public static function table($table)
    {
        return self :: prefix().$table;
    }

    /**
     * 删除操作
     * @param $table 表名
     * @param $where 筛选条件
     */
    public static function delete($table, $where)
    {
        if(empty($where)) {
            return false;
        }
        self :: query('delete from ' . self :: table($table) . ' where '.$where);
        return true;
    }

    /**
     * 插入操作
     * @param $table 表名
     * @param $data 写入数据
     * @param $insert_id 是否返回新插入ID
     * @param $replace 是否替换写入
     */
    public static function insert($table, $data, $insertid = false,$replace=false)
    {
        $sql = self :: implode_save($data);
        if(empty($sql)) {
            return false;
        }
        $mng = $replace===false?'insert':'replace';
        self :: query($mng.' into '.self :: table($table).' SET '.$sql);
        if($insertid===true){
            return self :: $db->insert_id();
        }
        return true;
    }

    /**
     * 修改操作
     * @param unknown_type $table
     * @param unknown_type $data
     * @param unknown_type $condition
     */
    public static function update($table, $data, $condition)
    {
        $sql = self :: implode_save($data);
        if(empty($sql)) {
            return false;
        }
        self :: query('update ' . self :: table($table) . ' set ' . $sql . ' where ' . $condition);
        return true;
    }

    /**
     * 增改用到
     * @param unknown_type $array
     */
    private static function implode_save($array)
    {
        $arr = array();
        foreach ($array as $k => $v) {
            if(is_array($v)){
                $v = serialize($v);
            }
            $arr[] = self::parse_key($k) . '=' . self::parse_value($v);
        }
        return implode(' , ', $arr);
    }

    /**
     * 获取某一条记录
     * @param $sql
     */
    public static function fetch_array($sql)
    {
        $query = self :: query($sql);
        $result = self :: $db->fetch_array($query);
        self :: $db->free_result($query);
        return $result;
    }

    /**
     * 获取列表数据，返回数组
     * @param $sql
     * @param $key
     */
    public static function fetch_all($sql, $key = '')
    {
        $data = array();
        $query = self :: query($sql);
        while($row = self :: $db->fetch_array($query)) {
            if($key && isset($row[$key])) {
                $data[$row[$key]] = $row;
            } else {
                $data[] = $row;
            }
        }
        self :: $db->free_result($query);
        return $data;
    }

    /**
     * 获取某一字段的值，默认为第一个
     * @param $sql
     * @param $index 偏移量
     */
    public static function result_onefield($sql,$index=0)
    {
        self :: checksql($sql);
        return self :: $db->result_onefield($sql,$index);
    }

    public static function query($sql)
    {
        self :: checksql($sql);
        return self :: $db->query($sql);
    }

    /**
     * 取得结果集中行的数目
     * @param $resourceid
     */
    public static function num_rows($resourceid)
    {
        return self :: $db->num_rows($resourceid);
    }

    private static function quote($string)
    {
        return self::$db->quote($string);
    }

    /**
     * 返回上一个 MySQL 操作产生的文本错误信息
     */
    public static function error()
    {
        return self :: $db->error();
    }

    /**
     * 返回上一个 MySQL 操作中的错误信息的数字编码
     */
    public static function errno()
    {
        return self :: $db->errno();
    }

    /**
     * 字段名分析
     * @param unknown_type $key
     */
    private static function parse_key($key)
    {
        if(is_array($key)) {
            return array_map(array(self,'parse_key'), $key);
        } else {
            if(preg_match('/concat/i', $key)) {
                return $key;
            }
            $key = str_replace('`', '', $key);
            if(preg_match('/\./',$key)){
                list($pre,$key) = explode('.',$key);
                return $pre.'.`' . $key . '`';
            }
            return '`' . $key . '`';
        }
    }

    /**
     * value分析
     * @param unknown_type $value
     */
    private static function parse_value($value)
    {
        if(is_string($value)) {
            return self::quote($value);
        }
        if(is_int($value) || is_float($value)){
            return $value;
        }
        if(is_array($value)) {
            return array_map(array('self','parse_value'), $value);
        }
        if(is_bool($value)){
            return $value ? 1 : 0;
        }
        return '\'\'';
    }

    /**
     * limit分析
     * @param unknown_type $start
     * @param unknown_type $size
     */
    public static function parse_limit($start, $size = 0)
    {
        if(strpos($start, 'limit')!==false) {
            return $start;
        }
        if(strpos($start, ',')!==false) {
            list($start,$size) = explode(',', $start);
        }
        $start = max(0,(int)$start);
        $size = max(0,(int)$size);
        if($start && $size) {
            return ' limit '.$start.', '.$size;
        }
        if($start) {
            return ' limit '.$start;
        }
        if($size) {
            return ' limit '.$size;
        }
        return '';
    }

    /**
     * where子单元分析
     * @param $field
     * @param $val
     * @param $exp
     * @throws Exception
     */
    public static function fbuild($field, $val, $exp = '=')
    {
        $field = self :: parse_key($field);
        if(is_array($val)) {
            $exp = $exp == 'not in' ?'not in': 'in';
        }

        switch($exp) {
            case '=' :
            case '<>' :
            case '>' :
            case '<' :
            case '<=' :
            case '>=' :
                return $field  .' '. $exp . self :: parse_value($val);
            case 'like' :
                if(!preg_match('/%/', $val)) {
                    $val = '%' . $val . '%';
                }
                return $field .' ' . $exp . self :: parse_value($val);
            case 'in' :
            case 'not in' :
                $val = (array)$val;
                $val = $val ? implode(',', self :: parse_value($val)) : '\'\'';
                return $field .' '.$exp . ' (' . $val . ')';
            case 'find' :
                return 'FIND_IN_SET(' . self :: parse_value($val) . ', ' . $field . ')>0';
            case 'nofind' :
                return 'FIND_IN_SET(' . self :: parse_value($val) . ', ' . $field . ')<1';
            case 'between' :
            case 'not between' :
                list($min, $max) = explode(',', $val);
                return '(' . $field . ' '.$exp.'  ' . (int) $min . ' and ' . (int) $max . ')';
            case 'regexp' :
                return $field . ' '.$exp.' ' . self :: parse_value($val);
            case 'NULL' :
            case 'NOT NULL' :
                return $field . ' IS ' . $exp;
            default :
                throw new Exception('undefined: "' . $exp . '"');
        }
    }

    /**
     * SQL语句过滤程序
     * @param unknown_type $sql
     */
    private static function checksql($sql)
    {
        global $_M;
        $clean = '';
        $old_pos = 0;
        $pos = -1;

        $notallow = "[^0-9a-z@\._-]{1,}(union|sleep|benchmark|load_file|outfile|intodumpfile|substring|--)[^0-9a-z@\.-]{1,}";
        if(preg_match('/'.$notallow.'/i', $sql)){
            return false;
        }

        //完整的SQL检查
        while (true){
            $pos = strpos($sql, '\'', $pos + 1);
            if ($pos === false){
                break;
            }
            $clean .= substr($sql, $old_pos, $pos - $old_pos);
            while (true){
                $pos1 = strpos($sql, '\'', $pos + 1);
                $pos2 = strpos($sql, '\\', $pos + 1);
                if ($pos1 === false){
                    break;
                }elseif ($pos2 == false || $pos2 > $pos1){
                    $pos = $pos1;
                    break;
                }
                $pos = $pos2 + 1;
            }
            $clean .= '$s$';
            $old_pos = $pos + 1;
        }
        $clean .= substr($sql, $old_pos);
        $clean = trim(strtolower(preg_replace(array('~\s+~s' ), array(' '), $clean)));
        $res = true;
        if (strpos($clean, 'union') !== false && preg_match('~(^|[^a-z])union($|[^[a-z])~s', $clean) != 0) {
            $res = false;
        }
        elseif (strpos($clean, '/*') > 2 || strpos($clean, '--') !== false || strpos($clean, '#') !== false){
            $res = false;
        }
        elseif (strpos($clean, 'sleep') !== false && preg_match('~(^|[^a-z])sleep($|[^[a-z])~s', $clean) != 0){
            $res = false;
        }
        elseif (strpos($clean, 'benchmark') !== false && preg_match('~(^|[^a-z])benchmark($|[^[a-z])~s', $clean) != 0){
            $res = false;
        }
        elseif (strpos($clean, 'load_file') !== false && preg_match('~(^|[^a-z])load_file($|[^[a-z])~s', $clean) != 0){
            $res = false;
        }
        elseif (strpos($clean, 'into outfile') !== false && preg_match('~(^|[^a-z])into\s+outfile($|[^[a-z])~s', $clean) != 0){
            $res = false;
        }
        elseif (preg_match('~\([^)]*?select~s', $clean) != 0){
            $res = false;
        }
        if($res===false) {
            throw new Exception('Forbidden sql:"'.$sql.'"');
        }
        return true;
    }
}
