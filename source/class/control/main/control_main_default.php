<?php
/**
 * 默认控制类
 * @copyright			(C) 2016-2099 MOPCMS.COM
 * @license				http://www.mopcms.com/license/
 */
!defined('IN_MOPCMS') && exit('Access failed');
class control_main_default extends control_main
{
    /**
     * 验证码
     */
    public function seccode()
    {
        $img = new helper_securimage();
        $img->show();
    }

    /**
     * 专题报名页面
     */
    public function entry()
    {
        $sid = (int)_gp('sid');
        $result = loadm('archives_specials')->entry($sid);
        $result['data']['__iframe'] = (int)_gp('__iframe')?1:0;
        return $this->output($result);
    }
    /**
     * 专题报名处理方法
     */
    public function entry_submit()
    {
        global $_M;
        $sid = (int)_gp('sid');
        $referer = filterString(_gp('referer'));
        $result = loadm('archives_specials')->entry_save($sid);
        $result['referer'] = $referer?$referer:pseudoUrl($_M['cururl']);
        return $result;
    }

    /**
     * 首页
     */
    public function index()
    {
        global $_M;
        if($_M['iswap']){
            include template('index');
            exit;
        }else{
            if(_gp('makehtml')){
                include template('index');
                $str = output();
                file_put_contents(MOPROOT.'/index.html',$str);
            }
            header('Location:'.$_M['sys']['cmspath'].'index.html');
        }
    }

    /**
     * 下载并安装应用
     */
    public function downloadPlugin_submit()
    {
        global $_M;
        $isupdate = (int)_gp('isupdate');
        $dlkey = filterString(_gp('dlkey'));
        $data = _gp('data');
        $sitekey = &$_M['config']['sitekey'];
        $row = unserialize(xxtea($dlkey,'de',$sitekey));
        $identifier = filterString(aval($row, 'identifier'));
        if(empty($row['time'])||empty($identifier)){
            getjson(11212);
        }
        if(empty($data)){
            getjson(11214);
        }
        if($row['time']+3600<TIME){
            getjson(11213);
        }
        require_once loadlib('file');
        if($isupdate==1){
            putFile(MOPDATA.'plugins/update_'.xxtea($identifier,'en',$sitekey).'.xml', $data);
            $result = loadm('admin_plugin')->update($identifier,$sitekey);
            if($result['code']!=100){
                getjson($result);
            }
            $result = loadm($identifier.':')->update();
        }else{
            putFile(MOPDATA.'plugins/'.xxtea($identifier,'en',$sitekey).'.xml', $data);
            $result = loadm('admin_plugin')->install($identifier,$sitekey);
            if($result['code']!=100){
                getjson($result);
            }
            $result = loadm($identifier.':')->install();
        }
        getjson($result);
    }

    /**
     * 从官方网站上获取系统升级文件
     */
    public function cmsupgrade_submit()
    {
        global $_M;
        $dlkey = filterString(_gp('dlkey'));
        $data = _gp('data');
        $sitekey = &$_M['config']['sitekey'];
        $row = unserialize(xxtea($dlkey,'de',$sitekey));
        $finalv = filterString(aval($row, 'finalv'));
        $curv = filterString(aval($row, 'curv'));
        if(empty($row['time'])||empty($finalv)||empty($curv)){
            getjson(11212);
        }
        if(empty($data)){
            getjson(11214);
        }
        if($row['time']+3600<TIME){
            getjson(11213);
        }
        require_once loadlib('file');
        $fileurl = MOPDATA.'upgrade/'.$curv.'_'.$finalv.'.xml';
        putFile($fileurl, $data);
        if(!is_file($fileurl)){
            return getjson(10707);
        }
        getjson(100);
    }
}