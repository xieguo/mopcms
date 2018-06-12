<?php
/**
 * 自定义表单控制类
 * @copyright			(C) 2016-2099 MOPCMS.COM
 * @license				http://www.mopcms.com/license/
 */
!defined('IN_MOPCMS') && exit('Access failed');
class control_main_customform extends control_main
{
    private $formid = 0;

    /**
     * 输出数据
     * @param $result 数据
     * @param $template 调用模板名称
     * @param $referer 跳转URL
     */
    protected function output($res='',$template='',$referer='')
    {
        $temp= $template?$template:$this->temp();
        return parent::output($res,$temp,$referer);
    }

    /**
     * 如果对应模板不存在，就调用默认模板
     */
    private function temp()
    {
        global $_M;
        if($this->formid && is_file(MOPTEMPLATE .($_M['iswap']?'wap/':''). CURSCRIPT . '/' . $this->mod . '/' . $this->ac . '_' . $this->formid . '.htm')) {
            return $this->ac . '_' . $this->formid;
        }
        return $this->ac;
    }

    /**
     * 自定义表单发布页面
     */
    public function customform_pub()
    {
        $this->formid = (int)_gp('formid');
        $res = loadm('main_customform')->fetch_by_formid($this->formid,'pub');
        $res['data']['__iframe'] = (int)_gp('__iframe')?1:0;
        return $this->output($res);
    }
    /**
     * 自定义表单发布处理方法
     */
    public function customform_pub_submit()
    {
        global $_M;
        $formid = (int)_gp('formid');
        $referer = filterString(_gp('referer'));
        $res = loadm('main_customform')->save($formid);
        $res['referer'] = $referer?$referer:pseudoUrl($_M['cururl']);
        return $res;
    }

    /**
     * 自定义表单列表展示
     */
    public function customform_list()
    {
        $this->formid = (int)_gp('formid');
        $res = loadm('main_customform')->fetch_by_formid($this->formid,'show');
        if($res['code']!=100){
            return $res;
        }
        $sc = filterString(_gp('sc'));
        $order = filterString(_gp('order'));
        $res['data']['search_fields'] = loadm('main_customform')->search_fields($this->formid);
        $res['data']['order_fields'] = loadm('main_customform')->orderby_fields($this->formid,$sc,$order);
        $ordercheck = false;
        foreach ($res['data']['order_fields'] as $v){
            if($v['fieldname']==$order){
                $ordercheck = true;
                break;
            }
        }
        $order = $ordercheck===true?$order:'';
        $post = array();
        $post['formid'] = $this->formid;
        $post['words'] = filterString(_gp('words'));
        $post['where'] = array('ischeck'=>2);
        $post['sc'] = filterString(_gp('sc'));
        $post['order'] = $order;
        $result = loadm('main_customform')->data_list($post);
        //计算底部操作需要占用的td数
        $result['colspan'] = count($result['fieldlist']);
        $result['colspan'] += 3;
        $res['data'] += $result;
        return $this->output($res);
    }

    /**
     * 自定义表单详细数据
     */
    public function customform_view()
    {
        $this->formid = (int)_gp('formid');
        $res = loadm('main_customform')->fetch_by_formid($this->formid,'show');
        if($res['code']!=100){
            return $res;
        }
        $id = (int)_gp('id');
        $result = loadm('main_customform')->data_view($this->formid,$id);
        if (empty($result)){
            return getResult(10004);
        }
        if($result['ischeck']==1){
            return getResult(10021);
        }
        $res['data']['_data'] = $result;
        return $this->output($res);
    }
}