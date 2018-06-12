<?php
/**
 * 后台静态文件生成控制类
 * @copyright			(C) 2016-2099 MOPCMS.COM
 * @license				http://www.mopcms.com/license/
 */
!defined('IN_MOPCMS') && exit('Access failed');
class control_admin_buildhtml extends control_admin
{
    public function buildhtml_index()
    {
        return $this->output();
    }
    
    /**
     * 栏目静态文件生成页面
     */
    public function buildhtml_column()
    {
        global $_M;
        if(_gp('buildhtml')) {
            $this->_buildhtml_column();
        } else {
            $columnid = (int) _gp('columnid');
            $_data = array();
            $_data['options'] = t('column')->getOptions($columnid, '', true);
            return $this->output($_data);
        }
    }
    private function _buildhtml_column()
    {
        global $_M,$_data,$arc;
        $columnid = (int) _gp('columnid');
        $updatesub = (int) _gp('updatesub');
        $index = (int) _gp('index');
        $mkpage = max(1, (int) _gp('mkpage'));
        $maxpagesize = _gp('maxpagesize') ? (int) _gp('maxpagesize') : 50;

        //检测获取所有栏目ID
        if($updatesub == 1) {
            if($columnid=='0'){
                $columnids = t('column')->get_cache('getSubids', $columnid);
            }else{
                $columnids = t('column')->get_cache('getSubids', $columnid, true);
            }
        } else {
            if(empty($columnid)){
                showMsg(11101);
            }
            $columnids = array($columnid);
        }

        //当前更新栏目的ID
        if(isset($columnids[$index])) {
            $tid = $columnids[$index];
        } else {
            showMsg(11100);
        }

        $finish = false;
        if(!empty($tid)) {
            $arc = new archive_list($tid);
            $arc->create_html($mkpage, $maxpagesize);

            $mkpage = $mkpage + $maxpagesize;
            if($mkpage >= $arc->maxpages) {
                $finish = true;
            }
        }

        $nextpage = $index +1;
        if($nextpage >= count($columnids) && $finish) {
            showMsg('完成所有栏目列表更新！<a href="?mod=column&ac=view&id='.$tid.'" target="_blank">浏览</a>');
        } else {
            if($finish) {
                $gourl = $_M['cururl'] . '&updatesub=' . $updatesub . '&maxpagesize=' . $maxpagesize . '&columnid=' . $columnid . '&index=' . $nextpage . '&mkpage=';
                showMsg('成功创建栏目(' . $tid . ')：，继续进行操作！', $gourl, '', 100);
            } else {
                $gourl = $_M['cururl'] . '&updatesub=' . $updatesub . '&mkpage=' . $mkpage . '&maxpagesize=' . $maxpagesize . '&columnid=' . $columnid . '&index=' . $index;
                showMsg('栏目(' . $tid . ')：，已生成页数' . $mkpage, $gourl, '', 100);
            }
        }
    }

    /**
     * 文档静态文件生成页面
     */
    public function buildhtml_archives()
    {
        global $_M;
        if(_gp('buildhtml')) {
            $this->_buildhtml_archives();
        } else {
            $columnid = (int) _gp('columnid');
            $_data = array();
            $_data['options'] = t('column')->getOptions($columnid, '', true);
            return $this->output($_data);
        }
    }
    private function _buildhtml_archives()
    {
        global $_M,$_data;
        $columnid = (int) _gp('columnid');
        $start = (int) _gp('start');
        $pagesize = _gp('pagesize') ? (int) _gp('pagesize') : 50;
        $totalnum = (int) _gp('totalnum');
        $starttime = !empty($_GET['starttime']) ? preg_replace('/[^\d]/', '', _gp('starttime')) : TIME;

        //获取条件
        $where = array();
        $where['status'] = 1;
        $where['ishtml'] = 1;
        $where['redirecturl'] = '';
        if($columnid) {
            $where['columnid'] = t('column')->get_cache('getSubids', $columnid, true);
        }

        //统计记录总数
        if(empty($totalnum)) {
            $totalnum = t('archives')->count($where);
        }

        $tjnum = $start;
        $row = t('archives')->fetchList($where, 'id', $start . ',' . $pagesize);
        foreach($row as $v) {
            $tjnum++;
            $arc = new archive_view($v['id']);
            $arc->create_html();
        }

        $t2 =(microtime(true) - $_M['starttime']);
        $ttime = number_format(((TIME - $starttime) / 60), 2);

        //返回提示信息
        $tjlen = $totalnum > 0 ? ceil(($tjnum / $totalnum) * 100) : 100;
        $dvlen = $tjlen * 2;
        $tjsta = "<div style='width:200;height:15;border:1px solid #898989;text-align:left'><div style='width:$dvlen;height:15;background-color:#829D83'></div></div>";
        $tjsta .= '<br/>本次用时：' . number_format($t2, 2) . '，总用时：' . $ttime . ' 分钟，到达位置：' .($start + $pagesize) . "<br/>完成创建文件总数的：$tjlen %，继续执行任务...";

        if($tjnum < $totalnum) {
            $nurl = $_M['cururl'] . '&columnid=' . $columnid . '&totalnum=$totalnum&start=' .($start + $pagesize) . '&pagesize=' . $pagesize . '&starttime=' . $starttime;
            showMsg($tjsta, $nurl, '', 100);
        } else {
            if($columnid) {
                showMsg('生成文件：' . $totalnum . ' 总用时：' . $ttime . ' 分钟，现转向当前栏目更新&gt;&gt;', '?mod=' . $_M['mod'] . '&ac=column&columnid=' . $columnid . '&buildhtml=1');
            } else {
                showMsg('完成所有创建任务！，生成文件：' . $totalnum . ' 总用时：' . $ttime . ' 分钟。');
            }
        }
    }
}