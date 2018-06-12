<?php
//error_reporting(0);
define('IN_MOPCMS', true);
define('MOPINSTALL', str_replace("\\", '/', dirname(__FILE__)));
define('MOPROOT', str_replace("\\", '/', substr(MOPINSTALL, 0, -7)));
define('MOPDATA', rtrim(MOPROOT,'/') . '/data/');

if(is_file(MOPDATA.'install.lock')){
    showMsg('本程序已经安装，无需重复安装！');
}

$_M = array();
$_M['sys']['static_url'] = '../static/';
include('../source/function/function_mop.php');
if (version_compare(PHP_VERSION, '5.3.0')<0) {
    showMsg(102);
}

function writeTest($dir)
{
    global $_M;
    $tfile = $dir.'mtext.txt';
    $fp = @fopen($tfile,'w');
    if(!$fp){
        return false;
    }
    fclose($fp);
    $rs = @unlink($tfile);
    if($rs){
        return true;
    }
    return false;
}

$step = max(1,(int)_gp('step'));
if($step==1){
    include('./template/step_1.htm');
    exit();
}
elseif($step==2){
    if(!_gp('isread')){
        showMsg("请先阅读并同意本协议",'?step=1');
    }
    $filesize = @ini_get('file_uploads') ? ini_get('upload_max_filesize') : 'unknow';
    $tmp = function_exists('gd_info') ? gd_info() : array();
    $gd = empty($tmp['GD Version']) ? 'noext' : $tmp['GD Version'];
    unset($tmp);
    $disksize = function_exists('disk_free_space')?floor(disk_free_space(MOPROOT) / (1024*1024)).'M':'unknow';

    $dirs = array('../','../data/','../data/backupdata/','../data/cache/','../data/datacache/','../data/errorlog/','../data/plugins/','../data/sessions/','../data/template/','../plugins/','../uploads/','../uploads/allimg/','../uploads/image/','../uploads/userup/');
    $isok = true;
    include('./template/step_2.htm');
    exit();
}
elseif($step==3){
    if(_gp('check')!='ok'){
        showMsg("步骤2检测不通过",'?step=2&isread=1');
    }
    $dbdriver = filterString(_gp('dbdriver'));
    include('./template/step_3.htm');
    exit();
}
elseif($step==4){
    $dbdriver = _gp('dbdriver')=='pdo'?'pdo':'mysql';
    $dbhost = filterString(_gp('dbhost'));
    $dbport = filterString(_gp('dbport'));
    $dbuser = filterString(_gp('dbuser'));
    $dbpwd = filterString(_gp('dbpwd'));
    $dbprefix = filterString(_gp('dbprefix'));
    $dbname = filterString(_gp('dbname'));
    $adminuser = filterString(_gp('adminuser'));
    $adminpwd = filterString(_gp('adminpwd'));
    if ($dbdriver == 'pdo'){
        $portstr = !empty($dbport) && $dbport!=3306?'port='.$dbport.';':'';
        $options = array();
        $options[PDO::MYSQL_ATTR_INIT_COMMAND] = 'SET character_set_connection=utf8, character_set_results=utf8, character_set_client=binary, sql_mode=\'\'';
        try {
            $link = new PDO('mysql:host='.$dbhost.';'.$portstr, $dbuser, $dbpwd, $options);
        } catch(Exception $exc) {
            showMsg('数据库连接失败，请重新设置！','-1');
        }
        $link->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
        //获得数据库版本信息
        $query = $link->prepare('SELECT VERSION();');
        $query->execute();
        $version = $query->fetchColumn(0);
        if (version_compare($version, '5.0.1')<0) {
            showMsg('MYSQL版本要大于5.0.1','-1');
        }
        $query->closeCursor();

        $query = $link->query("CREATE DATABASE IF NOT EXISTS `".$dbname."`;");
        try {
            $link = new PDO('mysql:host='.$dbhost.';'.$portstr.'dbname='.$dbname, $dbuser, $dbpwd, $options);
        } catch(Exception $exc) {
            showMsg('选择数据库失败，可能是你没权限，请先创建一个数据库！','-1');
        }
    } else {
        $link = mysql_connect($dbhost,$dbuser,$dbpwd) or showMsg('数据库连接失败，请重新设置！','-1');
        //获得数据库版本信息
        $res = mysql_query('SELECT VERSION();',$link);
        $row = mysql_fetch_array($rs);
        if (version_compare($row[0], '5.0.1')<0) {
            showMsg('MYSQL版本要大于5.0.1','-1');
        }
        mysql_query("CREATE DATABASE IF NOT EXISTS `".$dbname."`;",$link);
        mysql_select_db($dbname, $link) or showMsg('选择数据库失败，可能是你没权限，请先创建一个数据库！','-1');
        mysql_query("SET character_set_connection=utf8, character_set_results=utf8, character_set_client=binary,sql_mode=''", $link);
    }

    //生成后台访问文件
    $filename = filterString(_gp('filename'));
    if(empty($filename)){
        showMsg('后台访问地址不能为空','-1');
    }
    $code = <<<EOT
<?php
define('CURSCRIPT', 'admin');
define('ISMANAGE', true);
if (!empty(\$_GET['pluginid'])){
    define('ISPLUGIN', true);
}
require_once (dirname(__FILE__) . '/source/init.php');
include_once loadlib('misc');
if(!empty(\$_M['sys']['permit_area'])){
    \$_M['sys']['permit_area'] = str_replace(array(',','，'), '|', \$_M['sys']['permit_area']);
    if(!preg_match('/'.\$_M['sys']['permit_area'].'/', convertip(\$_M['ip']))){
        header("HTTP/1.0 404 Not Found");
        exit;
    }
}
if(empty(\$_M['mod'])){
    \$_M['mod'] = 'main';
}
\$_M['admin'] = t('admin')->loginInfo();
setPromptMsg(loadc('','',1));
EOT;
    file_put_contents(MOPROOT.$filename.'.php', $code) or showMsg('后台访问地址创建失败，请检查根目录是否可写入！','-1');

    //生成配置文件
    $cfg = array();
    $cfg['db']['connecttype'] = $dbdriver;
    $cfg['db']['dbhost'] = $dbhost;
    $cfg['db']['port'] = $dbport;
    $cfg['db']['dbuser'] = $dbuser;
    $cfg['db']['dbpwd'] = $dbpwd;
    $cfg['db']['pconnect'] = '0';
    $cfg['db']['dbname'] = $dbname;
    $cfg['db']['tablepre'] = $dbprefix;
    $cfg['prefix'] = random(6).'_';
    $cfg['redis']['server'] = '127.0.0.1';
    $cfg['redis']['port'] = '6379';
    $cfg['redis']['password'] = '';
    $cfg['redis']['pconnect'] = '1';
    $cfg['redis']['selectdb'] = '1';
    $cfg['cookie']['prefix'] = random(4).'_';
    $cfg['cookie']['domain'] = '';
    $cfg['cookie']['path'] = '/';
    $cfg['output']['charset'] = 'utf-8';
    $cfg['output']['gzip'] = '0';
    $cfg['authkey'] = md5($dbdriver.$dbhost.$dbport.$dbuser.$dbpwd.$dbname.time().random(20));
    $cfg['sitekey'] = md5($dbdriver.$dbhost.$dbport.$dbuser.$dbpwd.$dbname.time().random(20));

    $config = "<?php\n\r!defined('IN_MOPCMS') && exit ('Access failed');\n\r".'$cfg = '.var_export($cfg,true).';return $cfg;';
    file_put_contents(MOPDATA.'config.php', $config) or showMsg('配置文件创建失败，请检查../data目录是否可写入！','-1');

    //创建数据表
    $content = file_get_contents(MOPINSTALL.'/installsql.txt');
    $content = str_replace('#@#', $dbprefix, $content);
    foreach (explode('; ', $content) as $v){
        $v = trim($v);
        if($v){
            $dbdriver == 'pdo'?$link->query($v):mysql_unbuffered_query($v,$link);
        }
    }
    $_SERVER["REQUEST_SCHEME"] = !empty($_SERVER["REQUEST_SCHEME"])?$_SERVER["REQUEST_SCHEME"]:'http';
    $basehost = $_SERVER["REQUEST_SCHEME"].'://' . $_SERVER['HTTP_HOST'].str_replace('\\','',dirname(dirname($_SERVER['SCRIPT_NAME'])));
    $sql = 'update '.$dbprefix.'sys_setting set value=\''.$basehost.'\' where name=\'basehost\'';
    $sql1 = 'update '.$dbprefix.'sys_setting set value=\''.$basehost.'/static/\' where name=\'static_url\'';
    $dbdriver == 'pdo'?$link->query($sql):mysql_unbuffered_query($sql,$link);
    $dbdriver == 'pdo'?$link->query($sql1):mysql_unbuffered_query($sql1,$link);

    //增加管理员帐号
    $adminuser = filterString(_gp('adminuser'));
    $adminpwd = filterString(_gp('adminpwd'));
    $pwd = substr(md5($adminpwd . $cfg['authkey']), 5, 20);
    $sql = 'INSERT INTO `'.$dbprefix."admin` VALUES ('1', '1', '".$adminuser."', '".$pwd."', 'MOPCMS', '', '', '', '');";
    $dbdriver == 'pdo'?$link->query($sql):mysql_unbuffered_query($sql,$link);

    unlink('./index.php');
    file_put_contents(MOPDATA.'install.lock', '');
    include('./template/step_4.htm');
    exit();
}
