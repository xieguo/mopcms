<?php
/**
 * 模型文档主表处理类
 * @copyright           (C) 2016-2099 MOPCMS.COM
 * @license             http://www.mopcms.com/license/
 */
!defined('IN_MOPCMS') && exit('Access failed');

class table_archives extends base_table
{
    private $check_words = array('-1'=>'已删除','0'=>'<font color="red">未审核</font>','1'=>'已审核');

    /**
     * 对数据重新处理
     * @param array $data
     */
    protected function parseData(&$data)
    {
        if(!empty($data['createtime'])) {
            $data['_createtime'] = mdate($data['createtime']);
        }
        if(!empty($data['updatetime'])) {
            $data['_updatetime'] = mdate($data['updatetime']);
        }
        if(!empty($data['updatehtmltime'])) {
            $data['_updatehtmltime'] = mdate($data['updatehtmltime'], 'dt');
        }
        if(isset($data['status'])) {
            $data['_status'] = $this->check_words[$data['status']];
        }
    }

    /**
     * 某栏目下文档数量
     * @param unknown_type $columnid
     */
    public function countByColumnid($columnid)
    {
        $columnids = t('column')->getSubids($columnid, true);
        $where = array();
        $where['columnid'] = $columnids;
        $where[] = db::fbuild('status', 0, '>=');
        return $this->count($where);
    }

    public function listByCondition($para)
    {
        global $_M;
        $sc = !empty($para['sc']) && $para['sc'] == 'asc' ? 'asc' : 'desc';
        $pagesize = !empty($para['pagesize'])? (int) $para['pagesize'] : 20;
        $start =($_M['page'] - 1) * $pagesize;
        $order = !empty($para['order']) ? $para['order']:$this->pkey;
        $where = !empty($para['where']) ? ' where ' . $this->buildWhere($para['where']) : '';
        if($this->redis_enable) {
            $keystr = str_replace("'", '', var_export($where, true)) . '_' . $start. '_' . $pagesize. '_' . aval($para, 'limit'). '_' . aval($para, 'table') . '_' . $order . '_' . $sc . '_' .($_M['iswap'] ? 1 : '0');
            $key = __FUNCTION__ . '_' . md5($keystr);
            $lastupdate = $this->redis_get('lastupdate');
            $createtime = $this->redis_get($key.':createtime');
            $row = $this->redis_get($key);
            if($row && !empty($createtime) && $createtime>$lastupdate) {
                return $row;
            }
        }
        if(!empty($para['table'])) {
            $leftjoin = strpos($where, ' m.') && pluginIsAvailable('member') ? ' left join ' . db :: table('member') . ' m on m.id=arc.mid ' : '';
            $sql = 'Select arc.* from ' . db :: table($this->tname) . ' arc left join ' . db :: table($para['table']) . ' addt on addt.id=arc.id ' . $leftjoin . ' ' . $where . ' order by ' . $order . ' ' . $sc;
        } else {
            $sql = 'Select arc.* from ' . db :: table($this->tname) . ' arc ' . $where . ' order by ' . $order . ' ' . $sc;
        }
        if(!empty($para['limit'])) {
            if(is_numeric($para['limit'])){
                $start = 0;
                $pagesize = $para['limit'];
            }else{
                list($start, $pagesize) = explode(',', $para['limit']);
            }
        }
        require_once loadlib('archives');
        $row = db :: fetch_all($sql . db :: parse_limit($start, $pagesize));
        foreach($row as $k => $v) {
            $v['fulltitle'] = $v['title'];
            if(!empty($para['titlelen'])){
                $v['title'] = msubstr($v['title'],$para['titlelen']);
            }
            $v['htmltitle'] = $v['title'];
            if(!empty($v['color'])){
                $v['htmltitle'] = '<font color="'.$v['color'].'">'.$v['htmltitle'].'</font>';
            }
            if(strpos($v['flag'],'b')!==false){
                $v['htmltitle'] = '<b>'.$v['htmltitle'].'</b>';
            }

            if(!empty($para['imgs'])){
                $v['imgs'] = array();
                $para['table'] = t('models')->fetchFields($v['modelid'], 'tablename');
                $htmltext = t('models_fields')->fetchFields(array('modelid'=>$v['modelid'],'datatype'=>'htmltext'), 'fieldname');
                $para['fields'] = &$htmltext;
            }

            if($this->redis_enable) {
                $v += archiveInfo($v['id'], aval($para,'fields'));
            }else{
                $this->parseData($v);
                $v['link'] = $this->getHtmlLink($v);
                if(!empty($para['fields']) && !empty($para['table'])){
                    $fields = $para['fields']=='*'?'*':'id,'.$para['fields'];
                    $v['attach'] = t($para['table'])->fetchFields($v['id'],$fields);
                    if(!empty($v['attach'])){
                        $v['attach']['modelid'] = $v['modelid'];
                        $this->attachFieldsValue($para['fields'], $v['attach']);
                    }
                }
            }
            if(!empty($para['imgs']) && !empty($htmltext) && !empty($v['attach'][$htmltext])){
                $v['imgs'] = filterImgs(mstripslashes($v['attach'][$htmltext]),$para['imgs']);
            }
            $v['i'] = $k;
            $row[$k] = $v;
        }
        if($this->redis_enable) {
            $this->redis_set($key, $row, 600);
            $this->redis_set($key.':createtime', TIME, 864000);
        }
        return $row;
    }

    public function countByCondition($para)
    {
        global $_M;
        $where = !empty($para['where']) ? ' where ' . $this->buildWhere($para['where']) : '';
        if($this->redis_enable) {
            $key = md5(__FUNCTION__.$where);
            if($count = (int)$this->redis_get($key)) {
                return $count;
            }
        }
        if(!empty($para['table'])) {
            $leftjoin = strpos($where, ' m.') && pluginIsAvailable('member') ? ' left join ' . db :: table('member') . ' m on m.id=arc.mid ' : '';
            $sql = "Select count(*) from " . db :: table($this->tname) . " arc left join `" . db :: table($para['table']) . '` addt on addt.id=arc.id ' . $leftjoin . $where;
        } else {
            $sql = "Select count(*) from " . db :: table($this->tname) . ' arc ' . $where;
        }
        $count = (int) db :: result_onefield($sql);
        if($this->redis_enable) {
            $this->redis_set($key, $count, 600);
        }
        return $count;
    }

    /**
     * 保存某模型下的文档数据
     * @param $id 文档ID
     * @version 1.0
     */
    public function save($id = '')
    {
        global $_M;
        require_once loadlib('archives');
        $modelid = (int)_gp('modelid');
        $data = $row = array();
        $data['columnid'] = (int) _gp('columnid');
        if($id){
            $row = $this->fetch($id);
            if(empty($row)){
                return getResult(10004);
            }
            $modelid = $row['modelid'];
            if(empty($data['columnid'])){
                $data['columnid'] = $row['columnid'];
            }
        }

        if($data['columnid']){
            $_modelid = t('column')->fetchFields($data['columnid'], 'modelid');
            if($id && $row['modelid']!=$_modelid){
                return getResult(10402);
            }
            if(!$modelid){
                $modelid = $_modelid;
            }
            elseif($modelid!=$_modelid){
                return getResult(10402);
            }
        }
        if(empty($modelid)){
            return getResult(10401);
        }
        $model = t('models')->fetch($modelid);
        if(empty($data['columnid'])){
            $data['columnid'] = $model['columnid'];
        }

        if($modelid!=-1 && empty($data['columnid'])){
            return getResult(10404);
        }

        if(!empty($_M['admin']['_groupid']['_mpurviews']) && !in_array($modelid,$_M['admin']['_groupid']['_mpurviews'])){
            return getResult(10103);
        }

        if(empty($model['enable'])||empty($model['tablename'])){
            return getResult(10315);
        }
        //必填检测
        if(!$id){
            define('ATTACHCHECK',true);
        }
        $result = t('models_fields')->checkRequiredFields($modelid);
        if($result['code'] != 100) {
            return $result;
        }
        //唯一检测
        $result = t('models_fields')->checkUniqueFields($modelid,$id);
        if($result['code'] != 100) {
            return $result;
        }

        if($data['columnid'] && t('column')->fetchFields($data['columnid'], 'modelid')!=$modelid){
            return getResult(10402);
        }
        if(empty($_GET['title'])) {
            return getResult(10007,'',array('name'=>$model['titlename']));
        }

        $data['title'] = filterString(_gp('title'));
        if($model['unique']==1 && t('archives')->count(array('modelid'=>$modelid,'title'=>$data['title']))){
            return getResult(10020,'',array('title'=>'文档'));
        }
        $data['updatetime'] = TIME;
        if (!empty($_M['admin']['id'])) {
            $data['displayorder'] = (int)_gp('displayorder');
            $data['color'] = preg_replace('/[^\w#]/i','',_gp('color'));
            $data['writer'] = filterString(_gp('writer'));
            $data['ishtml'] = _gp('ishtml')?1:0;
            $data['redirecturl'] = filterString(_gp('redirecturl'));
            $data['adminid'] = (int)$_M['admin']['id'];
            $data['nocomment'] = _gp('nocomment')?1:0;
            $data['status'] = _gp('status')?1:0;
            $data['filename'] = filterString(_gp('filename'));
            $data['source'] = filterString(_gp('source'));
            $flag = filterString(_gp('flag'));
            $data['flag'] = is_array($flag)?implode(',',$flag):$flag;
            $data['thumb'] = filterString(_gp('picname'));
            $data['views'] = (int)_gp('views');
            if (empty($data['views'])){
                $data['views'] = $_M['sys']['arc_views'] == '-1' ? mt_rand(50, 200) : $_M['sys']['arc_views'];
            }
        }else{
            if(empty($model['allowpub'])) {
                return getResult(10403);
            }
            if($model['docstatus']==2) {
                $data['ishtml'] = 0;
            }
            elseif($model['docstatus']==1) {
                $data['ishtml'] = 1;
            }else{
                $data['status'] = 0;
                $data['ishtml'] = 0;
            }
            if(!empty($_FILES['thumb']['tmp_name'])) {
                $img = new mop_image;
                $result = $img->uploadAttachment('thumb');
                if($result['code'] != 100) {
                    return $result;
                }
                $data['thumb'] = $result['data'];
            }
            if($model['need_diysort']) {
                $data['diysort'] = (int) _gp('diysort');
            }
        }

        if($id){
            $row['attach'] = t($model['tablename'])->fetch($id);
        }

        $result = t('models_fields')->fetchFieldsValue($modelid, $row, $data);
        if($result['code'] != 100) {
            return $result;
        }
        $extras = $result['data'];
        unset($result);

        $attach_obj = t($model['tablename']);
        if(!empty($_M['admin']['id']) && $modelid==-1){
            $extras['template'] = filterString(_gp('template'));
            $extras['node'] = $attach_obj->nodeData();
        }

        //预处理
        if (method_exists($attach_obj, 'save_pre_processing')) {
            $result = $attach_obj->save_pre_processing($data,$extras);
            if($result['code'] != 100){
                return $result;
            }
        }

        if($id) {
            if(!empty($data['thumb']) && $data['thumb']!=$row['thumb']) {
                loadm('archives')->del_image($row['thumb']);
            } else {
                unset($data['thumb']);
            }
            unset($data['adminid']);
            $this->update($id, $data);
            $attach_obj->update($id, $extras);
            //更新修改时间
            $this->lastUpdateTime();
            $attach_obj->lastUpdateTime();
        } else {
            $data['mid'] = $_M['mid'];
            if(empty($data['writer'])) {
                if(defined('ISMANAGE') && ISMANAGE===true && !empty($_M['admin']['nickname'])) {
                    $data['writer'] = $_M['admin']['nickname'];
                }else{
                    $data['writer'] = $_M['username'];
                }
            }
            $data['modelid'] = $modelid;
            $data['createtime'] = TIME;
            $id = $this->insert($data,true);

            $extras['id'] = $id;
            $extras['columnid'] = $data['columnid'];
            $attach_obj->insert($extras);
        }
        //更新静态页面地址
        $this->update($id,array('htmllink'=>$this->insideHtmlLink($id)));
        clearAttachCache($id, $data['title']);//将上传的附件归属到相应的ID下

        //生成HTML
        if($data['ishtml']) {
            $arc = new archive_view($id);
            $arc->create_html();
        }
        $prompt_code = !empty($data['createtime'])?10407:10406;
        return getResult(100,$id,$prompt_code);
    }

    /**
     * 获取附加表部分字段处理后的数据
     * @param $fields 字段名
     * @param $v 字段值
     */
    public function attachFieldsValue($fields, &$v) {
        if(empty($v['modelid'])){
            return array();
        }
        $where = array();
        $where['modelid'] = $v['modelid'];
        if($fields!='*'){
            $where['fieldname'] = explode(',', $fields);
        }
        $fields = t('models_fields')->fetchList($where, 'title,fieldname,datatype,units,rules');
        $formdata = new mop_formdata;
        $formdata->get_value_from_db($fields, $v);
    }

    /**
     * 获取文档详细的URL
     * @param $row 某文档数据
     */
    public function getHtmlLink($row)
    {
        global $_M;
        if(empty($row)) {
            return '';
        }
        if(is_numeric($row)) {
            $row = $this->fetch($row);
        }
        if($row['status']<1||$row['ishtml']<1||$_M['iswap']){
            return pseudoUrl($_M['sys']['basehost']."/main.php?mod=archives&ac=view&id=".$row['id']);
        }
        if(!empty($row['redirecturl'])) {
            return $row['redirecturl'];
        }
        if(!empty($row['htmllink'])) {
            if(empty($row['column'])){
                $row['column'] = t('column')->fetch($row['columnid']);
            }
            if(!empty($row['column']['binddomain'])){
                return $row['column']['binddomain'] . preg_replace("#^" . $row['column']['sitepath'] . '#', '', $row['htmllink']);
            }
            return $_M['sys']['basehost'].$row['htmllink'];
        }
        return pseudoUrl($_M['sys']['basehost']."/main.php?mod=archives&ac=view&id=".$row['id']);
    }

    /**
     * 获取文档的存放路径
     */
    public function insideHtmlLink($row)
    {
        global $_M;
        if(empty($row)) {
            return '';
        }
        if(is_numeric($row)) {
            $row = $this->fetch($row);
        }
        if(empty($row['column'])){
            $row['column'] = t('column')->fetch($row['columnid']);
        }
        //专题URL单独处理
        if($row['modelid']==-1){
            $tablename = t('models')->fetchFields($row['modelid'],'tablename');
            $template = t($tablename)->fetchFields($row['id'],'template');
            return $_M['sys']['special_dir'] .($template ? $template : $row['id']).'/index.html';
        }

        $url = str_replace(array('[savedir]', '[Y]', '[m]', '[d]', '[aid]'),
        array($row['column']['savedir'],date('Y',$row['createtime']),date('m',$row['createtime']),date('d',$row['createtime']),$row['id']),$row['column']['ruleview']);
        if(!empty($row['filename'])){
            $pathinfo = pathinfo($url);
            $url = $pathinfo['dirname'].'/'.$row['filename'];
        }
        return str_replace('//', '/', $url);
    }
}