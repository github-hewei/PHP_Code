<?php
// 获取北京阳光集采的数据
ini_set( 'date.timezone', 'Asia/Shanghai' );
ini_set( 'memory_limit', '2048M' );
ini_set( 'max_execution_time', '0' );

$dir = __DIR__ . '/data/';
if( !is_dir( $dir ) && !mkdir( $dir ) ) {
    exit( "Error\n" );
}

# 请求验证码图片
$url = 'http://210.73.89.76/ServiceSelect/GetCode?t=1526458775780';
$ret = http_request( 'GET', $url );
if( $ret['ret']!=1 ) {
    exit( 'Error:' . $ret['err'] . "\n" );
}
$fn = $dir . 'vcode.gif';
file_put_contents( $fn, $ret['data'] );

fwrite( STDOUT, "Enter (data/vcode.gif) Verification Code : " );
$vcode = trim( fgets(STDIN) );
if( strlen( $vcode ) < 1 ) {
    exit( "Error\n" );
}

# 检验验证码
$url = "http://210.73.89.76/ServiceSelect/RegInputCode/";
$post_data = array( 'InputCode' => $vcode );
$ret = http_request( 'POST', $url, $post_data );
if( $ret['ret']!=1 ) {
    exit( 'Error:' . $ret['err'] . "\n" );
}
$data = json_decode( $ret['data'], true );
if( json_last_error()!==JSON_ERROR_NONE ) {
    exit( "json_decode() Error \n" );
}
if( !$data['istrue'] ) {
    exit( "Verifying code input is incorrect \n" );
}

# 请求药品列表
$size = 1000; // 每页请求条数
$url = "http://210.73.89.76/ServiceSelect/GetHosSelectList/";
$post_data = array(
    'sort' => '',
    'page' => 1,
    'pageSize' => $size,
    'group' => '',
    'filter' => '',
    'ProductName' => '',
    'OrgName' => '',
    'PermitNumber' => '',
    'BaseFlag' => '',
    'InputCode' => $vcode,
);
printf( "Req: P[%d] \n", 1 );
$ret = http_request( 'POST', $url, $post_data );
if( $ret['ret']!=1 ) {
    exit( 'Error:' . $ret['err'] . "\n" );
}
$data = json_decode( $ret['data'], true );
if( json_last_error()!==JSON_ERROR_NONE ) {
    exit( "json_decode() Error \n" );
}
$drugs = array();
$drugs = array_merge( $drugs, $data['Data'] );
$total = intval( $data['Total'] );
$count = ceil( $total / $size );
for( $p=2; $p<=$count; $p++ ) {
    printf( "Req: P[%d] \n", $p );
    $post_data['page'] = $p;
    $ret = http_request( 'POST', $url, $post_data );
    if( $ret['ret']!=1 ) {
        exit( 'Error:' . $ret['err'] . "\n" );
    }
    $data = json_decode( $ret['data'], true );
    if( json_last_error()!==JSON_ERROR_NONE ) {
        exit( "json_decode() Error \n" );
    }
    $drugs = array_merge( $drugs, $data['Data'] );
}

# 存储药品数据
$fn = $dir . sprintf( "/jicai_drugs_%s.sql", date('Ymd') );
$handle = fopen( $fn, 'w+' );
fwrite( $handle, "set names utf8;\n" );
$sql = "INSERT INTO jicai_drugs ( RN, ID, NAME_CHN, TRADE_NAME, DOSEAGE_FORM_NAME, SPEC, WRAP_NAME, PERMIT_NUMBER, STAND_RATE, PRODUCT_ID, BID_ORGID, ORG_NAME ) VALUES ";
foreach( $drugs as $k => $line ) {
    $tpl = "( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ),";
    $sql .= sprintf( $tpl
                     , mysql_real_escape_string( $line['RN'] )
                     , mysql_real_escape_string( $line['ID'] )
                     , mysql_real_escape_string( $line['NAME_CHN'] )
                     , mysql_real_escape_string( $line['TRADE_NAME'] )
                     , mysql_real_escape_string( $line['DOSEAGE_FORM_NAME'] )
                     , mysql_real_escape_string( $line['SPEC'] )
                     , mysql_real_escape_string( $line['WRAP_NAME'] )
                     , mysql_real_escape_string( $line['PERMIT_NUMBER'] )
                     , mysql_real_escape_string( $line['STAND_RATE'] )
                     , mysql_real_escape_string( $line['PRODUCT_ID'] )
                     , mysql_real_escape_string( $line['BID_ORGID'] )
                     , mysql_real_escape_string( $line['ORG_NAME'] ) );
    if( ($k+1) % 1000 === 0 ) {
        echo "Write SQL ...\n";
        $sql = substr( $sql, 0, -1 ) . ";\n";
        fwrite( $handle, $sql );
        $sql = "INSERT INTO jicai_drugs ( RN, ID, NAME_CHN, TRADE_NAME, DOSEAGE_FORM_NAME, SPEC, WRAP_NAME, PERMIT_NUMBER, STAND_RATE, PRODUCT_ID, BID_ORGID, ORG_NAME ) VALUES ";
    }
}
if( substr( $sql, -1 ) == ',' ) {
    $sql = substr( $sql, 0, -1 ) . ";\n";
    fwrite( $handle, $sql );
}
fclose( $handle );

# 请求医院列表
$size = 1000; // 每页请求条数
$url = "http://210.73.89.76/ServiceSelect/GridOrgInfoList/";
$post_data = array(
    'filter' => '',
    'group' => '',
    'InputCode' => '',
    'OrgName' => '',
    'OrgPrice' => '',
    'page' => 1,
    'pageSize' => $size,
    'ProductId' => '',
    'sort' => '',
);
$hosps = array();
$drugs_count = count( $drugs );
foreach( $drugs as $k => $line ) {
    $PRODUCT_ID = $line['ID'];
    printf( "Req: ID[%s] P[%d] C[%d/%d] \n", $PRODUCT_ID, 1, $k+1, $drugs_count );
    $post_data['ProductId'] = $PRODUCT_ID;
    $ret = http_request( 'POST', $url, $post_data );
    if( $ret['ret']!=1 ) {
        exit( 'Error:' . $ret['err'] . "\n" );
    }
    $data = json_decode( $ret['data'], true );
    if( json_last_error()!==JSON_ERROR_NONE ) {
        exit( "json_decode() Error \n" );
    }
    $hosps[ $PRODUCT_ID ] = array();
    $hosps[ $PRODUCT_ID ] = array_merge( $hosps[ $PRODUCT_ID ], $data['Data'] );
    $total = intval( $data['Total'] );
    $count = ceil( $total / $size );
    for( $p=2; $p<=$count; $p++ ) {
        printf( "Req: ID[%s] P[%d] C[%d/%d] \n", $PRODUCT_ID, $p, $k+1, $drugs_count );
        $post_data['page'] = $p;
        $ret = http_request( 'POST', $url, $post_data );
        if( $ret['ret']!=1 ) {
            exit( 'Error:' . $ret['err'] . "\n" );
        }
        $data = json_decode( $ret['data'], true );
        if( json_last_error()!==JSON_ERROR_NONE ) {
            exit( "json_decode() Error \n" );
        }
        $hosps[ $PRODUCT_ID ] = array_merge( $hosps[ $PRODUCT_ID ], $data['Data'] );
    }
}

# 存储医院数据
$fn = $dir . sprintf( "/jicai_hosps_%s.sql", date('Ymd') );
$handle = fopen( $fn, 'w+' );
fwrite( $handle, "set names utf8;\n" );
$sql = "INSERT INTO jicai_hosps ( RN, JC_ID, ID, NAME, PRICE ) VALUES ";
$i = 0;
foreach( $hosps as $JC_ID => $line ) {
    foreach( $line as $v ) {
        $tpl = "( '%s', '%s', '%s', '%s', '%s' ),";
        $sql .= sprintf( $tpl
                         , mysql_real_escape_string( $v['RN'] )
                         , mysql_real_escape_string( $JC_ID )
                         , mysql_real_escape_string( $v['ID'] )
                         , mysql_real_escape_string( $v['NAME'] )
                         , mysql_real_escape_string( $v['PRICE'] ) );
        $i++;
        if( $i % 1000 === 0 ) {
            echo "Write SQL ...\n";
            $sql = substr( $sql, 0, -1 ) . ";\n";
            fwrite( $handle, $sql );
            $sql = "INSERT INTO jicai_hosps ( RN, JC_ID, ID, NAME, PRICE ) VALUES ";
        }
    }

}
if( substr( $sql, -1 ) == ',' ) {
    $sql = substr( $sql, 0, -1 ) . ";\n";
    fwrite( $handle, $sql );
}

# 完成
exit( "Finish...\n" );

// 网络请求
function http_request( $method='GET', $url='', $data='' ) {
    $header = array(
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:59.0) Gecko/20100101 Firefox/59.0',
        'X-Requested-With: XMLHttpRequest',
    );
    $ch = curl_init();
    curl_setopt( $ch, CURLOPT_URL, $url );
    if( strtoupper($method)=='POST' ) {
        curl_setopt( $ch, CURLOPT_POST, true );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $data );
    }
    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
    curl_setopt( $ch, CURLOPT_HTTPHEADER, $header );
    curl_setopt( $ch, CURLOPT_COOKIEJAR, __DIR__ . '/cookie.jar' );
    curl_setopt( $ch, CURLOPT_COOKIEFILE, __DIR__ . '/cookie.jar' );
    $data = curl_exec( $ch );
    if( curl_errno( $ch ) !== CURLE_OK ) {
        $ret['ret'] = 0;
        $ret['err'] = curl_error( $ch );
        return $ret;
    }
    if( ($c=curl_getinfo( $ch, CURLINFO_HTTP_CODE )) != '200' ) {
        $ret['ret'] = 0;
        $ret['err'] = "http response code: $c";
        return $ret;
    }
    $ret['ret'] = 1;
    $ret['data'] = $data;
    return $ret;
}