<?php
/**
 * 评论模型类
 * @copyright			(C) 2016-2099 MOPCMS.COM
 * @license				http://www.mopcms.com/license/
 */
!defined('IN_MOPCMS') && exit('Access failed');
class model_main_comments extends base_model
{
    /**
     * 获取某文档的评论列表数据
     */
    public function info_list($post)
    {
        if(empty($post['manage'])){
            $result = $this->_comment_check($post['aid']);
            if($result['code']!=100){
                return $result;
            }
        }
        $para = array();
        $where = array();
        if(!empty($post['aid'])){
            $where['aid'] = $post['aid'];
        }
        if(!empty($post['mid'])){
            $where['mid'] = $post['mid'];
        }
        if(!empty($post['columnid'])){
            $where['columnid'] = $post['columnid'];
        }
        if(!empty($post['ischeck'])){
            $where['ischeck'] = $post['ischeck'];
        }
        if(!empty($post['words'])){
            $where[] = db::fbuild('content', $post['words'],'like');
        }
        $para['where'] = $where;
        if(!empty($post['page'])){
            $para['page'] = (int)$post['page'];
        }
        if(!empty($post['fields'])){
            $para['fields'] = $post['fields'];
        }
        if(!empty($post['pagesize'])){
            $para['pagesize'] = $post['pagesize'];
        }
        $_data = array();
        $_data['_data'] = !empty($result['data']['arc'])?$result['data']['arc']:'';
        $_data['row'] = t('archives_comment')->info_list($para);
        if(!empty($post['manage'])){
            foreach ($_data['row']['list'] as &$v){
                $v['_aid'] = t('archives')->fetch($v['aid']);
                $v['_aid']['link'] = t('archives')->getHtmlLink($v['_aid']);
            }
        }
        return getResult(100,$_data);
    }

    /**
     * 对某条评论顶踩操作
     * @param unknown_type $id
     * @param unknown_type $type
     */
    public function comments_dingcai($id,$type='ding')
    {
        global $_M;
        if(!$_M['sys']['allow_comments']) {
            return getResult(10901);
        }
        if(empty($id)){
            return getResult(10001);
        }
        $type = $type=='cai'?'cai':'ding';
        t('archives_comment')->updateInc($id,$type);
        t('archives_comment')->lastUpdateTime();
        return getResult(100);
    }

    /**
     * 保存评论
     * @param array $post
     */
    public function save($post)
    {
        return $this->_save($post);
    }
    private function _save($post)
    {
        global $_M;
        $result = $this->_comment_check($post['aid']);
        if($result['code']!=100){
            return $result;
        }
        $arc = &$result['data']['arc'];
        if(empty($post['content'])||strlen($post['content'])<4){
            return getResult(10900);
        }
        //限制游客发布次数
        if (empty($_M['sys']['visitor_comment'])) {
            return getResult(10903);
        }
        //检查评论间隔时间
        if (!empty($_M['sys']['feedback_time'])) {
            //检查最后发表评论时间，如果未登陆判断当前IP最后评论时间
            $dtime = $this->last_time();
            if (!empty($dtime) && (TIME - $dtime < $_M['sys']['feedback_time'])) {
                return getResult(10904);
            }
        }
        $data = array();
        $data['ischeck'] = 2;
        if($_M['sys']['feedbackcheck'] || $this->check_fbtime()){
            $data['ischeck'] = 1;
        }
        $data['aid'] = (int)$post['aid'];
        $data['columnid'] = $arc['columnid'];
        $data['username'] = !empty($_M['username']) ? $_M['username'] : '';
        $data['ip'] = $_M['ip'];
        $data['createtime'] = TIME;
        $data['mid'] = $_M['mid'];
        $data['content'] = $post['content'];
        $id = t('archives_comment')->insert($data, true);
        $row = t('archives_comment')->fetch($id);
        unset($row['content'],$row['ip'],$row['ischeck'],$row['aid']);
        return getResult(100,$row);
    }

    /**
     * 相关评论的前期检测
     */
    private function _comment_check($aid)
    {
        global $_M;
        if(empty($aid)){
            return getResult(10001);
        }
        if (!$_M['sys']['allow_comments']) {
            return getResult(10901);
        }
        $arc = t('archives')->fetch($aid);
        if (empty($arc)) {
            return getResult(10004);
        }
        if ($arc['nocomment'] == 1 || !empty($arc['redirecturl'])) {
            return getResult(10902);
        }
        return getResult(100,array('arc'=>$arc));
    }

    /**
     * 最后一次评论时间
     */
    private function last_time()
    {
        global $_M;
        $condition = array('ip'=>$_M['ip']);
        if ($_M['mid']){
            $condition = array('mid'=>$_M['mid']);
        }
        $row = t('archives_comment')->fetchList($condition,'createtime',1);
        return !empty($row)?$row[0]['createtime']:'';
    }

    /**
     * 回复某条评论
     * @param $cmtid 某评论ID
     * @param $msg 回复内容
     */
    public function reply($cmtid, $msg)
    {
        if(empty($cmtid)){
            return getResult(10001);
        }
        if(empty($msg)||strlen($msg)<4){
            return getResult(10900);
        }
        $row = t('archives_comment')->fetch($cmtid);
        if (empty($row)) {
            return getResult(10004);
        }
        //留言进行格式化处理
        $bmsg = strrchr($row['content'], '{/quote}');
        $bmsg = str_replace('{/quote}', '', $bmsg);
        $fmsg = trim($row['content'], $bmsg);
        if (!$bmsg) {
            $bmsg = $fmsg;
            $fmsg = '';
        }
        $msg = delHtml($msg,3);
        $post = $row;
        $post['content'] = '{quote}'.$fmsg.'{title}'.$row['username'].'的原帖：{/title}{content}'.$bmsg.'{/content}{/quote}'.$msg;
        return $this->_save($post);
    }

    /**
     * 判断在此时间内的评论自动进入待审核
     */
    private function check_fbtime()
    {
        global $_M;
        if (!empty($_M['sys']['fbchecktime']) && strpos($_M['sys']['fbchecktime'], '-')!==false) {
            list($start, $end) = explode('-', $_M['sys']['fbchecktime']);
            $start = strtotime($start);
            $end = strtotime($end);
            if ($start < $end && (TIME > $start && $end > TIME) || $start > $end && (TIME > $start || $end > TIME )) {
                return true;
            }
        }
        return false;
    }

    /**
     * 评论审核
     * @param unknown_type $id
     * @param unknown_type $ischeck
     */
    public function comments_check($id,$ischeck)
    {
        if(empty($id)){
            return getResult(10001);
        }
        $ischeck = $ischeck==1?1:2;
        $ids = explode(',', $id);
        t('archives_comment')->update(array('id'=>$ids),array('ischeck'=>$ischeck));
        t('archives_comment')->lastUpdateTime();
        return getResult(100);
    }

    /**
     * 评论删除
     * @param unknown_type $id
     */
    public function comments_del($id)
    {
        if(empty($id)){
            return getResult(10001);
        }
        $ids = explode(',', $id);
        t('archives_comment')->delete(array('id'=>$ids));
        return getResult(100);
    }
}