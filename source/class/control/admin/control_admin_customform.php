<?php
/**
 * 后台自定义表单管理控制类
 * @copyright			(C) 2016-2099 MOPCMS.COM
 * @license				http://www.mopcms.com/license/
 */
!defined('IN_MOPCMS') && exit('Access failed');
class control_admin_customform extends control_admin
{
    /**
     * 某自定义表单操作权限检测
     * @param $n 某权限
     */
    private function purview_check($formid = '')
    {
        global $_M;
        if(!empty($_M['admin']['_groupid']['_cpurviews']) && !in_array($formid,$_M['admin']['_groupid']['_cpurviews'])){
            setPromptMsg(10323, '-1', 'error');
        }
    }

    /**
     * 自定义列表页面
     */
    public function customform_list()
    {
        $res = t('customform')->info_list();
        return $this->output($res);
    }

    /**
     * 表单修改页面
     */
    public function customform_edit()
    {
        global $_M;
        $formid = (int)_gp('formid');
        $this->purview_check($formid);
        $result = loadm('main_customform')->customform_edit($formid);
        $result['referer'] = $_M['cururl'] . '&ac=list&formid=';
        return $this->output($result);
    }
    /**
     * 表单修改操作
     */
    public function customform_edit_submit()
    {
        return $this->customform_save();
    }
    private function customform_save()
    {
        global $_M;
        $post = array();
        $post['id'] = (int) _gp('formid');
        if(!empty($post['id'])){
            $this->purview_check($post['id']);
        }
        $post['formname'] = filterString(_gp('formname'));
        $post['frontpub'] = (int) _gp('frontpub') ? 1 : 0;
        $post['frontshow'] = (int) _gp('frontshow') ? 1 : 0;
        $post['needcheck'] = (int) _gp('needcheck') ? 1 : 0;
        $post['export'] = (int) _gp('export') ? 1 : 0;
        $post['enable'] = (int) _gp('enable') ? 1 : 0;
        $post['intro'] = filterString(_gp('intro'));
        $post['tablename'] = filterString(_gp('tablename'));
        $result = loadm('main_customform')->customform_save($post);
        $result['referer'] = $_M['cururl'] . '&ac=list&formid=';
        return $this->output($result,false);
    }

    /**
     * 表单添加页面
     */
    public function customform_add()
    {
        return $this->output();
    }
    /**
     * 表单添加操作
     */
    public function customform_add_submit()
    {
        return $this->customform_save();
    }

    /**
     * 表单删除操作
     */
    public function customform_del()
    {
        global $_M;
        $id = (int)_gp('id');
        $this->purview_check($id);
        $result = t('customform')->deleteById($id);
        $result['referer'] = $_M['cururl'] . '&ac=list&formid=';
        return $this->output($result,false);
    }

    /**
     * 表单字段列表页面
     */
    public function customform_fields_list()
    {
        global $_M;
        $formid = (int)_gp('formid');
        $this->purview_check($formid);
        $result = controlCache('main_customform','customform_fields_list',$formid,'customform_fields');
        $result['referer'] = $_M['cururl'] . '&do=list&id=';
        return $this->output($result);
    }
    /**
     * 字段排序操作
     */
    public function customform_fields_list_submit()
    {
        global $_M;
        $formid = (int)_gp('formid');
        $this->purview_check($formid);
        $row = t('customform_fields')->fetchList(array('formid'=>$formid), 'id');
        foreach($row as $v) {
            $displayorder = (int) _gp('displayorder' . $v['id']);
            t('customform_fields')->update($v['id'], array('displayorder' => $displayorder));
        }
        t('customform_fields')->lastUpdateTime();
        $result = getResult(100, '',10202,$_M['cururl'] . '&do=list&id=');
        return $this->output($result,false);
    }

    /**
     * 字段修改页面
     */
    public function customform_fields_edit()
    {
        global $_M;
        $formid = (int)_gp('formid');
        $this->purview_check($formid);
        $id = (int)_gp('id');
        $result = loadm('main_customform')->customform_fields_edit($formid,$id);
        $result['referer'] = $_M['cururl'] . '&do=list&id=';
        return $this->output($result);
    }
    public function customform_fields_edit_submit()
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
        $post['formid'] = (int) _gp('formid');
        $post['fieldname'] = filterString(_gp('fieldname'));
        $result = loadm('main_customform')->customform_fields_save($post);
        $result['referer'] = $_M['cururl'] . '&do=list&id=';
        return $this->output($result,false);
    }

    /**
     * 字段添加页面
     */
    public function customform_fields_add()
    {
        global $_M;
        $formid = (int)_gp('formid');
        $this->purview_check($formid);
        $result = loadm('main_customform')->customform_fields_add($formid);
        $result['referer'] = $_M['cururl'] . '&do=list&id=';
        return $this->output($result);
    }
    public function customform_fields_add_submit()
    {
        return $this->customform_fields_edit_submit();
    }

    /**
     * 删除某字段
     */
    public function customform_fields_del()
    {
        global $_M;
        $id = (int)_gp('id');
        $result = t('customform_fields')->deleteById($id);
        $result['referer'] = $_M['cururl'] . '&do=list&id=';
        return $this->output($result,false);
    }

    /**
     * 表单字段列表页面
     */
    public function customform_data_list()
    {
        global $_M;
        $post = array();
        $post['formid'] = (int)_gp('formid');
        $this->purview_check($post['formid']);
        $post['words'] = filterString(_gp('words'));
        $this->cururl($post);
        $result = loadm('main_customform')->data_list($post);
        //计算底部操作需要占用的td数
        $result['colspan'] = count($result['fieldlist']);
        $result['colspan'] += 3;
        $result['referer'] = $_M['cururl'] . '&do=list&id=';
        return $this->output($result);
    }

    /**
     * 某条自定义表单数据详细
     */
    public function customform_data_view()
    {
        $id = (int)_gp('id');
        $formid = (int)_gp('formid');
        $this->purview_check($formid);
        $result = loadm('main_customform')->data_view($formid,$id);
        if (empty($result)){
            return getResult(10004);
        }
        return $this->output($result,false);
    }

    /**
     * 审核
     */
    public function customform_data_check()
    {
        $id = (int)_gp('id');
        $formid = (int)_gp('formid');
        $this->purview_check($formid);
        $ischeck = _gp('ischeck')==1?1:2;
        $result = loadm('main_customform')->data_check($formid,$id,$ischeck);
        return $this->output($result,false);
    }

    /**
     * 删除某条数据
     */
    public function customform_data_del()
    {
        $id = (int)_gp('id');
        $formid = (int)_gp('formid');
        $this->purview_check($formid);
        $result = loadm('main_customform')->data_del($formid,$id);
        return $this->output($result,false);
    }

    /**
     * 专题报名数据导出
     */
    public function customform_data_export()
    {
        $formid = (int)_gp('formid');
        $this->purview_check($formid);
        echo loadm('main_customform')->data_export($formid);
        exit;
    }

    public function customform_data_save()
    {
        $data = array();
        $data['id'] = (int)_gp('id');
        $data['formid'] = (int)_gp('formid');
        $this->purview_check($data['formid']);
        return $this->output($data);
    }
    
    public function customform_data_save_submit()
    {
        global $_M;
        $id = (int)_gp('id');
        $formid = (int)_gp('formid');
        $res = loadm('main_customform')->save($formid,$id);
        $res['referer'] = $_M['cururl'].'&do=list&id=';
        return $res;
    }
}