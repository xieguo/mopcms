<?php
/**
 * 图片处理类
 * @copyright           (C) 2016-2099 MOPCMS.COM
 * @license             http://www.mopcms.com/license/
 */
!defined('IN_MOPCMS') && exit('Access failed');

class mop_image
{
    private $mark = array();
    private $imginfo = array();
    private $imagecreatefromfunc = '';
    private $imagefunc = '';
    private $supporttype = array('gif'=>false,'jpg'=>false,'png'=>false,'wbmp'=>false);

    public function __construct()
    {
        if(function_exists("imagecreatefromgif") && function_exists("imagegif")){
            $this->supporttype['gif'] = true;
        }
        if(function_exists("imagecreatefromjpeg") && function_exists("imagejpeg")){
            $this->supporttype['jpg'] = true;
        }
        if(function_exists("imagecreatefrompng") && function_exists("imagepng")){
            $this->supporttype['png'] = true;
        }
        if(function_exists("imagecreatefromwbmp") && function_exists("imagewbmp")){
            $this->supporttype['wbmp'] = true;
        }
    }

    private function watermark_gd($srcfile)
    {
        $mark = $this->mark;
        list($imagewidth, $imageheight) = $this->imginfo;
        if($mark['types']==1) {
            $watermark_file = MOPROOT.$mark['markimg'];
            $watermarkinfo = @getimagesize($watermark_file);
            list($logowidth, $logoheight) = $watermarkinfo;
            if($watermarkinfo[2]==1){
                $watermark_logo = @imagecreatefromgif($watermark_file);
            }
            elseif($watermarkinfo[2]==2){
                $watermark_logo = @imagecreatefromjpeg($watermark_file);
            }
            elseif($watermarkinfo[2]==3){
                $watermark_logo = @imagecreatefrompng($watermark_file);
            }
            if(empty($watermark_logo)) {
                return false;
            }
        } else {
            $ttf = MOPDATA.'fonts/simhei.ttf';
            if(!is_file($ttf)){
                return false;
            }
            $box = @imagettfbbox($mark['fontsize'], 0, $ttf, $mark['marktext']);
            $logowidth = max($box[2], $box[4]) - min($box[0], $box[6]);
            $logoheight = max($box[1], $box[3]) - min($box[5], $box[7]);
            $ax = min($box[0], $box[6]) * -1;
            $ay = min($box[5], $box[7]) * -1;
        }
        $wmwidth = $this->imginfo[0] - $logowidth;
        $wmheight = $this->imginfo[1] - $logoheight;
        if($wmwidth > 10 && $wmheight > 10) {
            switch($mark['markpos']) {
                case 1 :
                    $x = +15;
                    $y = +15;
                    break;
                case 2 :
                    $x =($imagewidth - $logowidth) / 2;
                    $y = +15;
                    break;
                case 3 :
                    $x = $imagewidth - $logowidth -5;
                    $y = +15;
                    break;
                case 4 :
                    $x = +15;
                    $y =($imageheight - $logoheight) / 2;
                    break;
                case 5 :
                    $x =($imagewidth - $logowidth) / 2;
                    $y =($imageheight - $logoheight) / 2;
                    break;
                case 6 :
                    $x = $imagewidth - $logowidth -5;
                    $y =($imageheight - $logoheight) / 2;
                    break;
                case 7 :
                    $x = +15;
                    $y = $imageheight - $logoheight -15;
                    break;
                case 8 :
                    $x =($imagewidth - $logowidth) / 2;
                    $y = $imageheight - $logoheight -15;
                    break;
                case 9 :
                    $x = $imagewidth - $logowidth -15;
                    $y = $imageheight - $logoheight -15;
                    break;
            }
            $dst_photo = @imagecreatetruecolor($imagewidth, $imageheight);
            $imagecreatefunc = $this->imagecreatefromfunc;
            $target_photo = $imagecreatefunc($srcfile);
            imagecopy($dst_photo, $target_photo, 0, 0, 0, 0, $imagewidth, $imageheight);
            $imagefunc = $this->imagefunc;
            if($mark['types'] == 1) {
                if($watermarkinfo[2]==1){
                    imagealphablending($watermark_logo, true);
                    imagecopymerge($dst_photo, $watermark_logo, $x, $y, 0, 0, $logowidth, $logoheight, $mark['diaphaneity']);
                }else{
                    imagecopy($dst_photo, $watermark_logo, $x, $y, 0, 0, $logowidth, $logoheight);
                }
                if($watermarkinfo[2]==2) {
                    $imagefunc($dst_photo, $srcfile, $mark['marktrans']);
                } else {
                    $imagefunc($dst_photo, $srcfile);
                }
            }
            elseif($mark['types'] == 2) {
                //给文字水印加阴影
                $shadowcolor = imagecolorallocate($dst_photo, 255, 255, 255);
                imagettftext($dst_photo,$mark['fontsize'], 0,$x + $ax + 2, $y + $ay + 2,$shadowcolor,$ttf,$mark['marktext']);

                $rgb = $this->hex2rgb($mark['fontcolor']);
                $color = imagecolorallocate($dst_photo, $rgb['r'], $rgb['g'], $rgb['b']);
                imagettftext($dst_photo, $mark['fontsize'], 0, $x + $ax, $y + $ay, $color, $ttf, $mark['marktext']);
                $imagefunc($dst_photo, $srcfile);
            }
            return true;
        }
    }
    /**
     * 十六进制 转 RGB
     * @param $hexColor 十六进制颜色
     */
    private function hex2rgb($hexColor)
    {
        $color = str_replace('#', '', $hexColor);
        if (strlen($color) > 3) {
            $rgb = array(
                'r' => hexdec(substr($color, 0, 2)),
                'g' => hexdec(substr($color, 2, 2)),
                'b' => hexdec(substr($color, 4, 2))
            );
        } else {
            $color = $hexColor;
            $r = substr($color, 0, 1) . substr($color, 0, 1);
            $g = substr($color, 1, 1) . substr($color, 1, 1);
            $b = substr($color, 2, 1) . substr($color, 2, 1);
            $rgb = array(
                'r' => hexdec($r),
                'g' => hexdec($g),
                'b' => hexdec($b)
            );
        }
        return $rgb;
    }

    /**
     *  图片自动加水印函数
     * @param $srcfile 图片源文件
     */
    public function waterImg($srcfile)
    {
        global $_M;
        if(!function_exists('imagecopy') || !function_exists('imagealphablending') || !function_exists('imagecopymerge')) {
            return false;
        }
        if(empty($this->mark)){
            loadcache('watermark');
            $this->mark = $_M['cache']['watermark'];
        }
        if($this->mark['types']==1 && (empty($this->mark['markimg'])||!is_readable(MOPROOT.$this->mark['markimg']))) {
            return false;
        }
        $this->imginfo = @getimagesize($srcfile);
        if($this->imginfo[0] < $this->mark['width'] || $this->imginfo[1] < $this->mark['height']) {
            return false;
        }

        switch($this->imginfo[2]) {
            case 1:
                if($this->supporttype['gif']===false){
                    return false;
                }
                $this->imagecreatefromfunc = 'imagecreatefromgif';
                $this->imagefunc = function_exists('imagegif') ? 'imagegif' : '';
                $filecontent = file_get_contents($srcfile);
                //判断是否为gif动画，是就不加水印
                if(strpos($filecontent, 'NETSCAPE2.0') !== false){
                    return false;
                }
                break;
            case 2:
                if($this->supporttype['jpg']===false){
                    return false;
                }
                $this->imagecreatefromfunc = 'imagecreatefromjpeg';
                $this->imagefunc = function_exists('imagejpeg') ? 'imagejpeg' : '';
                break;
            case 3:
                if($this->supporttype['png']===false){
                    return false;
                }
                $this->imagecreatefromfunc = 'imagecreatefrompng';
                $this->imagefunc = function_exists('imagepng') ? 'imagepng' : '';
                break;
        }
        return $this->watermark_gd($srcfile);
    }

    /*
     * 由文件或 URL 创建一个新图象
     * @param $srcfile 图片源文件
     */
    private function imagecreate($srcfile)
    {
        $srcInfo = getimagesize($srcfile);
        switch($srcInfo[2]) {
            case 1 :
                if($this->supporttype['gif']===false){
                    return false;
                }
                $im = imagecreatefromgif($srcfile);
                break;
            case 2 :
                if($this->supporttype['jpg']===false){
                    return false;
                }
                $im = imagecreatefromjpeg($srcfile);
                break;
            case 3 :
                if($this->supporttype['png']===false){
                    return false;
                }
                $im = imagecreatefrompng($srcfile);
                break;
            case 6 :
                if($this->supporttype['wbmp']===false){
                    return false;
                }
                $im = imagecreatefromwbmp($srcfile);
                break;
        }
        return array('info'=>$srcInfo,'image'=>$im);
    }

    /**
     *  缩图片自动生成函数，来源支持bmp、gif、jpg、png
     * @param     string  $srcfile  图片路径
     * @param     string  $toW  转换到的宽度
     * @param     string  $toH  转换到的高度
     * @param     string  $toFile  输出文件到
     */
    public function imageResize($srcfile, $toW, $toH, $toFile = "")
    {
        global $_M;
        if($toFile == ''){
            $toFile = $srcfile;
        }
        $image = $this->imagecreate($srcfile);
        if($image===false){
            return false;
        }
        $srcInfo = $image['info'];
        $im = $image['image'];
        $srcW = imagesx($im);
        $srcH = imagesy($im);
        if($srcW <= $toW && $srcH <= $toH) {
            return false;
        }
        $toWH = $toW / $toH;
        $srcWH = $srcW / $srcH;
        if($toWH <= $srcWH) {
            $ftoW = $toW;
            $ftoH = $ftoW *($srcH / $srcW);
        } else {
            $ftoH = $toH;
            $ftoW = $ftoH *($srcW / $srcH);
        }
        if($srcW > $toW || $srcH > $toH) {
            if(function_exists("imagecreatetruecolor")) {
                @ $ni = imagecreatetruecolor($ftoW, $ftoH);
                if($ni) {
                    imagecopyresampled($ni, $im, 0, 0, 0, 0, $ftoW, $ftoH, $srcW, $srcH);
                } else {
                    $ni = imagecreate($ftoW, $ftoH);
                    imagecopyresized($ni, $im, 0, 0, 0, 0, $ftoW, $ftoH, $srcW, $srcH);
                }
            } else {
                $ni = imagecreate($ftoW, $ftoH);
                imagecopyresized($ni, $im, 0, 0, 0, 0, $ftoW, $ftoH, $srcW, $srcH);
            }
            $jpgquality = !empty($_M['sys']['jpgquality'])?$_M['sys']['jpgquality']:85;
            switch($srcInfo[2]) {
                case 1 :
                    imagegif($ni, $toFile);
                    break;
                case 2 :
                    imagejpeg($ni, $toFile, $jpgquality);
                    break;
                case 3 :
                    imagepng($ni, $toFile);
                    break;
                case 6 :
                    imagebmp($ni, $toFile);
                    break;
                default :
                    return false;
            }
            imagedestroy($ni);
        }
        imagedestroy($im);
        return true;
    }

    /**
     *  会对空白地方填充满
     * @param     string  $srcfile  图片路径
     * @param     string  $toW  转换到的宽度
     * @param     string  $toH  转换到的高度
     * @param     string  $toFile  输出文件到
     */

    private function imageResizeNew($srcfile, $toW, $toH, $toFile = '')
    {
        global $_M;
        if($toFile == ''){
            $toFile = $srcfile;
        }
        $image = $this->imagecreate($srcfile);
        if($image===false){
            return false;
        }
        $srcInfo = $image['info'];
        $img = $image['image'];
        $width = imagesx($img);
        $height = imagesy($img);

        if(!$width || !$height) {
            return false;
        }

        $target_width = $toW;
        $target_height = $toH;
        $target_ratio = $target_width / $target_height;

        $img_ratio = $width / $height;

        if($target_ratio > $img_ratio) {
            $new_height = $target_height;
            $new_width = $img_ratio * $target_height;
        } else {
            $new_height = $target_width / $img_ratio;
            $new_width = $target_width;
        }

        if($new_height > $target_height) {
            $new_height = $target_height;
        }
        if($new_width > $target_width) {
            $new_height = $target_width;
        }

        $new_img = ImageCreateTrueColor($target_width, $target_height);
        $bgcolor = $_M['sys']['img_bgcolor'] == 0 ? ImageColorAllocate($new_img, 0xff, 0xff, 0xff) : 0;

        if(!@ imagefilledrectangle($new_img, 0, 0, $target_width -1, $target_height -1, $bgcolor)) {
            return false;
        }

        if(!@ imagecopyresampled($new_img, $img,($target_width - $new_width) / 2,($target_height - $new_height) / 2, 0, 0, $new_width, $new_height, $width, $height)) {
            return false;
        }

        switch($srcInfo[2]) {
            case 1 :
                imagegif($new_img, $toFile);
                break;
            case 2 :
                imagejpeg($new_img, $toFile, 100);
                break;
            case 3 :
                imagepng($new_img, $toFile);
                break;
            case 6 :
                imagebmp($new_img, $toFile);
                break;
            default :
                return false;
        }
        imagedestroy($new_img);
        imagedestroy($img);
        return true;
    }

    /**
     * 上传附件，默认上传图片
     * @param unknown_type $upname
     * @param unknown_type $maxwidth
     * @param unknown_type $maxheight
     * @param unknown_type $water
     * @param unknown_type $utype
     */
    public function uploadAttachment($upname, $maxwidth = 0, $maxheight = 0, $water = false, $utype = 'image')
    {
        global $_M;
        //判断是否是H5上传
        $img = '';
        if(empty($_FILES[$upname]['tmp_name']) || !is_uploaded_file($_FILES[$upname]['tmp_name'])) {
            $img = _gp($upname);
            if(!empty($img)) {
                $img = preg_replace('/data:image\/(jpeg|png);base64,/i', '', $img);
                $img = str_replace(' ', '+', $img);
                $data = base64_decode($img);
                $file = uniqid() . '.jpg';
                $success = file_put_contents(MOPROOT . $_M['sys']['upload_allimg'] . '/' . $file, $data);
                if($success) {
                    $_FILES[$upname]['tmp_name'] = MOPROOT . $_M['sys']['upload_allimg'] . '/' . $file;
                    $_FILES[$upname]['name'] = $file;
                    $_FILES[$upname]['type'] = 'image/jpeg';
                    $_FILES[$upname]['size'] = @ filesize($_FILES[$upname]['tmp_name']);
                } else {
                    return getResult(10604);
                }
            } else {
                return getResult(10604);
            }
        }

        require_once loadlib('misc');
        $_M['admin'] = t('admin')->loginInfo();
        if(!empty($_M['mid'])){
            $imgurl = $_M['sys']['upload_userup'] . '/' . $_M['mid'] . '/' . date('Y') . '/';
        }else{
            $imgurl = $_M['sys']['upload_allimg'] . '/' . date('Y') . '/'. date('m') . '/';
        }
        createdir(MOPROOT.'/'.$imgurl);

        $allAllowType = str_replace('||', '|', $_M['sys']['imgtype'] . '|' . $_M['sys']['mediatype'] . '|' . $_M['sys']['softtype']);
        $file_name = trim(preg_replace("#[ \r\n\t\*\%\\\/\?><\|\":]{1,}#", '', $_FILES[$upname]['name']));
        if(!empty($file_name) && (preg_match("/\.(php|pl|cgi|asp|aspx|jsp|php3|shtm|shtml|js)$/i", $file_name) || !preg_match("/\./", $file_name))) {
            return getResult(10601);
        }

        //防止伪装成图片的木马上传
        if(preg_match('#(GLOBAL|_GET|_POST|_COOKIE|assert|call_|create_|eval|_SERVER|function|defined|global|base64_)#', file_get_contents($_FILES[$upname]['tmp_name']))) {
            return getResult(10602);
        }
        //源文件类型检查
        if($utype == 'image') {
            if(!preg_match("/\.(" . $_M['sys']['imgtype'] . ")$/i", $file_name)) {
                return getResult(10603, '', array('name' => $_M['sys']['imgtype']));
            }
            $sparr = array('image/gif', 'image/pjpeg', 'image/jpeg', 'image/png', 'image/xpng', 'image/wbmp');
            $file_type = strtolower(trim($_FILES[$upname]['type']));
            if(!in_array($file_type, $sparr)) {
                return getResult(10604);
            }
        }
        elseif($utype == 'flash') {
            if(!preg_match("/\.swf$/", $file_name)){
                return getResult(10605);
            }
        }
        elseif($utype == 'media') {
            if(!preg_match("/\.(" . $_M['sys']['mediatype'] . ")$/", $file_name)){
                return getResult(10606, '', array('name' => $_M['sys']['mediatype']));
            }
        }
        elseif($utype == 'addon') {
            if(!preg_match("/\.(" . $allAllowType . ")$/", $file_name)){
                return getResult(10601);
            }
        }else{
            return getResult(10604);
        }

        $path = pathinfo($file_name);
        $file_name = TIME .mt_rand(1000000, 9999999) . '.'.strtolower($path['extension']);
        $filename = $imgurl . $file_name;
        $file_dir = MOPROOT . $filename;
        if($img) {
            @ copy($_FILES[$upname]['tmp_name'], $file_dir);
            @ unlink($_FILES[$upname]['tmp_name']);
        } else {
            if(!move_uploaded_file($_FILES[$upname]['tmp_name'], $file_dir)){
                return getResult(10607);
            }
        }
        $filesize = @ filesize($file_dir);

        t('uploads')->save(array('url' => $filename, 'isfirst' => 1));

        //加水印或缩小图片
        if($utype == 'image') {
            $maxwidth = $maxwidth?$maxwidth:$_M['sys']['img_width'];
            $maxheight = $maxheight?$maxheight:$_M['sys']['img_height'];
            if($maxwidth > 0 || $maxheight > 0) {
                if($_M['sys']['img_full'] == 1){
                    $this->imageResizeNew($file_dir, $maxwidth, $maxheight);
                }else{
                    $this->imageResize($file_dir, $maxwidth, $maxheight);
                }
            }
            $water = _gp('watermark')?true:$water;
            if($water) {
                $this->waterImg($file_dir);
            }
        }
        return getResult(100,$filename);
    }
}