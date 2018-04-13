<?php 
/**
 * 数据抓取
 * 北京市医药阳光采购综合管理平台
 */
if( !defined('STDOUT') ) exit;
ini_set('memory_limit', '2048M');
ini_set('max_execution_time', 0);

define('PATH', dirname(__FILE__).'/');

# 1.请求验证码并保存
echo put("开始请求验证码...\n");
$url = "http://210.73.89.76/ServiceSelect/GetCode?t=%s";
$url = sprintf($url, time());
$fn = PATH.'c.jpg';
if( !touch($fn) ) {
    exit( put("创建文件失败\n") );
}
$ret = request('get', $url, null, fopen($fn,'wb'));
if( !$ret ) {
    exit( put("请求验证码失败\n") );
}

# 2.接收终端输入的验证码
fwrite(STDOUT, put("请输入脚本所在目录下的验证码（c.jpg）\n:"));
$code = trim( fgets(STDIN) );
if( empty($code) ) {
    exit( put("验证码不能为空\n") );
}

# 3.提交验证码
echo put("开始校验验证码...\n");
$url = "http://210.73.89.76/ServiceSelect/RegInputCode";
$data = array('InputCode'=>$code);
$ret = request('post', $url, $data);
$retArr = json2arr( $ret );
if( !$retArr['istrue'] ) {
    exit( put("验证码输入有误\n") );
}

# 4.请求药品数据并保存
echo put("开始请求药品数据...\n");
$url = "http://210.73.89.76/ServiceSelect/GetHosSelectList";
$data = array(
    'sort' => '',
    'page' => '1',
    'pageSize' => '0',
    'group' => '',
    'filter' => '',
    'ProductName' => '',
    'OrgName' => '',
    'PermitNumber' => '',
    'BaseFlag' => '',
    'InputCode' => $code,
);
$fn = PATH.'drugs.json';
$ret = request('post', $url, $data);
$retArr = json2arr( $ret );
if( $retArr['Errors'] ) {
    var_dump($retArr);exit;
}
$total = intval( $retArr['Total'] );
$pageSize = 5000;
$pageCount = ceil( $total/$pageSize );

$drugs = array();
for($i=1; $i<=$pageCount; $i++) {
    echo put("开始请求药品数据第{$i}/{$pageCount}页...\n");
    $data['page'] = $i;
    $data['pageSize'] = $pageSize;
    $ret = request('post', $url, $data);
    $retArr = json2arr( $ret );
    if( $retArr['Errors'] ) {
        var_dump($retArr);exit;
    }
    $drugs = array_merge($drugs, $retArr['Data']);
}
if( !file_put_contents($fn, json_encode($drugs)) ) {
    exit( put("写入药品数据失败\n") );
}

# 5.请求每个药品对应的医院数据
echo put("开始请求医院数据...\n");
$url = "http://210.73.89.76/ServiceSelect/GridOrgInfoList";
$data = array(
    'sort' => '',
    'page' => '1',
    'pageSize' => '0',
    'group' => '',
    'filter' => '',
    'ProductId' => '',
    'OrgName' => '',
    'OrgPrice' => '',
    'InputCode' => $code,
);
$path = PATH."hosts/";
if( !is_dir($path) ) {
    if(!@mkdir($path)) {
        exit( put("创建文件夹失败...\n") );
    }
}
$count = count($drugs);
foreach($drugs as $key => $value) {
    echo "{$key}/{$count}\n";
    $data['ProductId'] = $value['ID'];
    $ret = request('post', $url, $data);
    $retArr = json2arr( $ret );
    if( $retArr['Errors'] ) {
        var_dump($retArr);exit;
    }
    $total = intval( $retArr['Total'] );
    $pageSize = 5000;
    $pageCount = ceil( $total/$pageSize );
    $hosts = array();
    for($i=1; $i<=$pageCount; $i++) {
        $data['page'] = $i;
        $data['pageSize'] = $pageSize;
        $ret = request('post', $url, $data);
        $retArr = json2arr( $ret );
        if( $retArr['Errors'] ) {
            var_dump($retArr);exit;
        }
        $hosts = array_merge($hosts, $retArr['Data']);
    }
    file_put_contents($path.$value['ID'], json_encode($hosts));
}

exit( put("完成....\n") );

// 发起网络请求
function request($type='get',$url='',$data=null,$fn=null)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    //curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookie.jar');
    if( file_exists('cookie.jar') ) {
        curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookie.jar');
    }
    if( $type=='post' ) {
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    }
    if( $fn ) {
        curl_setopt($ch, CURLOPT_FILE, $fn);
    }
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErrNo = curl_errno($ch);
    $curlErr = curl_error($ch);
    curl_close($ch);
    if( $httpCode=='0' ) {
        echo $curlErr;exit;
    } elseif( $httpCode!='200' ) {
        echo "HTTPCODE:{$httpCode}\n";
        var_dump($result);exit;
    }
    return $result;
}

// 对输出内容进行转码
function put($str='')
{
    if(PATH_SEPARATOR!=':') {
        $str = iconv('utf-8', 'gbk', $str);
    }
    return $str;
}

// json数据转数组
function json2arr($json='')
{
    $arr = json_decode($json,true);
    if( json_last_error()!==JSON_ERROR_NONE ) {
        var_dump($json);exit;
    }
    return $arr;
}