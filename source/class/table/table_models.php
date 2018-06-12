<?php
/**
 * 文档模型处理类
 * @copyright			(C) 2016-2099 MOPCMS.COM
 * @license				http://www.mopcms.com/license/
 */
!defined('IN_MOPCMS') && exit('Access failed');

class table_models extends base_table
{
    protected function parseData(& $data)
    {
        if(isset($data['enable'])) {
            $data['_enable'] = empty($data['enable']) ? '关闭' : '启用';
        }
        if(isset($data['allowpub'])) {
            $data['_allowpub'] = empty($data['allowpub']) ? '不允许' : '允许';
        }
    }

    public function fetchByIds($ids = '')
    {
        $where = array();
        $where['enable'] = 1;
        if(!empty($ids)) {
            $where['id'] = $ids;
        } else {
            $where[] = db :: fbuild('id', -1, '>');
        }
        return $this->fetchList($where, '*', '', '', 'asc');
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
            `id` int(11) NOT NULL default '0',
            `columnid` int(11) NOT NULL default '0',
            PRIMARY KEY  (`id`), KEY `columnid` (`columnid`)\r\n) 
            ENGINE=MyISAM DEFAULT CHARSET=utf8; ";
            return db :: query($sql);
        }
        return false;
    }

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
        if($row['issystem']) {
            return getResult(10309);
        }
        //获取某模型的所有栏目id
        $columnids = array();
        $arr = t('column')->fetchList(array('modelid' => $id), 'id');
        foreach($arr as $v) {
            $columnids[] = $v['id'];
        }

        //删除相关信息
        if($columnids) {
            t('models_fields')->delete(array('modelid' => $id));
            t('archives')->delete(array('columnid' => $columnids));
            t('specials')->delete(array('columnid' => $columnids));
            t('archives_comment')->delete(array('columnid' => $columnids));
            t('column')->delete($columnids);
        }
        if($_M['sys']['deleteform']){
            db :: query('DROP TABLE IF EXISTS `' . db :: table($row['tablename']) . '`;');
        }
        //删除频道配置信息
        $this->delete($id);
        return getResult(100,'',10206);
    }

    /**
     * 某模型是否可用
     * @param $modelid 模型ID
     */
    public function isAvailable($modelid)
    {
        if(empty($modelid)){
            return getResult(10401);
        }
        $row = $this->fetch($modelid);
        if($row['enable']!=1){
            return getResult(10315);
        }
        return getResult(100,$row);
    }
}