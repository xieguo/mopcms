<?php
/**
 * 模型文档详细处理类
 * @copyright           (C) 2016-2099 MOPCMS.COM
 * @license             http://www.mopcms.com/license/
 */
!defined('IN_MOPCMS') && exit('Access failed');

class archive_view
{
    public $arc = array();
    private $htmltext_field = '';
    private $split_fields = '';
    private $split_titles = '';
    private $maxpages= 1;
    private $page = 1;
    private $pathinfo = '';
    private $dynamic_url = '';

    public function __construct($id)
    {
        global $_M;
        $this->arc = archiveInfo($id, '*');
        $this->arc['column']['position'] = t('column')->position($this->arc['columnid']);
        $this->dynamic_url = $_M['sys']['basehost']."/main.php?mod=archives&ac=view&id=".$this->arc['id'];
        $this->page = $_M['page'];
        //不同频道下加载相应频道配置等待完善
        //.....
    }

    /**
     *  解析附加表的HTML内容
     */
    private function analysis_htmltext()
    {
        //处理要分页显示的字段
        $this->split_titles = array();
        $this->htmltext_field = t('models_fields')->fetchFields(array('modelid' => $this->arc['modelid'], 'datatype' => 'htmltext'), 'fieldname');
        if(!empty($this->arc['attach'][$this->htmltext_field])) {
            include_once loadlib('archives');
            $this->keys_link(mstripslashes($this->arc['attach'][$this->htmltext_field]));
            $this->split_fields = explode('#page#', $this->arc['attach'][$this->htmltext_field]);
            foreach($this->split_fields as $k => $v) {
                $str = msubstr($v, 100);
                $pos = strpos($str, '#title#');
                if($pos > 0) {
                    $this->split_fields[$k] = preg_replace("/^(.*)#title#/is", "", $v);
                    $this->split_titles[$k] = trim(msubstr($str, $pos));
                } else {
                    continue;
                }
            }
            $this->maxpages = count($this->split_fields);
            $this->page = $this->page > $this->maxpages? $this->maxpages: $this->page;
        }
    }

    /**
     * 将文章内容中设置的关键词加连接并过滤html代码中相应的关键词
     * @param type 文章内容
     */
    private function keys_link($content)
    {
        global $_M;
        if(empty($_M['sys']['keys_link']) || empty($content)){
            return '';
        }
        foreach($_M['sys']['keys_link'] as $k => $v) {
            $replace[] = '<a href="' . $v . '" target="_blank" title="' . $k . '">' . $k . '</a>';
            $pattern[] = "'$k'si";
        }
        $newcontent = $this->chang_key($content);
        if($newcontent !== false) {
            $content = $newcontent[0];
        }
        $content = preg_replace($pattern, $replace, $content);
        if($newcontent !== false) {
            foreach($newcontent[2] as $k => $v) {
                $p[$k] = "'$v'si";
            }
            $content = preg_replace($p, $newcontent[1], $content);
        }
        $this->arc['attach'][$this->htmltext_field] = $content;
    }
    /**
     * 过滤html代码中相应的关键词
     * @param type 文章内容
     * @return array
     */
    private function chang_key($content)
    {
        $s = '@!@!_#__';
        preg_match_all('|<(.*)>|isU', $content, $matches);
        $pattern = $matches[0];
        $content = preg_replace("'<[\/\!]*?[^<>]*?>'si", $s, $content);
        $arr = explode($s, $content);
        $str = '';
        if($pattern) {
            foreach($pattern as $k => $v) {
                $rep[$k] = $s . md5($k);
                $str .= $arr[$k] . $rep[$k];
            }
            if(!empty($arr[$k +1])) {
                $str .= $arr[$k +1];
            }
            return array($str, $pattern, $rep);
        }
        return false;
    }

    /**
     * 生成静态HTML
     */
    public function create_html()
    {
        global $_M;
        if(empty($this->arc['id'])){
            return false;
        }
        if(!empty($this->arc['redirecturl'])) {
            return false;
        }
        if($this->arc['status']<1||$this->arc['ishtml']<1){
            return false;
        }
        $this->analysis_htmltext();
        if(empty($this->arc['htmllink'])){
            $this->arc['htmllink'] = t('archives')->insideHtmlLink($this->arc);
            if(empty($this->arc['htmllink'])){
                return false;
            }
            t('archives')->update($this->arc['id'],array('htmllink'=>$this->arc['htmllink']));
        }
        $this->pathinfo = pathinfo($this->arc['htmllink']);
        $dirname = MOPROOT.'/'.$this->pathinfo['dirname'];
        createdir($dirname) or showMsg(10025);

        $title = $this->arc['title'];
        for($i = 1; $i <= $this->maxpages; $i++) {
            if($i > 1) {
                $this->arc['title'] = $title . "($i)";
                $htmlpath = MOPROOT . $this->get_htmlpage_url($i);
            } else {
                $htmlpath = MOPROOT . $this->arc['htmllink'];
            }
            $this->arc['attach'][$this->htmltext_field] = !empty($this->split_fields[$i -1])?$this->split_fields[$i -1]:'';
            $this->arc['page'] = $i;
            $this->arc['pagelist'] = $this->get_page($i, false);
            $this->save_html($htmlpath);
        }

        //生成附加页面
        if (!empty($this->arc['attach']['extrapage'])) {
            if(!function_exists('serializeData')){
                include_once loadlib('admin');
            }
            $extrapage = unserialize(serializeData($this->arc['attach']['extrapage']));
            $this->save_attach_html($extrapage);
        }

        t('archives')->update($this->arc['id'], array('updatehtmltime'=>TIME));
        return true;
    }
    /**
     * 保存HTML页面
     * @param $htmlpath 静态文件地址
     */
    private function save_html($htmlpath)
    {
        global $_M, $_data;
        $_data = $this->arc;
        $_M['_temp'] = $this->get_template();
        include template($_M['_temp'], '',true);
        $str = output();
        file_put_contents($htmlpath,$str) or die($htmlpath.$str);
    }
    /**
     * 返回分页的静态页面URL
     */
    private function get_htmlpage_url($i)
    {
        return $this->pathinfo['dirname'].'/'.$this->pathinfo['filename'] . '_' . $i . '.' . $this->pathinfo['extension'];
    }

    /**
     * 仅用于专题附加页面生成
     * @param $row 专题附加页面
     */
    private function save_attach_html($row)
    {
        global $_M, $_data;
        $_data = $this->arc;
        $dirname = !empty($this->arc['attach']['template'])?$this->arc['attach']['template']:$this->arc['id'];
        foreach($row as $tkey => $tval) {
            $tmp = $_M['sys']['special_dir'] .'/'. $dirname . '/template/' . $tkey;
            if (is_file(MOPROOT.$tmp)){
                include template('../'.$tmp, '', true);
                $str = output();
                $fp = @ fopen(MOPROOT . $_M['sys']['special_dir'] .'/'. $dirname . '/' . $tval, "w") or die("静态页面生成失败!");
                fwrite($fp, $str);
                fclose($fp);
            }
        }
    }
    /**
     * 动态显示文档
     * @param $loadtmp 加载模板
     */
    public function display($loadtmp = true)
    {
        global $_M, $_data;
        if(empty($this->arc['id'])){
            return getResult(10001);
        }
        if($this->arc['status']<0){
            return getResult(10004);
        }
        if(!empty($this->arc['redirecturl'])) {
            header('location:' . $this->arc['redirecturl']);
            exit;
        }
        $this->analysis_htmltext();
        $this->arc['attach'][$this->htmltext_field] = !empty($this->split_fields[$this->page -1])?$this->split_fields[$this->page -1]:'';
        $this->arc['pagelist'] = $this->get_page($this->page);
        $_data = $this->arc;
        if($loadtmp){
            include template($this->get_template(), '',true);
            exit;
        }
    }

    /**
     * 获得模板文件位置
     */
    private function get_template()
    {
        global $_M;
        $tmp = '/'.aval($this->arc, 'column/tempview');

        if(aval($this->arc, 'model/id') == -1) {
            $dirname = !empty($this->arc['attach']['template'])?$this->arc['attach']['template']:$this->arc['id'];
            $tmp = $_M['sys']['special_dir'] .'/'. $dirname . '/template/index.htm';
            if (is_file(MOPROOT.$tmp)){
                $tmp = '../'.$tmp;
            }else{
                $tmp = '/default/view_specials.htm';
            }
        }else{
            if (!is_file(MOPTEMPLATE.$tmp)){
                $tmp = '/default/view_default.htm';
            }
        }
        return preg_replace("/\/{1,}/", "/", $tmp);
    }

    private function get_page($page, $dynamic = true)
    {
        global $_M;
        if($_M['iswap']){
            return $this->wap_page($page);
        }
        return $this->pc_page($page,$dynamic);
    }

    /**
     * PC获得静态页面分页列表
     * @param $page 页数
     * @param dynamic 是否动态链接
     */
    private function pc_page($page, $dynamic = true)
    {
        if($this->maxpages== 1) {
            return '';
        }
        $prepage = $page -1;
        $nextpage = $page +1;
        $pagelist = '';
        $url = $dynamic === true ? pseudoUrl($this->dynamic_url) : $this->arc['htmllink'];
        if($page == 1) {
            $pagelist .= "<a class='previous btn'>上一页</a>";
        } else {
            if($prepage != 1) {
                $url = $dynamic === true ? pseudoUrl($this->dynamic_url. '&page=' . $prepage) : $this->get_htmlpage_url($prepage);
            }
            $pagelist .= "<a href='$url' class=\"btn btn-boo\">上一页</a>";
        }
        for($i = 1; $i <= $this->maxpages; $i++) {
            if($i == 1) {
                if($page != 1) {
                    $url = $dynamic === true ? pseudoUrl($this->dynamic_url) : $this->arc['htmllink'];
                    $pagelist .= "<a href='$url' class=\"btn btn-boo\">1</a>";
                } else {
                    $pagelist .= "<a class=\"btn btn-boo\">1</a>";
                }
            } else {
                $n = $i;
                if($page != $i) {
                    $url = $dynamic === true ? pseudoUrl($this->dynamic_url. '&page=' . $i) : $this->get_htmlpage_url($i);
                    $pagelist .= "<a href='$url' class=\"btn btn-boo\">" . $n . "</a>";
                } else {
                    $pagelist .= "<a class=\"btn btn-envato active\">{$n}</a>";
                }
            }
        }
        if($nextpage <= $this->maxpages) {
            $url = $dynamic === true ? pseudoUrl($this->dynamic_url. '&page=' . $nextpage) : $this->get_htmlpage_url($nextpage);
            $pagelist .= "<a href='$url' class=\"btn btn-boo\">下一页</a>";
        } else {
            $pagelist .= "<a class=\"btn btn-boo\">下一页</a>";
        }
        return $pagelist;
    }

    /**
     * WAP获得静态页面分页列表
     * @param $page 页数
     */
    private function wap_page($page)
    {
        if($this->maxpages== 1) {
            return '';
        }
        $prepage = $page -1;
        $nextpage = $page +1;
        $url = pseudoUrl($this->dynamic_url);
        $pagelist = '<div class="pager"><div class="pager-left"><div class="pager-first"><a class="pager-nav" href="'.$url.'">首页</a></div>';
        $pagelist .= '<div class="pager-pre"><a href="'.pseudoUrl($this->dynamic_url. '&page=' . $prepage).'" class="pager-nav">上一页</a></div></div>';
        $pagelist .= '<div class="pager-cen">'.$page.'/'.$this->maxpages.'</div>';
        $pagelist .= '<div class="pager-right"><div class="pager-next"><a href="'.pseudoUrl($this->dynamic_url. '&page=' . $nextpage).'" class="pager-nav">下一页</a></div>';
        $pagelist .= '<div class="pager-end"><a href="'.pseudoUrl($this->dynamic_url. '&page=' . $this->maxpages).'" class="pager-nav">尾页</a></div>';
        $pagelist .= '</div>';
        return $pagelist;
    }
}