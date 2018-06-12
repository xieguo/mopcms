<?php

/**
 * 后台栏目处理类
 * @copyright			(C) 2016-2099 MOPCMS.COM
 * @license				http://www.mopcms.com/license/
 */
!defined('IN_MOPCMS') && exit('Access failed');

class table_admin_menu extends base_table
{

    /**
     * 后台栏目分类多维数组
     * @param int $parentid 上级ID
     */
    public function menuList($parentid = 0)
    {
        $row = (array) $this->fetchListLoop(array('parentid' => $parentid, 'isshow' => 1), '*', '', 'pluginid asc,displayorder desc,id');
        $arr = array();
        foreach($row as $k => $v) {
            $v['submenu'] = (array) $this->menuList($v['id']);
            $arr[$v['id']] = $v;
        }
        return $arr;
    }

    public function parseData(&$data)
    {
        $url = '';
        if(!empty($data['mod'])) {
            $data['purview'] = $data['mod'];
            $url = '?mod=' . $data['mod'];
        }
        if(!empty($data['ac'])) {
            $url .= '&ac=' . $data['ac'];
            $data['purview'] .= '_'.$data['ac'];
        }
        if(!empty($data['do'])) {
            $url .= '&do=' . $data['do'];
            $data['purview'] .= '_'.$data['do'];
        }
        if(!empty($data['op'])) {
            $url .= '&op=' . $data['op'];
            $data['purview'] .= '_'.$data['op'];
        }
        if(!empty($data['pluginid']) && !empty($data['purview'])) {
            $url .= '&pluginid=' . $data['pluginid'];
            $data['purview'] = $data['pluginid'].':'.$data['purview'];
        }
        if(!empty($url)) {
            $data['url'] = $url.'&menuid='.aval($data,'id');
        }
        if(!empty($data['redirecturl'])) {
            $data['url'] = $data['redirecturl'];
        }
    }

    /**
     * 获取顶级分类ID
     * @param int $id
     */
    public function getTopid($id)
    {
        if(empty($id)) {
            return 0;
        }
        $row = $this->fetchFields($id, 'id,parentid');
        if(!$row) {
            return 0;
        }
        if($row['parentid'] == 0) {
            return $id;
        }
        return $this->getTopid($row['parentid']);
    }

    /**
     * 当前位置
     * @param int $id
     */
    public function getLocal($id = '')
    {
        $list = array();
        if(empty($id)) {
            return $list;
        }
        $row = $this->fetch($id);
        if(!empty($row['parentid'])) {
            $list = $this->getLocal($row['parentid']);
        }
        $list[] = $row;
        return $list;
    }
}