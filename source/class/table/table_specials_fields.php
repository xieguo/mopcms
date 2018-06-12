<?php
/**
 * 专题自定义字段处理类
 * @copyright			(C) 2016-2099 MOPCMS.COM
 * @license				http://www.mopcms.com/license/
 */
!defined('IN_MOPCMS') && exit('Access failed');

class table_specials_fields extends base_table
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
        if(!empty($data['rules'])) {
            $data['_rules'] = unserialize($data['rules']);
        }
    }

    /**
     * 生成表单结构
     * @param int $sid 模型ID
     * @param array $row 默认值
     */
    public function getFormFields($sid, $row = '')
    {
        global $_M;
        $condi = array('sid' => $sid, 'available' => 1, 'infront' => 1);
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
    public function fetchFieldsValue($sid, $row = '')
    {
        global $_M;
        $fields = $this->fetchList(array('sid' => $sid, 'available' => 1));
        $formdata = new mop_formdata();
        $data = '';
        return $formdata->fetchFieldsValue($fields,$row,$data);
    }
}