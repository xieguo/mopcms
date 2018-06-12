<?php
/**
 * 文档评论类
 * @copyright           (C) 2016-2099 MOPCMS.COM
 * @license             http://www.mopcms.com/license/
 */
!defined('IN_MOPCMS') && exit('Access failed');

class table_archives_comment extends base_table
{
    protected function parseData(&$data)
    {
        if(!empty($data['ischeck'])){
            $data['_ischeck'] = $data['ischeck']==1?'未审核':'已审核';
        }
        if(!empty($data['createtime'])){
            $data['_createtime'] = mdate($data['createtime'],'u');
        }
        if(!empty($data['content'])){
            $data['_content'] = $this->quoteReplace($data['content']);
        }
        if(isset($data['username']) && empty($data['username'])){
            $ip = '';
            if(!defined('ISMANAGE') && !empty($data['ip'])){
                list($ip1,$ip2,$ip3,$ip4) = explode('.', $data['ip']);
                $ip = '(IP:'.$ip1.'.'.$ip2.'.*.*)';
            }
            $data['username'] = '游客'.$ip;
        }
    }

    /**
     * 替换定义好的内容格式
     * @param $content
     */
    private function quoteReplace($content)
    {
        $search = array('{quote}','{title}','{/title}','&lt;br/&gt;','&lt;','&gt;','{content}','{/content}','{/quote}');
        $replace = array(
	    '<div class="decmt-box">',
	    '<div class="decmt-title"><span class="username">',
	    '</span></div>',
	    '<br>',
	    '<',
	    '>',
	    '<div class="decmt-content">',
	    '</div>',
	    '</div>'
	    );
	    return str_replace($search,$replace,$content);
    }
}