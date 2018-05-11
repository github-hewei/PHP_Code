<?php 
// 抓取健康报的资讯
ini_set( 'memory_limit', '2048M' );
ini_set( 'max_execution_time', '0' );

$dir = __DIR__ . '/jiankangbao/';

$url_list = array(
    '1' => 'http://www.jkb.com.cn/TCM/industryNews/',               # 行业新闻
    '2' => 'http://www.jkb.com.cn/TCM/diet/',                       # 药膳食疗
    '3' => 'http://www.jkb.com.cn/TCM/traditionalChineseMedicine/', # 中药宝库
    '4' => 'http://www.jkb.com.cn/TCM/specialtyClinics/',           # 特色诊疗
    '5' => 'http://www.jkb.com.cn/TCM/chineseCulture/',             # 中医文化
);

$fn = $dir . sprintf( 'url.serialize.%s.data', date('Ymd') );
if( !file_exists( $fn ) ) {
    $infor_list = array();
    foreach( $url_list as $type => $url ) {
        for( $page=1; true; $page++ ) {
            $new_url = $url;
            if( $page > 1 ) {
                $new_url .= $page . '.html';
            }
            echo $new_url . "\n";
            $ret = http_request( 'GET', $new_url );
            if( $ret['ret'] != 1 ) {
                break;
            }
            $p = "/<li class=\'[a-z-]{0,}\'>\s(.*)<\/li>/iUs";
            if( !preg_match_all( $p, $ret['data'], $m ) ) {
                continue ;
            }
            foreach( $m[0] as $key => $value ) {
                $title = '';        // 标题
                $detail_url = '';   // 详情地址
                $p = "/<h4>.*<a href=\"(.*)\" target=\"_blank\" class=\"ellipsis fl\" title=\".*\">(.*)<\/a>.*<\/h4>/iUs";
                if( preg_match( $p, $value, $m1 ) ) {
                    $title = trim( $m1[2] );
                    $detail_url = trim( $m1[1] );
                }
                $cover = '';        // 封面图地址
                $p = "/<img src=\"(.*)\" alt=\"\">/iUs";
                if( preg_match( $p, $value, $m2 ) ) {
                    $cover = trim( $m2[1] );
                }
                $id = pathinfo( $detail_url, PATHINFO_FILENAME );
                $temp = array(
                    'id'    => $id,
                    'type'  => $type,
                    'url'   => $detail_url,
                    'title' => $title,
                    'cover' => $cover,
                );
                $infor_list[] = $temp;
            }
        }
    }
    file_put_contents( $fn, serialize( $infor_list ) );
} else {
    $infor_list = unserialize( file_get_contents( $fn ) );
}

// 存储已经存在的资讯ID
$existsfn = $dir . 'information.exists.data';
$exists = array();
if( file_exists( $existsfn ) ) {
    $text = file_get_contents( $existsfn );
    foreach( explode( "\n", $text ) as $id ) {
        if( strlen( $id ) > 0 ) {
            $exists[ $id ] = 1;
        }
    }
}

$h2 = fopen( $existsfn, 'a+' );

// 存储新增资讯的SQL
$sqlfn = $dir . sprintf( 'information.insert.%s.sql', date('Ymd') );
$h1 = fopen( $sqlfn, 'a+' );
fwrite( $h1, "set names utf8;\n" );

// 匹配资讯详情
$i = 0;
foreach( $infor_list as $key => $value ) {

    if( array_key_exists( $value['id'], $exists ) ) {
        continue ;
    }

    $ret = http_request( 'GET', $value['url'] );
    if( $ret['ret'] != 1 ) {
        exit( $ret['err'] . "\n" );
    }
    $data = $ret['data'];

    // 匹配 时间/来源
    $from = '';
    $datetime = '';
    $p = "/<h5 class=\"mainLH5\">.*<span class=\"fl\" style=\"padding\-left: 0\">(.*)<\/span>.*<span class=\"fl\">来源：(.*)<\/span>.*<\/h5>/iUs";
    if( preg_match( $p, $data, $m ) ) {
        $from = trim( $m[2] );
        $datetime = trim( $m[1] );
    }

    // 匹配内容
    $content = '';
    $p = "/<div id=\"nc_con\">(.*)<\/div>\s+<div id=\"nc_page\">/iUs";
    if( preg_match( $p, $data, $m ) ) {
        $content = trim( $m[1] );
    }

    if( empty( $value['id'] )
        || empty( $value['title'] )
        // || empty( $datetime )
        // || empty( $content )
    ) {
        // continue ;
        exit( $value['url'] . "\n" );
    }

    $i ++;
    echo $i . "\n";

    $exists[ $value['id'] ] = 1;
    fwrite( $h2, $value['id'] . "\n" );

    // 写入SQL文件
    $tpl = "INSERT INTO temp_information (m_Type, m_OtherID, m_Title, m_Cover, m_Form, m_Body, m_PublishTime) VALUES ('%s', '%s', '%s', '%s', '%s', '%s', '%s');\n";
    $sql = sprintf( $tpl, $value['type']
        , mysql_real_escape_string( $value['id'] )
        , mysql_real_escape_string( $value['title'] )
        , mysql_real_escape_string( $value['cover'] )
        , mysql_real_escape_string( $from )
        , mysql_real_escape_string( $content )
        , mysql_real_escape_string( $datetime )
    );

    fwrite( $h1, $sql );

}
fclose( $h1 );
fclose( $h2 );

exit( "Finish...\n" );

// 网络请求
function http_request( $method='GET', $url='', $data='' ) {
    $header = array(
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:59.0) Gecko/20100101 Firefox/59.0',
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