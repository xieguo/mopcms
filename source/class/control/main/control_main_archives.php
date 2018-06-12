<?php
/**
 * 文档相关控制类
 * @copyright			(C) 2016-2099 MOPCMS.COM
 * @license				http://www.mopcms.com/license/
 */
!defined('IN_MOPCMS') && exit('Access failed');
class control_main_archives extends control_main
{
    public function archives_list()
    {
        global $arc;
        $id = (int)_gp('id');
        $arc = new archive_list($id);
        return $arc->display();
    }

    public function archives_view()
    {
        global $arc;
        $id = (int)_gp('id');
        require_once loadlib('archives');
        $arc = new archive_view($id);
        return $arc->display();
    }

    /**
     * 生成静态页
     */
    public function archives_makehtml()
    {
        global $_M,$arc;
        $id = (int)_gp('id');
        $type = filterString(_gp('type'));
        $cycle = (int)_gp('cycle');
        $cycle = $cycle>0?$cycle:86400;
        if($type=='a'){
            $updatehtmltime = t('archives')->fetchFields($id,'updatehtmltime');
            if($updatehtmltime && ($updatehtmltime+$cycle<TIME)){
                loadm('archives')->archives_makehtml($id);
            }
        }
        elseif($type=='s'){
            $updatehtmltime = t('singlepage')->fetchFields($id,'updatehtmltime');
            if($updatehtmltime && ($updatehtmltime+$cycle<TIME)){
                loadm('admin_singlepage')->refresh($id);
            }
        }
        elseif($type=='t'){
            $arc = new archive_list($id);
            if(empty($arc)){
                exit;
            }
            $filemtime = 0;
            if(is_file($arc->column['reallink'])){
                $filemtime = filemtime($arc->column['reallink']);
            }
            $filetime=$filemtime+$cycle;
            if($filetime && $filetime<TIME){
                $arc->create_html($_M['page']);
            }
        }
        exit;
    }

    /**
     * 对某条评论顶踩操作
     */
    public function archives_dcviews()
    {
        global $_M;
        $_M['mcallback'] = true;
        $id = (int)_gp('id');
        $type = filterString(_gp('type'));
        return loadm('archives')->dcviews($id,$type);
    }

    public function archives_search()
    {
        global $_M;
        $row = array();
        $row['words'] = filterString(_gp('words'));
        $row['url'] = $this->cururl($row);
        $row['where'] = db::fbuild('arc.status', 0, '>');
        $result = array('list'=>array(),'pagehtml'=>'');
        if(!empty($row['words'])){
            $result = t('archives')->archivesListPages($row);
        }
        $result['words'] = $row['words'];
        return $this->output($result);
    }

    /**
     * AJAX获取文档列表数据
     */
    public function archives_list_ajaxdata()
    {
        global $_M;
        $post = array();
        $post['columnid'] = (int)_gp('id');
        $post['words'] = filterString(_gp('words'));
        $post['pagesize'] = 10;
        $post['time'] = preg_replace('/[^\d]/', '', _gp('time'));
        $post['page'] = $_M['page'];
        return loadm('archives')->list_ajaxdata($post);
    }
}