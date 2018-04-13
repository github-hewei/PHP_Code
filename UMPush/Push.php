<?php 
/**
 * 友盟推送类
 */
class Umeng_Push
{
    private $appkey;
    private $secret;
    private $params;
    private $url;

    public function __construct($system='',$appkey='',$secret='')
    {
        $this->system = strtolower($system);
        switch ($this->system) {
            case 'android': 
                $this->appkey = '561cce2567e58ee42500363f';
                $this->secret = 'gpsqfbsmvubw6v5qofypsnpnxurvkkpk';break;
            case 'ios':
                $this->appkey = '56e2667167e58ef519001108';
                $this->secret = 'bqbugfwla6dgt0skdq4hn1yske79htpy';break;
            default: break;
        }
        if($appkey!=='' && $secret!=='') {
            $this->appkey = $appkey;
            $this->secret = $secret;
        }
        return true;
    }

    // 设置单个参数
    public function setParam($path,$value)
    {
        $pathArr = explode('.', $path);
        $tmpParam = &$this->params;
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

    // 批量设置参数
    public function setParamsByArray($paramsArr)
    {
        if(is_array($paramsArr)) {
            foreach($paramsArr as $key => $value) {
                $this->setParam($key, $value);
            }
        }
        return $this;
    }

    // 执行发送
    public function send()
    {
        $ret = array('status'=>0, 'error'=>'', 'data'=>'');
        $params = json_encode($this->params);
        $url = $this->url;
        $sign = $this->getSign($params, $url);
        $url .= "?sign={$sign}";
        try {
            $result = $this->request($url, $params);

        } catch (Exception $e) {
            $ret['status'] = 1;
            $ret['error'] = $e->getMessage();
            return $ret;
        }
        $ret['data'] = $result;
        return $ret;
    }

    // 发送推送
    public function push()
    {
        switch ($this->system) {
            case 'android': 
            $this->params = array(
                'appkey' => $this->appkey,
                'timestamp' => strval( time() ),
                'type' => '',
                'device_tokens' => '',
                'production_mode' => 'false',
                'description' => '',
                'payload' => array(
                    'display_type' => 'notification',
                    'body' => array('ticker'=>'','title'=>'','text'=>''),
                ),
            );break;
            case 'ios':
            $this->params = array(
                'appkey' => $this->appkey,
                'timestamp' => strval( time() ),
                'type' => '',
                'device_tokens' => '',
                'production_mode' => 'false',
                'description' => '',
                'payload' => array(
                       'aps' => array( 'alert' => '',),
                ),
            );break;
            default: break;
        }
        $this->url = "http://msg.umeng.com/api/send";
        return $this;
    }

    // 获取任务状态
    public function getStatus($task_id='')
    {
        $this->params = array(
            'appkey' => $this->appkey,
            'timestamp' => strval( time() ),
            'task_id' => $task_id,
        );
        $this->url = "http://msg.umeng.com/api/status";
        return $this;
    }

    // 批量上传 Token/alias
    public function upload($contents)
    {
        if(is_array($contents)) {
            $contents = implode("\n", $contents);
        }
        $this->params = array(
            'appkey' => $this->appkey,
            'timestamp' => strval( time() ),
            'content' => $contents,
        );
        $this->url = "http://msg.umeng.com/upload";
        return $this;
    }

    // 任务取消
    public function cancel($task_id='')
    {
        $this->params = array(
            'appkey' => $this->appkey,
            'timestamp' => strval( time() ),
            'task_id' => $task_id,
        );
        $this->url = "http://msg.umeng.com/api/cancel";
        return $this;
    }

    // 打印推送参数
    public function check()
    {
        echo "<pre>";
        print_r( $this->params );
        print_r( $this->url );exit;
    }

    // 发送请求
    protected function request($url, $data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErrNo = curl_errno($ch);
        $curlErr = curl_error($ch);
        curl_close($ch);
        $result = json_decode($result, true);
        if($httpCode=='0') {
            throw new Exception("ERRCODE:{$curlErrNo} ERRMSG:{$curlErr}");
        } elseif($httpCode!='200') {
            throw new Exception("HTTPCODE:{$httpCode} ERRMSG:{$result['data']}");
        } else {
            return $result['data'];
        }
        return false;
    }

    // 生成签名
    protected function getSign($params,$url)
    {
        return md5('POST'.$url.$params.$this->secret);
    }
}

# 文档：
# http://dev.umeng.com/push/ios/api-doc