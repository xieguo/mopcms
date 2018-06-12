<?php
/**
 * 后台缓存更新模型类
 * @copyright			(C) 2016-2099 MOPCMS.COM
 * @license				http://www.mopcms.com/license/
 */
!defined('IN_MOPCMS') && exit('Access failed');
class model_admin_cache extends base_model
{
    /**
     * 删除临时文件
     * @param $activepath 目录
     * @param $filename 文件名
     */
    public function temp_clear($activepath, $filename)
    {
        if(empty($filename)) {
            return getResult(10709);
        }
        $filename = MOPROOT . $activepath . '/' . $filename;

        if(is_file($filename)) {
            @ unlink($filename);
            $name = '文件';
        }
        elseif(is_dir($filename)){
            $name = '目录';
            require_once loadlib('file');
            rmRecurse($filename);
        }else{
            return getResult(10720);
        }
        return getResult(100, '', promptMsg(10710, array('name' => $name)));
    }
}