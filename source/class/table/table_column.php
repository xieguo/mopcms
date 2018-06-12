<?php
/**
 * 文档栏目分类
 * @copyright			(C) 2016-2099 MOPCMS.COM
 * @license				http://www.mopcms.com/license/
 */
!defined('IN_MOPCMS') && exit('Access failed');

class table_column extends base_table
{
    private $subids = array();
    private $options = '';
    private $_col = array();
    private $_cols = 0;
    public $reallink = '';
    private $pos = array();
     
    /**
     * 栏目分类多维数组
     * @param int $parentid 上级ID
     * @param boolean $loadsub 是否加载子栏目
     * @param string $fields 读取字段
     */
    public function columnList($parentid = 0, $loadsub = true, $fields = '*')
    {
        $row = (array) $this->fetchList(array('parentid' => $parentid), $fields, '', 'displayorder desc,id');
        if($loadsub === false) {
            return $row;
        }
        $arr = array();
        foreach($row as $k => $v) {
            $v['subcolumn'] = (array) $this->columnList($v['id'],$loadsub,$fields);
            $v['link'] = $this->columnLink($v);
            $arr[$v['id']] = $v;
        }
        return $arr;
    }

    /**
     * 下拉菜单选项
     * @param int $parentid
     * @param string $space 空格符
     */
    public function getOptions($parentid = 0, $space = '', $self = false)
    {
        if($self===true){
            $space .= '&nbsp;&nbsp;&nbsp;';
            $v = $this->fetchFields($parentid,'id,name');
            $this->options .= '<option value="' . aval($v,'id') . '">' . aval($v,'name','请选择') . '</option>';
        }
        $list = $this->columnList($parentid, false);
        foreach($list as $v) {
            $this->options .= '<option value="' . aval($v,'id') . '">' . $space . aval($v,'name') . '</option>';
            $this->getOptions(aval($v,'id'), $space . '&nbsp;&nbsp;&nbsp;');
        }
        return $this->options;
    }

    /**
     * 删除栏目
     * @param int $id 栏目ID
     * @param int $delfile 是否删除生成的相应文件夹及文件
     */
    function deleteById($id, $delfile = 0)
    {
        global $_M;
        require_once loadlib('file');

        $row = $this->fetch($id);
        if(empty($row)) {
            return getResult(10004);
        }
        $ids = $this->getSubids($id, true);
        $tablename = t('models')->fetchFields($row['modelid'], 'tablename');
        //删除数据库里的相关记录
        foreach($ids as $subid) {
            $savedir = ltrim($this->fetchFields($subid, 'savedir'),'/');
            if($delfile && !empty($savedir)) {
                delAllFiles(MOPROOT . '/'.$savedir);
            }
        }

        //删除数据库信息
        $this->delete(array('id'=>$ids));
        t('archives')->delete(array('columnid' => $ids));
        t('archives_comment')->delete(array('columnid' => $ids));
        if(!empty($tablename)) {
            t($tablename)->delete(array('columnid' => $ids));
        }
        $row['savedir'] = ltrim($row['savedir'],'/');
        if($delfile && !empty($row['savedir'])) {
            delAllFiles(MOPROOT . '/'.$row['savedir']);
        }
        return getResult(100,'',10206);
    }

    /**
     * 返回某栏目下级目录的栏目ID列表
     * @param int $id
     * @param boolean $self 是否包含自己
     */
    public function getSubids($parentid, $self = false)
    {
        foreach ((array)$parentid as $v){
            $this->_getSubids($v, $self);
        }
        $data = array_unique($this->subids);
        $this->subids = array();
        return $data;
    }
    private function _getSubids($parentid, $self = false)
    {
        if($self === true) {
            $this->subids[] = (int) $parentid;
        }
        $row = $this->fetchList(array('parentid' => $parentid), 'id');
        if($row) {
            foreach($row as $k => $v) {
                if($v['id']) {
                    $this->subids[] = (int) $v['id'];
                    $this->_getSubids($v['id']);
                }
            }
        }
        return $this->subids;
    }

    /**
     * 移动栏目
     * @param int $id
     * @param int $targetid 目标栏目
     */
    public function moveColumn($id, $targetid)
    {
        $row = $this->fetch($id);
        if($id == $targetid || $row['parentid'] == $targetid) {
            return getResult(10210);
        }
        $subids = $this->getSubids($id);
        if(in_array($targetid, $subids)) {
            return getResult(10211);
        }
        $row = $this->fetch($targetid);
        if(empty($row)) {
            return getResult(10212);
        }
        $row['topid'] = empty($row['topid']) ? $targetid : $row['topid'];
        $this->update($id, array('parentid' => $targetid, 'topid' => $row['topid'], 'sitepath' => $row['sitepath'], 'binddomain' => $row['binddomain']));
        $this->update($subids, array('topid' => $row['topid'], 'sitepath' => $row['sitepath'], 'binddomain' => $row['binddomain']));
        $this->lastUpdateTime();
        return getResult(100,'',10213);
    }

    /**
     * 合并栏目
     * @param int $id
     * @param int $targetid 目标栏目
     */
    public function mergeColumn($id, $targetid)
    {
        if(empty($id)||empty($targetid)){
            return getResult(10002);
        }
        if($id == $targetid) {
            return getResult(10214);
        }
        $row = $this->fetch($id);
        $subids = $this->getSubids($id);
        if($subids) {
            return getResult(10215, '', array('name' => $row['name']));
        }
        $target = $this->fetch($targetid);
        if($row['modelid']!=$target['modelid']){
            return getResult(10217);
        }
        $tablename = t('models')->fetchFields($row['modelid'], 'tablename');
        if($tablename) {
            t('archives_comment')->update(array('columnid' => $id), array('columnid' => $targetid));
            t('archives')->update(array('columnid' => $id), array('columnid' => $targetid));
            t($tablename)->update(array('columnid' => $id), array('columnid' => $targetid));
            $this->delete($id);
        }
        return getResult(100,'',10216);
    }

    /**
     * 后台左则菜单中已经使用的模型
     */
    public function groupByModelid()
    {
        return db :: fetch_all('SELECT c.modelid,m.modelname FROM ' . db :: table($this->tname) . ' c left join ' . db :: table('models') . ' m on m.id=c.modelid group by c.modelid order by c.modelid asc');
    }

    /**
     * 判断栏目属于第几级分类
     * @global type $_cat
     * @param type $id 栏目ID
     * @return type
     */
    public function columnLevel($id)
    {
        $this->_col[] = $id;
        $parentid = $this->fetchFields($id, 'parentid');
        if($parentid) {
            //$this->_col[] = $parentid;
            $this->columnLevel($parentid);
        }
        return $this->_col;
    }

    public function columnSelect($cats)
    {
        $str = '';
        if(isset($cats[$this->_cols])) {
            $list = $this->get_cache('columnList', $cats[$this->_cols], false);
            if(!empty($list)) {
                $_cati1 = $this->_cols;
                if($this->_cols > 0) {
                    $_cati1 = $this->_cols + 1;
                    $str .= '<span id="subcat' . $this->_cols . '">';
                }
                $str .= "<select onChange=\"subColumns(this.options[this.options.selectedIndex].value," . $_cati1 . ")\" class=\"selecttwo columns\"><option value=''>请选择</option>";
                foreach($list as $v) {
                    $index = $this->_cols + 1;
                    $select = !empty($cats[$index]) && $cats[$index] == $v['id'] ? 'selected' : '';
                    $str .= "<option value='" . $v['id'] . "' " . $select . ">" . $v['name'] . "</option>";
                }
                $str .= '</select>';
                if($this->_cols == 0)
                $str .= '<span id="subcat' . $this->_cols . '">';
                $this->_cols++;
                $str .= $this->columnSelect($cats) . '</span>';
            }
        }
        return $str;
    }

    /**
     * 获得某栏目的导航链接
     * @param $id 栏目ID
     */
    public function position($id){
        $row = $this->fetch($id);
        if(empty($row)){
            return array();
        }
        $this->pos[] = array('columnname'=>$row['name'],'link'=>$this->columnLink($row));
        if(!empty($row['parentid'])){
            $this->_position($row['parentid']);
        }
        $pos = array_reverse($this->pos);
        $this->pos = array();
        return $pos;
    }
    private function _position($id){
        $row = $this->fetch($id);
        if($row){
            $this->pos[] = array('columnname'=>$row['name'],'link'=>$this->columnLink($row));
            if(!empty($row['parentid'])){
                $this->_position($row['parentid']);
            }
        }
    }

    /**
     *  获得指定栏目的URL
     * @param $row 某栏目数据
     * @param $page 翻页数
     * @param $type 链接方式
     */
    public function columnLink($row,$page=1,$type='index')
    {
        global $_M;
        if(empty($row)) {
            return '';
        }
        if(is_numeric($row)) {
            $row = $this->fetch($row);
        }
        if(!empty($row['redirecturl'])) {
            return $row['redirecturl'];
        }
        if($row['accesstype'] == 2||$_M['iswap']) {
            return pseudoUrl($_M['sys']['basehost'] . "/main.php?mod=archives&ac=list&id=" . $row['id']);
        }
        if($type=='index') {
            $url = preg_replace("#\/{1,}#", '/', $row['savedir'] . '/');
        } else {
            $url = str_replace(array('[savedir]','[columnid]','[page]'), array($row['savedir'],$row['id'],$page),$row['rulelist']);
        }
        if(is_dir(MOPROOT.$url)){
            $url .= 'index.html';
        }
        $this->reallink = MOPROOT.$url;
        if(!empty($row['binddomain'])) {
            return $row['binddomain'] . preg_replace("#^" . $row['sitepath'] . '#', '', $url);
        }
        return $_M['sys']['basehost'] . $url;
    }
}