<?php
/**
 * 级联数据模型类
 * @copyright			(C) 2016-2099 MOPCMS.COM
 * @license				http://www.mopcms.com/license/
 */
!defined('IN_MOPCMS') && exit('Access failed');
class model_multilevel extends base_model
{
    /**
     * 级联数据列表
     * @param unknown_type $parentid
     * @param unknown_type $multidata
     */
    public function multilevel_list($parentid)
    {
        $_data = array('parentid'=>$parentid);
        $_data['list'] = t('multilevel')->fetchList(array('parentid' => $parentid), '', '', 'displayorder desc,id');
        $_data['position'] = $this->position($parentid);
        return getResult(100,$_data);
    }

    /**
     * 某级联数据导航
     * @param unknown_type $parentid
     */
    private function position($parentid)
    {
        static $pos;
        $pos[] = $row = t('multilevel')->fetch($parentid);
        if(empty($row)){
            return array();
        }
        if(!empty($row['parentid'])){
            $this->position($row['parentid']);
        }
        $arr = array();
        foreach($pos as $k=>$v){
            $arr[] = array('id'=>$v['id'],'name'=>$v['name']);
        }
        $arr = array_reverse($arr);
        unset($pos);
        return $arr;
    }

    /**
     * 级联数据添加
     */
    public function multilevel_add($parentid,$multidata,$identifier='')
    {
        $data = explode("\n", $multidata);
        $data = array_map('trim', $data);
        if(empty($data[0])){
            return getResult(10002);
        }
        if(empty($parentid) && empty($identifier)){
            return getResult(11001);
        }
        if(empty($parentid) && !empty($identifier)){
            $count = t('multilevel')->count(array('identifier'=>$identifier,'parentid'=>0));
            if($count){
                return getResult(11002);
            }
        }
        if(!empty($parentid)){
            $identifier = t('multilevel')->fetchFields($parentid,'identifier');
        }
        $data = array_reverse($data);
        foreach ($data as $v){
            t('multilevel')->insert(array('name'=>$v,'parentid'=>$parentid,'identifier'=>$identifier));
        }
        return getResult(100);
    }

    public function multilevel_edit_submit($id,$name,$displayorder)
    {
        t('multilevel')->update($id,array('name'=>$name,'displayorder'=>$displayorder));
        t('multilevel')->lastUpdateTime();
        return getResult(100,'',10013);
    }

    public function multilevel_del($id)
    {
        if(empty($id)){
            return getResult(10001);
        }
        $ids = t('multilevel')->getSubids($id,true);
        t('multilevel')->delete(array('id'=>$ids));
        return getResult(100,'',10016);
    }
}