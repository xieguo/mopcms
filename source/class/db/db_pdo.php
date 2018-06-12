<?php
/**
 * @copyright           (C) 2016-2099 MOPCMS.COM
 * @license             http://www.mopcms.com/license/
 */
!defined('IN_MOPCMS') && exit('Access failed');
class db_pdo
{
    private $link = '';
    private $query = '';

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
        $options = !empty($cfg['pconnect'])?array(PDO::ATTR_PERSISTENT => true):array();
        $options[PDO::MYSQL_ATTR_INIT_COMMAND] = 'SET character_set_connection=utf8, character_set_results=utf8, character_set_client=binary, sql_mode=\'\'';
        $portstr = !empty($cfg['port']) && $cfg['port']!=3306?'port='.$cfg['port'].';':'';
        $this->link = new PDO('mysql:host='.$cfg['dbhost'].';'.$portstr.'dbname='.$cfg['dbname'], $cfg['dbuser'], $cfg['dbpwd'], $options);
        if(!$this->link) {
            throw new Exception(10027);
        }
    }

    /**
     * 获取某一条记录
     * @param $query
     */
    public function fetch_array($query)
    {
        $result = $query->fetch(PDO::FETCH_ASSOC);
        return $result ? $result : array();
    }

    /**
     * 获取某一字段的值，默认为第一个
     * @param $sql
     * @param $index 偏移量
     */
    public function result_onefield($sql,$index=0)
    {
        $query = $this->execute($sql);
        $result = $query->fetchColumn($index);
        $this->free_result($query);
        return $result;
    }

    public function query($sql,$retry=true)
    {
        $operate = trim(strtolower(substr($sql, 0, strpos($sql, ' '))));
        if($operate=='select'){
            $this->link->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
        }
        $this->query = $this->link->query($sql);
        if(!$this->query) {
            $error = $this->link->errorInfo();
            if(in_array($error[1], array(2006, 2013)) && $retry===true) {
                $this->connect();
                return $this->query($sql, false);
            }
            throw new Exception($error[0].' '.$error[2].' SQL:"'.$sql.'"', $error[1]);
        }
        return $this->query;
    }

    private function execute($sql,$retry=true)
    {
        $operate = trim(strtolower(substr($sql, 0, strpos($sql, ' '))));
        if($operate=='select'){
            $this->link->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
        }
        $this->query = $this->link->prepare($sql);
        $this->query->execute();
        $error = $this->query->errorInfo();
        if($error[1]) {
            if(in_array($error[1], array(2006, 2013)) && $retry===true) {
                $this->connect();
                return $this->execute($sql,false);
            }
            throw new Exception($error[0].' '.$error[2].' SQL:"'.$sql.'"', $error[1]);
        }
        return $this->query;
    }

    public function quote($string)
    {
        return $this->link->quote($string);
    }

    public function error()
    {
        $error = $this->link->errorInfo();
        if(!$error[2] && $this->query){
            $error = $this->query->errorInfo();
        }
        return $error[2]?$error[2]:'';
    }

    public function errno()
    {
        $error = $this->link->errorCode();
        if(!$error && $this->query){
            $error = $this->query->errorCode();
        }
        return $error?$error:0;
    }

    public function num_rows($query)
    {
        $query = $query?$query:$this->query;
        return $query->rowCount();
    }

    public function free_result($query)
    {
        $query->closeCursor();
    }

    public function insert_id()
    {
        $id = $this->link->lastInsertId();
        return $id? $id : $this->result_onefield("SELECT last_insert_id()");
    }

    public function close() {}
}