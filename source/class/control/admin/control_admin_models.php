<?php
/**
 * 后台模型管理控制类
 * @copyright			(C) 2016-2099 MOPCMS.COM
 * @license				http://www.mopcms.com/license/
 */
!defined('IN_MOPCMS') && exit('Access failed');
class control_admin_models extends control_admin
{
    /**
     * 模型列表页面
     */
    public function models_list()
    {
        global $_M;
        $_data = array();
        $_data['models'] = t('models')->get_cache('fetchListLoop');
        $_data['plugin_member'] = pluginIsAvailable('member');
        return $this->output($_data);
    }

    /**
     * 模型修改页面
     */
    public function models_edit()
    {
        global $_M;
        $modelid = (int)_gp('modelid');
        $result = loadm('admin_models')->models_edit($modelid);
        $result['referer'] = $_M['cururl'] . '&ac=list&modelid=';
        return $this->output($result);
    }
    /**
     * 模型修改操作
     */
    public function models_edit_submit()
    {
        global $_M;
        $post = array();
        $post['id'] = (int) _gp('modelid');
        $post['modelname'] = filterString(_gp('modelname'));
        $post['byname'] = filterString(_gp('byname'));
        $post['enable'] = (int) _gp('enable') ? 1 : 0;
        if(pluginIsAvailable('member')) {
            $post['allowpub'] = (int) _gp('allowpub') ? 1 : 0;
            $post['docstatus'] = (int) _gp('docstatus') ?(_gp('docstatus') == 1 ? 1 : 2) : 0;
            $post['need_thumb'] = (int) _gp('need_thumb') ? 1 : 0;
            $post['need_diysort'] = (int) _gp('need_diysort') ? 1 : 0;
        }
        $post['titlename'] = filterString(_gp('titlename'));
        $post['unique'] = (int) _gp('unique') ? 1 : 0;
        $post['columnid'] = (int) _gp('columnid');
        $post['intro'] = filterString(_gp('intro'));
        $post['tablename'] = filterString(_gp('tablename'));
        $result = loadm('admin_models')->models_save($post);
        $result['referer'] = $_M['cururl'] . '&ac=list&modelid=';
        return $this->output($result,false);
    }

    /**
     * 模型添加页面
     */
    public function models_add()
    {
        return $this->output();
    }
    /**
     * 模型添加操作
     */
    public function models_add_submit()
    {
        $result = $this->models_edit_submit();
        return $this->output($result,false);
    }

    /**
     * 模型删除操作
     */
    public function models_del()
    {
        global $_M;
        $id = (int)_gp('id');
        $result = t('models')->deleteById($id);
        $result['referer'] = $_M['cururl'] . '&ac=list&modelid=';
        return $this->output($result,false);
    }

    /**
     * 模型字段列表页面
     */
    public function models_fields_list()
    {
        global $_M;
        $modelid = (int)_gp('modelid');
        $result = controlCache('admin_models','models_fields_list',$modelid,'models_fields');
        $result['referer'] = $_M['cururl'] . '&do=list&id=';
        return $this->output($result);
    }
    /**
     * 字段排序操作
     */
    public function models_fields_list_submit()
    {
        global $_M;
        $modelid = (int)_gp('modelid');
        $row = t('models_fields')->fetchList(array('modelid'=>$modelid), 'id');
        foreach($row as $v) {
            $displayorder = (int) _gp('displayorder' . $v['id']);
            t('models_fields')->update($v['id'], array('displayorder' => $displayorder));
        }
        t('models_fields')->lastUpdateTime();
        $result = getResult(100, '',10202,$_M['cururl'] . '&do=list&id=');
        return $this->output($result,false);
    }

    /**
     * 字段修改页面
     */
    public function models_fields_edit()
    {
        global $_M;
        $modelid = (int)_gp('modelid');
        $id = (int)_gp('id');
        $result = loadm('admin_models')->models_fields_edit($modelid,$id);
        $result['referer'] = $_M['cururl'] . '&do=list&id=';
        return $this->output($result);
    }
    public function models_fields_edit_submit()
    {
        global $_M;
        $post = array();
        $post['id'] = (int) _gp('id');
        $post['title'] = filterString(_gp('title'));
        $post['datatype'] = array_key_exists(_gp('datatype'), $_M['sys']['datatype']) ? _gp('datatype') : '';
        $post['available'] = (int) _gp('available');
        $post['unique'] = (int) _gp('unique');
        $post['infront'] = (int) _gp('infront');
        $post['required'] = (int) _gp('required');
        $post['search'] = (int) _gp('search');
        $post['joinsorting'] = (int) _gp('joinsorting');
        $post['inlist'] = (int) _gp('inlist');
        $post['displayorder'] = (int) _gp('displayorder');
        $post['checkrule'] = _gp('checkrule');
        $post['default'] = filterString(_gp('default'));
        $post['intro'] = filterString(_gp('intro'));
        $post['units'] = filterString(_gp('units'));
        $post['tip'] = filterString(_gp('tip'));
        $post['nullmsg'] = filterString(_gp('nullmsg'));
        $post['errormsg'] = filterString(_gp('errormsg'));
        $post['rules'] = serializeData(_gp('rules'));
        $post['maxlength'] = (int) _gp('maxlength');
        $post['css'] = preg_replace('/[^\w ]/i', '', _gp('css'));
        $post['modelid'] = (int) _gp('modelid');
        $post['fieldname'] = filterString(_gp('fieldname'));
        $result = loadm('admin_models')->models_fields_save($post);
        $result['referer'] = $_M['cururl'] . '&do=list&id=';
        return $this->output($result,false);
    }

    /**
     * 字段添加页面
     */
    public function models_fields_add()
    {
        global $_M;
        $modelid = (int)_gp('modelid');
        $result = loadm('admin_models')->models_fields_add($modelid);
        $result['referer'] = $_M['cururl'] . '&do=list&id=';
        return $this->output($result);
    }
    public function models_fields_add_submit()
    {
        return $this->models_fields_edit_submit();
    }

    /**
     * 删除某字段
     */
    public function models_fields_del()
    {
        global $_M;
        $id = (int)_gp('id');
        $result = t('models_fields')->deleteById($id);
        $result['referer'] = $_M['cururl'] . '&do=list&id=';
        return $this->output($result,false);
    }
}