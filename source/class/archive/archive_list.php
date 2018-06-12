<?php
/**
 * 模型文档列表处理类
 * @copyright           (C) 2016-2099 MOPCMS.COM
 * @license             http://www.mopcms.com/license/
 */
!defined('IN_MOPCMS') && exit('Access failed');

class archive_list
{
    public $column = array();
    public $maxpages= 0;
    public $pagesize = 20;
    public $total = 0;
    private $page = 1;

    public function __construct($id)
    {
        global $_M;
        $this->column = t('column')->fetch($id);
        if($this->column) {
            $this->column['columnlink'] = t('column')->columnLink($this->column);
            $this->column['reallink'] = t('column')->reallink;
            $this->column['model'] = t('models')->fetch($this->column['modelid']);
            $this->column['position'] = t('column')->position($id);
        }
    }

    /**
     * 统计列表里的记录
     */
    private function count_record()
    {
        global $_M, $_data;
        $_data['columnid'] = $this->column['id'];
        $_data['modelid'] = $this->column['modelid'];
        //统计数据库记录
        $this->total = (int) _gp('total');
        $this->page = max(1, (int) _gp('page'));
        if(empty($this->total)) {
            $where = array();
            if($_M['sys']['list_son'] == 1) {
                $where['columnid'] = t('column')->get_cache('getSubids',$this->column['id'],true);
            } else {
                $where['columnid'] = $this->column['id'];
            }
            $this->total = t('archives')->count($where);
        }
    }

    /**
     * 获得模板文件位置
     */
    private function get_template($tmp = 'list')
    {
        $tmp = $tmp == 'list' ? $this->column['templist'] : $this->column['tempindex'];
        if (!is_file(MOPTEMPLATE.$tmp)){
            $tmp = '/default/list_default.htm';
        }
        return preg_replace("/\/{1,}/", "/", $tmp);
    }

    /**
     *  列表创建HTML
     *
     * @access    public
     * @param     string  $startpage  开始页面
     * @param     string  $makepagesize  创建文件数目
     * @return    string
     */
    public function create_html($startpage = 1, $makepagesize = 0)
    {
        global $_M, $_data;
        if(!empty($this->column['redirecturl'])) {
            return false;
        }
        if($this->column['accesstype'] == 2) {
            return false;
        }
        if(!empty($this->column['tempindex'])){
            if(empty($this->column['savedir'])){
                return false;
            }
            $htmlurl = MOPROOT.$this->column['savedir'].'/index.html';
            $this->save_html($htmlurl,'index');
            return true;
        }
        $this->count_record();
        define('MAKEHTML', 1);
        $pagesize = $startpage+$makepagesize;
        if(empty($this->total)){
            $pagesize = 1;
        }
        for($this->page = $startpage; $this->page <= $pagesize; $this->page++) {
            $_M['page'] = $this->page;
            if($this->maxpages){
                $pagesize = $this->maxpages;
            }
            if(empty($this->maxpages) || $this->page<=$this->maxpages){
                $htmlurl = $this->_htmllink($this->page);
                if(empty($htmlurl)){
                    return false;
                }
                $this->save_html(MOPROOT . $htmlurl);
            }
        }
        return true;
    }

    /**
     * 获取文档列表的存放路径
     * @param $page 翻页数
     */
    private function _htmllink($page=1)
    {
        if(empty($this->column['rulelist'])){
            return '';
        }
        $savedir = MOPROOT.'/'.$this->column['savedir'];
        return str_replace(array('[savedir]', '[columnid]', '[page]'), array($this->column['savedir'], $this->column['id'], $page), $this->column['rulelist']);
    }

    /**
     * 保存HTML页面
     * @param $htmlpath 静态文件地址
     */
    private function save_html($truefilename, $tmp = 'list')
    {
        global $_M, $_data;
        $truefilename = preg_replace('/\/{1,}/', '/', $truefilename);
        $savedir = dirname($truefilename);
        createdir($savedir) or showMsg(10025);
        $_GET['page'] = $this->page;
        $_data += $this->column;
        $_M['_temp'] = $this->get_template($tmp);
        include template($_M['_temp'], '', true);
        $str = output();
        file_put_contents($truefilename,$str) or showMsg(11102);
        //生成默认首页
        if($this->page==1){
            file_put_contents(MOPROOT.$this->column['savedir'].'/index.html',$str);
        }
    }

    /**
     * 动态显示列表
     */
    public function display()
    {
        global $_M, $_data;
        if(empty($this->column)){
            return getResult(10001);
        }
        require_once loadlib('file');
        $_data += $this->column;
        $this->column['page'] = max(1, (int) _gp('page'));
        if(!empty($this->column['redirecturl'])) {
            return $this->column['redirecturl'];
        }
        if(!empty($this->column['tempindex'])){
            $tmp = $this->get_template('index');
        }else{
            $this->count_record();
            $tmp = $this->get_template();
            $this->column['pagelist'] = $this->get_page();
        }
        include template($tmp, '', true);
        exit;
    }

    /**
     * 获取分页列表
     * @param $dynamic 是否动态链接
     */
    public function get_page($dynamic = true)
    {
        global $_M;
        $pre = $this->page - 1;
        $next = $this->page + 1;
        $list_len = 3;
        if($this->total == 0) {
            return '';
        }
        $this->maxpages = max(1, ceil($this->total / $this->pagesize));
        if($this->maxpages<=1){
            return '';
        }
        $prepage = $nextpage = '';
        $maininfo = '';//"<a class=\"btn btn-boo\">共 <b>".$this->maxpages."</b>页<b>".$this->total."</b>条</a>";//显示不下了，需要的话就显示出来
        //获得上一页和主页的链接
        if($this->page != 1) {
            if($dynamic === true) {
                $url1 = pseudoUrl($_M['cururl']. '&page=' . $pre);
                $url2 = pseudoUrl($_M['cururl']);
            } else {
                $url1 = t('column')->columnLink($this->column,$pre,'');
                $url2 = t('column')->columnLink($this->column);
            }
            $prepage = "<a href='" . $url1 . "' class=\"previous btn\">上一页</a>";
            $indexpage = "<a href='" . $url2 . "' class=\"btn\">首页</a>";
        } else {
            $indexpage = '';
        }

        //下一页,未页的链接
        if($this->page != $this->maxpages && $this->maxpages > 1) {
            if($dynamic === true) {
                $url1 = pseudoUrl($_M['cururl']. '&page=' . $next);
                $url2 = pseudoUrl($_M['cururl']. '&page=' . $this->maxpages);
            } else {
                $url1 = t('column')->columnLink($this->column, $next,'');
                $url2 = t('column')->columnLink($this->column, $this->maxpages,'');
            }
            $nextpage = "<a href='" . $url1 . "' class=\"btn btn-boo\">下一页</a>";
            $endpage = "<a href='" . $url2 . "' class=\"btn btn-boo\">末页</a>";
        } else {
            $endpage = '';
        }

        //获得数字链接
        $listdd = "";
        $total_list = $list_len * 2 + 1;
        if($this->page >= $total_list) {
            $j = $this->page - $list_len;
            $total_list = $this->page + $list_len;
            if($total_list > $this->maxpages) {
                $total_list = $this->maxpages;
            }
        } else {
            $j = 1;
            if($total_list > $this->maxpages) {
                $total_list = $this->maxpages;
            }
        }
        for($j; $j <= $total_list; $j++) {
            if($j == $this->page) {
                $listdd .= "<a class=\"btn btn-envato active\">$j</a>";
            } else {
                if($dynamic === true) {
                    $url = pseudoUrl($_M['cururl']. '&page=' . $j);
                } else {
                    $url = t('column')->columnLink($this->column, $j,'');
                }
                $listdd .= "<a href='" . $url . "' class=\"btn btn-boo\">" . $j . "</a>";
            }
        }
        if($this->page>=$this->maxpages){
            $this->total = $this->maxpages;
        }
        return '<span class="btn-group paging_full_numbers" style="margin:15px 0;">'.$indexpage . $prepage . $listdd . $nextpage . $endpage . $maininfo.'</span>';
    }
}