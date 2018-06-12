<?php
/**
 * 后台缓存控制类
 * @copyright			(C) 2016-2099 MOPCMS.COM
 * @license				http://www.mopcms.com/license/
 */
!defined('IN_MOPCMS') && exit('Access failed');
class control_admin_cache extends control_admin
{
    /**
     * 清除缓存
     */
    public function cache_clear()
    {
        return $this->output();
    }
    public function cache_clear_submit()
    {
        $item = filterString(_gp('item'));
        
        if(aval($item,0)=='template'){
            loadm('admin_cache')->temp_clear('/data', $item[0]);
            createdir(MOPDATA.$item[0]);
        }
        if(aval($item,1)=='datacache'){
            loadm('admin_cache')->temp_clear('/data', $item[1]);
            createdir(MOPDATA.$item[1]);
        }
        $result = getResult(100,'',10507);
        return $this->output($result,false);
    }
}