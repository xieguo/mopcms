<?php
/**
 * 后台栏目管理控制类
 * @copyright			(C) 2016-2099 MOPCMS.COM
 * @license				http://www.mopcms.com/license/
 */
!defined('IN_MOPCMS') && exit('Access failed');
class control_admin_column extends control_admin
{
    /**
     * 栏目列表页面
     */
    public function column_list()
    {
        global $_M;
        $_data = array();
        $_data['columns'] = t('column')->get_cache('columnList');
        return $this->output($_data);
    }
    public function column_list_submit()
    {
        $row = t('column')->fetchList('', 'id');
        foreach($row as $v) {
            if($displayorder = (int) _gp('displayorder' . $v['id'])) {
                t('column')->update($v['id'], array('displayorder' => $displayorder));
            }
        }
        t('column')->lastUpdateTime();
        $result = getResult(100, '', 10202);
        return $this->output($result,false);
    }

    /**
     * 子栏目
     */
    public function column_list_subcolumn()
    {
        $id = (int) _gp('id');
        return controlCache('admin_column','column_subcolumn',$id,'column')	;
    }

    /**
     * 栏目修改页面
     */
    public function column_edit()
    {
        global $_M;
        $columnid = (int) _gp('id');
        $result = $this->column_allow_check($columnid);
        if($result['code']!=100){
            return $result;
        }
        $result = loadm('admin_column')->column_edit($columnid);
        return $this->output($result);
    }
    /**
     * 栏目修改操作
     */
    public function column_edit_submit()
    {
        $post = array();
        $post['id'] = (int) _gp('id');
        $post['allowpub'] = _gp('allowpub') ? 1 : 0;
        $post['ishidden'] = _gp('ishidden') ? 1 : 0;
        $post['modelid'] = (int) _gp('modelid');
        $post['name'] = filterString(_gp('name'));
        $post['byname'] = filterString(_gp('byname'));
        $post['displayorder'] = (int) _gp('displayorder');
        $post['savedir'] = '/' . preg_replace('/^\/+/', '', filterString(_gp('savedir')));
        $post['accesstype'] = (int) _gp('accesstype');
        $post['filename'] = filterString(_gp('filename'));
        $post['binddomain'] = filterString(_gp('binddomain'));
        $post['tempindex'] = filterString(_gp('tempindex'));
        $post['templist'] = filterString(_gp('templist'));
        $post['tempview'] = filterString(_gp('tempview'));
        $post['ruleview'] = filterString(_gp('ruleview'));
        $post['rulelist'] = filterString(_gp('rulelist'));
        $post['keywords'] = filterString(_gp('keywords'));
        $post['description'] = filterString(_gp('description'));
        $post['redirecturl'] = filterString(_gp('redirecturl'));
        $post['inherit'] = _gp('inherit') ? 1 : 0;
        $post['parentid'] = (int) _gp('parentid');
        $result = loadm('admin_column')->column_save($post);
        return $this->output($result,false);
    }

    /**
     * 栏目添加页面
     */
    public function column_add()
    {
        global $_M;
        $parentid = (int) _gp('parentid');
        $result = loadm('admin_column')->column_add($parentid);
        return $this->output($result);
    }
    /**
     * 栏目添加操作
     */
    public function column_add_submit()
    {
        return $this->column_edit_submit();
    }

    /**
     * 栏目删除页面
     */
    public function column_del()
    {
        global $_M;
        $id = (int) _gp('id');
        $result = loadm('admin_column')->column_del($id);
        return $this->output($result);
    }
    /**
     * 栏目删除操作
     */
    public function column_del_submit()
    {
        $id = (int) _gp('id');
        $delfile = (int) _gp('delfile');
        $result = t('column')->deleteById($id, $delfile);
        if($result['code']==100){
            $result['referer'] = '?mod=column&ac=list&menuid=35';
        }
        return $this->output($result,false);
    }

    /**
     * 栏目合并页面
     */
    public function column_merge()
    {
        global $_M;
        $id = (int) _gp('id');
        $result = loadm('admin_column')->column_merge($id);
        return $this->output($result);
    }
    /**
     * 栏目合并操作
     */
    public function column_merge_submit()
    {
        $id = (int) _gp('id');
        $targetid = (int)_gp('targetid');
        $result = t('column')->mergeColumn($id,$targetid);
        return $this->output($result,false);
    }

    /**
     * 栏目移动页面
     */
    public function column_move()
    {
        global $_M;
        $id = (int) _gp('id');
        $result = loadm('admin_column')->column_merge($id);
        return $this->output($result);
    }
    /**
     * 栏目移动操作
     */
    public function column_move_submit()
    {
        $id = (int) _gp('id');
        $targetid = (int)_gp('targetid');
        $result = t('column')->moveColumn($id,$targetid);
        return $this->output($result,false);
    }

    /**
     * 预览
     */
    public function column_view()
    {
        $id = (int)_gp('id');
        $link = t('column')->columnLink($id);
        header('location:'.$link);
        exit;
    }
}