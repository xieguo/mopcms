<?php
/**
 * 自定义表单字段管理处理类
 * @copyright           (C) 2016-2099 MOPCMS.COM
 * @license             http://www.mopcms.com/license/
 */
!defined('IN_MOPCMS') && exit('Access failed');

class table_customform_fields extends base_table
{
    protected function parseData(& $data)
    {
        if(isset($data['available'])) {
            $data['_available'] = empty($data['available']) ? '<font color="red">否</font>' : '<font color="green">是</font>';
        }
        if(isset($data['infront'])) {
            $data['_infront'] = empty($data['infront']) ? '<font color="red">否</font>' : '<font color="green">是</font>';
        }
        if(isset($data['required'])) {
            $data['_required'] = empty($data['required']) ? '<font color="red">否</font>' : '<font color="green">是</font>';
        }
        if(isset($data['unique'])) {
            $data['_unique'] = empty($data['unique']) ? '<font color="red">否</font>' : '<font color="green">是</font>';
        }
        if(isset($data['search'])) {
            $data['_search'] = empty($data['search']) ? '<font color="red">否</font>' : '<font color="green">是</font>';
        }
        if(isset($data['joinsorting'])) {
            $data['_joinsorting'] = empty($data['joinsorting']) ? '<font color="red">否</font>' : '<font color="green">是</font>';
        }
        if(isset($data['inlist'])) {
            $data['_inlist'] = empty($data['inlist']) ? '<font color="red">否</font>' : '<font color="green">是</font>';
        }
        if(!empty($data['rules'])) {
            $data['_rules'] = unserialize($data['rules']);
        }
    }

    /**
     * 检测某字段在主表和附表中是否存在（类外部有用到）
     */
    public function fieldCheck($formid, $fieldname)
    {
        if($this->count(array('formid' => $formid, 'fieldname' => $fieldname))) {
            return getResult(10310);
        }
        return getResult(100);
    }

    /**
     * 删除某字段
     * @param int $id
     * @version 1.0
     */
    public function deleteById($id)
    {
        if(empty($id)) {
            return getResult(10006);
        }
        $row = $this->fetch($id);
        if(empty($row)) {
            return getResult(10004);
        }
        $tablename = t('customform')->fetchFields($row['formid'], 'tablename');
        $formdata = new mop_formdata($tablename);
        $formdata->field_delete($row['fieldname']);
        $this->delete($id);
        return getResult(100,'',10314);
    }

    /**
     * 获取在列表中显示的附加字段
     * @param $formid 表单ID
     */
    public function getInlistFields($formid)
    {
        $listfields = array();
        $result = $this->fetchList(array('formid' => $formid, 'available' => 1, 'inlist' => 1), 'fieldname');
        if(empty($result)) {
            return '';
        }
        foreach($result as $v) {
            $listfields[] = $v['fieldname'];
        }
        return implode(',', $listfields);
    }

    /**
     * 获取在列表中参与排序的字段
     * @param $formid 表单ID
     */
    public function getOrderByFields($formid)
    {
        return $this->fetchList(array('formid' => $formid, 'available' => 1, 'joinsorting' => 1), 'title,fieldname','','displayorder');
    }

    /**
     * 生成筛选条件
     * @param $formid 表单ID
     */
    public function fetchSearchCondition($formid)
    {
        global $_M;
        static $result = array();
        if(empty($formid)){
            return array();
        }
        if(empty($result)){
            $fields = $this->fetchList(array('formid' => $formid, 'available' => 1, 'search' => 1), 'fieldname,datatype,rules,title', '', 'displayorder');
            $formdata = new mop_formdata();
            $result = $formdata->fetchSearchCondition($fields);
        }
        return $result;
    }

    /**
     * 生成表单结构
     * @param int $formid 表单ID
     * @param array $row 默认值
     */
    public function getFormFields($formid, $row = '')
    {
        global $_M;
        $condi = array('formid' => $formid, 'available' => 1, 'infront' => 1);
        if(!empty($_M['admin']['id'])) {
            unset($condi['infront']);
        }
        $fields = (array) $this->fetchList($condi, '*', '', 'displayorder');
        $formdata = new mop_formdata();
        return $formdata->form_fields_html($fields,$row);
    }

    /**
     * 获取表单自定义字段数据
     */
    public function fetchFieldsValue($formid, $row = '')
    {
        global $_M;
        $fields = $this->fetchList(array('formid' => $formid, 'available' => 1));
        $formdata = new mop_formdata();
        $data = '';
        return $formdata->fetchFieldsValue($fields,$row,$data);
    }
}