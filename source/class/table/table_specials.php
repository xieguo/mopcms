<?php
/**
 * 专题处理类
 * @copyright           (C) 2016-2099 MOPCMS.COM
 * @license             http://www.mopcms.com/license/
 */
!defined('IN_MOPCMS') && exit('Access failed');

class table_specials extends base_table
{
    protected function parseData(&$data)
    {
        if(!empty($data['node'])){
            $data['node'] = unserialize($data['node']);
        }
    }

    /**
     * 获取表单提交的节点数据
     */
    public function nodeData()
    {
        global $_M;
        $row = array();
        for($i=1;$i<=$_M['sys']['maxnode'];$i++){
            $ids = preg_replace('/[^0-9,]/i','',_gp('ids'.$i));
            $tmp = _gp('tmp'.$i);
            if(empty($ids) && empty($tmp)){
                continue;
            }
            $row[$i]['name'] = filterString(_gp('name'.$i));
            $row[$i]['model'] = (int)_gp('model'.$i);
            $row[$i]['ids'] = $ids;
            $row[$i]['tmp'] = $tmp;
        }
        if($row){
            return serialize($row);
        }
        return '';
    }
}