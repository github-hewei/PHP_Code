<?php // CODE BY HW 
// KeWail 短信服务
ini_set('date.timezone', 'Asia/Shanghai');

class KewailSMS
{
    // 在 Kewail上 申请到的 accesskey
    private $accesskey = "5cb13dd087b65f1eeda47e34";

    // 在 Kewail上 申请到的 secretkey
    private $secretkey = "548e7428d13a42f99728ac279371c447";

    // 接口请求地址
    private $base_url;

    // 请求get参数
    private $get_data;

    // 请求post参数
    private $post_data;

    /**
     * 构造方法
     * @param string $accesskey
     * @param string $secretkey 
     */
    public function __construct($accesskey="", $secretkey="")
    {
        if( $accesskey !== "" ) {
            $this->accesskey = $accesskey;
        }
        if( $secretkey !== "" ) {
            $this->secretkey = $secretkey;
        }
    }

    /**
     * 单发短信
     * @param string $mobile 手机号
     * @param string $msg   短信内容
     * @param string $type  短信类型 0通知短信 1营销短信
     * @param string $nationcode 国家码
     * @param string $extend 通道扩展码
     * @param string $ext    扩展字段, 回包中会原样返回
     * @return null|array(result=>错误码, errmsg=>错误信息)
     */
    public function sendSingleSms($mobile, 
                                  $msg, 
                                  $type=0, 
                                  $nationcode='86',
                                  $extend = "",
                                  $ext = "") 
    {
        $this->base_url = "https://live.kewail.com/sms/v1/sendsinglesms";
        $random = mt_rand();
        $time = time();
        $this->get_data = array(
            'accesskey' => $this->accesskey, 
            'random'    => $random,
        );
        $sign_arr = array(
            'secretkey' => $this->secretkey,
            'random'    => $random,
            'time'      => $time,
            'mobile'    => $mobile,
        );
        $data = array();
        $data['tel']['nationcode'] = $nationcode;
        $data['tel']['mobile']     = $mobile;
        $data['type'] = $type;
        $data['msg'] = $msg;
        $data['sig'] = hash('sha256', http_build_query($sign_arr));
        $data['time'] = $time;
        $data['ext'] = $ext;
        $data['extend'] = $extend;
        $this->post_data = $data;
        $ret = $this->send_request();
        return $ret;
    }

    /**
     * 指定模板单发短信
     * @param string $mobile 手机号
     * @param string $templateId 在后台添加的模版的id
     * @param array $params 要写入到模板中的参数
     * @param string $type  短信类型 0通知短信 1营销短信
     * @param string $signId 指定其他签名
     * @param string $nationcode 国家码
     * @param string $extend 通道扩展码
     * @param string $ext    扩展字段, 回包中会原样返回
     * @return null|array(result=>错误码, errmsg=>错误信息)
     */
    public function sendSingleSmsV2($mobile, 
                                    $templateId,
                                    $params,
                                    $type=0, 
                                    $signId="",
                                    $nationcode='86',
                                    $extend = "",
                                    $ext = "")
    {
        $this->base_url = "https://live.kewail.com/sms/v2/sendsinglesms";
        $random = mt_rand();
        $time = time();
        $this->get_data = array(
            'accesskey' => $this->accesskey, 
            'random'    => $random,
        );
        $sign_arr = array(
            'secretkey' => $this->secretkey,
            'random'    => $random,
            'time'      => $time,
        );
        $data = array();
        $data['tel']['nationcode'] = $nationcode;
        $data['tel']['mobile']     = $mobile;
        $data['templateId'] = $templateId;
        $data['params'] = $params;
        $data['signId'] = $signId;
        $data['type'] = $type;
        $data['sig'] = hash('sha256', http_build_query($sign_arr));
        $data['time'] = $time;
        $data['extend'] = $extend;
        $data['ext'] = $ext;
        $this->post_data = $data;
        $ret = $this->send_request();
        return $ret;
    }

    /**
     * 发送请求
     * @return array(result=>错误码, errmsg=>错误信息)
     */
    private function send_request()
    {
        $get_data = http_build_query($this->get_data);
        $url = $this->base_url . '?' . $get_data;
        $post_data = json_encode($this->post_data);
        $header = array(
            "Content-type: application/json",
            "Content-length: " . strlen($post_data),
        );
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $content = curl_exec($ch);
        if( curl_errno($ch) !== CURLE_OK ) {
            return NULL;
        }
        $result = json_decode($content, true);
        if( json_last_error() !== JSON_ERROR_NONE ) {
            return NULL;
        }
        return $result;
    }
}


// 测试
if( $_SERVER['PHP_SELF'] == 'KewailSMS.php' ) {
    // 单发短信
    $smsMod = new KewailSMS;
    $result = $smsMod->sendSingleSms("13718691968", "【Kewail科技】您注册的验证码：123456有效时间30分钟。");
    if( is_null($result) ) {
        exit("Error\n");
    }
    if( $result['result'] !== 0 ) {
        var_dump( $result );
        exit("Failed\n");
    }   

    var_dump( $result );
    exit("OK\n");

    // 模板短信
    // $smsMod = new KewailSMS;
    // $params = array('7890123');
    // $result = $smsMod->sendSingleSmsV2("13718691968", "5a9599a56fcafe461546b953", $params);
    // if( is_null($result) ) {
    //     exit("Error\n");
    // }
    // if( $result['result'] !== 0 ) {
    //     var_dump( $result );
    //     exit("Failed\n");
    // }   

    // var_dump( $result );
    // exit("OK\n");
}
