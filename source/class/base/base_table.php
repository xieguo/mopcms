<?php
/**
 * table类的基类
 * @copyright            (C) 2016-2099 MOPCMS.COM
 * @license                http://www.mopcms.com/license/
 */
!defined('IN_MOPCMS') && exit('Access failed');

class base_table extends base_core
{

    public $tname;
    protected $pkey = 'id';
    private $redis_ttl = 86400;
    public $redis_enable = false;
    private static $redis = false;

    public function __construct()
    {
        if (!is_object(self::$redis)) {
            global $_M;
            self::$redis = new base_redis($_M['config']['redis']);
        }
        $this->redis_enable = self::$redis->enable();
        $this->tname = str_replace('table_', '', get_class($this));
        parent:: __construct();
    }

    public function redis()
    {
        return self::$redis;
    }

    public function count($condition = '')
    {
        $sql = $this->selectsql('count(*)', $condition);
        return (int)db:: result_onefield($sql);
    }

    public function update($condition, $data)
    {
        if (empty($condition) || empty($data) || !is_array($data)) {
            return false;
        }
        $where = $this->buildWhere($condition);
        $result = db:: update($this->tname, $data, $where);
        if ($this->redis_enable && $result) {
            $row = $this->fetchList($where, $this->pkey);
            foreach ($row as $v) {
                $this->redis_update($v[$this->pkey], $data);
                $this->clearFetchFieldsCache($v[$this->pkey]);
            }
        }
        return $result;
    }

    /**
     * 某字段递增/减处理
     * @param unknown_type $condition
     * @param unknown_type $field
     * @param unknown_type $val
     */
    public function updateInc($condition, $field, $val = 1, $glue = '+')
    {
        if (empty($condition) || empty($field)) {
            return false;
        }
        $glue = $glue == '-' ? '-' : '+';
        $where = $this->buildWhere($condition);
        $result = db:: query('update ' . db:: table($this->tname) . ' set ' . $field . '=' . $field . $glue . $val . ' where ' . $where);
        if ($this->redis_enable && $result) {
            $row = $this->fetchList($where, $this->pkey . ',' . $field);
            foreach ($row as $v) {
                $this->redis_update($v[$this->pkey], array($field => $v[$field]));
                $this->clearFetchFieldsCache($v[$this->pkey]);
            }
        }
        return true;
    }

    public function delete($condition)
    {
        if (empty($condition)) {
            return false;
        }
        $condition = $this->buildWhere($condition);
        if ($this->redis_enable) {
            $row = $this->fetchList($condition, $this->pkey);
            foreach ($row as $v) {
                $this->redis_del($v[$this->pkey]);
                $this->clearFetchFieldsCache($v[$this->pkey]);
            }
        }
        $this->lastUpdateTime();
        return db:: delete($this->tname, $condition);
    }

    public function insert($data, $insertid = false, $replace = false)
    {
        $this->lastUpdateTime();
        return db:: insert($this->tname, $data, $insertid, $replace);
    }

    public function fetch($id)
    {
        if (empty($id)) {
            return array();
        }
        if ($data = $this->redis_get($id)) {
            $this->parseData($data);
            return $data;
        }
        $sql = $this->selectsql('*', db:: fbuild($this->pkey, $id));
        $data = db:: fetch_array($sql);
        if (!empty($data)) {
            $this->redis_set($id, $data);
            $this->parseData($data);
        }
        return $data;
    }

    protected function parseData(&$data)
    {
        if (!empty($data['createtime'])) {
            $data['_createtime'] = mdate($data['createtime'], 'dt');
        }
    }

    /**
     * 获取列表数据（会先遍历一遍数据）
     * @param unknown_type $where
     * @param unknown_type $fields
     * @param unknown_type $limit
     * @param unknown_type $order
     * @param unknown_type $sc
     */
    public function fetchListLoop($where = '', $fields = '*', $limit = '', $order = '', $sc = 'desc')
    {
        return $this->_fetchList(array('where' => $where, 'fields' => $fields, 'limit' => $limit, 'order' => $order, 'sc' => $sc, 'loop' => true));
    }

    /**
     * 获取列表数据（不会先遍历一遍数据）
     * @param unknown_type $where
     * @param unknown_type $fields
     * @param unknown_type $limit
     * @param unknown_type $order
     * @param unknown_type $sc
     */
    public function fetchList($where = '', $fields = '*', $limit = '', $order = '', $sc = 'desc')
    {
        return $this->_fetchList(array('where' => $where, 'fields' => $fields, 'limit' => $limit, 'order' => $order, 'sc' => $sc));
    }

    private function _fetchList($post)
    {
        $where = aval($post, 'where');
        $limit = aval($post, 'limit');
        $order = !empty($post['order']) ? $post['order'] : $this->pkey;
        $sc = $post['sc'] == 'asc' ? 'asc' : 'desc';
        $fields = !empty($post['fields']) ? $post['fields'] : '*';
        $loop = aval($post, 'loop', false);
        $orderby = ' order by ' . $order . ' ' . $sc;
        if ($this->redis_enable) {
            global $_M;
            $keystr = str_replace("'", '', var_export($where, true)) . '_' . $fields . '_' . $limit . '_' . $order . '_' . $sc . '_' . ($_M['iswap'] ? 1 : '0');
            $key = __FUNCTION__ . '_' . md5($keystr);
            $list = $this->redis_get($key);

            $lastupdate = $this->redis_get('lastupdate');
            $createtime = $this->redis_get($key . ':createtime');
            if (empty($list) || empty($createtime) || $createtime < $lastupdate) {
                if (preg_match('/distinct /i', $fields) || $loop === false) {
                    $sql = $this->selectsql($fields, $where, $orderby, $limit);
                } else {
                    $sql = $this->selectsql($this->pkey, $where, $orderby, $limit);
                }
                $list = (array)db:: fetch_all($sql);
                $this->redis_set($key, $list, 600);
                $this->redis_set($key . ':createtime', TIME, 864000);
            }
            if ($loop === true) {
                foreach ($list as $k => $v) {
                    $data = $this->fetchFields($v[$this->pkey], $fields);
                    $list[$k] = is_array($data) ? $data : array($fields => $data);
                }
            }
        } else {
            $sql = $this->selectsql($fields, $where, $orderby, $limit);
            $list = (array)db:: fetch_all($sql);
            if ($loop === true) {
                foreach ($list as $k => $v) {
                    $this->parseData($v);
                    $list[$k] = $v;
                }
            }
        }
        return $list;
    }

    public function fetchFields($val, $field = '*')
    {
        if (empty($val)) {
            return array();
        }
        //如果开启redis，走其缓存
        if ($this->redis_enable) {
            $key = md5(__FUNCTION__ . '_' . str_replace("'", '', var_export($val, true)));
            $indexid = $this->redis_get($key);

            $lastupdate = $this->redis_get('lastupdate');
            $createtime = $this->redis_get($key . ':createtime');
            if (empty($indexid) || empty($createtime) || $createtime < $lastupdate) {
                if (is_array($val)) {
                    $sql = $this->selectsql($this->pkey, $val);
                    $indexid = db:: result_onefield($sql);
                } else {
                    $indexid = $val;
                }
                if (empty($indexid)) {
                    return null;
                }
                $this->redis_set($key, $indexid);
                $this->redis_set($key . ':createtime', TIME, 864000);
                //记录某ID在哪些KEY里，删除时方便做更新
                $vals = (array)$this->redis_get(__FUNCTION__ . '_' . $indexid);
                $vals[$key] = 1;
                $this->redis_set(__FUNCTION__ . '_' . $indexid, $vals);
            }
            $data = $this->fetch($indexid);
            if (preg_match('/,/', $field)) {
                $fields = explode(',', $field);
                $row = array();
                foreach ($fields as $v) {
                    if (preg_match('/ as /i', $v)) {
                        list($v, $v1) = explode(' as ', $v);
                        if (isset($data[$v])) {
                            $row[trim($v1)] = trim($data[$v]);
                        }
                    } else {
                        if (isset($data[$v])) {
                            $row[$v] = $data[$v];
                        }
                        if (isset($data['_' . $v])) {
                            $row['_' . $v] = $data['_' . $v];
                        }
                    }
                }
                return $row;
            }
            if ($field == '*') {
                return $data;
            }
            if (isset($data[$field])) {
                return $data[$field];
            }
            return null;
        }
        $sql = $this->selectsql($field, $val);
        $data = db:: fetch_array($sql);
        $this->parseData($data);
        if (strpos($field, ',') === false && $field != '*') {
            return !empty($data[$field]) ? $data[$field] : '';
        }
        return $data;
    }

    /**
     * 清除所有fetchFields的key中包含某ID的缓存
     * @param unknown_type $id
     */
    private function clearFetchFieldsCache($id)
    {
        if (!$this->redis_enable) {
            return false;
        }
        $vals = $this->redis_get('fetchFields_' . $id);
        if ($vals) {
            foreach ($vals as $key => $v) {
                $this->redis_del($key);
            }
        }
        return true;
    }

    public function buildWhere($val)
    {
        $where = '';
        if (empty($val)) {
            $where = '1';
        } elseif (is_array($val)) {
            $arr = array();
            foreach ($val as $k => $v) {
                if (is_numeric($k)) {
                    $arr[] = $v;
                } else {
                    $arr[] = db:: fbuild($k, $v);
                }
            }
            $where = implode(' and ', $arr);
        } elseif (is_numeric($val)) {
            $where = db:: fbuild($this->pkey, $val);
        } elseif (is_string($val)) {
            $where = $val;
        }
        return $where;
    }

    public function tableFields()
    {
        return (array)db:: fetch_all('SHOW FIELDS FROM ' . db:: table($this->tname), 'Field');
    }

    protected function redis_get($key)
    {
        if (!$this->redis_enable) {
            return false;
        }
        $this->_key($key);
        return self::$redis->get($key);
    }

    protected function redis_set($key, $data, $redis_ttl = '')
    {
        if (!$this->redis_enable) {
            return false;
        }
        $this->_key($key);
        if (!$redis_ttl) {
            $redis_ttl = $this->redis_ttl;
        }
        $data = $data ? $data : '';
        return self::$redis->set($key, $data, $redis_ttl);
    }

    protected function redis_del($key)
    {
        if (!$this->redis_enable) {
            return false;
        }
        $this->_key($key);
        return self::$redis->del($key);
    }

    protected function redis_update($key, $data)
    {
        if (!$this->redis_enable) {
            return false;
        }
        if (($_data = $this->redis_get($key)) !== false) {
            return $this->redis_set($key, array_merge($_data, $data));
        }
        return false;
    }

    private function _key(&$key)
    {
        global $_M;
        $key = md5($_M['config']['authkey'] . $_M['config']['prefix'] . $this->tname . $key);
    }

    public function sum($condition = '', $field)
    {
        $sql = $this->selectsql('sum(' . $field . ')', $condition);
        $sum = db:: result_onefield($sql);
        return round($sum, 2);
    }

    private function selectsql($fields, $where = '', $orderby = '', $limit = '')
    {
        if (!empty($limit)) {
            $limit = db::parse_limit($limit);
        }
        $where = !empty($where) ? ' WHERE ' . $this->buildWhere($where) : '';
        return 'SELECT ' . $fields . ' FROM ' . db:: table($this->tname) . $where . ' ' . $orderby . ' ' . $limit;
    }

    //根据不同条件获取文档列表
    public function archivesList($row)
    {
        if (!empty($row['where'])) {
            $row['where'] = urlencode($row['where']);
        }
        require_once loadlib('tags');
        return arclist(json_encode($row));
    }

    /**
     * 走arclist实现翻页
     * @param array $row
     */
    public function archivesListPages($row)
    {
        global $_M;
        $pagesize = !empty($row['pagesize']) ? $row['pagesize'] : 30;
        $page = !empty($row['page']) ? $row['page'] : (int)_gp('page');
        $url = !empty($row['url']) ? $row['url'] : $_M['cururl'];

        $row['count'] = 1;
        $count = $this->archivesList($row);
        unset($row['pagesize'], $row['page'], $row['url'], $row['count']);

        $maxpages = ceil($count / $pagesize);
        $page = $page > $maxpages ? $maxpages : $page;
        $page = max(1, $page);
        $start = ($page - 1) * $pagesize;
        $infolist = array();
        if (!empty($count)) {
            $row['limit'] = $start . ',' . $pagesize;
            $infolist = $this->archivesList($row);
        }
        $para = array();
        $para['count'] = $count;
        $para['pagesize'] = $pagesize;
        $para['curpage'] = $page;
        $para['url'] = !empty($row['url']) ? $row['url'] : $_M['cururl'];
        $para['shownum'] = !empty($row['shownum']) ? 1 : '';
        $para['jump'] = !empty($row['jump']) ? 1 : '';
        $para['maxpages'] = $maxpages;
        $pagehtml = $this->pagehtml($para);
        return array('list' => $infolist, 'pagehtml' => $pagehtml, 'count' => $count, 'maxpages' => $maxpages, 'page' => $page, 'pagesize' => $pagesize);
    }

    /**f
     * 返回列表数据及分页
     * @param array $row
     */
    public function info_list($row = array())
    {
        global $_M;
        $where = !empty($row['where']) ? $row['where'] : '';
        $fields = !empty($row['fields']) ? $row['fields'] : '*';
        $pagesize = !empty($row['pagesize']) ? $row['pagesize'] : 30;
        $page = !empty($row['page']) ? $row['page'] : (int)_gp('page');
        $order = !empty($row['order']) ? $row['order'] : '';
        $sc = !empty($row['sc']) ? $row['sc'] : 'desc';

        $count = $this->count($where);
        $maxpages = ceil($count / $pagesize);
        $page = $page > $maxpages ? $maxpages : $page;
        $page = max(1, $page);
        $start = ($page - 1) * $pagesize;
        $infolist = array();
        if (!empty($count)) {
            $infolist = $this->fetchListLoop($where, $fields, db::parse_limit($start, $pagesize), $order, $sc);
        }

        $para = array();
        $para['count'] = $count;
        $para['pagesize'] = $pagesize;
        $para['curpage'] = $page;
        $para['url'] = !empty($row['url']) ? $row['url'] : $_M['cururl'];
        $para['shownum'] = !empty($row['shownum']) ? 1 : '';
        $para['jump'] = !empty($row['jump']) ? 1 : '';
        $para['maxpages'] = $maxpages;
        $pagehtml = $this->pagehtml($para);
        return array('list' => $infolist, 'pagehtml' => $pagehtml, 'count' => $count, 'maxpages' => $maxpages, 'page' => $page, 'pagesize' => $pagesize);
    }

    /**
     * 分页
     * @param $post ['count'] 总数
     * @param $post ['pagesize'] 每页数量
     * @param $post ['curpage'] 当前页面
     * @param $post ['url'] 链接地址
     * @param $post ['maxpages'] 最大页数
     * @param $post ['page'] 显示多少翻页数量
     * @param $post ['shownum'] 是否显示数量及页面总数
     * @param $post ['jump'] 是否可以键盘输入页数跳转
     */
    private function pagehtml($post)
    {
        global $_M;
        if ($post['count'] <= $post['pagesize']) {
            return '';
        }
        if ($_M['iswap']) {
            return $this->wap_pagehtml($post);
        }
        $page = !empty($post['page']) ? $post['page'] : 5;
        $a_name = '';
        $url = $post['url'];
        if (strpos($post['url'], '#') !== FALSE) {
            $a_strs = explode('#', $url);
            $url = $a_strs[0];
            $a_name = '#' . $a_strs[1];
        }

        $dot = '...';
        $url .= strpos($url, '?') !== FALSE ? '&' : '?';

        $page -= strlen($post['curpage']) - 1;
        $page = max(1, $page);
        $offset = floor($page * 0.5);

        $curpage = $post['curpage'];
        if ($page > $post['maxpages']) {
            $from = 1;
            $to = $post['maxpages'];
        } else {
            $from = $curpage - $offset;
            $to = $from + $page - 1;
            if ($from < 1) {
                $to = $curpage + 1 - $from;
                $from = 1;
                if ($to - $from < $page) {
                    $to = $page;
                }
            } elseif ($to > $post['maxpages']) {
                $from = $post['maxpages'] - $page + 1;
                $to = $post['maxpages'];
            }
        }
        $norewrite = defined('ISMANAGE') && ISMANAGE === true;

        $pagehtml = '<div class="btn-group paging_full_numbers">';
        $pagehtml .= ($curpage - $offset > 1 && $post['maxpages'] > $page ? '<a href="' . $this->rewriteUrl($url . 'page=1', $norewrite) . $a_name . '" page="1" class="btn">首页</a>' : '') .
            ($curpage > 1 ? '<a href="' . $this->rewriteUrl($url . 'page=' . ($curpage - 1), $norewrite) . $a_name . '" page="' . ($curpage - 1) . '" class="previous btn">上一页</a>' : '') .
            ($curpage - $offset > 1 && $post['maxpages'] > $page ? '<SPAN class=pagebox_pre><a href="' . $this->rewriteUrl($url . 'page=1', $norewrite) . $a_name . '" page="1" class="btn">1 ' . $dot . '</a></SPAN>' : '');
        for ($i = $from; $i <= $to; $i++) {
            $pagehtml .= $i == $curpage ? '<a class="btn btn-envato active">' . $i . '</a>' : '<a class="btn btn-boo" href="' . $this->rewriteUrl($url . 'page=' . $i, $norewrite) . $a_name . '" page="' . $i . '">' . $i . '</a>';
        }
        $pagehtml .= ($to < $post['maxpages'] ? '<a href="' . $this->rewriteUrl($url . 'page=' . $post['maxpages'], $norewrite) . $a_name . '" page="' . $post['maxpages'] . '" class="btn btn-boo">' . $dot . ' ' . $post['maxpages'] . '</a>' : '') .
            ($curpage < $post['maxpages'] ? '<a href="' . $this->rewriteUrl($url . 'page=' . ($curpage + 1), $norewrite) . $a_name . '" page="' . ($curpage + 1) . '" class="btn btn-boo">下一页</a><a href="' . $this->rewriteUrl($url . 'page=' . $post['maxpages'], $norewrite) . $a_name . '" page="' . $post['maxpages'] . '" class="btn btn-boo">末页</a>' : '') .
            (!empty($post['jump']) && $post['maxpages'] > $page ? '<input title="输入页码，按回车快速跳转" class="btn btn-boo" type="text" name="custompage" style="width:30px;" id="custompage" onkeydown="if(event.keyCode==13) {window.location=\'' . $url . 'page=' . '\'+this.value; doane(event);}" />' : '');

        $pagehtml = $pagehtml ? $pagehtml . (!empty($post['shownum']) ? '<a class="btn btn-boo">共<b>' . $post['maxpages'] . '</b>页&nbsp; | &nbsp;共<b>' . $post['count'] . '</b>条</a>' : '') : '';
        $pagehtml .= '</div>';
        return $pagehtml;
    }

    private function wap_pagehtml($post)
    {
        if ($post['count'] <= $post['pagesize']) {
            return '';
        }
        $a_name = '';
        $url = $post['url'];
        if (strpos($post['url'], '#') !== FALSE) {
            $a_strs = explode('#', $url);
            $url = $a_strs[0];
            $a_name = '#' . $a_strs[1];
        }
        $url .= strpos($url, '?') !== FALSE ? '&' : '?';
        $curpage = $post['curpage'];
        $norewrite = defined('ISMANAGE') && ISMANAGE === true;

        $pagehtml = '<div class="pager"><div class="pager-left">';
        $pagehtml .= '<div class="pager-first"><a href="' . $this->rewriteUrl($url . 'page=1', $norewrite) . $a_name . '" class="pager-nav">首页</a></div>';
        $pagehtml .= '<div class="pager-pre"><a href="' . $this->rewriteUrl($url . 'page=' . ($curpage - 1), $norewrite) . $a_name . '" class="pager-nav">上一页</a></div></div>';
        $pagehtml .= '<div class="pager-cen">' . $curpage . '/' . $post['maxpages'] . '</div>';
        $pagehtml .= '<div class="pager-right">';
        $pagehtml .= '<div class="pager-next"><a href="' . $this->rewriteUrl($url . 'page=' . ($curpage + 1), $norewrite) . $a_name . '" class="pager-nav">下一页</a></div>';
        $pagehtml .= '<div class="pager-end"><a href="' . $this->rewriteUrl($url . 'page=' . $post['maxpages'], $norewrite) . $a_name . '" class="pager-nav">末页</a></div>';
        $pagehtml .= '</div></div>';
        return $pagehtml;
    }

    /**
     * 优先调用自定义的伪静态函数
     * @param unknown_type $url
     */
    private function rewriteUrl($url, $norewrite = false)
    {
        if ($norewrite === true) {
            return $url;
        }
        if (function_exists('rewriteUrl')) {
            return rewriteUrl($url);
        }
        return pseudoUrl($url);
    }

    /**
     * 获取以文件形式缓存数据,第一参数为调用的方法，其它参数为方法中涉及参数
     */
    public function get_cache()
    {
        global $_M;
        $argv = func_get_args();
        $args = array_slice($argv, 1);
        if (!$_M['sys']['data_filecache'] && !$this->redis_enable) {
            return call_user_func_array(array($this, $argv[0]), $args);
        }
        $name = md5(str_replace("'", '', var_export($args, true)));
        $cache = new helper_datacache($this->tname . '/' . $argv[0] . '/' . substr($name, -2) . '/' . $name, $this->tname);
        $info = $cache->get_cache();
        if (empty($info)) {
            $info = call_user_func_array(array($this, $argv[0]), $args);
            $cache->save_cache($info);
        }
        return $info;
    }

    /**
     * 生成最近一次更新时间
     */
    public function lastUpdateTime($do = '')
    {
        global $_M;
        if ($this->redis_enable) {
            if ($do == 'get') {
                return $this->redis_get('lastupdate');
            }
            return $this->redis_set('lastupdate', TIME, 864000);
        }
        if ($_M['sys']['data_filecache']) {
            $url = MOPDATA . 'datacache/' . $this->tname . '/lastupdate.php';
            if ($do == 'get') {
                return is_file($url) ? trim(file_get_contents($url)) : 0;
            }
            require_once loadlib('file');
            putFile($url, TIME);
            return true;
        }
    }
}