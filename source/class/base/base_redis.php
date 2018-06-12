<?php
/**
 * redis类
 * @copyright           (C) 2016-2099 MOPCMS.COM
 * @license             http://www.mopcms.com/license/
 */
!defined('IN_MOPCMS') && exit('Access failed');

class base_redis
{
    private static $enable = false;
    public static $redis;

    public function __construct($config)
    {
        if (empty(self::$enable) && extension_loaded('redis') && !empty($config['server'])) {
            try {
                self::$redis = new Redis();
                if ($config['pconnect']) {
                    $connect = @self::$redis->pconnect($config['server'], $config['port']);
                } else {
                    $connect = @self::$redis->connect($config['server'], $config['port']);
                }
                if ($connect) {
                    self::$enable = true;
                    !empty($config['password']) && self::$redis->auth($config['password']);
                    self::$redis->setOption(Redis::OPT_SERIALIZER, 0);//0 值不序列化保存，1反之(序列化可以存储对象)
                    self::$redis->select($config['selectdb']);
                }
            } catch (RedisException $e) {
                base_error:: exception($e);
            }
        }
    }

    /**
     * 是否可用
     * @return bool
     */
    public function enable()
    {
        return self::$enable;
    }

    /**
     * 用于设置给定 key 的值
     * @param $key
     * @param $data
     * @param int $ttl
     * @return bool|int
     */
    public function set($key, $data, $ttl = 5184000)
    {
        if (!empty($data)) {
            if (!is_numeric($data)) {
                $data = serialize($data);
            }
            self::$redis->set($key, $data);
            if ($ttl) {
                self::$redis->expire($key, $ttl);
            }
            return true;
        }
        return $this->del($key);
    }

    /**
     * 指定的 key 不存在时，才为 key 设置指定的值
     * @param $key
     * @param $data
     * @param int $ttl
     * @return bool|int
     */
    public function setnx($key, $data, $ttl = 5184000)
    {
        if (!empty($data)) {
            if (!is_numeric($data)) {
                $data = serialize($data);
            }
            $res = self::$redis->setnx($key, $data);
            if ($res && $ttl) {
                self::$redis->expire($key, $ttl);
            }
            return $res;
        }
        return $this->del($key);
    }

    /**
     * 将 key 中储存的数字值增一
     * @param $key
     * @param int $ttl
     * @return int
     */
    public function incr($key, $ttl = 5184000)
    {
        $res = self::$redis->incr($key);
        if ($ttl) {
            self::$redis->expire($key, $ttl);
        }
        return $res;
    }

    /**
     * 将 key 中储存的数字值减一
     * @param $key
     * @param int $ttl
     * @return int
     */
    public function decr($key, $ttl = 5184000)
    {
        $res = self::$redis->decr($key);
        if ($ttl) {
            self::$redis->expire($key, $ttl);
        }
        return $res;
    }

    /**
     * 将 key 所储存的值减去指定的减量值
     * @param $key
     * @param int $num
     * @param int $ttl
     * @return int
     */
    public function decrby($key, $num = 1, $ttl = 5184000)
    {
        $res = self::$redis->decrBy($key, $num);
        if ($ttl) {
            self::$redis->expire($key, $ttl);
        }
        return $res;
    }

    /**
     * 将 key 中储存的数字加上指定的增量值
     * @param $key
     * @param int $num
     * @param int $ttl
     * @return int
     */
    public function incrby($key, $num = 1, $ttl = 5184000)
    {
        $res = self::$redis->incrby($key, $num);
        if ($ttl) {
            self::$redis->expire($key, $ttl);
        }
        return $res;
    }

    /**
     * 用于获取指定 key 的值
     * @param $key
     * @return bool|mixed|string
     */
    public function get($key)
    {
        $data = self::$redis->get($key);
        if (is_numeric($data)) {
            return $data;
        }
        return unserialize($data);
    }

    /**
     * 用于检查给定 key 是否存在
     * @param $key
     * @return bool
     */
    public function exists($key)
    {
        return self::$redis->exists($key);
    }

    /**
     * 用于查找所有符合给定模式 pattern 的 key
     * @param $pattern
     * @return array
     */
    public function keys($pattern)
    {
        return self::$redis->keys($pattern);
    }

    /**
     * 用于设置 key 的过期时间
     * @param $key
     * @param $ttl
     * @return bool
     */
    public function expire($key, $ttl)
    {
        return self::$redis->expire($key, $ttl);
    }

    /**
     * 用于删除已存在的键
     * @param $key
     * @return int
     */
    public function del($key)
    {
        return self::$redis->del($key);
    }

    /**
     * 哈希：用于同时将多个 field-value (字段-值)对设置到哈希表中
     * @param $key
     * @param $data
     * @param int $ttl
     * @return bool
     */
    public function hmset($key, $data, $ttl = 5184000)
    {
        self::$redis->hmset($key, $data);
        if ($ttl) {
            self::$redis->expire($key, $ttl);
        }
        return true;
    }

    /**
     * 哈希：用于为哈希表中的字段赋值
     * @param $key
     * @param $field
     * @param $data
     * @param int $ttl
     * @return bool
     */
    public function hset($key, $field, $data, $ttl = 5184000)
    {
        self::$redis->hset($key, $field, $data);
        if ($ttl) {
            self::$redis->expire($key, $ttl);
        }
        return true;
    }

    /**
     * 哈希：返回哈希表中指定字段的值
     * @param $key
     * @param $field
     * @return string
     */
    public function hget($key, $field)
    {
        return self::$redis->hget($key, $field);
    }

    /**
     * 哈希：用于为哈希表中的字段值加上指定增量值
     * @param $key
     * @param $field
     * @param int $num
     * @return int
     */
    public function hincrby($key, $field, $num = 1)
    {
        return self::$redis->hincrby($key, $field, $num);
    }

    /**
     * 哈希：用于查看哈希表的指定字段是否存在
     * @param $key
     * @param $field
     * @return bool
     */
    public function hexists($key, $field)
    {
        return self::$redis->hexists($key, $field);
    }

    /**
     * 哈希：获取哈希表中字段的数量
     * @param $key
     * @return int
     */
    public function hlen($key)
    {
        return self::$redis->hlen($key);
    }

    /**
     * 哈希：返回哈希表中，所有的字段和值
     * @param $key
     * @return array
     */
    public function hgetall($key)
    {
        return self::$redis->hgetall($key);
    }

    /**
     * 哈希：用于删除哈希表 key 中的一个或多个指定字段，不存在的字段将被忽略
     * @param $key
     * @param $fields
     */
    public function hdel($key, $fields)
    {
        $keys = is_array($fields) ? implode('\',\'', $fields) : $fields;
        eval("self::\$redis->hdel(\$key,'" . $keys . "');");
    }

    /**
     * 有序集合：用于将一个或多个成员元素及其分数值加入到有序集当中
     * @param $key
     * @param $score
     * @param $member
     * @param int $ttl
     * @return bool
     */
    public function zadd($key, $score, $member, $ttl = 5184000)
    {
        self::$redis->zadd($key, $score, $member);
        if ($ttl) {
            self::$redis->expire($key, $ttl);
        }
        return true;
    }

    /**
     * 有序集合：用于移除有序集中的一个或多个成员，不存在的成员将被忽略
     * @param $key
     * @param $members
     */
    public function zrem($key, $members)
    {
        $keys = is_array($members) ? implode('\',\'', $members) : $members;
        eval("\$res = self::\$redis->zrem(\$key,'" . $keys . "');");
    }

    /**
     * 有序集合：用于计算集合中元素的数量
     * @param $key
     * @return int
     */
    public function zcard($key)
    {
        return self::$redis->zCard($key);
    }

    /**
     * 有序集合：返回有序集中，指定区间内的成员
     * @param $key
     * @param $start
     * @param $end
     * @param null $withscores
     * @return array
     */
    public function zrange($key, $start, $end, $withscores = null)
    {
        return self::$redis->zRange($key, $start, $end, $withscores);
    }

    /**
     * 有序集合：返回有序集中，指定区间内的成员
     * @param $key
     * @param $start
     * @param $end
     * @param null $withscores
     * @return array
     */
    public function zrevrange($key, $start, $end, $withscores = null)
    {
        return self::$redis->zRevRange($key, $start, $end, $withscores);
    }

    /**
     * 有序集合：返回有序集中成员的排名。其中有序集成员按分数值递减(从大到小)排序
     * @param $key
     * @param $member
     * @return int
     */
    public function zrevRank($key, $member)
    {
        return self::$redis->zRevRank($key, $member);
    }

    /**
     * 有序集合：返回有序集中指定成员的排名。其中有序集成员按分数值递增(从小到大)顺序排列
     * @param $key
     * @param $member
     * @return int
     */
    public function zrank($key, $member)
    {
        return self::$redis->zrank($key, $member);
    }

    /**
     * 有序集合：返回有序集中，成员的分数值
     * @param $key
     * @param $member
     * @return float
     */
    public function zscore($key, $member)
    {
        return self::$redis->zScore($key, $member);
    }

    /**
     * 以秒为单位返回 key 的剩余过期时间
     * @param $key
     * @return int
     */
    public function ttl($key)
    {
        return self::$redis->ttl($key);
    }

    /**
     * 集合：将一个或多个成员元素加入到集合中，已经存在于集合的成员元素将被忽略
     * @param $key
     * @param $member
     * @param int $ttl
     * @return bool
     */
    public function sadd($key, $member, $ttl = 5184000)
    {
        if (is_array($member)) {
            $members = str_replace(' ', '', implode('\',\'', $member));
            eval("self::\$redis->sadd(\$key,'" . $members . "');");
            unset($member,$members);
        } else {
            self::$redis->sadd($key, $member);
        }
        if ($ttl) {
            self::$redis->expire($key, $ttl);
        }
        return true;
    }

    /**
     * 集合：移除集合中的一个或多个成员元素
     * @param $key
     * @param $member
     * @return bool
     */
    public function srem($key, $member)
    {
        if (is_array($member)) {
            $members = str_replace(' ', '', implode('\',\'', $member));
            eval("self::\$redis->srem(\$key,'" . $members . "');");
            unset($member,$members);
        } else {
            self::$redis->srem($key, $member);
        }
        return true;
    }

    /**
     * 集合：返回集合中元素的数量
     * @param $key
     * @return int
     */
    public function scard($key)
    {
        return self::$redis->scard($key);
    }

    /*
     * 集合：判断成员元素是否是集合的成员
     * @param $key
     * @param $val
     * @return bool
     */
    public function sismember($key, $val)
    {
        return self::$redis->sismember($key, $val);
    }

    /**
     * 列表：将一个或多个值插入到列表头部
     * @param $key
     * @param $value
     */
    public function lpush($key, $value)
    {
        $value = is_array($value) ? implode('\',\'', $value) : $value;
        eval("\$res = self::\$redis->lpush(\$key,'" . $value . "');");
    }
}
