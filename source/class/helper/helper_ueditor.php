<?php
/**
 * ueditor编辑器处理类
 * @copyright           (C) 2016-2099 MOPCMS.COM
 * @license             http://www.mopcms.com/license/
 */
if (!defined('IN_MOPCMS')) {
    exit('Access failed');
}

class helper_ueditor
{
    private $config; //配置信息

    public function __construct()
    {
        $this->config = json_decode(preg_replace("/\/\*[\s\S]+?\*\//", "", file_get_contents(MOPSUPPLIER.'/ueditor/config.json')), true);
    }

    public function config()
    {
        return $this->config;
    }
    /**
     * 图片列表
     */
    public function listimage()
    {
        return $this->getlist();
    }
    /**
     * 附件列表
     */
    public function listfile()
    {
        return $this->getlist(array(2,3,4,5,6));
    }

    /**
     * 遍历获取目录下的指定类型的文件
     * @param $mediatype 附件类型
     */
    private function getlist($mediatype=1)
    {
        $start = (int)_gp('start');
        $page = floor($start/20);
        $where = array();
        $where['mediatype'] = $mediatype;
        $where['isfirst'] = 1;
        $condition = array();
        $condition['where'] = $where;
        $condition['page'] = $page;
        $condition['pagesize'] = (int)_gp('size');
        $condition['fields'] = 'url,title,mediatype';
        $info = t('uploads')->info_list($condition);
        $list = array();
        foreach ($info['list'] as $k=>$v) {
            if ($v['mediatype'] == 1) {
                $v['url'] = imageResize($v['url']);
            }
            $list[$k] = $v;
        }
        $result = array("state" => 'no match file','list' => array(),'start' => $start,'total' =>0);
        if($list){
            $result = array('state' => 'SUCCESS','list' => $list,'start' => $start,'total' => $info['count']);
        }
        unset($info);
        return $result;
    }

    public function catchimage()
    {
        global $_M;
        $result = array('state'=>'ERROR','url'=>'','title'=>'','original'=>'','type'=>'','size'=>'');

        /* 抓取远程图片 */
        $list = array();
        $source = filterString(_gp($this->config['catcherFieldName']));
        foreach ($source as $imgurl) {
            $url = imageResize($imgurl,$_M['sys']['img_width'],$_M['sys']['img_height'],true);
            $pathinfo = pathinfo($url);
            $result = array();
            $result['url'] = $url;
            $result['title'] = $pathinfo['basename'];
            $result['original'] = '';
            $result['type'] = '.'.$pathinfo['extension'];
            $result['size'] = filesize($url);
            $list[] = $result;
        }
        return array('state'=> count($list) ? 'SUCCESS':'ERROR','list'=> $list);
    }
    public function uploadimage()
    {
        return $this->upload($this->config['imageFieldName']);
    }
    public function uploadscrawl()
    {
        return $this->upload($this->config['scrawlFieldName']);
    }
    public function uploadvideo()
    {
        return $this->upload($this->config['videoFieldName']);
    }
    public function uploadfile()
    {
        return $this->upload($this->config['fileFieldName']);
    }

    /**
     * 上传文件的主处理方法
     * @param $fieldname 字段名称
     * @return array(
     *     "state" => "",          //上传状态，上传成功时必须返回"SUCCESS"
     *     "url" => "",            //返回的地址
     *     "title" => "",          //新文件名
     *     "original" => "",       //原始文件名
     *     "type" => ""            //文件类型
     *     "size" => "",           //文件大小
     * )
     */
    private function upload($fieldname)
    {
        $img = new mop_image;
        $result = array('state'=>'SUCCESS','url'=>'','title'=>'','original'=>'','type'=>'','size'=>'');
        $data = $img->uploadAttachment($fieldname);
        if ($data['code']!=100){
            $result['state'] = $data['msg'];
            return $result;
        }
        $pathinfo = pathinfo($data['data']);
        $result['url'] = imageResize($data['data']);
        $result['title'] = $pathinfo['basename'];
        $result['original'] = $_FILES[$fieldname]['name'];
        $result['type'] = '.'.$pathinfo['extension'];
        $result['size'] = $_FILES[$fieldname]['size'];
        unset($pathinfo);
        return $result;
    }
}