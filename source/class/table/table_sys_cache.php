<?php
/**
 * 缓存数据处理类
 * @copyright           (C) 2016-2099 MOPCMS.COM
 * @license             http://www.mopcms.com/license/
 */
!defined('IN_MOPCMS') && exit('Access failed');

class table_sys_cache extends base_table
{
    protected function parseData(&$data)
    {
        if(!empty($data['data'])){
            $data['data'] = unserialize($data['data']);
        }
    }

    /**
     * 获取某缓存数据
     * @param $name 缓存名称
     */
    public function fetchByName($name)
    {
        return $this->fetchFields(array('name'=>$name),'data');
    }

    /**
     * 保存缓存数据
     * @param $name 缓存名称
     * @param $data 缓存数据
     */
    public function save($name, $data)
    {
        if(empty($name)){
            return false;
        }
        $data = !empty($data)?serialize($data):'';
        $id = $this->fetchFields(array('name'=>$name),'id');
        if($id){
            $this->update($id,array('data'=>$data));
            $this->lastUpdateTime();
        }else{
            $this->insert(array('name' => $name,'data' =>$data));
        }
        return true;
    }
}