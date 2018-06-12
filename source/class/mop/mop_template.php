<?php
/**
 * 模板解析类
 * @copyright           (C) 2016-2099 MOPCMS.COM
 * @license             http://www.mopcms.com/license/
 */
!defined('IN_MOPCMS') && exit('Access failed');

class mop_template
{
    private $search = array();
    private $replace = array();
    private $tpldir = array();

    /**
     * 解析模板并生成临时解析php文件
     * @param unknown_type $tplfile
     * @param unknown_type $cachefile
     */
    public function parse_template($tplfile, $cachefile)
    {
        if($fp = @fopen(MOPROOT.$tplfile, 'r')) {
            $template = @fread($fp, filesize(MOPROOT.$tplfile));
        } else {
            base_error::error('template no exist:' . $tplfile);
        }
        if(!@$fp = fopen(MOPROOT.$cachefile, 'w')) {
            base_error::error('cachefile can not create:' .$cachefile);
        }
        $tplfile = str_replace('//', '/', $tplfile);
        $pathinfo = pathinfo($tplfile);
        $this->tpldir = $pathinfo['dirname'];
        $template = $this->format_template($template);
        flock($fp, 2);
        fwrite($fp, $template);
        fclose($fp);
    }

    /**
     * 将模板中标签解析成PHP代码
     */
    public function format_template($template)
    {
        $regexp = "((\\\$[a-zA-Z_][\w]*(\-\>)?[\w]*)(\[[\w\:\-\.\"\'\[\]\$]+\])*)";
        $template = preg_replace("/([\n\r]+)\t+/s", "\\1", $template);
        $template = preg_replace("/\<\!\-\-\{(.+?)\}\-\-\>/s", "{\\1}", $template);
        $template = preg_replace_callback("/[\n\r\t]*\{eval\s+(.+?)\s*\}[\n\r\t]*/is", array($this, 'tags_eval'), $template);
        $template = preg_replace_callback("/[\n\r\t]*\{list\s+(.+?)\s*\}[\n\r\t]*/is", array($this, 'tags_list'), $template);
        $template = preg_replace("/\{(\\\$[\w\-\>\[\]\'\"\$\.]+)\}/s", "<?=\\1?>", $template);
        $template = preg_replace_callback("/$regexp/s", array($this, 'addquote'), $template);
        $template = preg_replace_callback("/\<\?\=\<\?\=$regexp\?\>\?\>/s", array($this, 'addquote'), $template);
        $template = "<?php if(!defined('IN_MOPCMS')) exit('Access failed');?>\n$template";
        $template = preg_replace_callback("/[\n\r\t]*\{template\s+(.+?)\}[\n\r\t]*/is", array($this, 'tags_template'), $template);
        $template = preg_replace_callback("/[\n\r\t]*\{echo\s+(.+?)\}[\n\r\t]*/is", array($this, 'tags_echo'), $template);
        $template = preg_replace_callback("/[\n\r\t]*\{aval\s+(\S+)\s+(\S+)\}[\n\r\t]*/is", array($this, 'tags_aval'), $template);
        $template = preg_replace_callback("/[\n\r\t]*\{aval\s+(\S+)\s+(\S+)\s+(\S+)\}[\n\r\t]*/is", array($this, 'tags_aval'), $template);
        $template = preg_replace_callback("/([\n\r\t]*)\{if\s+(.+?)\}([\n\r\t]*)/is", array($this, 'tags_if'), $template);
        $template = preg_replace_callback("/([\n\r\t]*)\{elseif\s+(.+?)\}([\n\r\t]*)/is", array($this, 'tags_elseif'), $template);
        $template = preg_replace("/\{else\}/i", "<?php } else { ?>", $template);
        $template = preg_replace_callback("/[\n\r\t]*\{loop\s+(\S+)\s+(\S+)\}[\n\r\t]*/is", array($this, 'tags_loop'), $template);
        $template = preg_replace_callback("/[\n\r\t]*\{loop\s+(\S+)\s+(\S+)\s+(\S+)\}[\n\r\t]*/is", array($this, 'tags_loop'), $template);
        $template = preg_replace_callback("/[\n\r\t]*\{for\s+(\S+)\s+(\S+)\s+(\S+)\}[\n\r\t]*/is", array($this, 'tags_for'), $template);
        $template = preg_replace("/\{\/(if|for|loop|list)\}/i", "<?php } ?>", $template);
        $template = preg_replace("/\{([a-zA-Z_][\w]*)\}/s", "<?=\\1?>", $template);
        if(!empty($this->search)) {
            $template = str_replace($this->search, $this->replace, $template);
        }
        //将前面的<?=转回来，如果直接echo，会导致{loop $xx $x}类标签中的$xx先解析成echo而出错
        $template = preg_replace("/\<\?\=(.+?)\?\>/is", "<?php echo \\1;?>", $template);
        return $template;
    }

    /**
     * template标签解析
     * @param unknown_type $matches
     */
    private function tags_template($matches)
    {
        $expr = '<?php include template(\'' . $matches[1] . '\',\''.$this->tpldir.'\'); ?>';
        return $this->strip_phptags($expr);
    }

    /**
     * echo标签解析
     * @param unknown_type $matches
     */
    private function tags_echo($matches)
    {
        $expr = '<?php echo ' . $matches[1] . '; ?>';
        return $this->strip_phptags($expr);
    }

    /**
     * aval标签解析
     * @param unknown_type $matches
     */
    private function tags_aval($matches)
    {
        if(!empty($matches[3])){
            $expr = '<?php echo aval($'.$matches[1].',\''.$matches[2].'\',\''.$matches[3].'\');?>';
        }else{
            $expr = '<?php echo aval($'.$matches[1].',\''.$matches[2].'\');?>';
        }
        return $this->strip_phptags($expr);
    }

    /**
     * if标签解析
     * @param unknown_type $matches
     */
    private function tags_if($matches)
    {
        $expr = $matches[1] . '<?php if(' . $matches[2] . ') { ?>' . $matches[3];
        return $this->strip_phptags($expr);
    }

    /**
     * elseif标签解析
     * @param unknown_type $matches
     */
    private function tags_elseif($matches)
    {
        $expr = $matches[1] . '<?php } elseif(' . $matches[2] . ') { ?>' . $matches[3];
        return $this->strip_phptags($expr);
    }

    /**
     * loop遍历标签解析
     * @param unknown_type $matches
     */
    private function tags_loop($matches)
    {
        if (!empty($matches[3])) {
            $expr = '<?php if(empty(' . $matches[1] . ') || !is_array(' . $matches[1] . ')){ ' . $matches[1] . '=array();} foreach(' . $matches[1] . ' as ' . $matches[2] . ' => ' . $matches[3] . ') { ?>';
        }
        else {
            $expr = '<?php if(empty(' . $matches[1] . ') || !is_array(' . $matches[1] . ')){ ' . $matches[1] . '=array();} foreach(' . $matches[1] . ' as ' . $matches[2] . ') { ?>';
        }
        return $this->strip_phptags($expr);
    }

    /**
     * for循环标签解析
     * @param unknown_type $matches
     */
    private function tags_for($matches)
    {
        $expr = '<?php for($'.$matches[1].'='.$matches[2].';$'.$matches[1].'<='.$matches[3].';$'.$matches[1].'++) { ?>';
        if($matches[2]>$matches[3]){
            $expr = '<?php for($'.$matches[1].'='.$matches[2].';$'.$matches[1].'>='.$matches[3].';$'.$matches[1].'--) { ?>';
        }
        return $this->strip_phptags($expr);
    }

    /**
     * eval标签解析
     * @param unknown_type $matches
     */
    private function tags_eval($matches)
    {
        $php = str_replace('\"', '"', $matches[1]);
        $i = count($this->search);
        $this->search[$i] = $search = "<!--".__FUNCTION__."_$i-->";
        $this->replace[$i] = "<?php $php?>";
        return $search;
    }

    /**
     * list标签解析
     * @param unknown_type $matches
     */
    private function tags_list($matches)
    {
        $tagcode = $matches[1];
        $row = array();
        $tags = explode(' ',$tagcode);
        foreach($tags as $v){
            if($v){
                $v = preg_replace('/["\']/', '', $v);
                list($key,$val) = explode('=',$v);
                if(strpos($val, '$')!==false){
                    $val = str_replace("\\\"", "\"", preg_replace("/\[([\w\-\.]+)\]/s", "['\\1']", trim($val)));
                    $row[trim($key)] = '\'.(isset('.$val.')?'.$val.':\'\').\'';
                }else{
                    $row[trim($key)] = trim($val);
                }
            }
        }

        $indexk = !empty($row['index-key'])?$row['index-key']:'k';
        $indexv = !empty($row['index-value'])?$row['index-value']:'v';

        $data = json_encode($row);
        $func = !empty($row['func'])?$row['func']:'arclist';
        $i = count($this->search);
        $this->search[$i] = $search = "<!--".__FUNCTION__."_$i-->";
        if(strpos($func, ':')){
            list($plugin,$func) = explode(':', $func);
            $funcfile = MOPPLUGINS.$plugin.'/source/function/function_tags.php';
            if(pluginIsAvailable($plugin) && is_file($funcfile)){
                include_once $funcfile;
                $this->replace[$i] = "<?php \$_infoslist = function_exists('$func')?(array)$func('$data'):array(); \$_{$func} = \$_infoslist;foreach(\$_infoslist as \${$indexk}=>\${$indexv}){?>";
            }else{
                $this->replace[$i] = "<?php \$_infoslist = array(); foreach(\$_infoslist as \${$indexk}=>\${$indexv}){?>";
            }
        }else{
            $this->replace[$i] = "<?php require_once loadlib('tags');\$_infoslist = function_exists('$func')?(array)$func('$data'):array(); \$_{$func} = \$_infoslist;foreach(\$_infoslist as \${$indexk}=>\${$indexv}){?>";
        }
        return $search;
    }

    /**
     * 防止出现多个Access failed判断代码，保证只出现一次
     */
    private function getphptemplate($content)
    {
        $pos = strpos($content, "\n");
        return $pos !== false ? substr($content, $pos + 1) : $content;
    }

    /**
     * 将[xxx]转成['xxx']
     */
    private function addquote($matches)
    {
        $var = '<?='.$matches[1].'?>';
        return str_replace("\\\"", "\"", preg_replace("/\[([\w\-\.\:]+)\]/s", "['\\1']", $var));
    }

    /**
     * 将标签中的<?=$xx?>转换回来
     */
    private function strip_phptags($expr)
    {
        return str_replace("\\\"", "\"", preg_replace("/\<\?\=(\\\$.+?)\?\>/s", "\\1", $expr));
    }
}