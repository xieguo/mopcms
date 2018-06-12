<?php
/**
 * 定位点信息管理
 * @copyright           (C) 2016-2099 MOPCMS.COM
 * @license             http://www.mopcms.com/license/
 */
!defined('IN_MOPCMS') && exit('Access failed');

class table_location_infolist extends base_table
{
    protected function parseData(&$data)
    {
        if(!empty($data['createtime'])){
            $data['_createtime'] = mdate($data['createtime'],'dt');
        }
        $style = '';
        if(!empty($data['color'])){
            $style .= 'color:'.$data['color'].';';
        }
        if(!empty($data['size'])){
            $style .= 'font-size:'.$data['size'].'px;';
        }
        if(!empty($data['isbold'])){
            $style .= 'font-weight:bold;';
        }
        if($style){
            $style = 'style="'.$style.'"';
        }
        if(!empty($data['title'])){
            $data['_title'] = $data['title'];
            if($style){
                $data['_title'] = '<font '.$style.'>'.$data['title'].'</font>';
            }
        }
    }
}