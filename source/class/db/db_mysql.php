<?php
/**
 * @copyright           (C) 2016-2099 MOPCMS.COM
 * @license             http://www.mopcms.com/license/
 */
!defined('IN_MOPCMS') && exit('Access failed');

class db_mysql
{
    private $link = '';

    public function __construct(){}

    /**
     * 数据库连接，MYSQL版本需大于5.0.1
     */
    public function connect($cfg)
    {
        if(empty($cfg)) {
            base_error::error(10026);
        }
        if(empty($cfg['dbname'])) {
            base_error::error(10028);
        }

        $dbhost = $cfg['dbhost'].(!empty($cfg['port']) && $cfg['port']!=3306?':'.$cfg['port']:'');
        if($cfg['pconnect']) {
            $this->link = @mysql_pconnect($dbhost, $cfg['dbuser'], $cfg['dbpwd'], MYSQL_CLIENT_COMPRESS);
        } else {
            $this->link = @mysql_connect($dbhost, $cfg['dbuser'], $cfg['dbpwd'], 1, MYSQL_CLIENT_COMPRESS);
        }
        if(!$this->link) {
            throw new Exception(10027);
        } else {
            mysql_query("SET character_set_connection=utf8, character_set_results=utf8, character_set_client=binary,sql_mode=''", $this->link);
            @mysql_select_db($cfg['dbname'], $this->link);
        }
    }

    /**
     * 获取某一条记录
     * @param $query
     */
    public function fetch_array($query)
    {
        $result = mysql_fetch_array($query, MYSQL_ASSOC);
        return $result ? $result : array();
    }

    /**
     * 获取某一字段的值，默认为第一个
     * @param $sql
     * @param $index 偏移量
     */
    public function result_onefield($sql,$index=0)
    {
        $query = $this->query($sql);
        $result = @mysql_result($query, $index);
        $this->free_result($query);
        return $result;
    }

    public function query($sql,$retry=true)
    {
        $operate = trim(strtolower(substr($sql, 0, strpos($sql, ' '))));
        $func = $operate=='select' ? 'mysql_query' : 'mysql_unbuffered_query';
        if(!($query = $func($sql, $this->link))) {
            if(in_array($this->errno(), array(2006, 2013)) && $retry===true) {
                $this->connect();
                return $this->query($sql, false);
            }
            throw new Exception($this->error().' SQL:"'.$sql.'"', $this->errno());
        }
        return $query;
    }

    public function quote($string)
    {
        return '\'' . addcslashes($string, "\n\r\\'\"") . '\'';
    }

    public function error()
    {
        return (($this->link) ? mysql_error($this->link) : mysql_error());
    }

    public function errno()
    {
        return intval(($this->link) ? mysql_errno($this->link) : mysql_errno());
    }

    public function num_rows($query)
    {
        return mysql_num_rows($query);
    }

    public function num_fields($query)
    {
        return mysql_num_fields($query);
    }

    public function free_result($query)
    {
        return mysql_free_result($query);
    }

    public function insert_id()
    {
        return ($id = mysql_insert_id($this->link)) >= 0 ? $id : $this->result_onefield('SELECT last_insert_id()');
    }

    public function close()
    {
        return mysql_close($this->link);
    }
}