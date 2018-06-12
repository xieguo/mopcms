<?php
/**
 * 后台文档管理模型类
 * @copyright			(C) 2016-2099 MOPCMS.COM
 * @license				http://www.mopcms.com/license/
 */
!defined('IN_MOPCMS') && exit('Access failed');
class model_admin_archives extends base_model
{
    /**
     * 文档列表页面
     */
    public function archives_list($post)
    {
        $where = array();
        $where[] = db :: fbuild('arc.status', '-1', '>');
        if(pluginIsAvailable('member')) {
            $mid = !empty($post['username'])?t('member')->fetchFields(array('username' => $post['username']), 'mid'):'';
            if($mid){
                $where['mid'] = $mid;
            }
        }
        if(!empty($post['words'])){
            if(is_numeric($post['words'])){
                $where['id'] = $post['words'];
            }else{
                $where[] = db::fbuild('CONCAT(title,writer)',$post['words'],'like');
            }
        }
        if(!empty($post['flag'])){
            $where[] = db::fbuild('flag',$post['flag'],'find');
        }
        if(!empty($post['adminid'])){
            $where['adminid'] = $post['adminid'];
        }

        $data = array();
        if(empty($post['columnid'])){
            $data['columnid'] = aval($post, 'mycolumnid');
        }else{
            $data['columnid'] = $post['columnid'];
            $post['modelid'] = t('column')->fetchFields($data['columnid'],'modelid');
        }
        $arr = t('models_fields')->fetchSearchCondition($post['modelid']);
        $where = array_merge($where, $arr['where']);
        unset($arr);
        $data['modelid'] = $post['modelid'];
        $data['fields'] = t('models_fields')->getInlistFields($post['modelid']);
        $data['where'] = t('archives')->buildWhere($where);
        if(!empty($post['flag'])){
            $data['flag'] = $post['flag'];
        }
        if(!empty($post['mid'])){
            $data['mid'] = $post['mid'];
        }
        $data['pagesize'] = !empty($post['pagesize'])?$post['pagesize']:50;
        if($post['order']) {
            $result = t('models_fields')->fieldCheck($post['modelid'], $post['order']);
            if($result['code'] != 100) {
                $data['order'] = $post['order'];
            }
        }
        $data['shownum'] = 1;
        $result = t('archives')->archivesListPages($data);
        if($data['modelid']!=-1){
            foreach($result['list'] as $k=>$v){
                $v['_columnid']['name'] = t('column')->fetchFields($v['columnid'],'name');
                $result['list'][$k] = $v;
            }
        }else{
            foreach($result['list'] as $k=>$v){
                $v['entrycount'] = t('specials_entry')->count(array('sid'=>$v['id']));
                $result['list'][$k] = $v;
            }
        }
        $_data = $post;
        $_data['result'] = $result;
        return getResult(100,$_data);
    }

    /**
     * 文档编辑页面
     */
    public function archives_edit($id)
    {
        if(empty($id)) {
            return getResult(10006);
        }
        loadcache('locationpage');
        $result = archiveInfo($id, '*');
        if(empty($result)) {
            return getResult(10303);
        }
        $_data = array('id'=>$id,'modelid'=>$result['modelid']);
        $_data['_data'] = $result;
        $_data['models'] = t('models')->fetchByIds();
        return getResult(100,$_data);
    }

    /**
     * 文档添加页面
     */
    public function archives_add($modelid)
    {
        $result = t('models')->isAvailable($modelid);
        if($result['code']!=100){
            return $result;
        }
        $row = t('archives')->fetchList('','id',1);
        $_data = array('modelid'=>$modelid);
        $_data['titlename'] = $result['data']['titlename'];
        $_data['displayorder'] = aval($row, '0/id');
        $_data['models'] = t('models')->fetchByIds();
        return getResult(100,$_data);
    }

    /**
     * 回收站列表
     * @param unknown_type $post
     */
    public function recyclebin_list($post)
    {
        require_once loadlib('archives');
        $data = array();
        $where = array('status'=>-1);
        if(!empty($post['words'])){
            $where[] = db::fbuild('title', $post['words'],'like');
        }
        $data['where'] = $where;
        $data['pagesize'] = 50;
        $data['shownum'] = 1;
        $data['jump'] = 1;
        $result = t('archives')->info_list($data);
        $result['words'] = aval($post,'words');
        return getResult(100,$result);
    }
}