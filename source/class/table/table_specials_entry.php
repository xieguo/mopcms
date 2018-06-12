<?php
/**
 * 专题报名类
 * @copyright			(C) 2016-2099 MOPCMS.COM
 * @license				http://www.mopcms.com/license/
 */
!defined('IN_MOPCMS') && exit('Access failed');

class table_specials_entry extends base_table
{
    protected function parseData(&$data)
    {
        if(!empty($data['createtime'])) {
            $data['_createtime'] = mdate($data['createtime'],'dt');
        }
        if(!empty($data['ischeck'])) {
            $data['_ischeck'] = $data['ischeck']==1?'未审核':'已审核';
        }
    }
}