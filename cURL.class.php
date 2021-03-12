<?php //CODE BY HW
/**
 * cURL封装
 * @author hewei
 * @datetime 2021-3-12 11:41:36
 * @version 2.0
 */
class cURL {

    /**
     * cURL选项
     * @var array
     */
    protected $options = array(
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 30,
    );

    /**
     * 上传文件
     * @var array
     */
    protected $files = array();

    /**
     * 请求数据
     * @var null
     */
    protected $data;

    /**
     * 请求方法
     * @var string
     */
    protected $method;

    /**
     * 是否是POST请求
     * @var bool
     */
    protected $post;

    /**
     * 请求地址
     * @var null
     */
    protected $url;

    /**
     * 响应信息
     * @var
     */
    protected $info;

    /**
     * 响应主体
     * @var
     */
    protected $response;

    /**
     * 是否将响应json转为数组
     * @var
     */
    protected $jsonToArray;

    /**
     * cURL constructor.
     * @param null $url 请求地址
     * @param string $method 请求方法
     * @param null $data 请求数据
     * @param array $options cURL选项
     */
    public function __construct($url = null, $method = 'GET', $data = null, $options = array()) {
        $this->setUrl($url);
        $this->setMethod($method);
        $this->setData($data);
        $this->setOptions($options);
    }

    /**
     * 设置请求地址
     * @param string $arg 请求地址
     * @return $this
     */
    public function setUrl($arg) {
        $this->url = $arg;
        return $this;
    }

    /**
     * 获取请求地址
     * @return null|string
     */
    public function getUrl() {
        return $this->url;
    }

    /**
     * 设置cURL选项
     * @param int $option 选项
     * @param mixed $value 选项值
     * @return $this
     */
    public function setOption($option, $value = true) {
        $this->options[$option] = $value;
        return $this;
    }

    /**
     * 批量设置cURL选项
     * @param array $options 选项数组
     * @return $this
     */
    public function setOptions($options) {
        foreach($options as $option => $value) {
            $this->options[$option] = $value;
        }
        return $this;
    }

    /**
     * 获取cURL选项值
     * @param mixed $option 选项
     * @return mixed|null
     */
    public function getOption($option) {
        return isset($this->options[$option]) ? $this->options[$option] : null;
    }

    /**
     * 获取全部cURL选项
     * @return array
     */
    public function getOptions() {
        return $this->options;
    }

    /**
     * 添加HTTP请求头
     * @param array|string $arg
     * @return $this
     */
    public function addHeader($arg) {
        $this->setOption(CURLOPT_HTTPHEADER, array_merge($this->getHeaders(), is_array($arg) ? $arg : array($arg)));
        return $this;
    }

    /**
     * 获取全部HTTP请求头
     * @return array
     */
    public function getHeaders() {
        return isset($this->options[CURLOPT_HTTPHEADER]) ? $this->options[CURLOPT_HTTPHEADER] : array();
    }

    /**
     * 设置Cookie文件
     * @param bool|string $arg 文件地址或true
     * @return $this
     */
    public function setCookieFile($arg = true) {
        $cookie = is_bool($arg) ? dirname(__FILE__) . DIRECTORY_SEPARATOR . 'cookie.tmp' : $arg;
        $this->setOption(CURLOPT_COOKIEJAR, $cookie);
        $this->setOption(CURLOPT_COOKIEFILE, $cookie);
        return $this;
    }

    /**
     * 获取Cookie文件地址
     * @return mixed|null
     */
    public function getCookieFile() {
        return isset($this->options[CURLOPT_COOKIEFILE]) ? $this->options[CURLOPT_COOKIEFILE] : null;
    }

    /**
     * 设置浏览器User-Agent
     * @param bool|string $arg 自定义UA或true
     * @return $this
     */
    public function setUa($arg = true) {
        $customUa = 'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:86.0) Gecko/20100101 Firefox/86.0';
        $this->addHeader(is_bool($arg) ? $customUa : (string)$arg);
        return $this;
    }

    /**
     * 模拟XMLHttpRequest请求
     * @return $this
     */
    public function setXhr() {
        $this->addHeader('X-Requested-With: XMLHttpRequest');
        return $this;
    }

    /**
     * 设置是否关闭证书验证
     * @param bool $arg
     * @return $this
     */
    public function setNoSSL($arg = true) {
        $this->setOption(CURLOPT_SSL_VERIFYPEER, $arg ? false : true);
        $this->setOption(CURLOPT_SSL_VERIFYHOST, $arg ? false : true);
        return $this;
    }

    /**
     * 设置是否是POST请求
     * @param bool $arg
     * @return $this
     */
    public function setPost($arg = true) {
        $this->post = $arg;
        return $this;
    }

    /**
     * 设置请求数据
     * @param mixed $arg
     * @return $this
     */
    public function setData($arg) {
        $this->data = $arg;
        return $this;
    }

    /**
     * 设置发送文件
     * @param string $file 文件路径
     * @param string $name 文件名称
     * @return $this
     * @throws cURLException
     */
    public function setFile($file, $name = 'file') {
        if(!class_exists('CURLFile')) {
            throw new \cURLException('CURLFile class does not exist');
        }
        if(!file_exists($file)) {
            throw new \cURLException('file does not exists. ' . $file);
        }
        $this->files[$name] = $file;
        return $this;
    }

    /**
     * 批量设置发送文件
     * @param array $files 文件列表
     * @return $this
     * @throws cURLException
     */
    public function setFiles($files) {
        foreach($files as $key => $value) {
            $this->setFile($value, $key);
        }
        return $this;
    }

    /**
     * 获取上传文件
     * @param mixed $arg
     * @return mixed|null
     */
    public function getFile($arg = 'file') {
        return isset($this->files[$arg]) ? $this->files[$arg] : null;
    }

    /**
     * 获取全部上传文件
     * @return array
     */
    public function getFiles() {
        return $this->files;
    }

    /**
     * 设置HTTP请求的方法，如GET|POST|HEAD|PUT|OPTION|DELETE|CONNECT等
     * @param string $arg 请求方法
     * @return $this
     */
    public function setMethod($arg) {
        if($arg === 'GET' || $arg === 'POST') {
            $this->setPost($arg === 'POST');
        } else {
            $this->setOption(CURLOPT_CUSTOMREQUEST, $arg);
        }
        return $this;
    }

    /**
     * 获取HTTP请求的方法
     * @return string
     */
    public function getMethod() {
        return isset($this->options[CURLOPT_CUSTOMREQUEST]) ? $this->options[CURLOPT_CUSTOMREQUEST] : ($this->post ? 'POST' : 'GET');
    }

    /**
     * 设置是否将返回JSON数据转为数组
     * @param bool $arg
     * @return $this
     */
    public function setJsonToArray($arg = true) {
        $this->jsonToArray = $arg;
        return $this;
    }

    /**
     * 发送cURL请求
     * @return mixed
     * @throws cURLException
     */
    public function exec() {
        if(is_array($this->files) && $this->files) {
            $this->post = true;
            if(!is_array($this->data)) {
                throw new \cURLException('The data property can only be an array');
            }
            foreach($this->files as $name => $file) {
                $mime = null;
                if(class_exists('finfo')) {
                    $fi = finfo_open(FILEINFO_MIME);
                    $mime = finfo_file($fi, $file);
                    finfo_close($fi);
                }
                $this->data[$name] = new \CURLFile($file, $mime);
            }
        }
        $url = $this->url;
        if($this->post) {
            $this->setOption(CURLOPT_POST, true);
            $this->setOption(CURLOPT_POSTFIELDS, $this->data);
        } else {
            if(is_array($this->data)) {
                $url = $url . (strpos($url, '?') ? '&' : '?') . http_build_query($this->data);
            } else {
                $url = $url . (strpos($url, '?') ? '&' : '?') . trim((string)$this->data, '?');
            }
        }
        if($this->method) {
            $this->setOption(CURLOPT_CUSTOMREQUEST, $this->method);
        }
        $this->setOption(CURLOPT_URL, $url);
        $curl = curl_init();
        if(false == curl_setopt_array($curl, $this->options)) {
            throw new \cURLException('Options contain wrong options');
        }
        $this->response = curl_exec($curl);
        if(curl_errno($curl) !== CURLE_OK) {
            throw new \cURLException(curl_error($curl), curl_errno($curl), 500);
        }
        $this->info = curl_getinfo($curl);
        if($this->jsonToArray) {
            $result = json_decode($this->response, true);
            if(json_last_error() !== JSON_ERROR_NONE) {
                throw new \cURLException('json_decode error' . (function_exists('json_last_error_msg') ? ': ' . json_last_error_msg() : ''), 0, 501);
            }
            $this->response = $result;
        }
        curl_close($curl);
        return $this->response;
    }

    /**
     * 获取响应信息
     * @param null|string $arg 响应信息的键名或null
     * @return mixed
     */
    public function getInfo($arg = null) {
        return is_null($arg) ? $this->info : (isset($this->info[$arg]) ? $this->info[$arg] : null);
    }

    /**
     * 获取响应的数据
     * @return mixed
     */
    public function getResponse() {
        return $this->response;
    }

}


/**
 * cURL异常处理类
 */
class cURLException extends \Exception {

    /**
     * cURL错误编号
     * @var int
     */
    public $curl_errorno;

    /**
     * cURLException constructor.
     * @param string $message 异常信息
     * @param int $curl_errorno cURL错误编号
     * @param int $code 异常错误编号
     * @param Exception|null $previous
     */
    public function __construct($message = '', $curl_errorno = 0, $code = 0, \Exception $previous = null) {
        $this->curl_errorno = $curl_errorno;
        parent::__construct($message, $code, $previous);
    }

    /**
     * 获取cURL错误信息
     * @return NULL|string
     */
    public function curl_strerror() {
        return function_exists('curl_strerror') ? curl_strerror($this->curl_errorno) : '';
    }

}
