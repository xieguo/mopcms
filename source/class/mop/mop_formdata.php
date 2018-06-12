<?php
/**
 * 模型或自定义表单字段或字段值处理
 * @copyright           (C) 2016-2099 MOPCMS.COM
 * @license             http://www.mopcms.com/license/
 */
!defined('IN_MOPCMS') && exit('Access failed');

class mop_formdata
{
    private $table = '';

    public function __construct($table='')
    {
        $this->table = $table;
    }

    /**
     * 用于编辑添加模型时创建相应字段
     */
    public function field_update($fieldname, $data)
    {
        $table = db :: table($this->table);
        $datatype = $data['datatype'];
        $fields = t($this->table)->tableFields();
        if(!empty($fields[$fieldname])) {
            $sql = 'ALTER TABLE  `' . $table . '` MODIFY COLUMN `' . $fieldname . '` ';
            if($datatype == 'multitext' || $datatype == 'multidate' || $datatype == 'htmltext' || $datatype == 'imgs') {
                $sql .= 'TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ';
            }
            elseif($datatype == 'int' || $datatype == 'datetime' || $datatype == 'date' || $datatype == 'multilevel') {
                $sql .= 'INT( 11 ) NOT NULL DEFAULT  \'0\' ';
            }
            elseif($datatype == 'float') {
                $sql .= 'double( 15,3 ) NOT NULL ';
            }
            elseif($datatype == 'price') {
                $sql .= 'decimal( 15,2 ) NOT NULL ';
            } else {
                $sql .= 'VARCHAR( 250 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT  \'\' ';
            }
        } else {
            $sql = 'ALTER TABLE  `' . $table . '` ADD `' . $fieldname . '` ';
            if($datatype == 'multitext' || $datatype == 'multidate' || $datatype == 'htmltext' || $datatype == 'imgs') {
                $sql .= 'TEXT NOT NULL ';
            }
            elseif($datatype == 'int' || $datatype == 'datetime' || $datatype == 'multilevel') {
                $sql .= 'INT( 11 ) NOT NULL DEFAULT  \'0\' ';
            }
            elseif($datatype == 'float') {
                $sql .= 'double( 15,3 ) NOT NULL ';
            }
            elseif($datatype == 'price') {
                $sql .= 'decimal( 15,2 ) NOT NULL ';
            } else {
                $sql .= 'VARCHAR( 250 ) NOT NULL ';
            }
        }
        db :: query($sql . ' COMMENT \'' . $data['title'] . '\'');

        if(empty($fields[$fieldname]['Key']) && !empty($data['search'])) {
            db :: query('ALTER TABLE  `' . $table . '` ADD INDEX (`' . $fieldname . '`)');
        }
        if(!empty($fields[$fieldname]['Key']) && empty($data['search'])) {
            db :: query('ALTER TABLE  `' . $table . '` DROP INDEX `' . $fieldname . '`');
        }
    }

    /**
     * 删除字段
     */
    public function field_delete($fieldname)
    {
        $fields = t($this->table)->tableFields();
        if($fields[$fieldname]) {
            $table = db :: table($this->table);
            db :: query('ALTER TABLE  `' . $table . '` DROP `' . $fieldname . '`');
            return true;
        }
        return false;
    }

    /**
     * 生成表单结构
     * @param int $fields 字段数组
     * @param array $row 默认值
     */
    public function form_fields_html($fields, $row = '')
    {
        global $_M;
        foreach($fields as $k => $v) {
            $v['maxlength'] = $maxlength = !empty($v['maxlength']) ? ' maxlength="' . $v['maxlength'] . '"' : '';
            $v['rules'] = unserialize($v['rules']);
            $v['default'] = !empty($row[$v['fieldname']]) ? $row[$v['fieldname']] : html_entity_decode($v['default']);

            //Validform规则设置
            $v['checkrule'] =(empty($v['checkrule']) && $v['required']) ? '*' : $v['checkrule'];
            $this->regexp_change($v['checkrule']);
            $title = $v['title'];
            if (strpos($v['title'], '#')) {
                list($title,) = explode('#', $v['title']);
            }

            $datatype = !empty($v['checkrule']) ? ' datatype="' . $v['checkrule'] . '" ' : '';
            $v['intro'] = empty($v['intro']) && $datatype ?((in_array($v['datatype'], array('select', 'radio', 'checkbox'))) ? '必选' : '') : $v['intro'];
            $nullmsg = !empty($v['nullmsg']) ? ' nullmsg="' . $v['nullmsg'] . '" ' : (!empty($v['required'])?' nullmsg="请输入'.$title. '" ':'');
            $ignore = empty($v['required']) ? ' ignore="ignore" ' : '';
            $tip = $altercss = '';
            if(!empty($v['tip'])) {
                $tip = ' tip="' . $v['tip'] . '" placeholder="' . $v['tip'] . '" ';
                $v['css'] .= ' altercss';
                $altercss = ' altercss="altercss" ';
            }else{
                $tip = !empty($v['required'])?' tip="请输入'.$title. '" placeholder="请输入'.$title. '"':'';
            }
            $errormsg = !empty($v['errormsg']) ? ' errormsg="' . $v['errormsg'] . '" ' :  (!empty($v['required'])?' errormsg="请输入正确的'.$title. '" ':'');
            $v['validform'] = $validform = ' sucmsg="正确" ' . $altercss . $datatype . $nullmsg . $tip . $errormsg . $ignore.$maxlength;

            if($_M['iswap']){
                $this->wapform($v);
            }else{
                $this->pcform($v);
            }
            $fields[$k] = $v;
        }
        return $fields;
    }

    /**
     * 正则表达式由于PC和WAP规则不一样，做个转换
     * @param unknown_type $str
     */
    private function regexp_change(&$str)
    {
        global $_M;
        if($_M['iswap']){
            if($str=='*'){
                $str = '[\S]{1,}';
            }
            elseif(preg_match('/^\*[\d]{1,}-/i', $str)){
                $str = str_replace(array('*','-'), array('[\S]{',','), $str).'}';
            }
            elseif($str=='n'||$str=='p'){
                $str = '[0-9]*';
            }
            elseif(preg_match('/^n[\d]{1,}-/i', $str)){
                $str = str_replace(array('*','-'), array('[\d]{',','), $str).'}';
            }
            elseif($str=='s'){
                $str = '[^`\^~!@#$%&*()_+\[\]{};\\\'\?\.,<>/:\|]{1,}';
            }
            elseif(preg_match('/^s[\d]{1,}-/i', $str)){
                $str = str_replace(array('s','-'), array('[^`\^~!@#$%&*()_+\[\]{};\\\'\?\.,<>/:\|]{',','), $str).'}';
            }
            elseif($str=='m'){
                $str = '[\d]{11}';
            }
            elseif($str=='tel'){
                $str = '[\d-]{7,13}';
            }
            elseif($str=='e'){
                $str = '[a-zA-Z0-9-_]{1,}[@]{1}[a-zA-Z0-9-]{1,}[.]{1}[a-zA-Z]{2,4}';
            }
            elseif($str=='e'){
                $str = '^http[s]{0,}://[a-zA-Z0-9-]{1,}[.]{1}[\S]{1,}';
            }
            elseif($str=='money2' || preg_match('/^number_/i', $str)){
                $str = '[\d.]{1,}';
            }
        }
    }

    /**
     * 生成PC版表单结构
     * @param unknown_type $v
     */
    private function pcform(&$v)
    {
        global $_M;
        $validform = &$v['validform'];
        switch($v['datatype']){
            case 'text':
            case 'int':
                $v['field'] = '<input type="text"' . $validform . ' class="span6 ' . $v['css'] . '" id="' . $v['fieldname'] . '" name="' . $v['fieldname'] . '" value="' . $v['default'] . '" >';
                break;
            case 'multitext':
                $v['field'] = '<textarea class="span12 ' . $v['css'] . '" id="' . $v['fieldname'] . '" name="' . $v['fieldname'] . '" rows="4">' . $v['default'] . '</textarea>';
                break;
            case 'htmltext':
                require_once loadlib('misc');
                $v['default'] = mstripslashes($v['default']);
                $extra = array();
                if(empty($_M['admin']['id'])) {
                    $extra = array('identity' => 'member','initialFrameWidth'=>'600');
                    if(CURSCRIPT=='member'){
                        $extra['serverUrl'] = '"'.rewriteUrl('?mod=ueditor').'"';
                    }
                }
                $v['field'] = ueditor($v['fieldname'], $v['default'], $extra);
                break;
            case 'tel':
            case 'mobile':
                $v['field'] = '<input type="tel"' . $validform . ' class="span6 ' . $v['css'] . '" id="' . $v['fieldname'] . '" name="' . $v['fieldname'] . '" value="' . $v['default'] . '" >';
                break;
            case 'float':
            case 'price':
                $v['default'] = round($v['default'], 2);
                $v['default'] = !empty($v['default']) ? $v['default'] : '';
                $v['field'] = '<input type="text"' . $validform . ' class="span6 ' . $v['css'] . '" id="' . $v['fieldname'] . '" name="' . $v['fieldname'] . '" value="' . $v['default'] . '" >';
                break;
            case 'select':
                $option_num = count($v['rules']);
                $selected_style = $option_num > 5 ? '{maxHeight: 200}' : '';
                $str = '<select data-am-selected="' . $selected_style . '" class="selecttwo ' . $v['css'] . '"' . $validform . ' id="' . $v['fieldname'] . '" name="' . $v['fieldname'] . '"><option value="">请选择</option>';
                foreach($v['rules'] as $key => $val) {
                    if($key) {
                        $selected = $v['default'] != '' && $key == $v['default'] ? 'selected' : '';
                        $str .= '<option value="' . $key . '" ' . $selected . '>' . $val . '</option>';
                    }
                }
                $str .= '</select>';
                $v['field'] = $str;
                break;
            case 'radio':
                $str = '';
                $i = 0;
                foreach($v['rules'] as $key => $val) {
                    if(empty($key)) {
                        continue;
                    }
                    $i++;
                    $checked = $v['default'] != '' && $key == $v['default'] ? 'checked' : '';
                    $str .= '<label for="' . $v['fieldname'] . $key . '" class="radio inline"><input type="radio"' .($i == 1 ? $validform : '') . ' class="radio ' . $v['css'] . '" id="' . $v['fieldname'] . $key . '" name="' . $v['fieldname'] . '" value="' . $key . '" ' . $checked . '/>' . $val . '</label>';
                }
                $v['field'] = $str;
                break;
            case 'checkbox':
                $str = '';
                $i = 0;
                foreach($v['rules'] as $key => $val) {
                    if(empty($key)) {
                        continue;
                    }
                    $i++;
                    $def = explode(',', $v['default']);
                    $checked = in_array($key, $def) ? 'checked' : '';
                    $str .= '<label for="' . $v['fieldname'] . $key . '" class="checkbox inline"><input type="checkbox" ' .($i == 1 ? $validform : '') . ' class="checkbox ' . $v['css'] . '" id="' . $v['fieldname'] . $key . '" name="' . $v['fieldname'] . '[]" value="' . $key . '" ' . $checked . '>' . $val . '</label>';
                }
                $v['field'] = $str;
                break;
            case 'multilevel':
                if (strpos($v['title'], '#')) {
                    list($v['title'], $v['fieldname']) = explode('#', $v['title']);
                }
                $str = $this->multilevel_js($v['fieldname'], (int) $v['default'], $validform, $v['css']);
                $v['field'] = $str;
                break;
            case 'date':
            case 'datetime':
                $format = $v['datatype'] == 'date' ? 'd' : 'dt';
                if (!empty($v['default'])){
                    if(strlen($v['default'])>9){
                        $v['default'] = mdate($v['default'],$format);
                    }else{
                        $v['default'] = mdate((TIME + $v['default'] * 3600 * 24),$format);
                    }
                }else{
                    $v['default'] = mdate(TIME,$format);
                }
                $str = '';
                if(empty($loadnums)) {
                    $loadnums = 1;
                    $str = '<style type="text/css">[class^="icon-arrow-"], [class*=" icon-arrow-"] {background-image:none;}' .
                    '.datetimepicker table {margin-top: -5px;}' .
                    '.datetimepicker table td {font-size:12px !important;height:auto !important;}' .
                    '.datetimepicker table th {font-weight: 400 !important;height: 40px !important;}' .
                    '.datetimepicker table .icon-arrow-left::before, .datetimepicker table .icon-arrow-right::before {font-size:18px;margin-top: 8px;}' .
                    '.datetimepicker-years span.year, .datetimepicker-months span.year, .datetimepicker-hours span.year, .datetimepicker-minutes span.year, .datetimepicker-years span.month, .datetimepicker-months span.month, .datetimepicker-hours span.month, .datetimepicker-minutes span.month, .datetimepicker-years span.hour, .datetimepicker-months span.hour, .datetimepicker-hours span.hour, .datetimepicker-minutes span.hour, .datetimepicker-years span.minute, .datetimepicker-months span.minute, .datetimepicker-hours span.minute, .datetimepicker-minutes span.minute {width: 53px !important;}' .
                    '</style><link rel="stylesheet" href="' . $_M['sys']['static_url'] . 'assets/plugins/amaze.datetimepicker/css/amazeui.datetimepicker.css" />' .
                    '<script src="' . $_M['sys']['static_url'] . 'assets/plugins/amaze.datetimepicker/js/amazeui.datetimepicker.min.utf8.js"></script>';
                }
                $str .= '<div class="input-prepend"><span class="add-on"><span class=" fontello-icon-calendar-1"></span></span>' .
                '<input type="text" ' . $validform . ' name="' . $v['fieldname'] . '" id="' . $v['fieldname'] . '" class="am-form-field ' . $v['css'] . '" value="' . $v['default'] . '" /></div>' .
                '<script>$(document).ready(function() {$(\'#' . $v['fieldname'] . '\').datetimepicker({language: \'zh-CN\',autoclose: true,';
                if($v['datatype'] == 'date') {
                    $str .= 'format: \'yyyy-mm-dd\',startView: 2,minView: 2,';
                } else {
                    $str .= 'format: \'yyyy-mm-dd hh:ii\',';
                }
                $str .= '});});</script>';
                $v['field'] = $str;
                break;
            case 'multidate':
                $v['field'] = '<input type="text" readonly id="' . $v['fieldname'] . '" name="' . $v['fieldname'] . '" value="' . $v['default'] . '">';
                if(!isset($multidate)) {
                    $multidate = 1;
                    $v['field'] .= '<script src="' . $_M['sys']['static_url'] . 'assets/plugins/bootstrap-datepicker/bootstrap-datepicker.min.js"></script>' .
                    '<script src="' . $_M['sys']['static_url'] . 'assets/plugins/bootstrap-datepicker/bootstrap-datepicker.zh-CN.min.js" charset="UTF-8"></script>'.
                    '<link href="' . $_M['sys']['static_url'] . 'assets/plugins/bootstrap-datepicker/bootstrap-datepicker3.min.css" rel="stylesheet">';
                    $v['field'] .= '<script>$.fn.mdatepicker = $.fn.datepicker.noConflict();</script>';
                }
                $v['field'] .= '<script>$(\'#' . $v['fieldname'] . '\').mdatepicker({format: \'yyyy-mm-dd\',language: \'zh-CN\',multidate: true,multidateSeparator: ","})</script>';
                break;
            case 'imgs':
                $imgs = array();
                if (!empty($v['default'])) {
                    $imgs = unserialize($v['default']);
                    $imgs = array_reverse($imgs);
                }
                $id = (int)_gp('id');
                $str = '<link rel="stylesheet" href="' . $_M['sys']['static_url'] . 'webuploader/0.1.5/webuploader.css" />' .
                '<script src="' . $_M['sys']['static_url'] . 'webuploader/0.1.5/webuploader.min.js"></script>' .
                '<link rel="stylesheet" href="' . $_M['sys']['static_url'] . 'webuploader/image-upload/css/css.css" />' .
				'<script>var basehost = "'.trim($_M['sys']['basehost'],'/').'";</script>'.
                '<script src="' . $_M['sys']['static_url'] . 'webuploader/image-upload/js/upload.js"></script><script>var _setText = 1;</script>' .
                '<div id="uploader"><div class="queueList" ><div id="dndArea" class="placeholder"><div id="filePicker"></div>' .
                '<p>或将照片拖到这里</p></div></div><div class="statusBar" style="display:none;"><div class="progress"><span class="text">0%</span>' .
                '<span class="percentage"></span></div><div style="overflow:hidden;"><div class="btns"><div id="filePicker2"></div>' .
                '<div class="uploadBtn">开始上传</div></div><div class="info"></div></div></div></div>' .
                '<div class="mod_list_photo" id="'.$id.'"><ul>';
                foreach($imgs as $ik=>$iv){
                    $iv['path'] = !empty($iv['path'])?$iv['path']:$iv['img'];
                    $str .= '<li class="libtn" id="oldpic'.$ik.'"><div class="box_photo">' .
                    '<em><a href="'.imageResize($iv['path'],2000,2000).'" target="_blank"><i></i><img src="'.imageResize($iv['path'],160,150).'" /></a></em>' .
                    '</div><div class="btn_photo"><a ref="'.$iv['path'].'" mfid="'.$v['id'].'" class="btn_delete_pic"><span>删除</span></a>' .
                    '<a href="'.imageResize($iv['path'],2000,2000).'" target="_blank" class="btn_view_pic"><span>查看</span></a>' .
                    '</div><div class="box_text"><input type="text" class="span12" name="imgmsg'.$ik.'" value="'.$iv['text'].'" placeholder="图片未命名" />' .
                    '</div></li>';
                }
                $str .= '</ul></div>';
                $v['field'] = $str;
                break;
            case 'img':
            case 'media':
            case 'addon':
                $str = '';
                if ($v['default']) {
                    if ($v['datatype'] == 'img') {
                        $str = '<div class="am-cf am-margin-vertical"><a href="' . $v['default'] . '" target="_blank"><img src="' . $v['default'] . '" style="height:80px;" class="am-img-thumbnail" /></a></div>';
                    }
                    else {
                        $str = '<a href="' . $v['default'] . '">' . $v['default'] . '</a>';
                    }
                    $validform = '';
                }
                $v['field'] = '<style type="text/css">input[name="' . $v['fieldname'] . '"] {cursor: pointer;
                left: -100px;opacity: 0;position: relative;width: 96px;z-index: 1;}</style>
                <div class="am-form-group am-form-file"><button type="button" class="am-btn am-btn-default am-btn-sm"><i class="am-icon-cloud-upload"></i> 本地上传</button><input type="file" name="' . $v['fieldname'] . '" onChange="$(\'#' . $v['fieldname'] . '_html\').html(this.value);$(\'#' . $v['fieldname'] . '_hidden\').val(this.value);" />
                <input type="hidden" id="' . $v['fieldname'] . '_hidden" value="' . $v['default'] . '" ' . $validform . ' /></div><div id="' . $v['fieldname'] . '_html" class="am-badge"></div>' . $str;
                break;
            case 'hidden':
                $v['field'] = '<input name="' . $v['fieldname'] . '" id="' . $v['fieldname'] . '" type="hidden" value="' . $v['default'] . '"/>';
                break;
            default:
                $v['field'] = '<input type="text" ' . $validform . ' class="am-form-field ' . $v['css'] . '" id="' . $v['fieldname'] . '" name="' . $v['fieldname'] . '" value="' . $v['default'] . '" >';
        }
    }

    /**
     * 生成WAP版表单结构
     * @param unknown_type $v
     */
    private function wapform(&$v)
    {
        global $_M;
        $search = array(' tip="',' datatype="',' nullmsg="',' errormsg="');
        $replace = array(' placeholder="',' pattern="', ' emptyTips="',' notMatchTips="');
        $validform = str_replace($search, $replace, $v['validform']);
        if(!empty($v['required'])){
            $validform .= ' required ';
        }
        switch($v['datatype']){
            case 'text':
                $v['field'] = '<input '.$validform.' class="weui_input ' . $v['css'] . '" type="text" id="' . $v['fieldname'] . '" name="' . $v['fieldname'] . '" value="' . $v['default'] . '"  >';
                break;
            case 'int':
                $v['field'] = '<input '.$validform.' class="weui_input ' . $v['css'] . '" type="member" id="' . $v['fieldname'] . '" name="' . $v['fieldname'] . '" value="' . $v['default'] . '"  >';
                break;
            case 'multitext':
                $v['field'] = '<div class="weui_cell"><div class="weui_cell_bd weui_cell_primary"><textarea '.$validform.' class="weui_textarea ' . $v['css'] . '" id="' . $v['fieldname'] . '" name="' . $v['fieldname'] . '" rows="3">' . $v['default'] . '</textarea></div></div>';
                break;
            case 'htmltext':
                require_once loadlib('misc');
                $v['default'] = mstripslashes($v['default']);
                $extra = array();
                if(empty($_M['admin']['id'])) {
                    $extra = array('identity' => 'member');
                }
                $v['field'] = ueditor($v['fieldname'], $v['default'], $extra);
                break;
            case 'tel':
                $v['field'] = '<input type="tel"' . $validform . ' class="weui_input ' . $v['css'] . '" id="' . $v['fieldname'] . '" name="' . $v['fieldname'] . '" value="' . $v['default'] . '" >';
                break;
            case 'mobile':
                $v['field'] = '<input type="tel"' . $validform . ' class="weui_input ' . $v['css'] . '" id="' . $v['fieldname'] . '" name="' . $v['fieldname'] . '" value="' . $v['default'] . '" >';
                break;
            case 'float':
                $v['default'] = round($v['default'], 3);
                $v['default'] = !empty($v['default']) ? $v['default'] : '';
                $v['field'] = '<input type="text"' . $validform . ' class="weui_input ' . $v['css'] . '" id="' . $v['fieldname'] . '" name="' . $v['fieldname'] . '" value="' . $v['default'] . '" >';
                break;
            case 'price':
                $v['default'] = round($v['default'], 2);
                $v['default'] = !empty($v['default']) ? $v['default'] : '';
                $v['field'] = '<input type="text"' . $validform . ' class="weui_input ' . $v['css'] . '" id="' . $v['fieldname'] . '" name="' . $v['fieldname'] . '" value="' . $v['default'] . '" >';
                break;
            case 'select':
                $str = '<select ' . $validform . ' class="weui_select ' . $v['css'] . '" id="' . $v['fieldname'] . '" name="' . $v['fieldname'] . '"/>';
                foreach($v['rules'] as $key => $val) {
                    $str .= '<option value="'.$key.'">'.$val.'</option>';
                }
                $str .= '</select>';
                $v['field'] = $str;
                break;
                //另一种下拉风格，如果可选参数比较多滑动不顺畅，如想使用此风格可自己手动修改
            case 'select_bak':
                $str = '<script>$(function() {$("#show-' . $v['fieldname'] . '").select({title: "'.$v['title'].'",items: [';
                foreach($v['rules'] as $key => $val) {
                    $str .= '{title: "'.$val.'",value: "'.$key.'"},';
                }
                $str .= ']});';
                $str .= '$(document).on("click","#show-' . $v['fieldname'] . '",function(){';
                $str .= '$(".weui-picker-container-visible").addClass("visible-' . $v['fieldname'] . '");';
                $str .= '});';
                $str .= '$(document).on("click",".visible-' . $v['fieldname'] . ' .weui_check_label",function(){';
                $str .= '$("#' . $v['fieldname'] . '").val($(this).find(".weui_check").val());';
                $str .= '});';
                $str .= '});</script>';
                $str .= '<input type="hidden" id="' . $v['fieldname'] . '" value="' . $v['default'] . '" />';
                $str .= '<input ' . $validform . ' class="weui_input ' . $v['css'] . '" type="text" id="show-' . $v['fieldname'] . '"/>';
                $v['field'] = $str;
                break;
            case 'radio':
                $str = '<div class="weui_cells weui_cells_radio">';
                foreach($v['rules'] as $key => $val) {
                    $checked = $key == $v['default'] ? ' checked="checked"' : '';
                    $str .= '<label class="weui_cell weui_check_label" for="' . $v['fieldname'] . $key . '">';
                    $str .= '<div class="weui_cell_bd weui_cell_primary">' . $val . '</div>';
                    $str .= '<div class="weui_cell_ft">';
                    $str .= '<input type="radio" class="weui_check ' . $v['css'] . '" value="' . $key . '" data-text="' . $val . '" name="' . $v['fieldname'] . '" id="' . $v['fieldname'] . $key . '" '.$checked.'>';
                    $str .= '<span class="weui_icon_checked"></span>';
                    $str .= '</div></label>';
                }
                $str .= '</div>';
                $v['field'] = $str;
                break;
            case 'checkbox':
                $str = '<div class="weui_cells weui_cells_checkbox">';
                foreach($v['rules'] as $key => $val) {
                    $def = explode(',', $v['default']);
                    $checked = in_array($key, $def) ? ' checked="checked"' : '';
                    $str .= '<label class="weui_cell weui_check_label" for="' . $v['fieldname'] . $key . '"><div class="weui_cell_hd">';
                    $str .= '<input type="checkbox" class="weui_check ' . $v['css'] . '" id="' . $v['fieldname'] . $key . '" name="' . $v['fieldname'] . '[]" value="' . $key . '" data-text="' . $val . '" ' . $checked . '>';
                    $str .= '<i class="weui_icon_checked"></i></div>';
                    $str .= '<div class="weui_cell_bd weui_cell_primary"><p>' . $val . '</p></div></label>';
                }
                $str .= '</div>';
                $v['field'] = $str;
                break;
            case 'multilevel':
                if (strpos($v['title'], '#')) {
                    list($v['title'], $v['fieldname']) = explode('#', $v['title']);
                }
                $str = $this->multilevel_js($v['fieldname'], (int) $v['default'], $validform, $v['css']);
                $v['field'] = $str;
                break;
            case 'multidate':
            case 'date':
                if (!empty($v['default'])){
                    if(strlen($v['default'])>9){
                        $v['default'] = mdate($v['default']);
                    }else{
                        $v['default'] = mdate(TIME + $v['default'] * 3600 * 24);
                    }
                }else{
                    $v['default'] = mdate(TIME);
                }

                $str = '<script>$(function() {$("#' . $v['fieldname'] . '").calendar();});</script>';
                $str .= '<input type="text" ' . $validform . ' name="' . $v['fieldname'] . '" id="' . $v['fieldname'] . '" class="weui_input ' . $v['css'] . '" value="' . $v['default'] . '"/>';
                $v['field'] = $str;
                break;
            case 'datetime':
                if (!empty($v['default'])){
                    if(strlen($v['default'])>9){
                        $v['default'] = mdate($v['default'],'dt');
                    }else{
                        $v['default'] = mdate((TIME + $v['default'] * 3600 * 24),'dt');
                    }
                }else{
                    $v['default'] = mdate(TIME,'dt');
                }

                $str = '<script>$(function() {$("#' . $v['fieldname'] . '").datetimePicker({ title: "选择日期时间" });});</script>';
                $str .= '<input type="text" ' . $validform . ' name="' . $v['fieldname'] . '" id="' . $v['fieldname'] . '" class="weui_input ' . $v['css'] . '" value="' . $v['default'] . '"/>';
                $v['field'] = $str;
                break;
            case 'multidate':
                $v['field'] = '<input type="text" readonly id="' . $v['fieldname'] . '" name="' . $v['fieldname'] . '" value="' . $v['default'] . '">';
                if(!isset($multidate)) {
                    $multidate = 1;
                    $v['field'] .= '<script src="' . $_M['sys']['static_url'] . 'assets/plugins/bootstrap-datepicker/bootstrap-datepicker.min.js"></script>' .
                    '<script src="' . $_M['sys']['static_url'] . 'assets/plugins/bootstrap-datepicker/bootstrap-datepicker.zh-CN.min.js" charset="UTF-8"></script>'.
                    '<link href="' . $_M['sys']['static_url'] . 'assets/plugins/bootstrap-datepicker/bootstrap-datepicker3.min.css" rel="stylesheet">';
                    $v['field'] .= '<script>$.fn.mdatepicker = $.fn.datepicker.noConflict();</script>';
                }
                $v['field'] .= '<script>$(\'#' . $v['fieldname'] . '\').mdatepicker({format: \'yyyy-mm-dd\',language: \'zh-CN\',multidate: true,multidateSeparator: ","})</script>';
                break;
            case 'imgs':
                $imgs = array();
                if (!empty($v['default'])) {
                    $imgs = unserialize($v['default']);
                    $imgs = array_reverse($imgs);
                }
                $id = (int)_gp('id');
                $str = '<link rel="stylesheet" href="' . $_M['sys']['static_url'] . 'webuploader/0.1.5/webuploader.css" />' .
                '<script src="' . $_M['sys']['static_url'] . 'webuploader/0.1.5/webuploader.min.js"></script>' .
                '<link rel="stylesheet" href="' . $_M['sys']['static_url'] . 'webuploader/image-upload/css/css.css" />' .
				'<script>var basehost = "'.trim($_M['sys']['basehost'],'/').'",iswap="'.$_M['iswap'].'";</script>'.
                '<script src="' . $_M['sys']['static_url'] . 'webuploader/image-upload/js/upload.js"></script><script>var _setText = 1;</script>' .
                '<div id="uploader"><div class="queueList" ><div id="dndArea" class="placeholder"><div id="filePicker"></div>' .
                '<p>或将照片拖到这里</p></div></div><div class="statusBar" style="display:none;"><div class="progress"><span class="text">0%</span>' .
                '<span class="percentage"></span></div><div style="overflow:hidden;"><div class="btns"><div id="filePicker2"></div>' .
                '<div class="uploadBtn">开始上传</div></div><div class="info"></div></div></div></div>' .
                '<div class="mod_list_photo" id="'.$id.'"><ul>';
                foreach($imgs as $ik=>$iv){
                    $iv['path'] = !empty($iv['path'])?$iv['path']:$iv['img'];
                    $str .= '<li class="libtn" id="oldpic'.$ik.'"><div class="box_photo">' .
                    '<em><a href="'.imageResize($iv['path'],2000,2000).'" target="_blank"><i></i><img src="'.imageResize($iv['path'],160,150).'" /></a></em>' .
                    '</div><div class="btn_photo"><a ref="'.$iv['path'].'" mfid="'.$v['id'].'" class="btn_delete_pic"><span>删除</span></a>' .
                    '<a href="'.imageResize($iv['path'],2000,2000).'" target="_blank" class="btn_view_pic"><span>查看</span></a>' .
                    '</div><div class="box_text"><input type="text" class="span12" name="imgmsg'.$ik.'" value="'.$iv['text'].'" placeholder="图片未命名" />' .
                    '</div></li>';
                }
                $str .= '</ul></div>';
                $v['field'] = $str;
                break;
            case 'img':
                $posturl = pseudoUrl($_M['sys']['cmspath'].'main.php?mod=ajax&ac=upload&do=img&fieldname='.$v['fieldname']);
                $default = '';
                if ($v['default']) {
                    $default = '<li class="weui_uploader_file" style="background-image:url(' . $v['default'] . ')"></li>';
                }
                $str = <<<EOT
                <style type="text/css">
				    #contain-{$v['fieldname']} .weui_uploader_bd ul {margin: 0px;}
				</style>
                <script src="{$_M['sys']['static_url']}wap/lrz.min.js"></script>
                <div class="weui_cells weui_cells_form">
                <div class="weui_cell">
                <div class="weui_cell_bd weui_cell_primary">
                <div class="weui_uploader">
	                <div class="weui_uploader_bd">
		                <ul class="weui_uploader_files" id="img-{$v['fieldname']}">{$default}</ul>
		                <div class="weui_uploader_input_wrp">
			                <input class="weui_uploader_input" type="file" accept="image/jpg,image/jpeg,image/png,image/gif" id="upload-{$v['fieldname']}" />
			                <input type="hidden" id="{$v['fieldname']}" name="{$v['fieldname']}" value="{$v['default']}"/>
		                </div>
	                </div>
                </div>
                </div>
                </div>
                </div>
                <script>
                $(function() {
                var f = document.querySelector("#upload-{$v['fieldname']}");
                f.onchange = function() {
                    lrz(this.files[0], {
                            width: {$_M['sys']['img_width']},
                            fieldName: "{$v['fieldname']}"
                        }).then(function(rst) {
                            var xhr = new XMLHttpRequest();
                            xhr.open('POST', '{$posturl}');
                            xhr.onload = function() {
                                if(xhr.status === 200) {
                                    var obj = eval('(' + xhr.responseText + ')');
                                    if(obj.code!=100){
                                    	alert(obj.msg);
                                    }else{
                                    	\$('#img-{$v['fieldname']}').html('<li class="weui_uploader_file" style="background-image:url(' + obj.data + ')"></li>');
                                    	\$("#{$v['fieldname']}").val(obj.data);
                                    }
                                } 
                            };
                            xhr.upload.onprogress = function(e) {
                                var percentComplete = ((e.loaded / e.total) || 0) * 100;
                            }
                            rst.formData.append('size', rst.fileLen);
                            rst.formData.append('base64', rst.base64);
                            xhr.send(rst.formData);
                            return rst;
                        })
                        .catch(function(err) {
                            alert(err);
                        })
                }
                });</script>
EOT;
                $v['field'] = $str;
                break;
            case 'media':
            case 'addon':
                $posturl = pseudoUrl($_M['sys']['cmspath'].'main.php?mod=ajax&ac=upload&do=img&fieldname='.$v['fieldname']);
                $default = '';
                if ($v['default']) {
                    if ($v['datatype'] == 'img') {
                        $default = '<li class="weui_uploader_file" style="background-image:url(' . $v['default'] . ')"></li>';
                    } else {
                        $default = '<li href="' . $v['default'] . '">' . $v['default'] . '</li>';
                    }
                }
                $str = <<<EOT
                <style type="text/css">
				    #contain-{$v['fieldname']} .weui_uploader_bd ul {margin: 0px;}
				</style>
                <div class="weui_cells weui_cells_form">
                <div class="weui_cell">
                <div class="weui_cell_bd weui_cell_primary">
                <div class="weui_uploader">
	                <div class="weui_uploader_bd">
		                <div class="weui_uploader_input_wrp">
			                <input class="weui_uploader_input" type="file" id="{$v['fieldname']}" name="{$v['fieldname']}"/>
		                </div>
		                <ul class="weui_uploader_files" id="img-{$v['fieldname']}">{$default}</ul>
	                </div>
                </div>
                </div>
                </div>
                </div>
                <script>
                $(function() {
                var f = document.querySelector("#{$v['fieldname']}");
                f.onchange = function() {
                var file = f.files;  
                \$('#img-{$v['fieldname']}').html('<li><span class="f14">'+file[0].name+'</span></li>');
                }
                });</script>
EOT;
                $v['field'] = $str;
                break;
            case 'hidden':
                $v['field'] = '<input name="' . $v['fieldname'] . '" id="' . $v['fieldname'] . '" type="hidden" value="' . $v['default'] . '"/>';
                break;
            default:
                $v['field'] = '<input '.$validform.' class="weui_input ' . $v['css'] . '" type="text" id="' . $v['fieldname'] . '" name="' . $v['fieldname'] . '" value="' . $v['default'] . '"  >';
        }
    }

    /**
     * 获取枚举的级联菜单
     * @global type $_M
     * @param type $identifier 枚举类型
     * @param type $evalue 父ID
     * @param type $validform 表单验证
     * @param type $css 样式
     * @param type $name 默认标题
     * @return string
     */
    private function multilevel_js($identifier, $evalue, $validform = '', $css = '', $name = '')
    {
        global $_M;
        $forms = '<style type="text/css">.mlSelect{width:auto;}</style>';
        $forms .= '<script type="text/javascript" src="' . $_M['sys']['static_url'] . 'js/multilevel.js"></script>';
        $forms .= '<script type="text/javascript">';
        $forms .= '$(document).ready(function(){';
        $forms .= 'var ml = new multiLevel();';
        $css = ($_M['iswap']?'':'mlSelect ').$css;
        $forms .= 'var option = {evalue:"' . $evalue . '",identifier:"' . $identifier . '",drawID:"' . $identifier . '_span",defaultName:"' . $name . '",validform:\'' . $validform . '\',css:"' . $css . '",basehost:"' . $_M['sys']['basehost'] . '",iswap:"'.$_M['iswap'].'"};';
        $forms .= 'ml.init(option);';
        $forms .= '});';
        $forms .= '</script>';
        $forms .= '<span id="' . $identifier . '_span"></span>';
        return $forms;
    }

    /**
     * 获取表单自定义字段提交的数据
     * @param $fields 自定义字段列表
     * @param $row 原来的数据
     * @param $data 提交的数据
     */
    public function fetchFieldsValue($fields, $row = '', &$data)
    {
        global $_M;
        $vals = array();
        foreach($fields as $k => $v) {
            $rule = unserialize($v['rules']);
            switch ($v['datatype']){
                case 'htmltext':
                    $htmltext = _gp($v['fieldname']);
                    if(!empty($data)){
                        $this->filter_htmltext($htmltext, $data);
                    }
                    $vals[$v['fieldname']] = $htmltext;
                    break;
                case 'int':
                    $vals[$v['fieldname']] = (int) _gp($v['fieldname']);
                    break;
                case 'multilevel':
                    $fieldname = $v['fieldname'];
                    if (strpos($v['title'], '#')) {
                        list($v['title'], $fieldname) = explode('#', $v['title']);
                    }
                    $vals[$v['fieldname']] = (int) _gp($fieldname);
                    break;
                case 'tel':
                    $vals[$v['fieldname']] = preg_replace('/[^0-9-]/', '', _gp($v['fieldname']));
                    break;
                case 'float':
                    $vals[$v['fieldname']] = round((float) _gp($v['fieldname']), 3);
                    break;
                case 'price':
                    $vals[$v['fieldname']] = round((float) _gp($v['fieldname']), 2);
                    break;
                case 'select':
                case 'radio':
                    $vals[$v['fieldname']] = _gp($v['fieldname']);
                    if(empty($rule[_gp($v['fieldname'])])) {
                        $vals[$v['fieldname']] = '';
                    }
                    break;
                case 'checkbox':
                    $vals[$v['fieldname']] = '';
                    if(!empty($_GET[$v['fieldname']])) {
                        $arr = array();
                        foreach($_GET[$v['fieldname']] as $m) {
                            if(!empty($rule[$m])) {
                                $arr[] = $m;
                            }
                        }
                        $vals[$v['fieldname']] = implode(',', $arr);
                    }
                    break;
                case 'date':
                case 'datetime':
                    $vals[$v['fieldname']] = _gp($v['fieldname']) ? strtotime(_gp($v['fieldname'])) : '';
                    break;
                case 'imgs':
                    $imgurls = array();
                    if(!empty($row['attach'][$v['fieldname']])) {
                        $imgurls = unserialize($row['attach'][$v['fieldname']]);
                        foreach($imgurls as $_k => $_v) {
                            $_v['text'] = str_replace("'", "`", filterString(_gp('imgmsg' . $_k)));
                            $imgurls[$_k] = $_v;
                        }
                    }
                    if (!session_id()){
                        session_start();
                    }
                    if(!empty($_SESSION['bigfile_info'])){
                        if(count($_SESSION['bigfile_info']) > $_M['sys']['webuploader_maxupload']) {
                            $_SESSION['bigfile_info'] = '';
                            foreach($_SESSION['bigfile_info'] as $_v) {
                                loadm('archives')->del_image($_v['img']);
                            }
                            return getResult(10608);
                        }
                        if(is_array($_SESSION['bigfile_info'])) {
                            foreach($_SESSION['bigfile_info'] as $_k => $_v) {
                                if($imginfos = getimagesize(MOPROOT . $_v)) {
                                    $key = md5($_v);
                                    $imgurls[$key]['img'] = $_v;
                                    $imgurls[$key]['text'] = filterString(_gp('picinfook' . $_k));
                                    $imgurls[$key]['width'] = $imginfos[0];
                                    $imgurls[$key]['height'] = $imginfos[1];
                                }
                            }
                        }
                        $_SESSION['bigfile_info'] = '';
                    }
                    $vals[$v['fieldname']] = $imgurls ? serialize($imgurls) : '';
                    break;
                case 'img':
                    if($_M['iswap']){
                        $vals[$v['fieldname']] = filterString(_gp($v['fieldname']));
                        break;
                    }
                case 'media':
                case 'addon':
                    if(empty($_FILES[$v['fieldname']]['tmp_name'])) {
                        continue;
                    }
                    $img = new mop_image;
                    $utype = $v['datatype']=='img'?'image':$v['datatype'];
                    $result = $img->uploadAttachment($v['fieldname'],'','','',$utype);
                    if($result['code'] != 100) {
                        return $result;
                    }
                    $vals[$v['fieldname']] = $result['data'];
                    if(!empty($row['attach'])) {
                        if(empty($vals[$v['fieldname']])) {
                            unset($vals[$v['fieldname']]);
                        } else {
                            !empty($row['attach'][$v['fieldname']]) && loadm('archives')->del_image($row['attach'][$v['fieldname']]);
                        }
                    }
                    break;
                default:
                    $vals[$v['fieldname']] = filterString(_gp($v['fieldname']));
            }
            //违禁词过滤
            if(!empty($vals[$v['fieldname']])) {
                $vals[$v['fieldname']] = wordsFilter($vals[$v['fieldname']]);
            }
        }
        return getResult(100, $vals);
    }

    /**
     * 过滤内容数据
     * @param string $htmltext 内容
     * @param array $row
     */
    private function filter_htmltext(& $htmltext, & $row)
    {
        global $_M;
        $this->filter_danger_img_src($htmltext);
        $htmltext = mstripslashes($htmltext);
        //远程图片本地化
        if($_M['sys']['rm_remote'] == 1) {
            $this->get_ext_resource($htmltext);
        }

        //删除非站内链接
        if(_gp('dellink') == 1) {
            preg_match_all("/<a[ \t\r\n]{1,}href=[\"']{0,}http:\/\/(.*)<\/a>/isU", $htmltext, $key);
            foreach($key[0] as $v) {
                if(!preg_match('/' . $_M['sys']['domain'] . '/i', $v)) {
                    $v1 = strip_tags($v);
                    $htmltext = str_replace($v, $v1, $htmltext);
                }
            }
        }

        if(empty($row['description']) && $_M['sys']['auto_description'] > 0) {
            $row['description'] = msubstr(delHtml($htmltext), $_M['sys']['auto_description']);
            $row['description'] = trim(preg_replace('/#p#|#e#/', '', strip_tags($row['description'])));
        }
        $row['description'] = str_replace(array("\n", "\r"), array('', ''), $row['description']);

        //自动获取缩略图
        if(_gp('autothumb') == 1 && empty($row['thumb'])) {
            preg_match_all("/(src)=[\"|'| ]{0,}([^>]*\.(gif|jpg|bmp|png))/isU", $htmltext, $img_array);
            $img_array = array_unique($img_array[2]);
            if(count($img_array) > 0) {
                $row['thumb'] = preg_replace("/[\"|'| ]{1,}/", '', $img_array[0]);
            }
        }
        $row['title'] = htmlspecialchars_decode($row['title']); //防止重复处理
        $row['title'] = filterString($row['title']);
        //自动获取关键字
        if(empty($row['keywords'])) {
            $row['keywords'] = $this->auto_get_keywords($row['title']);
        }
        $row['title'] = $_M['sys']['title_maxlen'] ? msubstr($row['title'], $_M['sys']['title_maxlen']) : $row['title'];
        $htmltext = preg_replace("/<sty(.*)\\/style>|<scr(.*)\\/script>|<!--(.*)-->/isU", "", $htmltext);
        $htmltext = maddslashes($htmltext);
    }
    /**
     * 过滤掉字符串中带有非图片url的图片链接
     * @param $htmltext 要过滤的字符串
     * @return string 过滤后的字符串
     */
    private function filter_danger_img_src(& $htmltext)
    {
        preg_match_all("/<img src(.*?)=(.*?)>/i", $htmltext, $match);
        $danger_urls = array();
        if(count($match) == 3) {
            foreach($match[2] as $v) {
                if(preg_match("/(.php|\?|&)/i", $v, $m)) {
                    $danger_urls[] = $v;
                    continue;
                }
                if(!preg_match("/(\.gif|\.jpg|\.png)/i", $v, $m)) {
                    $danger_urls[] = $v;
                    continue;
                }
            }
        }
        foreach($danger_urls as $v) {
            $htmltext = str_replace($v, "''", $htmltext);
        }
    }
    /**
     * 获取文章内容里的外部图片，不建议使用此功能，不能保证外部图片是否有伪装成图片的木马
     * @global type $_M
     * @param type $htmltext 内容
     * @return type
     */
    private function get_ext_resource(& $htmltext)
    {
        global $_M;
        require_once loadlib('misc');

        $htd = new helper_httpdown();
        $basehost = "http://" . $_SERVER["HTTP_HOST"];
        $img_array = array();
        preg_match_all("/src=[\"|'|\s]{0,}(http:\/\/([^>]*)\.(gif|jpg|png))/isU", $htmltext, $img_array);
        $img_array = array_unique($img_array[1]);
        $imgUrl = $_M['sys']['upload_remote'] . '/' . date('Ymd');
        $imgPath = MOPROOT . $imgUrl;
        createdir($imgPath);

        foreach($img_array as $key => $value) {
            if(preg_match("#" . $basehost . "#i", $value)) {
                continue;
            }
            if($_M['sys']['basehost'] != $basehost && preg_match("#" . $_M['sys']['basehost'] . "#i", $value)) {
                continue;
            }
            if(!preg_match("#^http:\/\/#i", $value)) {
                continue;
            }
            $htd->OpenUrl($value);
            $itype = $htd->GetHead("content-type");
            $itype = substr($value, -4, 4);
            if(!preg_match("#\.(jpg|gif|png)#i", $itype)) {
                if($itype == 'image/gif') {
                    $itype = ".gif";
                }
                elseif($itype == 'image/png') {
                    $itype = ".png";
                } else {
                    $itype = '.jpg';
                }
            }
            $filename = date('YmdHis') . mt_rand(1000, 8000) . '-' . $key . $itype;
            $value = trim($value);
            $filepath = $imgPath . '/' . $filename;
            $fileurl = $imgUrl . '/' . $filename;
            $rs = $htd->SaveToBin($filepath);
            if($rs) {
                if($_M['sys']['multi_site'] == 1) {
                    $fileurl = $_M['sys']['basehost'] . $fileurl;
                }
                $htmltext = str_replace($value, $fileurl, $htmltext);
                if(_gp('watermark') == 1) {
                    $img = new mop_image;
                    $img->waterImg($filepath, 'down');
                }
            }
        }
        $htd->Close();
    }
    /**
     * 自动根据标题获取关键字
     * @global type $_M
     * @param type $title 标题
     * @return string
     */
    private function auto_get_keywords($title)
    {
        $sp = new helper_splitword(CHARSET, CHARSET);
        $sp->SetSource($title, CHARSET, CHARSET);
        $sp->StartAnalysis();
        $titleindexs = preg_replace("/#p#|#e#/", '', $sp->GetFinallyIndex());
        $keywords = '';
        if(is_array($titleindexs)) {
            foreach($titleindexs as $k => $v) {
                if(strlen($keywords . $k) >= 60) {
                    break;
                } else {
                    $keywords .= $k . ',';
                }
            }
        }
        return filterString(trim($keywords, ','));
    }

    /**
     * 从数据库中读取的附表或自定义表单中字段值处理
     * @param $fields 字段名
     * @param $v 字段值
     */
    public function get_value_from_db($fields, &$v)
    {
        if (empty($fields)) {
            return array();
        }
        foreach ($fields as $val) {
            $v[$val['fieldname'] . '_units'] = !empty($val['units'])?$val['units']:'';
            switch ($val['datatype']){
                case 'price':
                    $v['_' . $val['fieldname']] = round($v[$val['fieldname']], 2);
                    if (!$v['_' . $val['fieldname']]) {
                        $v['_' . $val['fieldname']] = '面议';
                        $v[$val['fieldname'] . '_units'] = '';
                    }
                    break;
                case 'date':
                    $v['_' . $val['fieldname']] = !empty($v[$val['fieldname']]) ? mdate($v[$val['fieldname']]) : '';
                    break;
                case 'datetime':
                    $v['_' . $val['fieldname']] = !empty($v[$val['fieldname']]) ? mdate($v[$val['fieldname']], 'dt') : '';
                    break;
                case 'float':
                    $v['_' . $val['fieldname']] = $v[$val['fieldname']] = round($v[$val['fieldname']], 3);
                    if (!$v[$val['fieldname']]) {
                        $v[$val['fieldname']] = '';
                        $v[$val['fieldname'] . '_units'] = '';
                    }
                    break;
                case 'checkbox':
                    if (!empty($v[$val['fieldname']])) {
                        $rules = unserialize($val['rules']);
                        $arr = array();
                        foreach (explode(',', $v[$val['fieldname']]) as $_v) {
                            if (!empty($_v)){
                                $arr[] = is_numeric($_v) ? $rules[$_v] : $_v;
                            }
                        }
                        $v['_' . $val['fieldname']] = implode('、', $arr);
                    }
                    break;
                case 'select':
                case 'radio':
                    $v['_' . $val['fieldname']] = '';
                    if (!empty($v[$val['fieldname']])) {
                        $rules = !empty($val['rules'])?unserialize($val['rules']):array();
                        $v['_' . $val['fieldname']] = isset($rules[$v[$val['fieldname']]])?$rules[$v[$val['fieldname']]]:'';
                    }
                    break;
                case 'multilevel':
                    $v['_' . $val['fieldname']] = !empty($v[$val['fieldname']])?t('multilevel')->fetchFields($v[$val['fieldname']], 'name'):'';
                    if (!empty($val['title']) && strpos($val['title'], '#')) {
                        list($val['title'], $fieldname) = explode('#', $val['title']);
                        $v['_' . $fieldname] = $v['_' . $val['fieldname']];
                    }
                    break;
                case 'imgs':
                    $imgurls = !empty($v[$val['fieldname']]) ? unserialize($v[$val['fieldname']]) : array();
                    $v['_' . $val['fieldname']] = array_reverse($imgurls);
                    break;
                case 'htmltext':
                    $v[$val['fieldname']] = !empty($v[$val['fieldname']]) ? mstripslashes($v[$val['fieldname']]) : '';
                    $v['_' . $val['fieldname']] = &$v[$val['fieldname']];
                    break;
                default:
                    $v['_' . $val['fieldname']] = empty($v['_' . $val['fieldname']]) ? aval($v,$val['fieldname']) : $v['_' . $val['fieldname']];
            }
            if (!empty($val['rules']) && $val['datatype'] != 'price' && $val['datatype'] != 'date' && $val['datatype'] != 'datetime' && $val['datatype'] != 'multilevel' && is_numeric($v[$val['fieldname']])) {
                $rules = unserialize($val['rules']);
                $v['_' . $val['fieldname']] = $rules[$v[$val['fieldname']]];
            }
        }
    }

    /**
     * 生成筛选条件
     * @param $modelid 模型ID
     */
    public function fetchSearchCondition($fields)
    {
        global $_M;
        static $result = array();
        if(!empty($result)){
            return $result;
        }
        $where = $tags = array();
        foreach($fields as $k => $v) {
            if($v['rules']) {
                $v['rules'] = unserialize($v['rules']);
                if(preg_match('/`/', _gp($v['fieldname']))) {
                    $_GET[$v['fieldname']] = explode('`', _gp($v['fieldname']));
                }
                if(!empty($_GET[$v['fieldname']]) && is_array($_GET[$v['fieldname']])) {
                    $row = array();
                    foreach($_GET[$v['fieldname']] as $val) {
                        if(array_key_exists($val, $v['rules'])) {
                            $row[] = $val;
                        }
                    }
                    $_GET[$v['fieldname']] = $row;
                }
                elseif(array_key_exists(preg_replace('/[^\d.,]/', '', _gp($v['fieldname'])), $v['rules'])) {
                    $_GET[$v['fieldname']] = preg_replace('/[^\d.,]/', '', _gp($v['fieldname']));
                }else{
                    $_GET[$v['fieldname']] = '';
                }
            }
            switch ($v['datatype']){
                case 'multilevel':
                    $fieldname = $v['fieldname'];
                    if (strpos($v['title'], '#')) {
                        list($v['title'], $v['fieldname']) = explode('#', $v['title']);
                    }
                    $fieldvalue = (int) _gp($v['fieldname']);
                    $count = t('multilevel')->count(array('identifier'=>$v['fieldname'],'id'=>$fieldvalue));
                    $_GET[$v['fieldname']] = $count ? $fieldvalue : '';
                    $fieldvalue = (int) _gp($v['fieldname']);

                    if (empty($fieldvalue)) {
                        $fieldvalue = t('multilevel')->fetchFields(array('identifier'=>$v['fieldname'],'parentid'=>0),'id');
                    }
                    $rules = t('multilevel')->fetchList(array('parentid'=>$fieldvalue),'id,name','','displayorder');
                    if(empty($rules)){
                        $fieldvalue = t('multilevel')->fetchFields($fieldvalue,'parentid');
                        $rules = t('multilevel')->fetchList(array('parentid'=>$fieldvalue),'id,name','','displayorder');
                    }

                    $ml = t('multilevel')->fetchFields($fieldvalue,'name,parentid');
                    $v['rules'] = array();
                    if(empty($ml['parentid'])){
                        //$v['rules'][0] = '全部';
                    }else{
                        $v['rules'][$fieldvalue] = $ml['name'];
                    }
                    foreach ($rules as $val){
                        $v['rules'][$val['id']] = $val['name'];
                    }
                    break;
                case 'int':
                    $_GET[$v['fieldname']] = (int) _gp($v['fieldname']);
                    break;
                case 'tel':
                    $_GET[$v['fieldname']] = preg_replace('/[^0-9-]/', '', _gp($v['fieldname']));
                    break;
                case 'float':
                    $_GET[$v['fieldname']] = (float) _gp($v['fieldname']);
                    break;
                case 'price':
                    $_GET[$v['fieldname']] = preg_replace('/[^\d.,]/', '', _gp($v['fieldname']));
                    break;
                case 'date':
                case 'datetime':
                    $_GET[$v['fieldname']] = strtotime(_gp($v['fieldname']));
                    break;
                default:
                    $_GET[$v['fieldname']] = filterString(_gp($v['fieldname']));
            }

            $fieldvalue = _gp($v['fieldname']);
            if($fieldvalue) {
                if(!empty($_POST)) {
                    if(is_array($fieldvalue)) {
                        $_M['cururl'] .= '&' . $v['fieldname'] . '=' . implode('`', $fieldvalue);
                    } else {
                        $_M['cururl'] .= '&' . $v['fieldname'] . '=' . $fieldvalue;
                    }
                }

                if(preg_match('/^([\d.]+),([\d.]+)$/',$fieldvalue)) {
                    $where[] = db :: fbuild($v['fieldname'], $fieldvalue, 'between');
                } else {
                    if($v['datatype'] == 'checkbox') {
                        $where[] = db :: fbuild($v['fieldname'], $fieldvalue, 'find');
                    }
                    elseif($v['datatype'] == 'multilevel') {
                        $where[$fieldname] = t('multilevel')->getSubids($fieldvalue,true);
                    } else {
                        $where[$v['fieldname']] = $fieldvalue;
                    }
                }
                if(!empty($v['rules'][$fieldvalue])) {
                    $tags[] = array($v['fieldname'], $v['rules'][$fieldvalue]);
                }
            }
            $fields[$k] = $v;
        }
        //tags搜索条件提示信息，fields 参与搜索的字段，where参与搜索的sql条件
        $result = array('tags'=>$tags,'fields' => $fields, 'where' => $where);
        return $result;
    }
}