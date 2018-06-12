<?php
/**
 * 管理员分组处理类
 * @copyright			(C) 2016-2099 MOPCMS.COM
 * @license				http://www.mopcms.com/license/
 */
!defined('IN_MOPCMS') && exit('Access failed');

class table_admin_group extends base_table {
    /**
     * 对数据重新处理
     * @param array $data
     */
    protected function parseData(& $data)
    {
        if(!empty($data['purviews'])) {
            $data['_purviews'] = explode(',', $data['purviews']);
        }
        if(!empty($data['mpurviews'])) {
            $data['_mpurviews'] = explode(',', $data['mpurviews']);
        }
        if(!empty($data['cpurviews'])) {
            $data['_cpurviews'] = explode(',', $data['cpurviews']);
        }
    }
}