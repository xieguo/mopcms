<?php
/**
 * 标签调用函数库
 * @copyright			(C) 2016-2099 MOPCMS.COM
 * @license				http://www.mopcms.com/license/
 */
!defined('IN_MOPCMS') && exit('Access failed');

/**
 * 模型表单显示标签调用
 */
function modelFields($row)
{
    global $_data;
    $row = json_decode($row,true);
    if(empty($row['modelid'])) {
        return array();
    }
    $_data['attach'] = !empty($_data['attach'])?$_data['attach']:array();
    return t('models_fields')->getFormFields($row['modelid'], $_data['attach']);
}

/**
 * 模型文档标签调用标签
 */
function arclist($row)
{
    global $_M;
    $row = json_decode($row,true);
    $row['words'] = !empty($row['words']) ? filterString($row['words']) : '';
    $where = array();

    //按不同情况设定SQL条件 排序方式
    if(empty($row['idlist'])) {
        if(!empty($row['where'])) {
            $where[] = urldecode($row['where']);
        }
        //如果开启会员插件
        if(pluginIsAvailable('member')) {
            if(!empty($row['mid'])) {
                $where['arc.mid'] = (int) $row['mid'];
            }
            if(!empty($row['diysort'])) {
                $where['arc.diysort'] = (int) $row['diysort'];
            }
        }

        if(!empty($row['days']) && $row['days'] > 0) {
            $limitday = TIME -($row['days'] * 86400);
            $where[] = db :: fbuild('arc.createtime', $limitday, '>');
        }
        //关键字条件
        if(!empty($row['words'])) {
            $keys = array();
            foreach(explode(',', str_replace(' ', ',', $row['words'])) as $val) {
                if($val) {
                    $keys[] = $val;
                }
            }
            if($keys) {
                $where[] = db :: fbuild('CONCAT(arc.title,arc.keywords)', implode('|', $keys), 'regexp');
            }
        }
        //文档属性
        if(!empty($row['flag'])) {
            $flags = explode(',', $row['flag']);
            for($i = 0; isset($flags[$i]); $i++) {
                $where[] = db :: fbuild('arc.flag', $flags[$i], 'find');
            }
        }
        if(!empty($row['columnid'])) {
            if(empty($row['modelid'])) {
                $row['modelid'] = t('column')->fetchFields($row['columnid'], 'modelid');
            }
            if($_M['sys']['list_son'] == 1) {
                if(is_string($row['columnid']) && preg_match("/,/", $row['columnid'])) {
                    $columnids = array();
                    foreach(explode(',', $row['columnid']) as $v) {
                        $columnids[] = t('column')->getSubids($v, true);
                    }
                } else {
                    $columnids = t('column')->getSubids($row['columnid'], true);
                }
            }else{
                $columnids = $row['columnid'];
            }
            $where['arc.columnid'] = $columnids;
        }

        if(!empty($row['noflag'])) {
            $noflags = explode(',', $row['noflag']);
            for($i = 0; isset($noflags[$i]); $i++) {
                $where[] = db :: fbuild('arc.flag', $noflags[$i], 'nofind');
            }
        }
    } else {
        $where['arc.id'] = explode(',', $row['idlist']);
    }

    if(!empty($row['modelid'])) {
        $row['modelid'] = (int) $row['modelid'];
        $where['arc.modelid'] = $row['modelid'];
        $para['table'] = t('models')->fetchFields($row['modelid'], 'tablename');
    }
    if(defined('ISMANAGE')) {
        $where[] = db :: fbuild('arc.status', 0, '>=');
    }else{
        $where[] = db :: fbuild('arc.status', 0, '>');
    }

    $para['where'] = $where;
    if(!empty($row['count'])) {
        return t('archives')->countByCondition($para);
    }
    if (!empty($row['sc'])){
        $para['sc'] = $row['sc'];
    }
    if (!empty($row['limit'])){
        $para['limit'] = $row['limit'];
    }
    if (!empty($row['order'])){
        $para['order'] = $row['order'];
    }
    if (!empty($row['pagesize'])){
        $para['pagesize'] = $row['pagesize'];
    }
    if (!empty($row['fields'])){
        $para['fields'] = $row['fields'];
    }
    if (!empty($row['titlelen'])){
        $para['titlelen'] = $row['titlelen'];
    }
    if (!empty($row['imgs'])){
        $para['imgs'] = $row['imgs'];
    }
    return t('archives')->listByCondition($para);
}

/**
 * 栏目翻页列表标签调用标签
 */
function infolist($row)
{
    global $_M, $_data, $arc;
    $row = json_decode($row,true);
    $list = !empty($row['modelid'])?t('models_fields')->fetchSearchCondition($row['modelid']):'';
    if(!empty($list['where'])){
        $row['where'] = t('archives')->buildWhere($list['where']);
    }
    if(!empty($row['columnid']) && is_object($arc)) {
        $arc->pagesize = $row['pagesize'] = !empty($row['pagesize']) ? $row['pagesize'] : 20;
        $row['limit'] =(($_M['page'] -1) * $row['pagesize']) . ',' . $row['pagesize'];
        $show = defined('CURSCRIPT') && CURSCRIPT == 'main' && $_M['mod']=='archives' && $_M['ac']=='list' ? true : false;
        $row['count'] = 1;
        $_data['total'] = $arc->total = arclist(json_encode($row));
        $_data['pagelist'] = $arc->get_page($show);
        unset($row['count']);
    }

    $row['order'] = !empty($row['order'])?$row['order']:filterString(_gp('order'));
    $row['sc'] = !empty($row['sc'])?$row['sc']:filterString(_gp('sc'));
    return arclist(json_encode($row));
}

/**
 * 获取筛选条件标签
 * @param $row
 */
function searchFields($row)
{
    global $_M, $_data;
    $row = json_decode($row,true);
    $type = !empty($row['type']) && $row['type']=='tags' ? 'tags' : 'fields';
    if (empty($row['modelid'])){
        return array();
    }
    $id = (int)_gp('id');
    if(defined('MAKEHTML')){
        $id = $_data['id'];
        $cururl = $_M['sys']['basehost'] . "/main.php?mod=archives&ac=list&id=" . $id;
    }else{
        $cururl = $_M['cururl'];
    }
    $list = t('models_fields')->get_cache('fetchSearchCondition', $row['modelid']);
    if($type=='fields'){
        foreach ($list['fields'] as $k=>$v){
            $rules = $v['rules'];
            $v['rules'] = array();
            foreach ($rules as $key=>$val){
                $arr = array();
                $arr['link'] = pseudoUrl($cururl.'&'.$v['fieldname'].'='.$key);
                $arr['active'] = $key==_gp($v['fieldname'])?'active':'';
                $arr['value'] = $key;
                $arr['name'] = $val;
                $v['rules'][$key] = $arr;
            }
            $list['fields'][$k] = $v;
        }
    }
    return $list[$type];
}
/**
 * 参与排序字段列表标签
 */
function orderbyFields($row)
{
    global $_M, $_data;
    $row = json_decode($row,true);
    if (empty($row['modelid'])){
        return array();
    }
    $id = (int)_gp('id');
    $sc = filterString(_gp('sc'));
    $order = filterString(_gp('order'));
    $list = t('models_fields')->get_cache('getOrderByFields', $row['modelid']);
    if(defined('MAKEHTML')){
        $id = $_data['id'];
        $cururl = $_M['sys']['basehost'] . "/main.php?mod=archives&ac=list&id=" . $id;
    }else{
        $cururl = $_M['cururl'];
    }
    foreach ($list as $k=>$v){
        $url = $cururl.'&order='.$v['fieldname'].'&sc='.($sc=='asc'?'desc':'asc');
        $v['link'] = pseudoUrl($url);
        $v['order'] = $order;
        $v['sc'] = $sc=='asc'?'up':'down';
        $list[$k] = $v;
    }
    return $list;
}

/**
 * 栏目调用标签
 */
function column($row)
{
    $row = json_decode($row,true);
    $order = !empty($row['order']) ? $row['order'] : 'displayorder';
    $sc = !empty($row['sc']) ? $row['sc'] : 'desc';
    $limit = !empty($row['limit']) ? $row['limit'] : '';
    $infolist = array();
    if($row['type'] == 'top') {
        $infolist = t('column')->fetchList(array('parentid' => 0, 'ishidden' => 0), '*', $limit, $order, $sc);
    }
    elseif($row['type'] == 'parent' && !empty($row['columnid'])) {
        $parentid = t('column')->fetchFields($row['columnid'], 'parentid');
        $infolist = t('column')->fetchList(array('parentid' => $parentid, 'ishidden' => 0), '*', $limit, $order, $sc);
    }
    elseif($row['type'] == 'son' && !empty($row['columnid'])) {
        $infolist = t('column')->fetchList(array('parentid' => $row['columnid'], 'ishidden' => 0), '*', $limit, $order, $sc);
    }
    elseif($row['type'] == 'this' && !empty($row['columnid'])) {
        $infolist = t('column')->fetchList($row['columnid']);
    }
    foreach($infolist as $k => $v) {
        $v['selected'] = false;
        if(isset($row['id']) && $v['id'] == $row['id']) {
            $v['selected'] = true;
        }
        $v['link'] = t('column')->columnLink($v['id']);
        $infolist[$k] = $v;
    }
    return $infolist;
}

/**
 * 相关文档标签
 * @param $row
 */
function relateArchives($row)
{
    $row = json_decode($row,true);
    if(!empty($row['pcolumnid'])) {
        $row['columnid'] = $row['pcolumnid'];
    }
    if(!empty($row['id'])) {
        if(empty($row['where'])){
            $row['where'] = 'arc.id <> ' . $row['id'];
        }else{
            $row['where'] .= ' AND arc.id <> ' . $row['id'];
        }
    }
    return arclist(json_encode($row));
}

/**
 * 专题报名表单标签
 * @param $row
 */
function entryField($row)
{
    $row = json_decode($row,true);
    if(empty($row['sid'])) {
        return array();
    }
    return t('specials_fields')->getFormFields($row['sid']);
}

/**
 * 自定义表单标签
 * @param unknown_type $row
 */
function customformField($row)
{
    $row = json_decode($row,true);
    if(empty($row['formid'])) {
        return array();
    }
    $_data = array();
    if(!empty($row['id'])) {
        $tablename = t('customform')->fetchFields($row['formid'],'tablename');
        $_data = t($tablename)->fetch($row['id']);
    }
    return t('customform_fields')->getFormFields($row['formid'], $_data);
}

/**
 * 定位点标签
 * @param unknown_type $row
 */
function pointlist($row)
{
    $row = json_decode($row,true);
    $cache = !empty($row['cache'])?$row['cache']:300;
    unset($row['cache']);
    $res = controlCache('admin_location','location_infolist',$row,$cache);
    if($res['code']!=100){
        return array();
    }
    $pos = &$res['data']['point'];
    $infos = &$res['data']['list']; 
    $i = 0;
    foreach($infos as $k => $v) {
        if(empty($v['aid'])){
            continue;
        }
        $i++;
        $v['i'] = $i;
        if(!empty($row['fields'])) {
            $fields = array();
            $modelid = t('archives')->fetchFields($v['aid'], 'modelid');
            foreach(explode(',', $row['fields']) as $val) {
                if(t('models_fields')->count(array('id' => $modelid, 'fieldname' => trim($val)))){
                    $fields[] = $val;
                }
            }
            $row['fields'] = implode(',', $fields);
        }else{
            $row['fields'] = '';
        }
        $v['archives'] = archiveInfo($v['aid'], $row['fields']);
        $v['link'] = &$v['archives']['link'];
        $title = $v['title'];
        $v['title'] = $v['_title'];
        if(!empty($pos['titlelen'])){
            $tit = mb_substr(htmlspecialchars_decode($title), 0, $pos['titlelen'], 'utf-8');
            $v['title'] = str_replace($title, $tit, $v['_title']);
        }
        $v['summary'] = !empty($v['summary']) ?(!empty($pos['summarylen']) ? mb_substr($v['summary'], 0, $pos['summarylen'], 'utf-8') : $v['summary']) : '';
        $classstr = !empty($row['class']) ? " class='" . $row['class'] . "'" : "";
        $targetstr = !empty($row['target']) ? " target='" . $row['target'] . "'" : "";
        if(!empty($v['thumb'])){
            $v['thumb'] = imageResize($v['thumb'], $pos['imgwidth'], $pos['imgheight']);
            $v['img'] = '<img src="' . $v['thumb'] . '" width="' . $pos['imgwidth'] . '" height="' . $pos['imgheight'] . '" border=0 alt="' . strip_tags($v['_title']) . '">';
            $v['imglink'] = '<a href="' . $v['link'] . '" '.$classstr.' '.$targetstr.'>'.$v['img'].'</a>';
        }
        $v['textlink'] = '<a href="' . $v['link'] . '" '.$classstr.' '.$targetstr.'>' . $v['title'] . '</a>';
        $infos[$k] = $v;
    }
    return $infos;
}

/**
 * 获取级联数据标签
 * @param unknown_type $row
 */
function multilevel($row)
{
    $row = json_decode($row,true);
    $cache = !empty($row['cache'])?$row['cache']:300;
    unset($row['cache']);
    $res = controlCache('main_multilevel','multilevelList',$row,$cache);
    return !empty($res['data'])?$res['data']:array();
}

/**
 * 专题报名数据调用标签
 * @param unknown_type $row
 */
function entryData($row)
{
    $row = json_decode($row,true);
    $cache = !empty($row['cache'])?$row['cache']:300;
    unset($row['cache']);
    $res = controlCache('archives_specials','entryLimit',$row,$cache);
    return !empty($res['data'])?$res['data']:array();
}

/**
 * 自定义表单数据调用标签
 * @param $row
 */
function customformData($row)
{
    $row = json_decode($row,true);
    $cache = !empty($row['cache'])?$row['cache']:300;
    unset($row['cache']);
    $res = controlCache('main_customform','dataLimit',$row,$cache);
    return !empty($res['data'])?$res['data']:array();
}