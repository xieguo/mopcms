<?php
/**
 * 全站通用函数库
 * @copyright           (C) 2016-2099 MOPCMS.COM
 * @license             http://www.mopcms.com/license/
 */
!defined('IN_MOPCMS') && exit('Access failed');

/**
 * 获取数组中某一元素
 * @param $arr 数组
 * @param $val 元素
 * @param $default 都不存在时的默认值
 */
function aval($arr, $val, $default = null)
{
    if (($pos = strpos($val, '/')) !== false) {
        $str1 = substr($val, 0, $pos);
        $str2 = trim(substr($val, $pos), '/');
        if (!isset($arr[$str1])) {
            return $default;
        }
        return aval($arr[$str1], $str2);
    }
    return isset($arr[$val]) ? $arr[$val] : $default;
}

function t($table)
{
    return base_init:: t($table);
}

/**
 * 加载相应的PHP文件
 * @param $name
 */
function loadlib($name)
{
    $folder = 'function';
    if (strpos($name, '/') !== false) {
        list($folder, $name) = explode('/', $name);
    }
    if (strpos($name, ':') !== false) {
        list($plugin, $name) = explode(':', $name);
        return MOPPLUGINS . $plugin . '/source/' . $folder . '/' . $folder . '_' . $name . '.php';
    }
    return MOPSRC . '/' . $folder . '/' . $folder . '_' . $name . '.php';
}

/**
 * 获取外部输入数据
 * @param $val
 */
function _gp($val)
{
    if (isset($_GET[$val])) {
        $value = $_GET[$val];
    } elseif (isset($_POST[$val])) {
        $value = $_POST[$val];
    }
    return isset($value) ? wordsFilter($value) : null;
}

//违禁词处理
function wordsFilter($word)
{
    global $_M;
    if (is_array($word)) {
        foreach ($word as $k => $v) {
            $word[$k] = wordsFilter($v);
        }
    } else {
        $word = trim($word);
        $notallowstr = aval($_M, 'sys/notallowstr');
        $replacestr = aval($_M, 'sys/replacestr');
        if ($notallowstr) {
            foreach (explode('|', $notallowstr) as $key => $val) {
                if (preg_match("/$val/i", $word)) {
                    getjson(10008, '', array('name' => $val));
                }
            }
        }
        if ($replacestr) {
            foreach (explode('|', $replacestr) as $key => $val) {
                if (preg_match("/$val/i", $word)) {
                    $word = str_replace($val, '***', $word);
                }
            }
        }
    }
    return $word;
}

/**
 * 某插件是否可用
 * @param $identifier 标识符
 */
function pluginIsAvailable($identifier)
{
    static $status = array();
    if (isset($status[$identifier])) {
        return $status[$identifier];
    }
    $status[$identifier] = t('plugins')->count(array('identifier' => $identifier, 'available' => 1)) > 0;
    return $status[$identifier];
}

/**
 * 生成文件夹
 * @param $dir
 */
function createdir($dir)
{
    $dir = preg_replace("/\/{1,}/", "/", $dir);
    if (!is_dir($dir)) {
        createdir(dirname($dir));
        $res = @mkdir($dir, 0777);
        if ($res === false) {
            return $res;
        }
    }
    return true;
}

/**
 * 输出缓冲区的内容
 */
function output()
{
    global $_M;
    $content = ob_get_contents();
    if ($content) {
        ob_end_clean();
    }
    if (!ob_start(!empty($_M['allowgzip']) ? 'ob_gzhandler' : null)) {
        ob_start();
    }
    return $content;
}

/**
 * 伪静态URL处理
 */
function pseudoUrl($url, $basehost = '')
{
    global $_M;
    if (preg_match('/^&/', $url)) {
        $url = $_M['cururl'] . $url;
    }
    if (!$_M['sys']['pseudourl']) {
        return $basehost . $url;
    }

    $query = parse_url($url, PHP_URL_QUERY);
    parse_str($query, $output);
    if ($basehost) {
        $url = $basehost;
    } else {
        $basescript = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_FILENAME);
        $basescript = !empty($basescript) ? $basescript : 'main';
        $url = $_M['sys']['basehost'] . '/' . $basescript;
    }
    $url = rtrim($url, '/');
    $url .= '/' . $output['mod'] . '/';
    if (!empty($output['ac'])) {
        $url .= $output['ac'] . '/';
    }
    if (!empty($output['do'])) {
        $url .= $output['do'] . '/';
    }
    if (!empty($output['op'])) {
        $url .= $output['op'] . '/';
    }
    $mcallback = isset($output['mcallback']);
    unset($output['mod'], $output['ac'], $output['do'], $output['op'], $output['q'], $output['mcallback']);
    if (!empty($output)) {
        if ($_M['sys']['pseudourl'] == 1) {
            $val = xxtea(json_encode($output), 'en', $_M['config']['authkey']);
        } elseif ($_M['sys']['pseudourl'] == 2) {
            $arr = array();
            foreach ($output as $k => $v) {
                $v = is_array($v) ? implode('`', $v) : $v;
                if (!empty($v) || $v == '0') {
                    $arr[] = urlencode(str_replace(array('-', '_'), array('&#045;', '&#095;'), $k) . '-' . str_replace(array('-', '_'), array('&#045;', '&#095;'), $v));
                }
            }
            $val = implode('_', $arr);
        }
        $url .= urlencode($val) . '.html';
        //方便JS中url的拼接生成URL
        $url = str_replace(array('%2527%2B', '%2B%2527'), array('\'+', '+\''), $url);
    }
    if ($mcallback) {
        $url .= '?mcallback=?';
    }
    return $url;
}

function loadcache($cachenames)
{
    global $_M;
    $cachenames = is_array($cachenames) ? $cachenames : array($cachenames);
    foreach ($cachenames as $name) {
        if (empty($_M['cache'][$name])) {
            $_M['cache'][$name] = t('sys_cache')->get_cache('fetchByName', $name);
        }
    }
    return true;
}

function xxtea($string, $ende = 'de', $key = '')
{
    global $_M;
    if (empty($string)) {
        return '';
    }
    $key = md5($key ? $key : $_M['authkey']);
    $tea = new helper_xxtea;
    if ($ende == 'de') {
        return $tea->xxtea_decrypt($string, $key);
    }
    return $tea->xxtea_encrypt($string, $key);
}

/**
 * 获取专题节点数据
 * @param $nodeid 节点ID
 * @param $id 文档ID
 */
function nodedata($nodeid, $id = '')
{
    global $_data;
    $id = !empty($id) ? $id : $_data['id'];
    if (empty($id) || empty($nodeid)) {
        return '';
    }
    static $node = '';
    if (empty($node)) {
        $node = t('specials')->fetchFields($id, 'node');
    }
    if (empty($node)) {
        return '';
    }
    $ids = $node[$nodeid]['ids'];
    $num = !empty($ids) ? count(explode(',', $ids)) : 0;
    if ($num) {
        $cachefile = '/template/nodedata_' . md5($id . '_' . $nodeid) . '.tpl.php';
        $tmp = new mop_template();
        if (!@$fp = fopen(MOPDATA . $cachefile, 'w')) {
            $tmp->error('directory_notfound', dirname(MOPDATA . $cachefile));
        }
        $str = "{list idlist='" . $ids . "' modelid='" . $node[$nodeid]['model'] . "' fields='*'}" . $node[$nodeid]['tmp'] . "{/list}";
        $template = $tmp->format_template($str);
        flock($fp, 2);
        fwrite($fp, $template);
        fclose($fp);
        include MOPDATA . $cachefile;
    }
}

/**
 * @param $file 模板文件
 * @param $tpldir 模板文件夹
 * @param $force 是否自动查找模板
 * @param $gettmp 是否返回模板地址
 */
function template($file, $tpldir = '', $force = false, $gettmp = false)
{
    global $_M;
    if (strpos($file, ':')) {
        list($pluginid, $file) = explode(':', $file);
    }
    $wapdir = $_M['iswap'] && !defined('ISMANAGE') ? 'wap/' : '';
    if ($force === false) {
        if ($tpldir) {
            $tpldir = rtrim($tpldir, '/') . '/';
        }
        //插件模板判断加载
        if (!empty($pluginid)) {
            //调用插件模板生成静态页面判断
            if (!empty($_M['_temp'])) {
                $file = trim($file, '/');
                $tplfile = '/plugins/' . $pluginid . '/template/' . $file;
                if (!is_file(MOPROOT . $tplfile)) {
                    $tplfile = '/plugins/' . $pluginid . '/template/default/' . $file . '.htm';
                    if (!is_file(MOPROOT . $tplfile)) {
                        $tplfile = '/template/' . CURSCRIPT . '/default/' . $file . '.htm';
                    }
                }
            } else {
                if (!$tpldir) {
                    $tpldir = '/plugins/' . $pluginid . '/template/' . $wapdir . CURSCRIPT . '/' . (CURSCRIPT == 'plugin' ? $_M['ac'] : $_M['mod']) . '/';
                }
                $tplfile = $tpldir . $file . '.htm';
                if (!is_file(MOPROOT . $tplfile)) {
                    $tplfile = rtrim($tpldir, 'default/') . '/default/' . $file . '.htm';
                    if (!is_file(MOPROOT . $tplfile)) {
                        $tplfile = '/template/' . $wapdir . CURSCRIPT . '/default/' . $file . '.htm';
                        if (!is_file(MOPROOT . $tplfile)) {
                            $tplfile = '/template/' . $wapdir . '/default/' . $file . '.htm';
                        }
                    }
                }
            }
            $cachefile = '/data/template/plugin_' . $pluginid . '_' . md5($tplfile) . '.tpl.php';
        } else {
            //防止模板中<!--{template headhtml}-->生成的缓存PHP文件和前后台使用同一个
            if (!empty($_M['_temp'])) {
                $parse = pathinfo($_M['_temp']);
                $tpldir = '/template/' . $parse['dirname'] . '/';
                $tplfile = $tpldir . $file . '.htm';
                $tplname = md5($tplfile);
                $cachefile = '/data/template/' . $tplname . '.tpl.php';
            } else {
                if (!$tpldir || !preg_match('/^\/template\//', $tpldir)) {
                    $tpldir = $tpldir ? $tpldir . '/' : $_M['mod'] . '/';
                    $tpldir = '/template/' . $wapdir . CURSCRIPT . '/' . $tpldir;
                }
                $tplfile = $tpldir . $file . '.htm';
            }
        }

        if (!is_file(MOPROOT . $tplfile) && !is_file(substr(MOPROOT . $tplfile, 0, -4) . '.php')) {
            $tplfile = $tpldir . 'default/' . $file . '.htm';
            if (!is_file(MOPROOT . $tplfile) && !is_file(substr(MOPROOT . $tplfile, 0, -4) . '.php')) {
                $tplfile = str_replace('/' . $_M['mod'] . '/', '/', $tpldir) . 'default/' . $file . '.htm';
                if (!is_file(MOPROOT . $tplfile) && !is_file(substr(MOPROOT . $tplfile, 0, -4) . '.php')) {
                    $tplfile = $tpldir . '../default/' . $file . '.htm';
                    if (!is_file(MOPROOT . $tplfile) && !is_file(substr(MOPROOT . $tplfile, 0, -4) . '.php')) {
                        $tplfile = $tpldir . '../../default/' . $file . '.htm';
                    }
                }
            }
        }
        if (!is_file(MOPROOT . $tplfile)) {
            $tplfile = '/template/default/' . $file . '.htm';
        }
        if (empty($cachefile)) {
            if (!$tpldir || !preg_match('/^\/template\//', $tpldir)) {
                $cachefile = '/data/template/' . CURSCRIPT . '_' . $_M['mod'] . '_' . str_replace('/', '_', $file) . '.tpl.php';
            } else {
                $cachefile = '/data/template/' . md5($tplfile) . '.tpl.php';
            }
        }
    } else {
        if (!empty($pluginid)) {
            $file = trim($file, '/');
            $tplfile = '/plugins/' . $pluginid . '/template/' . $file;
            if (!is_file(MOPROOT . $tplfile)) {
                $tplfile = '/plugins/' . $pluginid . '/template/default/' . $file . '.htm';
                if (!is_file(MOPROOT . $tplfile)) {
                    $tplfile = '/template/' . CURSCRIPT . '/default/' . $file . '.htm';
                }
            }
        } else {
            $tplfile = '/template/' . $wapdir . $file;
            if (!is_file(MOPROOT . $tplfile)) {
                $tplfile = '/template/default/' . basename($file);
            }
        }
        $cachefile = '/data/template/' . md5($tplfile) . '.tpl.php';
    }

    if (!is_file(MOPROOT . $tplfile)) {
        base_error:: error('“' . $tplfile . '” template no exist');
    }
    if ($gettmp) {
        return $tplfile;
    }
    if ($cachefile) {
        $filemtime = is_file(MOPROOT . $cachefile) ? @filemtime(MOPROOT . $cachefile) : '';
        if (empty($filemtime) || @ filemtime(MOPROOT . $tplfile) > $filemtime) {
            $template = new mop_template();
            $template->parse_template($tplfile, $cachefile);
        }
        return MOPROOT . $cachefile;
    }
}

/**
 * 字符串过滤
 * @param $string 字符串
 * @param $except 不过滤字符
 */
function filterString($string, $except = '')
{
    if (is_array($string)) {
        foreach ($string as $key => $val) {
            $string[$key] = filterString($val);
        }
    } else {
        if (defined('ISMANAGE') && ISMANAGE === true) {
            $search = array('"', '<', '>', '\'', '\\');
            $replace = array('&quot;', '&lt;', '&gt;', '&#039;', '&#092;');
        } else {
            $search = array('"', '<', '>', '\'', '\\', '|', '*', '$', '(', ')', '%', '@', '+', ';');
            $replace = array('&quot;', '&lt;', '&gt;', '&#039;', '&#092;', '&#124;', '&#042;', '&#036;', '&#040;', '&#041;', '&#037;', '&#064;', '&#043;', '&#059;');
        }
        if ($except) {
            $except = explode(',', $except);
            foreach ($except as $v) {
                $key = array_search($v, $search);
                unset($search[$key], $replace[$key]);
            }
        }
        $string = str_replace($search, $replace, $string);
    }
    return $string;
}

function random($length, $type = "number,upper,lower")
{
    $case = explode(",", $type);
    $count = count($case);
    $lower = "abcdefghijklmnopqrstuvwxyz";
    $upper = strtoupper($lower);
    $number = "0123456789";
    $str_list = "";
    for ($i = 0; $i < $count; ++$i) {
        $str_list .= ${$case[$i]};
    }
    return substr(str_shuffle($str_list), 0, $length);
}

function maddslashes($string, $force = 1)
{
    if (is_array($string)) {
        $keys = array_keys($string);
        foreach ($keys as $key) {
            $val = $string[$key];
            unset($string[$key]);
            $string[addslashes($key)] = maddslashes($val, $force);
        }
    } else {
        $string = addslashes($string);
    }
    return $string;
}

function mstripslashes($string)
{
    if (empty($string)) {
        return $string;
    }
    if (is_array($string)) {
        foreach ($string as $key => $val) {
            $string[$key] = mstripslashes($val);
        }
    } else {
        $string = stripslashes($string);
    }
    return $string;
}

function set_cookie($key, $value = '', $life = 0, $httponly = false)
{
    global $_M;
    $config = &$_M['config']['cookie'];
    if ($value == '' || $life < 0) {
        $value = '';
        $life = -1;
    }
    $key = $config['prefix'] . $key;
    $_COOKIE[$key] = $value;
    $life = $life > 0 ? TIME + $life : ($life < 0 ? TIME - 31536000 : 0);
    $secure = $_SERVER['SERVER_PORT'] == 443 ? 1 : 0;
    setcookie($key, $value, $life, $config['path'], $config['domain'], $secure, $httponly);
}

function get_cookie($key)
{
    global $_M;
    $config = &$_M['config']['cookie'];
    $key = $config['prefix'] . $key;
    return isset($_COOKIE[$key]) ? $_COOKIE[$key] : '';
}

/**
 * 生成指定大小图片
 * @param $pic 图片URL
 * @param $width 图片宽度
 * @param $height 图片高度
 * @param $remote 远程图片是否拉到本地
 */
function imageResize($pic, $width = '', $height = '', $remote = false)
{
    global $_M;
    $nopic = !empty($_M['nopic']) ? $_M['nopic'] : $_M['sys']['static_url'] . 'images/nopic.gif';
    if (empty($pic)) {
        return $nopic;
    }
    if (empty($width) && empty($height)) {
        return !preg_match("/^(https?:\/\/)/i", $pic) ? $_M['sys']['basehost'] . $pic : $pic;
    }

    $newpic = '';
    if (!preg_match("/^(https?:\/\/)/i", $pic)) {
        $oldurl = MOPROOT . $pic;
        $ptype = strrchr($pic, '.');
        //如果有已经生成的图片直接返回
        $newpic = str_replace($ptype, "_{$width}x{$height}" . $ptype, $pic);
        if (is_file(MOPROOT . $newpic)) {
            return $_M['sys']['basehost'] . $newpic;
        }

        $oldurl1 = str_replace($ptype, strtolower($ptype), $oldurl);
        $oldurl2 = str_replace($ptype, strtoupper($ptype), $oldurl);
        if (is_file($oldurl1)) {
            $oldurl = $oldurl1;
        } elseif (is_file($oldurl2)) {
            $oldurl = $oldurl2;
        } else {
            $pic = $nopic;
        }
        $imgdata = is_file($oldurl) ? @getimagesize($oldurl) : array();
        if (!$imgdata) {
            if (is_file($oldurl)) {
                @ unlink($oldurl);
            }
            $pic = str_replace($_M['sys']['basehost'], '/', $nopic);
            $pic = str_replace('//', '/', $pic);
            $oldurl = MOPROOT . $pic;
            $ptype = strrchr($pic, '.');
            $imgdata = @ getimagesize($oldurl);
        }
        if ($imgdata[0] > $width || $imgdata[1] > $height) {
            $newpic = str_replace($ptype, "_{$width}x{$height}" . $ptype, $pic);
            $newurl = MOPROOT . $newpic;
            if (!is_file($newurl)) {
                if (is_file($oldurl) && @ copy($oldurl, $newurl)) {
                    $img = new mop_image;
                    $img->imageResize($newurl, $width, $height);
                    t('uploads')->save(array('url' => $newpic, 'src' => $pic));
                }
            }
        } else {
            $newpic = $pic;
        }

        if ($newpic && is_file(MOPROOT . $newpic)) {
            return $_M['sys']['basehost'] . $newpic;
        }
        return $nopic;
    } else {
        if ($remote === true) {
            $purl = parse_url($pic);
            $picpath = md5($pic) . '.jpg';
            $picdir = MOPROOT . $_M['sys']['upload_allimg'] . '/../remote/';

            if (!is_file($picdir . $picpath)) {
                require_once loadlib('file');
                if (isRemoteFileExists($pic)) {
                    $picdata = @ file_get_contents($pic);
                    @ file_put_contents($picdir . $picpath, $picdata);
                    $img = new mop_image;
                    $img->imageResize($picdir . $picpath, $width, $height);
                }
            }
            return $_M['sys']['basehost'] . '/uploads/remote/' . $picpath;
        }
        return $pic;
    }
}

/**
 * 获取某一模型文档信息
 */
function archiveInfo($aid, $fields = '')
{
    global $_M;
    $aid = intval($aid);
    if (empty($aid)) {
        return array();
    }
    $arc = t('archives')->fetch($aid);
    if (empty($arc)) {
        return $arc;
    }
    $arc['link'] = t('archives')->getHtmlLink($aid);
    $arc['column'] = array();
    if (!empty($arc['columnid'])) {
        $arc['column'] = t('column')->fetch($arc['columnid']);
        $arc['column']['link'] = t('column')->columnLink($arc['column']);
    }
    $arc['model'] = t('models')->fetch($arc['modelid']);
    if ($arc['modelid'] == -1) {
        $arc['column']['savedir'] = $_M['sys']['special_dir'] . (!empty($arc['attach']['template']) ? $arc['attach']['template'] : $arc['id']);
    }
    if ($fields && !empty($arc['model']['tablename'])) {
        if ($fields != '*') {
            $fields = 'aid,' . $fields;
        }
        $arc['attach'] = t($arc['model']['tablename'])->fetchFields($aid, $fields);
        $arc['attach']['modelid'] = $arc['modelid'];
        t('archives')->attachFieldsValue($fields, $arc['attach']);
    }
    return $arc;
}

function mdate($timestamp, $format = 'd')
{
    global $_M;
    static $dformat = 'Y-m-d';
    static $tformat = 'H:i:s';
    static $dtformat = 'Y-m-d H:i:s';
    $format = empty($format) || $format == 'dt' ? $dtformat : ($format == 'd' ? $dformat : ($format == 't' ? $tformat : $format));
    $timestamp = (int)$timestamp;//不强转，如果是字符类型会产生一个警告
    if ($format == 'u') {
        $today = strtotime('today');
        $title = date($dtformat, $timestamp);
        if ($timestamp >= $today) {
            $time = TIME - $timestamp;
            if ($time > 3600) {
                return '<span title="' . $title . '">' . intval($time / 3600) . '&nbsp;小时前</span>';
            }
            if ($time > 1800) {
                return '<span title="' . $title . '">半小时前</span>';
            }
            if ($time > 60) {
                return '<span title="' . $title . '">' . intval($time / 60) . '&nbsp;分钟前</span>';
            }
            if ($time > 0) {
                return '<span title="' . $title . '">' . $time . '&nbsp;秒前</span>';
            }
            if ($time == 0) {
                return '<span title="' . $title . '">刚刚</span>';
            }
            return $title;
        }
        if (($days = intval(($today - $timestamp) / 86400)) >= 0 && $days < 7) {
            if ($days < 2) {
                $day = $days == 1 ? '前天' : '昨天';
                return '<span title="' . $title . '">' . $day . '&nbsp;' . date($tformat, $timestamp) . '</span>';
            }
            return '<span title="' . $title . '">' . ($days + 1) . '&nbsp;天前</span>';
        }
        return $title;
    }
    return date($format, $timestamp);
}

function cur_url()
{
    global $_M;
    $nowurl = trim($_SERVER["QUERY_STRING"], '?');
    $output = array();
    parse_str($nowurl, $output);
    foreach ($output as $k => $v) {
        if ($v === '') {
            unset($output[$k]);
        }
    }
    return $_M['sys']['cmspath'] . $_M['filename'] . '?' . urldecode(http_build_query($output));
}

function delHtml($document, $type = 1)
{
    if ($type == 1) {
        $document = preg_replace("#<\/p>#i", "\n", $document);
        $document = preg_replace("#<br>#i", "\n", $document);
        $document = preg_replace("#<br \/>#i", "\n", $document);
        $search = array("'<script[^>]*?>.*?</script>'si", // 去掉 javascript
            "'<[\/\!]*?[^<>]*?>'si", // 去掉 HTML 标记
            "'([\r\n])[\s]+'", // 去掉空白字符
            "'&(quot|#34);'i", // 替换 HTML 实体
            "'&(amp|#38);'i",
            "'&(lt|#60);'i",
            "'&(gt|#62);'i",
            "'&(nbsp|#160);'i");
        $replace = array("", "", "\\1", "\"", "&", "<", ">", " ");

        $document = preg_replace($search, $replace, $document);
        $document = preg_replace("#\n#i", "<br>", $document);
        $document = preg_replace("#<br> <br>#i", "", $document);
        return $document;
    }
    //过滤非汉字字母数字字符
    if ($type == 2) {
        return preg_replace("/&[A-Za-z].*;|[^" . chr(0x80) . "-" . chr(0xff) . "\w,]/isU", "", strip_tags($document));
    }
    //过滤掉CSS、JS、HTML代码
    if ($type == 3) {
        $str = preg_replace("/<sty(.*)\\/style>|<scr(.*)\\/script>|<!--(.*)-->/isU", "", $document);
        $alltext = "";
        $start = 1;
        for ($i = 0; $i < strlen($str); $i++) {
            if ($start == 0 && $str[$i] == ">") {
                $start = 1;
            } else if ($start == 1) {
                if ($str[$i] == "<") {
                    $start = 0;
                    $alltext .= " ";
                } else if (ord($str[$i]) > 31) {
                    $alltext .= $str[$i];
                }
            }
        }
        $alltext = preg_replace("/&([^;&]*)(;|&)/", "", $alltext);
        $alltext = preg_replace("/[ ]+/s", " ", $alltext);
        return $alltext;
    }
}

function getWeek($time = '')
{
    $time = $time > 0 ? $time : TIME;
    $arr = array('日', '一', '二', '三', '四', '五', '六');
    return $arr[date('w', $time)];
}

function ismobile($mobile)
{
    if (empty($mobile)) {
        return false;
    }
    return preg_match('/^1[3456789][\d]{9}$/', $mobile) || preg_match('/^0[\d]{10,11}$/', $mobile);
}

function msubstr($str, $length, $start = 0)
{
    return mb_substr($str, $start, $length, CHARSET);
}

function showMsg($msg, $referer = '', $types = '', $litime = 3000)
{
    global $_M;
    if ((!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strpos($_SERVER['HTTP_X_REQUESTED_WITH'], 'XMLHttpRequest') !== false) || (!empty($_SERVER['HTTP_ACCEPT']) && $_SERVER['HTTP_ACCEPT'] == '*/*')) {
        getjson($msg);
    }
    if (is_array($msg)) {
        $referer = !empty($msg['referer']) ? $msg['referer'] : '-1';
        $msg = !empty($msg['msg']) ? $msg['msg'] : '';
    } elseif (is_numeric($msg)) {
        $msg = promptMsg($msg);
    }
    $linkurl = $referer;
    if (!empty($referer)) {
        if ($referer == '-1') {
            $referer = 'history.go(-1);';
        } else {
            $referer = 'location="' . $referer . '";';
        }
    }
    if ($types == 'alert') {
        $msg = '<script>alert("' . str_replace('"', "'", $msg) . '");' . $referer . '</script>';
    } else {
        $htmlhead = '<html><head><title>' . aval($_M, 'sys/webname') . '提示信息</title><meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
        $htmlhead .= '<link href="' . $_M['sys']['static_url'] . 'assets/css/lib/bootstrap.css" rel="stylesheet">';
        $htmlhead .= '<link href="' . $_M['sys']['static_url'] . 'assets/css/mopcms.css" rel="stylesheet">';
        $htmlhead .= '<style type="text/css">.modal{margin: 30px auto 0;position: inherit;width: ' . (aval($_M, 'iswap') ? '90%' : '450px') . ';}.modal-body{background-color: #ffffff;font-size: 16px;}</style>';
        $htmlhead .= '</head><body leftmargin="0" topmargin="0"><center><script>';
        $htmlfoot = '</script></center></body></html>';
        $rmsg = 'document.write(\'<div class="modal modal-shadow"><div class="modal-header"><h4>' . aval($_M, 'sys/webname') . ' 提示信息！</h4></div>\');';
        $msg = empty($msg) ? '未知错误' : $msg;
        if ($referer) {
            if ($linkurl == '-1') {
                $linkurl = 'javascript:' . $referer;
            }
            $msg .= '<br /><a href="' . $linkurl . '" style="color:#666666;font-size: 14px;line-height: 40px;">如果你的浏览器没反应，请点击这里...</a>';
            $rmsg .= 'setTimeout(\'' . $referer . '\',' . (empty($litime) ? 1000 : $litime) . ');';
        }
        $rmsg .= 'document.write(\'<div class="modal-body"><p>' . str_replace("'", "\"", $msg) . '</p></div></div>\');';
        $msg = $htmlhead . $rmsg . $htmlfoot;
    }
    echo $msg;
    exit;
}

function getResult($code, $data = '', $para = '', $referer = '')
{
    if (is_array($code)) {
        return array('code' => $code['code'], 'msg' => $code['msg'], 'data' => $data, 'referer' => $referer);
    }
    $plugin = '';
    if (strpos($code, ':')) {
        list($plugin, $code) = explode(':', $code);
    }
    return array('code' => $code, 'msg' => promptMsg($code, $para, $plugin), 'data' => $data, 'referer' => $referer);
}

function promptMsg($code, $para = '', $plugin = '')
{
    if ($plugin) {
        require MOPPLUGINS . $plugin . '/source/prompt/prompt_' . $plugin . '.php';
        $name = 'prompt_' . $plugin;
    } else {
        require loadlib('prompt/main');
        $name = 'prompt_main';
    }
    $arr = $$name;
    if (empty($arr[$code])) {
        return '';
    }
    $str = str_replace(':', '&#058;', $arr[$code]);
    if ($para) {
        if (is_string($para) && strpos($para, ':')) {
            list($plugin, $code) = explode(':', $para);
            $str = promptMsg($code, '', $plugin);
        } elseif (is_array($para)) {
            extract($para);
            eval("\$str = \"$str\";");
        } elseif (is_string($para)) {
            $str = $para;
        } elseif (is_numeric($para)) {
            $str = promptMsg($para, '', $plugin);
        }
    }
    return $str;
}

/**
 * 返回JSON数据
 * @param int $code 错误代码
 * @param array $data 返回数据
 * @param array $para 错误文字中显示参数
 */
function getjson($code, $data = '', $para = array())
{
    global $_M;
    if (empty($code)) {
        exit;
    }
    if (is_numeric($code)) {
        $code = getResult($code, $data, $para);
    }
    $json = json_encode($code);
    if (isset($_GET['mcallback']) && isset($_M['mcallback']) && $_M['mcallback'] === true) {
        $mcallback = filterString(_gp('mcallback'));
        echo $mcallback . "(" . $json . ")";
    } else {
        echo $json;
    }
    exit;
}

/**
 * 设置操作完成后的提示信息
 * @param string $msg 提示信息
 * @param string $url 跳转URL
 * @param string $type 提示信息类型
 * @param string $layout 显示位置
 * @param int $life cookie的有效时间
 */
function setPromptMsg($msg, $url = '', $type = 'success', $layout = 'center', $life = 3)
{
    global $_M;
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strpos($_SERVER['HTTP_X_REQUESTED_WITH'], 'XMLHttpRequest') !== false) {
        getjson($msg);
    }
    $referer = !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '?';
    $url = (empty($url) || $url == '-1') ? $referer : $url;
    if (is_numeric($msg)) {
        $type = 'error';
        $_GET['prompt_code'] = $msg;
        $msg = promptMsg($msg);
    } elseif (is_array($msg)) {
        $_GET['prompt_code'] = $msg['code'];
        if (!empty($msg['referer'])) {
            $diyurl = $url = $msg['referer'];
        }
        $type = 'error';
        if ($msg['code'] == 100) {
            $type = 'success';
        }
        $msg = $msg['msg'];
        $reload = !empty($msg['reload']);
    }
    $arr = array('msg' => $msg, 'type' => $type, 'layout' => $layout, 'life' => $life * 1000);
    set_cookie('prompt_msg', serialize($arr), 3000);

    //防止死循环
    if (!empty($reload)) {
        parse_str(parse_url($url, PHP_URL_QUERY), $output1);
        parse_str(str_replace('?', '', $_M['cururl']), $output2);
        if (empty($diyurl) && $output1 == $output2) {
            $url = '?';
        }
    }
    header('location:' . $url);
    exit;
}

/**
 * 获取操作提示信息
 */
function getPromptMsg()
{
    global $_M;
    $prompt_msg = get_cookie('prompt_msg');
    if (empty($prompt_msg)) {
        return '';
    }
    $arr = unserialize($prompt_msg);
    set_cookie('prompt_msg');
    return '<script>$(function(){notyfy({text: "' . $arr['msg'] . '",type: "' . $arr['type'] . '",dismissQueue: true,layout:"' . $arr['layout'] . '",timeout: ' . $arr['life'] . ',});})</script>';
}

/**
 * 表单提交有效性校验
 */
function submitcheck()
{
    $formsubmit = _gp('formsubmit');
    if (!$formsubmit) {
        return null;
    }
    global $_M;
    if (($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($formsubmit) && $formsubmit == FORMSUBMIT && (empty($_SERVER['HTTP_REFERER']) || preg_replace("/https?:\/\/([^\:\/]+).*/i", "\\1", $_SERVER['HTTP_REFERER']) == preg_replace("/([^\:]+).*/", "\\1", $_SERVER['HTTP_HOST'])))) {
        return true;
    }
    $dlkey = filterString(_gp('dlkey'));
    if ($dlkey) {
        $row = unserialize(xxtea($dlkey, 'de', $_M['config']['sitekey']));
        if (!empty($row['time']) && $row['time'] + 3600 > TIME) {
            return true;
        }
    }
    return false;
}

/**
 * 加载控制类
 * @param $classname 类名
 * @param $method 方法名
 * @param $submitcheck 返回的数据格式
 */
function loadc($classname = '', $method = '', $submitcheck = 0)
{
    global $_M, $_data;
    if (empty($_M['mod'])) {
        return getResult(10017);
    }
    $obj = '';
    $pluginid = '';
    if (empty($classname)) {
        //插件调用判断
        if (defined('ISPLUGIN')) {
            if (CURSCRIPT == 'admin') {
                $pluginid = filterString(_gp('pluginid'));
                $classname = 'control_' . CURSCRIPT . '_' . $pluginid . '_' . $_M['mod'];
            } elseif (CURSCRIPT == 'plugin') {
                $pluginid = $_M['mod'];
                $classname = 'control_' . CURSCRIPT . '_' . $pluginid . '_' . $_M['ac'];
            } else {
                $pluginid = CURSCRIPT;
                $classname = 'control_' . CURSCRIPT . '_' . $pluginid . '_' . $_M['mod'];
            }

            if (is_file(MOPPLUGINS . $pluginid . '/source/class/control/' . $classname . '.php')) {
                include_once MOPPLUGINS . $pluginid . '/source/class/control/' . $classname . '.php';
                $obj = new $classname;
            } else {
                $classname = 'control_' . CURSCRIPT . '_' . $pluginid . '_default';
                if (is_file(MOPPLUGINS . $pluginid . '/source/class/control/' . $classname . '.php')) {
                    include_once MOPPLUGINS . $pluginid . '/source/class/control/' . $classname . '.php';
                    $obj = new $classname;
                }
            }
        } else {
            $classname = 'control_' . CURSCRIPT . '_' . $_M['mod'];
            if (is_file(MOPSRC . '/class/control/' . CURSCRIPT . '/' . $classname . '.php')) {
                include_once MOPSRC . '/class/control/' . CURSCRIPT . '/' . $classname . '.php';
                $obj = new $classname;
            } else {
                $classname = 'control_' . CURSCRIPT . '_default';
                if (is_file(MOPSRC . '/class/control/' . CURSCRIPT . '/' . $classname . '.php')) {
                    include_once MOPSRC . '/class/control/' . CURSCRIPT . '/' . $classname . '.php';
                    $obj = new $classname;
                }
            }
        }
    }

    if (empty($method)) {
        $method = $_M['mod'] . ($_M['ac'] ? '_' . $_M['ac'] : '') . ($_M['do'] ? '_' . $_M['do'] : '') . ($_M['op'] ? '_' . $_M['op'] : '');
    }
    if (empty($method)) {
        return getResult(10017);
    }

    //能够正常执行但未启用的插件不于执行，但对像uediter之类的方法于以放行
    $iscallable = is_callable(array($obj, $method));
    if ($pluginid && $iscallable && !pluginIsAvailable($pluginid)) {
        return getResult(11201);
    }

    $method_submit = $method . '_submit';
    if (is_callable(array($obj, $method_submit))) {
        $formcheck = submitcheck();
        if ($formcheck !== null) {
            if ($formcheck === false) {
                if ($submitcheck == 1) {
                    setPromptMsg(10005, '-1', 'error');
                }
                return getResult(10005);
            }
            $rs = $obj->$method_submit();
            if ($rs['code'] != 100) {
                return $rs;
            }
            if (!empty($rs['data']['template'])) {
                extract($rs['data'], EXTR_SKIP);
                if (!empty($pluginid)) {
                    include_once(template($template, $pluginid, true));
                } else {
                    include_once(template($template));
                }
                exit;
            }
            return $rs;
        }
    }
    if (!$iscallable) {
        include_once MOPSRC . '/class/control/control_global.php';
        $obj = new control_global();
    }
    $rs = $obj->$method();
    if ($rs['code'] != 100) {
        $rs['reload'] = true;
        return $rs;
    }

    if (!empty($rs['data']['template'])) {
        //为了使用EXTR_SKIP，对$_data单独处理
        if (!empty($rs['data']['_data'])) {
            $_data = $rs['data']['_data'];
        }
        extract($rs['data'], EXTR_SKIP);
        if (!empty($pluginid)) {
            $template = $pluginid . ':' . $template;
        }
        include_once(template($template));
        exit;
    }
    $rs['reload'] = true;
    return $rs;
}

/**
 * 加载模型类
 * @param unknown_type $modelname
 */
function loadm($modelname)
{
    static $mdl = array();
    if (isset($mdl[$modelname])) {
        return $mdl[$modelname];
    }
    if (strpos($modelname, ':')) {
        list($pname, $mname) = explode(':', $modelname);
        $mname = 'model_' . $pname . ($mname ? '_' . $mname : '');
        $file = MOPPLUGINS . $pname . '/source/class/model/' . $mname . '.php';
    } else {
        $dirname = '';
        if (strpos($modelname, '_')) {
            list($dirname) = explode('_', $modelname);
            $dirname .= '/';
        }
        $mname = 'model_' . $modelname;
        $file = MOPSRC . '/class/model/' . $dirname . $mname . '.php';
    }
    if (is_file($file)) {
        include $file;
        $mdl[$modelname] = new $mname;
        return $mdl[$modelname];
    }
    base_error:: error(10029);
}

/**
 * 获取以文件形式读取并缓存loadm数据,第一参数为调用的模型类名,第二参数为调用的方法，其它参数为方法中涉及参数
 */
function controlCache()
{
    global $_M;
    $argv = func_get_args();
    if (empty($argv[0]) || empty($argv[1])) {
        base_error:: error(10001);
    }
    $obj = $argv[0];
    $func = $argv[1];
    $time = array_pop($argv);
    $args = array_slice($argv, 2);
    if (!$_M['sys']['data_filecache']) {
        return call_user_func_array(array(loadm($obj), $func), $args);
    }
    $name = md5($_M['iswap'] . '_' . str_replace("'", '', var_export($args, true)));
    $cache = new helper_datacache('control/' . $obj . '/' . $func . '/' . substr($name, -2) . '/' . $name, $time);
    $info = $cache->get_cache();
    if (empty($info)) {
        $info = call_user_func_array(array(loadm($obj), $func), $args);
        if (empty($info['code']) || $info['code'] == 100) {
            $cache->save_cache($info);
        }
    }
    return $info;
}