<?php 
/**
 * 验证码
 */
class Vcode
{
    private $width;//验证码宽度度
    private $height;//验证码高度
    private $num;//验证码位数
    private $code;//验证码
    private $image;//图像资源
    private $fonts;//验证码字体

    // 构造方法
    public function __construct($width=120,$height=50,$num=4)
    {
        $this->width = $width;
        $this->height = $height;
        $this->num = $num;
        $this->code = $this->createCode();
        $this->fonts = array(
            dirname( __FILE__ ) . '/font/swissko.ttf',
            dirname( __FILE__ ) . '/font/Eclectic.ttf',
        );
    }

    // 获取验证码图像
    public function getImage()
    {
        $this->createBack();//创建背景

        $this->outString();//加入验证字符

        $this->setdisturbcolor();//设置干扰色

        $this->printImage();//输出图像
    }

    // 加入验证字符
    public function outString()
    {
        for($i=0; $i<$this->num; $i++) {
            $strcolor = imagecolorallocate($this->image,rand(0,128),rand(0,128),rand(0,128));
            $fontsize = $this->height/2;
            $x = 4+($this->width/$this->num)*$i;
            $y = ($this->height/2)+($fontsize/2);
            imagettftext($this->image,$fontsize,rand(-40,40),$x,$y,$strcolor,$this->fonts[rand(0,count($this->fonts)-1)],substr($this->code,$i,1));
        }
    }

    // 设置干扰色
    public function setdisturbcolor()
    {
        // 干扰点
        for($i=0; $i<100; $i++) {
            $color = imagecolorallocate($this->image,rand(0,255),rand(0,255),rand(0,255));
            imagesetpixel($this->image,rand(1,$this->width-2),rand(1,$this->height-2), $color);
        }
        // 干扰线
        for($i=0; $i<3; $i++) {
            $color = imagecolorallocate($this->image,rand(0,255),rand(0,128),rand(0,255));
            imagearc($this->image,rand(-10,$this->width+10),rand(-10,$this->height+10),rand(30,300),rand(30,300),55,44,$color);
        }
    }

    // 输出图像
    public function printImage()
    {
        if(imagetypes() & IMG_GIF) {
            header("Content-type:image/gif");
            imagegif($this->image);
        } elseif(imagetypes() & IMG_PNG) {
            header("Content-type:image/png");
            imagegif($this->image);
        } elseif(function_exists("imagejpeg")) {
            header("Content-type:image/jpeg");
            imagegif($this->image);
        } else {
            die("no image support in this php server");
        }
        exit;
    }

    // 创建背景
    private function createBack()
    {
        $this->image = imagecreatetruecolor($this->width,$this->height);
        $bgcolor = imagecolorallocate($this->image,rand(225,255),rand(225,255),rand(225,255));
        imagefill($this->image,0,0,$bgcolor);
        $bordercolor = imagecolorallocate($this->image,0,0,0);
        imagerectangle($this->image,0,0,$this->width-1,$this->height-1,$bordercolor);
    }

    // 获取验证码字符串
    public function getCode()
    {
        return $this->code;
    }

    // 生成验证码字符串
    private function createCode()
    {
        $str = "3456789abcdefghijkmnpqrstuvwxyABCDEFGHIJKLMNPQRSTUVWXY";
        $code = "";
        for($i=0; $i<$this->num; $i++) {
            $code .= substr($str,rand(0,strlen($str)-1),1);
        }
        return $code;
    }

    // 析构方法
    public function __destruct()
    {
        imagedestroy($this->image);
    }
}