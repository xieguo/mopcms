<?php
error_reporting(0);

define('MOPSRC', str_replace("\\", '/', dirname(__FILE__)));
define('MOPROOT', str_replace("\\", '/', substr(MOPSRC, 0, -7)));
define('MOPDATA', MOPROOT . '/data/');
define('MOPSTATIC', MOPROOT . '/static/');
define('MOPSUPPLIER', MOPROOT . '/supplier/');
define('MOPPLUGINS', MOPROOT . '/plugins/');
define('MOPTEMPLATE', MOPROOT . '/template/');
define('IN_MOPCMS', true);
define('MOP_DEBUG', 1);//错误调试：0关闭，1仅保存错误日志，2保存并页面显示错误信息

$_M = $_data = $arc = array();
include(MOPSRC.'/function/function_mop.php');
require_once(MOPSRC . '/class/base/base_init.php');
if (version_compare(PHP_VERSION, '5.3.0')<0) {
    showMsg(102);
}

//异常处理
set_exception_handler(array('base_init', 'exception_handler'));
set_error_handler(array('base_init', 'error_handler'));
register_shutdown_function(array('base_init', 'register_shutdown'));
spl_autoload_register(array('base_init', 'autoload'));

class db extends base_db {}
base_init :: instance();

//加载入口文件函数库
$basescript = loadlib(defined('CURSCRIPT')?CURSCRIPT:$_M['basescript']);
if(is_file($basescript)){
    require_once $basescript;
}
unset($basescript);

!defined('CURSCRIPT') && define('CURSCRIPT', $_M['basescript']);

if(!empty($_M['sys']['forbidip'])) {
    $ips = explode(',', $_M['sys']['forbidip']);
    foreach($ips as $ipv) {
        if(preg_match('/' . $ipv . '/', $_M['ip'])) {
            header("HTTP/1.0 404 Not Found");
            exit;
        }
    }
}