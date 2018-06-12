<?php
/**
 * 插件控制类
 * @copyright			(C) 2016-2099 MOPCMS.COM
 * @license				http://www.mopcms.com/license/
 */
!defined('IN_MOPCMS') && exit('Access failed');
class control_admin_plugin extends control_admin
{
    /**
     * 应用中心
     */
    public function plugin_appcenter()
    {
        $_data = array();
        $_data['token'] = $this->appToken();
        return $this->output($_data);
    }

    /**
     * 插件列表
     */
    public function plugin_list()
    {
        global $_M;
        $post = array();
        $post['page'] = $_M['page'];
        $post['table'] = 'plugins';
        $post['order'] = 'isinstall desc,available desc,id';
        $result = loadm('admin_plugin')->info_list($post);
        $result['data']['token'] = $this->appToken();
        return $this->output($result);
    }

    /**
     * 应用安装
     */
    public function plugin_install()
    {
        global $_M;
        $plugin = filterString(_gp('plugin'));
        if(empty($plugin)){
            return getResult(11201);
        }
        $result = loadm('admin_plugin')->install($plugin,$_M['config']['sitekey']);
        if($result['code']!=100){
            return $result;
        }
        return loadm($plugin.':')->install();
    }

    /**
     * 应用卸载
     */
    public function plugin_uninstall()
    {
        $deldata = !empty($_GET['deldata']);
        $plugin = filterString(_gp('plugin'));
        if(empty($plugin)){
            return getResult(11201);
        }
        return loadm($plugin.':')->uninstall($deldata);
    }

    /**
     * 删除相关应用
     */
    public function plugin_delete()
    {
        global $_M;
        $plugin = filterString(_gp('plugin'));
        return loadm('admin_plugin')->delete($plugin,$_M['config']['sitekey']);
    }

    /**
     * 关闭或开启应用
     */
    public function plugin_setstatus()
    {
        $appid = (int)_gp('appid');
        $status = (int)_gp('status');
        return loadm('admin_plugin')->set_status($appid,$status);
    }
}