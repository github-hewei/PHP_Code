<?php 
/**
 * 极光推送 - 工具类
 */
class JPush
{
    private $_appkey = '';
    private $_secret = '';
    private $_params; # 请求参数
    private $_method; # 请求方法 GET|POST
    private $_url;    # 请求地址

    public function __construct($appkey='', $secret='')
    {
        if($appkey && $secret) {
            $this->_appkey = $appkey;
            $this->_secret = $secret;
        }
        return true;
    }

    // 准备推送
    public function push()
    {
        $this->_url = 'https://api.jpush.cn/v3/push';
        $this->_method = 'POST';
        $this->_params = array(
            'platform' => 'all',
            'audience' => 'all',
            'notification' => array(
                'android' => array(
                    'alert' => 'Hi Jpush',
                ),
                'ios' => array(
                    'alert' => 'Hi Jpush',
                ),
            ),
            'options' => array(
                'apns_production' => false,
            ),
        );
        return $this;
    }

    // 准备获取 cid
    public function cid($count=1, $type='push')
    {
        $this->_url = 'https://api.jpush.cn/v3/push/cid';
        $this->_method = 'GET';
        $this->_params = array(
            'count' => intval($count),
            'type' => $type,
        );
        return $this;
    }

    // 发送请求
    public function send()
    {
        $ret = array('status'=>0, 'error'=>'', 'data'=>'');
        try {
            if($this->_method==='GET') {
                $params = array();
                foreach($this->_params as $key => $value) {
                    $params[] = sprintf('%s=%s', $key, $value);
                }
                $this->_url .= '?' . implode('&', $params);
                $result = $this->request('GET', $this->_url);
            } else {
                $params = json_encode($this->_params);
                $result = $this->request('POST', $this->_url, $params);
            }
            $ret['data'] = $result;
            return $ret;

        } catch (Exception $e) {
            $ret['status'] = 1;
            $ret['error'] = $e->getMessage();
            return $ret;
        }
    }

    // 设置单个请求参数
    public function setParam($path,$value)
    {
        $pathArr = explode('.', $path);
        $tmpParam = &$this->_params;
        $number = count($pathArr) - 1;
        foreach($pathArr as $key => $val) {
            if($key===$number) {
                $tmpParam[ $val ] = $value;
                break;
            }
            if(array_key_exists($val,$tmpParam)) {
                $tmpParam = &$tmpParam[ $val ];
            } else {
                $tmpParam[ $val ] = array();
                $tmpParam = &$tmpParam[ $val ];
            }
        }
        unset($tmpParam);
        return $this;
    }

    // 批量设置请求参数
    public function setParamsByArray($paramsArr)
    {
        if(is_array($paramsArr)) {
            foreach($paramsArr as $key => $value) {
                $this->setParam($key, $value);
            }
        }
        return $this;
    }

    // 发送CURL请求
    protected function request($method='GET', $url='', $data=null)
    {
        $sign = base64_encode($this->_appkey.':'.$this->_secret);
        $header = array(
            'Content-Type: text/plain',
            'Accept: application/json',
            "Authorization: Basic $sign",
        );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        if( $method==='POST' ) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErrNo = curl_errno($ch);
        $curlErr = curl_error($ch);
        curl_close($ch);
        $result = json_decode($result, true);
        if($httpCode=='0') {
            throw new Exception("ERRCODE:{$curlErrNo} ERRMSG:{$curlErr}");
        } elseif($httpCode!='200') {
            throw new Exception("HTTPCODE:{$httpCode} ERRCODE:{$result['error']['code']} ERRMSG:{$result['error']['message']}");
        } else {
            return $result;
        }
        return false;
    }

    // 打印推送参数
    public function check()
    {
        echo "Request URL:".$this->_url."<br />\n";
        echo "Request Method:".$this->_method."<br />\n";
        echo "Request Params: <br />\n";
        var_dump($this->_params);exit;
    }

}

/*
极光推送接口文档
https://docs.jiguang.cn/jpush/server/push/rest_api_v3_push/#_1
*/

// 测试
$JPush = new JPush('e7dd7a0b85cfd9c34890e4fa', '2a60097bff5b8907a7e6f989');

$ret = $JPush->cid()->send();
if($ret['status']!=0) {
    exit($ret['error']);
}
$cid = $ret['data']['cidlist'][0];
$ret = $JPush->push()
             ->setParam('platform', array('ios', 'android'))
             ->setParam('cid', $cid)
             ->setParam('notification.android.alert', '消息标题')
             ->check();
if($ret['status']!=0) {
    exit($ret['error']);
}

var_dump($ret['data']);


