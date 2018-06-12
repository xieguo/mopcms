<?php
/**
 * 后台栏目管理模型类
 * @copyright			(C) 2016-2099 MOPCMS.COM
 * @license				http://www.mopcms.com/license/
 */
!defined('IN_MOPCMS') && exit('Access failed');
class model_admin_column extends base_model
{
    /**
     * 子栏目
     */
    public function column_subcolumn($id)
    {
        if(empty($id)) {
            return getResult(10006);
        }
        $columns = t('column')->columnList($id);
        $data = array();
        foreach($columns as $k => $v) {
            $data[$k]['id'] = $v['id'];
            $data[$k]['modelid'] = $v['modelid'];
            $data[$k]['name'] = $v['name'];
            $data[$k]['ishidden'] = $v['ishidden'];
            $data[$k]['redirecturl'] = $v['redirecturl'];
            $data[$k]['link'] = $v['link'];
            $data[$k]['displayorder'] = $v['displayorder'];
            $data[$k]['arcnum'] = t('archives')->countByColumnid($v['id']);
        }
        return getResult(100,$data);
    }

    /**
     * 多级栏目ajax加载显示
     * @param unknown_type $id
     * @param unknown_type $columnid
     * @param unknown_type $modelid
     * @param boolean $topmenu 是否加载顶级分类
     */
    public function column_ajaxcolumn($id,$columnid,$modelid,$topmenu=true)
    {
        $str = '';
        if($columnid){
            $arr = t('column')->columnLevel($columnid);
            if($topmenu===true){
                $arr[] = '';
            }
            $arr = array_reverse($arr);
            $str = t('column')->columnSelect($arr);
        }else{
            $levels = t('column')->columnLevel($id);
            $level = empty($id)?0:count($levels)+1;
            $list = t('column')->get_cache('columnList',$id,false);
            $datatype = $modelid==-1?'':'datatype=\"*\"';
            if(!empty($list)){
                $str = '<select '.$datatype." onChange=\"subColumns(this.options[this.options.selectedIndex].value,$level)\" class=\"selecttwo columns width100\"><option value=''>请选择</option>";
                foreach($list as $v){
                    $style = $modelid!=-1 && $modelid != $v['modelid'] && $v['parentid']!=0?' disabled="disabled" style="background-color:#f5f5f5"':'';
                    $str .= "<option value='{$v['id']}' $style>{$v['name']}</option>";
                }
                $str .= '</select> <span id="subcat'.$level.'"></span>';
            }
        }
        return getResult(100,$str);
    }

    /**
     * 栏目修改页面
     */
    public function column_edit($id)
    {
        if(empty($id)){
            return getResult(10006);
        }
        $_data = array();
        $_data['id'] = $id;
        $_data['row'] = t('column')->fetch($id);
        $_data['parent_binddomain'] = t('column')->fetchFields($_data['row']['parentid'],'binddomain');
        $_data['models'] = t('models')->fetchByIds();
        return getResult(100,$_data);
    }

    /**
     * 栏目添加页面
     */
    public function column_add($parentid)
    {
        $_data = array();
        $_data['row'] = !empty($parentid)?t('column')->fetch($parentid):array();
        $_data['models'] = t('models')->fetchByIds();
        return getResult(100,$_data);
    }
    /**
     * 栏目修改操作
     */
    public function column_save($post)
    {
        $data = array();
        $data['allowpub'] = $post['allowpub'];
        $data['ishidden'] = $post['ishidden'];
        $data['modelid'] = $post['modelid'];
        $data['name'] = $post['name'];
        if(empty($data['name'])) {
            return getResult(10207);
        }
        if(empty($data['modelid'])) {
            return getResult(10208);
        }
        $data['byname'] = $post['byname'];
        $data['displayorder'] = $post['displayorder'];
        $data['savedir'] = $post['savedir'];
        $data['accesstype'] = $post['accesstype'];
        $data['filename'] =$post['filename'];
        $data['binddomain'] = $post['binddomain'];
        $data['tempindex'] = $post['tempindex'];
        $data['templist'] = $post['templist'];
        $data['tempview'] = $post['tempview'];
        $data['ruleview'] = $post['ruleview'];
        if(empty($data['ruleview'])){
            $data['ruleview'] = '[savedir]/[Y]/[m][d]/[aid].html';
        }
        $data['rulelist'] = $post['rulelist'];
        if(empty($data['rulelist'])){
            $data['rulelist'] = '[savedir]/list_[columnid]_[page].html';
        }
        $data['keywords'] = $post['keywords'];
        $data['description'] = $post['description'];
        $data['redirecturl'] = $post['redirecturl'];

        if(!empty($post['id'])) {
            //如果生成HTML规则有更改文档htmllink删除重新获取
            $ruleview = t('column')->fetchFields($post['id'],'ruleview');
            if($ruleview!=$data['ruleview']){
                t('archives')->update(array('columnid'=>$post['id']),array('htmllink'=>''));
            }
            t('column')->update($post['id'], $data);
            $ids = t('column')->getSubids($post['id']);
            //更改子栏目属性
            if($ids && $data['binddomain']) {
                t('column')->update(array('id'=>$ids), array('binddomain' => $data['binddomain']));
            }
            if(!empty($post['inherit'])) {
                $sitepath = t('column')->fetchFields($post['id'],'sitepath');
                t('column')->update(array('id'=>$ids), array('modelid' => $data['modelid'], 'sitepath' => $sitepath,'tempindex' => $data['tempindex'],'templist' => $data['templist'], 'tempview' => $data['tempview'], 'ruleview' => $data['ruleview'], 'rulelist' => $data['rulelist']));
            }
            t('column')->lastUpdateTime();
            return getResult(100, '',10200,'?mod=column&ac=list&menuid=30');
        } else {
            $data['parentid'] = $post['parentid'];
            if($data['parentid']) {
                $row = t('column')->fetch($data['parentid']);
                $data['savedir'] = $row['savedir'] . '/' . $data['savedir'];
                $data['savedir'] = preg_replace("/\/+/", '/', $data['savedir']);

                $data['binddomain'] = $row['binddomain'];
                $data['sitepath'] = $row['sitepath'];
                $data['topid'] = empty($row['parentid'])?$row['id']:$row['topid'];
            } else {
                $data['sitepath'] = $data['savedir'];
                //检测二级域名
                if($data['binddomain']) {
                    $data['binddomain'] = preg_replace("/\/$/", "", $data['binddomain']);
                    if(!preg_match("/http:\/\//i", $data['binddomain'])) {
                        return getResult(10205);
                    }
                }
            }
            //创建目录
            $savedir = MOPROOT.'/'.$data['savedir'];
            if(!createdir($savedir)) {
                return getResult(10204);
            }
            $id = t('column')->insert($data, true);
            return getResult(100, $id,10203,'?mod=column&ac=list&menuid=30');
        }

    }

    /**
     * 栏目删除页面
     */
    public function column_del($id)
    {
        if(empty($id)){
            return getResult(10006);
        }
        $_data = array();
        $_data['id'] = $id;
        $_data['row'] = t('column')->fetch($id);
        if(empty($_data['row'])){
            return getResult(10004);
        }
        return getResult(100,$_data);
    }

    /**
     * 栏目合并页面
     */
    public function column_merge($id)
    {
        if(empty($id)){
            return getResult(10006);
        }
        $_data = array();
        $_data['id'] = $id;
        $_data['name'] = t('column')->fetchFields($id,'name');
        $_data['options'] = t('column')->getOptions();
        return getResult(100,$_data);
    }
}