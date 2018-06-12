<?php
/**
 * 插件类
 */
!defined('IN_MOPCMS') && exit('Access failed');

class table_plugins extends base_table
{
    private $types = array(6=>'插件',5=>'模板',4=>'扩展',3=>'企业站',2=>'行业站',7=>'其它');
    protected function parseData(&$data)
    {
        if(!empty($data['types'])){
            $data['_types'] = !empty($this->types[$data['types']])?$this->types[$data['types']]:$this->types[7];
        }
        if(!empty($data['createtime'])){
            $data['_createtime'] = mdate($data['createtime'],'dt');
        }
        if(!empty($data['links'])){
            $data['_links'] = unserialize($data['links']);
        }
    }
}