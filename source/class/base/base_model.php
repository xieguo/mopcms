<?php
/**
 * 模型基类
 * @copyright			(C) 2016-2099 MOPCMS.COM
 * @license				http://www.mopcms.com/license/
 */
!defined('IN_MOPCMS') && exit('Access failed');
class base_model
{
    public function __call($name, $arguments)
    {
        base_error :: error(10030);
    }
    /**
     * 必填字段检查
     * @param $requireds
     * @version 1.0
     */
    protected function _checkRequiredFields($requireds)
    {
        if(empty($requireds)) {
            return getResult(100);
        }
        $attachcheck = defined('ATTACHCHECK') && ATTACHCHECK===true;//附件、图片类的是否做必须判断
        foreach($requireds as $v) {
            switch ($v['datatype']){
                case 'checkbox':
                    if(empty($_GET[$v['fieldname']])) {
                        return getResult(10007, '', array('name' => $v['title']));
                    }
                    break;
                case 'multilevel':
                    $fieldname = $v['fieldname'];
                    if (strpos($v['title'], '#')) {
                        list($v['title'], $fieldname) = explode('#', $v['title']);
                    }
                    $val = (int)_gp($fieldname);
                    if(empty($val)) {
                        return getResult(10007, '', array('name' => $v['title']));
                    }
                    break;
                case 'img':
                case 'media':
                case 'addon':
                    if($attachcheck && empty($_FILES[$v['fieldname']]['tmp_name'])) {
                        return getResult(10007, '', array('name' => $v['title']));
                    }
                    break;
                case 'imgs':
                    session_start();
                    if($attachcheck && empty($_SESSION['bigfile_info'])){
                        return getResult(10007, '', array('name' => $v['title']));
                    }
                    break;
                default:
                    if(!_gp($v['fieldname'])) {
                        return getResult(10007, '', array('name' => $v['title']));
                    }
            }
        }
        return getResult(100);
    }

    /**
     * 数据字段是级联类型的对应关系转换过来
     * @param unknown_type $data
     */
    protected function multilevel_change(&$data)
    {
        foreach ($data as $k=>$v){
            if(!empty($v['datatype']) && $v['datatype'] == 'multilevel') {
                if (strpos($v['title'], '#')) {
                    list($v['title'], $v['fieldname']) = explode('#', $v['title']);
                }
                $data[$k] = $v;
            }
        }
    }

    /**
     * 数据导出
     * @param $id 表单ID
     * @param $fieldlist 字段列表
     * @param $datalist 数据列表
     */
    protected function export($id,$fieldlist,$datalist)
    {
        header("Content-Type:application/vnd.ms-excel");
        header('Content-Disposition:filename=data_'.$id.'.xls');
        header("Pragma: no-cache");
        header("Expires: 0");
        $html = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
				<head>
			   <meta http-equiv="expires" content="Mon, 06 Jan 1999 00:00:01 GMT">
			   <meta http-equiv=Content-Type content="text/html; charset=utf-8">
			   <!--[if gte mso 9]><xml>
			   <x:ExcelWorkbook>
			   <x:ExcelWorksheets>
				 <x:ExcelWorksheet>
				 <x:Name></x:Name>
				 <x:WorksheetOptions>
				   <x:DisplayGridlines/>
				 </x:WorksheetOptions>
				 </x:ExcelWorksheet>
			   </x:ExcelWorksheets>
			   </x:ExcelWorkbook>
			   </xml><![endif]-->
			  </head>';
        $html.='<table border="0" cellspacing="0" cellpadding="0"><tr> ';
        $html.="<td>序列号</td>";
        foreach ($fieldlist as $v) {
            if($v['datatype']=='multilevel' && strpos($v['title'],'#')){
                list($v['title'],) = explode('#',$v['title']);
            }
            $html .= '<td>' . $v['title'] . '</td>';
        }
        $html.="<td>IP</td>";
        if(pluginIsAvailable('member')){
            $html.="<td>会员</td>";
        }
        $html.="<td>创建日期</td>";
        $html.="<td>审核状态</td>";
        $html.="</tr>\n";
        $formdata = new mop_formdata;
        foreach ($datalist as $v) {
            $formdata->get_value_from_db($fieldlist, $v);
            $html.="<tr>";
            $html.="<td style='vnd.ms-excel.numberformat:#'>" . $v['id'] . "</td>";
            foreach ($fieldlist as $val) {
                if($val['datatype']=='img'){
                    $html .= '<td style="vnd.ms-excel.numberformat:@" height=110 width=170><img src="'.imageResize($v[$val['fieldname']]).'" height=100></td>';
                }
                elseif($val['datatype']=='imgs'){
                    $imgs = array();
                    foreach($v['_'.$val['fieldname']] as $val){
                        $imgs[] = '<img src="'.imageResize($val['img']).'" width=100>&nbsp;';
                    }
                    $html .= '<td style="vnd.ms-excel.numberformat:@" height=110 width=170>'.implode(' ', $imgs).'</td>';
                }else{
                    $html.="<td style='vnd.ms-excel.numberformat:@'>" . $v['_'.$val['fieldname']] . "</td>";
                }
            }
            $html.="<td style='vnd.ms-excel.numberformat:@'>" . $v['ip'] . "</td>";
            if(pluginIsAvailable('member')){
                $username = t('member')->fetchFields($v['mid'],'username');
                $username = $username?$username:'';
                $html.="<td style='vnd.ms-excel.numberformat:@'>" . $username . "</td>";
            }
            $html.="<td style='vnd.ms-excel.numberformat:@'>" . $v['_createtime'] . "</td>";
            $html.="<td style='vnd.ms-excel.numberformat:@'>" . $v['_ischeck'] . "</td>";
            $html.="</tr>";
        }
        $html.='</table>';
        return $html;
    }

    /**
     * 生成密码
     * @param unknown_type $pwd
     */
    protected function create_pwd($pwd)
    {
        global $_M;
        return substr(md5($pwd . $_M['config']['authkey']), 5, 20);
    }

    /**
     * 删除图片
     * @param type $pic 图片地址
     * @param type $path 目录地址
     * @return boolean
     */
    public function del_image($pic, $path = '')
    {
        return $this->_del_image($pic, $path);
    }
    protected function _del_image($pic, $path = '')
    {
        if(empty($pic)){
            return false;
        }
        $path = $path ? '/' . $path : '';
        if($pics = t('uploads')->fetchLikeUrl($pic)) {
            foreach($pics as $v) {
                t('uploads')->delete($v['id']);
                $upfile = MOPROOT . $path . $v['url'];
                if(@ is_file($upfile) && !preg_match('/nopic/i', $upfile)) {
                    @ unlink($upfile);
                } else {
                    $upfile = MOPROOT . $v['url'];
                    if(@ is_file($upfile) && !preg_match('/nopic/i', $upfile)) {
                        @ unlink($upfile);
                    }
                }
            }
            return true;
        }
        $upfile = MOPROOT . $path . $pic;
        if(@ is_file($upfile) && !preg_match('/nopic/i', $upfile)) {
            @ unlink($upfile);
            return true;
        } else {
            $upfile = MOPROOT . $pic;
            if(@ is_file($upfile) && !preg_match('/nopic/i', $upfile)) {
                @ unlink($upfile);
                return true;
            }
        }
        return false;
    }

    public function info_list($post)
    {
        if(empty($post['table'])){
            return getResult(10001);
        }
        $table = $post['table'];
        unset($post['table']);
        $_data = t($table)->info_list($post);
        return getResult(100,$_data);
    }
}