<?php
/**
 * 后台其它操作管理模型类
 * @copyright			(C) 2016-2099 MOPCMS.COM
 * @license				http://www.mopcms.com/license/
 */
!defined('IN_MOPCMS') && exit('Access failed');
class model_admin_main extends base_model
{
    /**
     * 最常用操作
     * @param $adminid 管理员ID
     * @param $limit 调取数量
     */
    public function common_operate($adminid,$limit=10)
    {
        $sql = 'select `mod`,ac,count(*) as num from '. db::table('admin_log') .' where adminid='.$adminid.' group by `mod`, `ac` order by num desc limit '.$limit;
        $list = db :: fetch_all($sql);
        $data = array();
        foreach($list as $v){
            if($row = t('admin_menu')->fetchFields(array('mod'=>$v['mod'],'ac'=>$v['ac'],'islist'=>1,'isshow'=>1))){
                t('admin_menu')->parseData($row);
                $data[] = $row;
            }
        }
        return $data;
    }

    /**
     * 最新操作
     * @param unknown_type $adminid
     * @param unknown_type $limit
     */
    public function lastest_operate($adminid,$limit=10)
    {
        $where = array();
        $where['adminid'] = $adminid;
        $where[] = db::fbuild('ac','','<>');
        $list = t('admin_log')->fetchList($where,'DISTINCT `mod`,`ac`',$limit);
        $data = array();
        foreach($list as $v){
            if($row = t('admin_menu')->fetchFields(array('mod'=>$v['mod'],'ac'=>$v['ac'],'islist'=>1,'isshow'=>1))){
                t('admin_menu')->parseData($row);
                $data[] = $row;
            }
        }
        return $data;
    }

    /**
     * 后台功能搜索
     */
    public function search($words)
    {
        if(empty($words)){
            $list = array();
        }else{
            $where = array();
            $where['islist'] = 1;
            $where['isshow'] = 1;
            $where[] = db::fbuild('concat(name,`mod`,`ac`)',$words,'like');
            $list = t('admin_menu')->fetchListLoop($where);
        }
        return getResult(100,array('list'=>$list));
    }

    /**
     * 系统环境信息
     */
    public function sys_environment($dbname)
    {
        $data = array();
        $data['phpversion'] = phpversion();
        $data['post_max_size'] = ini_get('post_max_size');
        $data['allow_url_fopen'] = ini_get("allow_url_fopen") ? '支持' : '不支持';
        $data['db_version'] = db::result_onefield('select version()');
        try {
            db::query('use information_schema');
            $data['db_size'] = db::result_onefield("select round(sum(data_length/1024/1024),2) from tables where table_schema='".$dbname."';");
            db::query('use '.$dbname);
        }catch (Exception $exc){
            $data['db_size'] = 0;
        }
        return getResult(100,$data);
    }
}