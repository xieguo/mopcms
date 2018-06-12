<?php
/**
 * 自定义表单处理类
 * @copyright           (C) 2016-2099 MOPCMS.COM
 * @license             http://www.mopcms.com/license/
 */
!defined('IN_MOPCMS') && exit('Access failed');

class table_customform extends base_table
{
    protected function parseData(&$data)
    {
        if(isset($data['enable'])){
            $data['_enable'] = $data['enable']?'启用中':'<font color="red">关闭中</font>';
        }
    }

    /**
     * 创建表
     * @param string $table 表名
     */
    public function createTable($table)
    {
        global $_M;
        if($table && db::num_rows(db::query("SHOW TABLES LIKE '" . db::table($table) . "'")) == 0) {
            $sql = "CREATE TABLE IF NOT EXISTS `" . db :: table($table) . "`(
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `ischeck` tinyint(1) NOT NULL default '1',
            `mid` int(11) NOT NULL default '0',
            `createtime` int(11) NOT NULL default '0',
            `ip` char(15) NOT NULL DEFAULT '',
            PRIMARY KEY  (`id`), KEY `id` (`id`)\r\n) 
            ENGINE=MyISAM DEFAULT CHARSET=utf8; ";
            db::query($sql);
            return true;
        }
        return false;
    }
    /*
     * 删除自定义表单
     */
    public function deleteById($id)
    {
        global $_M;
        if(empty($id)) {
            return getResult(10006);
        }
        $row = $this->fetch($id);
        if(empty($row)) {
            return getResult(10004);
        }
        t('customform_fields')->delete(array('formid'=>$id));
        if($_M['sys']['deleteform']){
            db :: query('DROP TABLE IF EXISTS `' . db :: table($row['tablename']) . '`;');
        }
        $this->delete($id);
        return getResult(100,'',10016);
    }
}