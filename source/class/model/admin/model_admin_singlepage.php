<?php
/**
 * 后台单页文档管理模型类
 * @copyright			(C) 2016-2099 MOPCMS.COM
 * @license				http://www.mopcms.com/license/
 */
!defined('IN_MOPCMS') && exit('Access failed');
class model_admin_singlepage extends base_model
{
    /**
     * 文档添加修改操作
     */
    public function save($post)
    {
        if(empty($post['title'])||empty($post['template'])||empty($post['link'])){
            return getResult(10002);
        }
        if(strpos($post['link'], '.')===false){
            return getResult(10419);
        }
        $id = $post['id'];
        $data = array();
        $data['title'] = $post['title'];
        $data['keywords'] = $post['keywords'];
        $data['description'] = $post['description'];
        $data['template'] = $post['template'];
        $data['link'] = $post['link'];
        $data['content'] = $post['content'];
        $data['updatehtmltime'] = TIME;
        if($id){
            t('singlepage')->update($id,$data);
            $code = 10013;
        }else{
            $data['createtime'] = TIME;
            $data['adminid'] = $post['adminid'];
            $id = t('singlepage')->insert($data,true);
            $code = 10014;
        }
        $result = $this->_createhtml($id,$data);
        if($result['code']!=100){
            return $result;
        }
        return getResult(100,$id,$code,'?mod=singlepage&ac=list&menuid=75');
    }

    /**
     * 更新操作
     * @param unknown_type $id
     */
    public function refresh($id)
    {
        return $this->_createhtml($id);
    }
    /**
     * 生成静态页面
     * @param unknown_type $id
     * @param unknown_type $row
     */
    private function _createhtml($id,$row='')
    {
        global $_M;
        if(empty($id)){
            return getResult(10001);
        }
        if(empty($row)){
            $row = t('singlepage')->fetch($id);
        }
        if(empty($row)){
            return getResult(10004);
        }
        $fileurl = preg_replace("/\/{1,}/", "/", MOPROOT.'/'.$row['link']);
        if(!createdir(dirname($fileurl))){
            return getResult(10023);
        }
        $_M['_temp'] = $row['template'];
        include template($_M['_temp'], '',true);
        $str = output();
        $fp = @fopen($fileurl, "w");
        if($fp===false){
            return getResult(10420);
        }
        fwrite($fp, $str);
        fclose($fp);
        return getResult(100,'',10024);
    }

    /**
     * 删除操作
     * @param unknown_type $id
     */
    public function del($id)
    {
        if(empty($id)){
            return getResult(10001);
        }
        $row = t('singlepage')->fetch($id);
        if(empty($row)){
            return getResult(10004);
        }
        $fileurl = preg_replace("/\/{1,}/", "/", MOPROOT.'/'.$row['link']);
        t('singlepage')->delete($id);
        is_file($fileurl) && @unlink($fileurl);
        return getResult(100,'',10016);
    }
}