<?php

/**
 * 数据缓存类
 * @copyright			(C) 2016-2099 MOPCMS.COM
 * @license				http://www.mopcms.com/license/
 */
if (!defined('IN_MOPCMS')) {
    exit ('Access failed');
}

class helper_datacache
{
    private $cache_time = 86400;//缓存时间
    private $filename = '';
    private $cache_dir = '';
    private $update_time = 0;//更新时间戳

    /**
     * 
     * @param $filename 缓存文件
     * @param $time 缓存时间，如为字符串视为判断某表对应的更新时间节点
     */
    public function __construct($filename,$time='')
    {
        global $_M;
        $this->filename = $filename;
        $this->cache_dir = MOPDATA . 'datacache/';
        if(is_numeric($time)){
            $this->cache_time = $time;
        }else{
            if (is_string($time)){
                $this->update_time = t($time)->lastUpdateTime('get');
            }
            $this->cache_time = $_M['sys']['data_filecache_time'];
        }
    }

    /**
     * 返回缓存URL及文件时间
     */
    private function cache_info()
    {
        $cache = array ('filemtime' => 0);
        $cache['filename'] = $this->cache_dir . $this->filename . '.php';
        if (is_file($cache['filename'])) {
            $cache['filemtime'] = filemtime($cache['filename']);
        }
        return $cache;
    }

    /**
     * 获取缓存数据
     */
    public function get_cache()
    {
        if(t('archives')->redis_enable!==false){
            $key = get_class($this).':cachekey='.md5($this->filename);
            $data = t('archives')->redis()->get($key);
            if(aval($data,'time',0) < $this->update_time){
                $data = array();
            }
            return aval($data,'content');
        }
        $indexcache = $this->cache_info();
        if (($indexcache['filemtime'] > $this->update_time) && (TIME - $indexcache['filemtime'] < $this->cache_time)) {
            return require ($indexcache['filename']);
        }
        return array ();
    }

    /**
     * 更新缓存数据
     * @param unknown_type $content
     */
    public function save_cache($content)
    {
        if(t('archives')->redis_enable!==false){
            $key = get_class($this).':cachekey='.md5($this->filename);
            $data = array('content'=>$content,'time'=>TIME);
            return t('archives')->redis()->set($key,$data,$this->cache_time);
        }
        $indexcache = $this->cache_info();
        if (is_file($indexcache['filename'])) {
            @ unlink($indexcache['filename']);
        }
        if (is_array($content)) {
            $content = var_export($content, true);
        } else {
            $content = '\'' . str_replace('\'', '"', $content) . '\'';
        }
        $text = "<?php \n\r if (!defined('IN_MOPCMS')) {\n\r	exit('Access failed');\n\r}\n\r return " . $content . ';';
        require_once loadlib('file');
        putFile($indexcache['filename'], $text);
    }

    /**
     * 删除过期的缓存文件
     * @param string $filename 缓存文件名
     */
    public function del_expire_file($filename)
    {
        $path = dirname($filename);
        if (is_dir($path)) {
            $dh = @ dir($path);
            while (($file = $dh->read()) !== false){
                if ($file != "." && $file != "..") {
                    if((TIME-filemtime($path.'/'.$file))>$this->cache_time){
                        @ unlink($path.'/'.$file);
                    }
                }
            }
        }
    }
}