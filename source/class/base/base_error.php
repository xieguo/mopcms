<?php
/**
 * 错误处理类
 */
!defined('IN_MOPCMS') && exit('Access failed');

class base_error
{
    /**
     * 错误回溯跟踪并保存或显示
     * @param $msg
     * @param $trace
     */
    public static function error($msg,$trace=array())
    {
        if (MOP_DEBUG != 1 && MOP_DEBUG != 2){
            return;
        }
        $trace = !empty($trace)?$trace:self :: debug_backtrace();
        $log = array();
        foreach ($trace as &$v){
            $v['file'] = str_replace(MOPROOT, '', str_replace('\\', '/', $v['file']));
            $file = isset ($v['file']) ? $v['file'] : '';
            $log[] = $v['line']." \t".$file."\n\r";
        }
        if(is_numeric($msg)){
            $msg = promptMsg($msg);
        }
        $msgsave = $msg . "\n\r" . implode('', $log);
        self :: savelog($msgsave);
        if(MOP_DEBUG==2){
            self :: show_error($msg, $trace);
            exit;
        }
    }

    /**
     * 异常处理
     * @param $exc
     */
    public static function exception($exc)
    {
        if (MOP_DEBUG != 1 && MOP_DEBUG != 2){
            return;
        }
        $errormsg = $exc->getMessage();
        if(is_numeric($errormsg)){
            $errormsg = promptMsg($errormsg);
        }
        if ($exc instanceof Exception) {
            $errormsg = '(' . $exc->getCode() . ') '.$errormsg;
        }

        $trace = $exc->getTrace();
        krsort($trace);
        $log = array ();
        foreach ($trace as $v) {
            if (!empty ($v['function'])) {
                $func = '';
                if (!empty ($v['class'])) {
                    $func .= $v['class'] . $v['type'];
                }
                $args = '';
                if (!empty ($v['args'])) {
                    $args = array();
                    foreach ($v['args'] as $arg) {
                        if (is_array($arg)) {
                            $args[] = 'array';
                        }
                        elseif (is_bool($arg)) {
                            $args[] = $arg ? 'true' : 'false';
                        } else {
                            $args[] = filterString($arg);
                        }
                    }
                    $args = implode(',', $args);
                }
                $func .= $v['function'] . '('.$args.')';
                $v['function'] = $func;
            }
            $log[] = array ('file' => aval($v, 'file'),'line' => aval($v, 'line'),'function' => aval($v, 'function'));
        }
        self::error($errormsg,$log);
    }

    /**
     * 错误回溯跟踪
     */
    private static function debug_backtrace()
    {
        $trace = debug_backtrace();
        krsort($trace);
        foreach ($trace as &$v) {
            $v['line'] = isset ($v['line']) ? $v['line'] : '';
            $v['file'] = isset ($v['file']) ? $v['file'] : '';
            $func = isset ($v['class']) ? $v['class'] : '';
            $func .= isset ($v['type']) ? $v['type'] : '';
            $func .= isset ($v['function']) ? $v['function'] : '';
            $v['function'] = $func;
        }
        return $trace;
    }

    /**
     * 显示错误信息
     * @param $errormsg
     * @param $trace
     */
    private static function show_error($errormsg, $trace = '')
    {
        global $_M;
        include template('showerror.htm', '', true);
        echo output();
        exit;
    }

    /**
     * 保存错误日志
     * @param $msg
     */
    private static function savelog($msg)
    {
        $file = MOPROOT . '/data/errorlog/' . date("Ym") . '_errorlog.php';
        $ip = 'IP:' . $_SERVER['REMOTE_ADDR'];
        $uri = 'Request: ' . filterString($_SERVER['REQUEST_URI']);
        $exit = !is_file($file)?"<?PHP exit;?>\n":'';
        $msg = $exit.mdate(time(),'dt') . "\t$ip\t$uri\t$msg\n\r\n\r";
        error_log($msg, 3, $file);
    }

}