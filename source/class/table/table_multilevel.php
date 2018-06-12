<?php
/**
 * 级联数据类
 * @copyright			(C) 2016-2099 MOPCMS.COM
 * @license				http://www.mopcms.com/license/
 */
!defined('IN_MOPCMS') && exit('Access failed');

class table_multilevel extends base_table
{
    private $subids = array();

    /**
     * 返回某分类下子分类ID列表
     * @param int $id
     * @param boolean $self 是否包含自己
     * @param int $type 返回数据形式,1一维数组，2多维
     */
    public function getSubids($parentid, $self = false,$type=1)
    {
        $data = $this->_getSubids($parentid, $self,$type);
        $this->subids = array();
        return $data;
    }
    private function _getSubids($parentid, $self = false,$type=1)
    {
        if($self === true) {
            $this->subids[] = (int) $parentid;
        }
        $row = $this->fetchList(array('parentid' => $parentid), 'id');
        if($row) {
            foreach($row as $k => $v) {
                if($v['id']) {
                    if($type==1){
                        $this->subids[] = (int) $v['id'];
                    }else{
                        $this->subids[$parentid][] = (int) $v['id'];
                    }
                    $this->_getSubids($v['id'],$self,$type);
                }
            }
        }
        return $this->subids;
    }
}