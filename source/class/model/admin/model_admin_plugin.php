<?php
/**
 * 插件模型类
 * @copyright           (C) 2016-2099 MOPCMS.COM
 * @license             http://www.mopcms.com/license/
 */
!defined('IN_MOPCMS') && exit('Access failed');
class model_admin_plugin extends base_model
{
    /**
     * 应用关闭或开启操作
     * @param $appid 应用ID
     * @param $available 是否可用(1可用,0不可用)
     */
    public function set_status($appid,$available)
    {
        if(empty($appid)){
            return getResult(10006);
        }
        $row = t('plugins')->fetch($appid);
        if(empty($row)){
            return getResult(10004);
        }
        $available = $available==1?1:0;
        t('plugins')->update($appid,array('available'=>$available));
        t('admin_menu')->update(array('pluginid'=>$row['identifier']),array('isshow'=>$available));
        $code = $available==1?11203:11202;
        return getResult(100,'',$code,'?mod=plugin&ac=list&menuid=86');
    }

    /**
     * 应用安装之将文件解出来
     * @param $identifier 应用标识符
     * @param $sitekey 应用下载密钥
     */
    public function install($identifier,$sitekey)
    {
        if(t('plugins')->count(array('identifier'=>$identifier,'isinstall'=>1))){
            return getResult(11207);
        }
        $pluginurl = MOPDATA.'plugins/'.xxtea($identifier,'en',$sitekey).'.xml';
        $result = $this->takeoutFile($pluginurl);
        if($result['code']!=100){
            return $result;
        }
        return getResult(100,'',11209);
    }

    /**
     * 应用 之将文件解出来
     * @param $identifier 应用标识符
     * @param $sitekey 应用下载密钥
     */
    public function update($identifier,$sitekey)
    {
        $pluginurl = MOPDATA.'plugins/update_'.xxtea($identifier,'en',$sitekey).'.xml';
        $result = $this->takeoutFile($pluginurl);
        if($result['code']!=100){
            return $result;
        }
        !empty($result['data']['version']) && t('plugins')->update(array('identifier'=>$identifier),array('version'=>$result['data']['version']));
        return getResult(100,'',11223);
    }

    /**
     * 从XML文件中解出文件
     * @param $pluginurl XML文件地址
     */
    private function takeoutFile($pluginurl)
    {
        if(!is_file($pluginurl)){
            return getResult(11211);
        }
        $xml = simplexml_load_file($pluginurl);
        if(!$xml){
            return getResult(11206);
        }
        foreach($xml->file as $v){
            if($v->path){
                $file = MOPROOT.$v->path;
                createdir(dirname($file));
                $content = base64_decode($v->content);
                if(!file_put_contents($file,$content)){
                    return getResult(11208,'',array('file'=>$v->path));
                }
            }
        }
        return getResult(100,array('version'=>current($xml->version)));
    }

    /**
     * 删除相关应用
     * @param $identifier 应用标识符
     * @param $sitekey 应用下载密钥
     */
    public function delete($identifier,$sitekey)
    {
        if(empty($identifier)){
            return getResult(10006);
        }
        $installxml = MOPDATA.'plugins/'.xxtea($identifier,'en',$sitekey).'.xml';
        if(is_file($installxml)){
            @unlink($installxml);
        }
        $updatexml = MOPDATA.'plugins/update_'.xxtea($identifier,'en',$sitekey).'.xml';
        if(is_file($updatexml)){
            @unlink($updatexml);
        }
        t('plugins')->delete(array('identifier'=>$identifier));
        t('admin_menu')->delete(array('pluginid'=>$identifier));
        t('sys_cache')->delete(array('name'=>'plugin:'.$identifier));
        return getResult(100,'',10016);
    }
}