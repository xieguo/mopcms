<?php
/**
 * 系统运行前的初始化配置类
 * @copyright           (C) 2016-2099 MOPCMS.COM
 * @license             http://www.mopcms.com/license/
 */
!defined('IN_MOPCMS') && exit('Access failed');

class base_init
{
    private static $config = array();
    private static $m = array();
    private static $table;

    public static function instance() {
        self::init();
        self::config();
        self::output();
        self::input();
        self::user();
    }

    private static function init()
    {
        define('TIME', time());
        if (function_exists('date_default_timezone_set')) {
            @date_default_timezone_set('Etc/GMT-8');
        }

        if (isset($_SERVER['HTTP_X_ORIGINAL_URL'])) {
            $_SERVER['REQUEST_URI'] = $_SERVER['HTTP_X_ORIGINAL_URL'];
        } else if (isset($_SERVER['HTTP_X_REWRITE_URL'])) {
            $_SERVER['REQUEST_URI'] = $_SERVER['HTTP_X_REWRITE_URL'];
        }
        if (function_exists('ini_get')) {
            $mlimit = (int)@ini_get('memory_limit');
            if ($mlimit < 32 && function_exists('ini_set')) {
                ini_set('memory_limit', '128m');
            }
        }

        global $_M;
        $_M['member'] = $_M['cache'] = array();
        $_M['mid'] = $_M['username'] = '';
        $_M['starttime'] = microtime(true);
        $_M['ip'] = self::ip();
        $_M['filename'] = basename($_SERVER['SCRIPT_FILENAME']);
        $_M['basescript'] = pathinfo($_M['filename'],PATHINFO_FILENAME);
        $_M['mopcmsurl'] = 'http://plugin.mopcms.com/';//用于接收通知、下载应用等
        $_M['iswap'] = false;
        $agent = aval($_SERVER, 'HTTP_USER_AGENT');
        if(strpos($agent,"NetFront") || strpos($agent,"iPhone") || strpos($agent,"MIDP-2.0") || strpos($agent,"Opera Mini") || strpos($agent,"UCWEB") || strpos($agent,"Android") || strpos($agent,"Windows CE") || strpos($agent,"SymbianOS")) {
            $_M['iswap'] = true;
        }
        $_M['iswx'] = false;
        if (strpos($agent,'MicroMessenger')!==false) {
            $_M['iswx'] = true;
        }
        self::$m = & $_M;
    }

    /**
     * 加载配置文件
     */
    private static function config()
    {
        $file = MOPDATA.'config.php';
        if(!is_file($file)){
            base_error::error(101);
        }

        self::$config = include_once $file;
        self::$m['config'] = & self::$config;
        db::init(self::$config['db']);
        if(!is_file(MOPDATA . 'cache/setting_cache.php')){
            $result = loadm('admin_setting')->rewrite_setting();
            if($result['code']!=100){
                exit($result['msg']);
            }
        }
        if(is_file(MOPDATA.'version.php')){
            @include_once MOPDATA.'version.php';
        }
        $sys = array();
        require_once(MOPDATA . 'cache/setting_cache.php');
        $sys['basehost'] = !empty($sys['basehost']) ? trim($sys['basehost'],'/') : $_SERVER["REQUEST_SCHEME"].'://' . $_SERVER['HTTP_HOST'].str_replace('\\', '', dirname($_SERVER['SCRIPT_NAME']));
        $sys['static_url'] = !empty($sys['static_url']) ? trim($sys['static_url'],'/').'/' : '/static/';
        $sys['special_dir'] = !empty(self::$m['sys']['special_dir'])?self::$m['sys']['special_dir']:'/s/';
        $sys['upload_allimg'] = $sys['medias_dir'] . '/allimg';
        $sys['upload_userup'] = $sys['medias_dir'] . '/userup';
        $sys['upload_remote'] = $sys['medias_dir'] . '/remote';
        $sys['version'] = defined('MOPCMS_VERSION')?MOPCMS_VERSION:'';
        $sys['cmsname'] = $sys['webname'] . '内容管理系统';
        $host = parse_url($sys['basehost']);
        $sys['domain'] = $host['host'];
        $sys['cmspath'] = (!empty($host['path'])?$host['path']:'').'/';
        $sys['data_filecache_time'] = !empty($sys['data_filecache_time'])?$sys['data_filecache_time']:86400;
        unset($host);
        self::$m['sys'] = & $sys;

        //Session保存路径
        $sessSavePath = MOPDATA."sessions/";
        if(is_writeable($sessSavePath) && is_readable($sessSavePath)){
            session_save_path($sessSavePath);
        }
        //Session跨域设置
        @session_set_cookie_params(0,self::$config['cookie']['path'],self::$config['cookie']['domain']);

    }

    private static function user()
    {
        global $_M;
        if(!pluginIsAvailable('member')) {
            return array();
        }
        if ($auth = get_cookie('mauthcode')) {
            $auth = maddslashes(explode("\t", xxtea($auth)));
        }
        list($pwd, $mid) = empty($auth) || count($auth) < 2 ? array('', '') : $auth;

        static $users = array();
        if($mid && empty($users[$mid])) {
            $users[$mid] = t('member')->fetch($mid);
        }
        $user = &$users[$mid];
        if (!empty($user) && $user['pwd'] == $pwd) {
            $_M['member'] = $user;
            $_M['mid'] = $user['id'];
            $_M['username'] = $user['username'];
        }
    }

    private static function input()
    {
        $saltkey = get_cookie('saltkey');
        if(empty($saltkey)){
            $saltkey = random(8);
            set_cookie('saltkey', $saltkey, 86400 * 30, 1, 1);
        }
        self::$m['authkey'] = md5(self::$config['authkey'] . $saltkey);
        $formsubmit = substr(md5(substr(TIME, 0, -5) . self::$m['mid'] . self::$m['authkey']), 8, 8);
        define('FORMSUBMIT', $formsubmit);

        if(!empty($_GET['q'])){
            $arr = json_decode(xxtea(_gp('q'),'de',self::$config['authkey']),true);
            if(empty($arr)){
                $arr = self::parse_q($_GET['q']);
            }
            if($arr){
                $_GET = array_merge($_GET, $arr);
                if(isset($_GET['op'])){
                    $arr['op'] = $_GET['op'];
                }
                if(isset($_GET['do'])){
                    $arr['do'] = $_GET['do'];
                }
                if(isset($_GET['ac'])){
                    $arr['ac'] = $_GET['ac'];
                }
                if(isset($_GET['mod'])){
                    $arr['mod'] = $_GET['mod'];
                }
                $_SERVER["QUERY_STRING"] = http_build_query(array_reverse($arr));
            }
        }
        self::$m['cururl'] = cur_url();

        if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST)) {
            $_GET = array_merge($_GET, $_POST);
        }

        //过滤submit是为了防止绕过表单校验
        self::$m['mod'] = empty($_GET['mod']) ? '' : preg_replace('/[^\w]|submit/i','',_gp('mod'));
        self::$m['ac'] = empty($_GET['ac']) ? '' : preg_replace('/[^\w]|submit/i','',_gp('ac'));
        self::$m['do'] = empty($_GET['do']) ? '' : preg_replace('/[^\w]|submit/i','',_gp('do'));
        self::$m['op'] = empty($_GET['op']) ? '' : preg_replace('/[^\w]|submit/i','',_gp('op'));
        self::$m['page'] = max(1, intval(_gp('page')));
    }

    /**
     * 第二种伪静态格式解析（XX-XX_YY-YY.html）
     * @param unknown_type $str
     */
    private static function parse_q($str)
    {
        if(empty($str)){
            return '';
        }
        $str = urldecode(urldecode($str));
        $arr = array();
        $gets = explode('_', $str);
        foreach ($gets as $v){
            $keyval = explode('-', $v);
            if(!empty($keyval[1])||aval($keyval, 1)=='0'){
                $key = str_replace(array('&#045;','&#095;'), array('-','_'), $keyval[0]);
                $val = str_replace(array('&#045;','&#095;'), array('-','_'), $keyval[1]);
                $val = strpos($val, '`')!==false?explode('`', $val):$val;
                $arr[$key] = $val;
            }
        }
        return $arr;
    }

    private static function output()
    {
        global $_M;
        if (!empty($_SERVER['HTTP_ACCEPT_ENCODING']) && strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') === false) {
            self::$config['output']['gzip'] = false;
        }

        $_M['allowgzip'] = self::$config['output']['gzip'] && function_exists('ob_gzhandler');
        if (!ob_start($_M['allowgzip'] ? 'ob_gzhandler' : null)) {
            ob_start();
        }
        define('CHARSET', self::$config['output']['charset']);
    }

    private static function ip()
    {
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && preg_match('/^([0-9]{1,3}\.){3}[0-9]{1,3}$/',$_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        if  (isset($_SERVER['HTTP_CLIENT_IP'])  && preg_match('/^([0-9]{1,3}\.){3}[0-9]{1,3}$/',$_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        }
        return $_SERVER['REMOTE_ADDR'];
    }

    /**
     * 获取table_xxx类的对象
     * @param $name
     */
    public static function t($name)
    {
        $classname = 'table_' . $name;
        if(!isset(self :: $table[$classname])) {
            $pname = '';
            if (strpos($name, ':')) {
                list($pname,$name) = explode(':', $name);
                $classname = 'table_'.$name;
            }
            if (!$pname && is_file(MOPSRC . '/class/table/' . $classname.'.php')) {
                include_once MOPSRC . '/class/table/' . $classname.'.php';
                self :: $table[$classname] = new $classname;
            }
            elseif ($pname && is_file(MOPPLUGINS . $pname.'/source/class/table/' . $classname.'.php')) {
                include_once MOPPLUGINS . $pname.'/source/class/table/' . $classname.'.php';
                self :: $table[$classname] = new $classname;
                self :: $table[$classname]->tname = $name;
            }
            else{
                self :: $table[$classname] = new base_table();
                self :: $table[$classname]->tname = $name;
            }
        }
        return self :: $table[$classname];
    }

    /**
     * 加载某文件
     * @param $name
     * @param $folder
     * @throws Exception
     */
    private static function import($name, $folder = '')
    {
        static $includes = array();
        $key = $folder . $name;
        if(!isset($includes[$key])) {
            $path = MOPSRC . '/' . $folder;
            $pre = basename(dirname($name));
            $filename = dirname($name) . '/' . $pre . '_' . basename($name) . '.php';
            if (!is_file($path . '/' . $filename) && strpos($name, '_') !== false) {
                list($folder) = explode('_', $name);
                $filename = $folder.'/'.basename($filename);
            }
            if(is_file($path . '/' . $filename)) {
                $includes[$key] = true;
                return include $path . '/' . $filename;
            } else {
                throw new Exception('missing file: ' . $filename);
            }
        }
        return true;
    }

    public static function exception_handler($exc)
    {
        base_error :: exception($exc);
    }

    public static function error_handler($errno, $errstr, $errfile, $errline)
    {
        base_error :: error($errstr);
    }

    public static function register_shutdown()
    {
        if($error = error_get_last()) {
            base_error :: error($error['message']);
        }
    }

    public static function autoload($class)
    {
        $class = strtolower($class);
        if(strpos($class, '_') === false) {
            $class = 'table_' . $class;
        }
        list($folder) = explode('_', $class);
        $file = 'class/' . $folder . '/' . substr($class, strlen($folder) + 1);

        try {
            self :: import($file);
            return true;
        } catch(Exception $exc) {
            base_error :: exception($exc);
        }
    }
}
