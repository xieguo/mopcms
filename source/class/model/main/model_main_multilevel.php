<?php
/**
 * 级联数据模型类
 * @copyright			(C) 2016-2099 MOPCMS.COM
 * @license				http://www.mopcms.com/license/
 */
!defined('IN_MOPCMS') && exit('Access failed');
class model_main_multilevel extends base_model
{
    /**
     * 获取级联数据
     */
    public function multilevelList($row)
    {
        if(empty($row['id']) && empty($row['parentid']) && empty($row['identifier'])) {
            return getResult(10001);
        }
        $where = array();
        if(!empty($row['id'])) {
            $where['id'] = $row['id'];
        }
        if(!empty($row['parentid'])) {
            $where['parentid'] = $row['parentid'];
        }
        if(!empty($row['identifier'])) {
            $where['identifier'] = $row['identifier'];
        }
        $limit = !empty($row['limit'])?$row['limit']:'';
        $list = t('multilevel')->fetchList($where,'*',$limit,'displayorder');
        return getResult(100,$list);
    }
}