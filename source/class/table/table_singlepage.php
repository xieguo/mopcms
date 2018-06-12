<?php
/**
 * 单页文档处理类
 * @copyright           (C) 2016-2099 MOPCMS.COM
 * @license             http://www.mopcms.com/license/
 */
!defined('IN_MOPCMS') && exit('Access failed');

class table_singlepage extends base_table
{
    protected function parseData(&$data)
    {
        if(!empty($data['createtime'])){
            $data['_createtime'] = mdate($data['createtime'],'dt');
        }
        if(!empty($data['content'])){
            $data['content'] = mstripslashes($data['content']);
        }
    }
}