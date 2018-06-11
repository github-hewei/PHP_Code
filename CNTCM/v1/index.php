<?php // CODE BY HEWEI
# 抓取中国中医药报的数据

ini_set( 'date.timezone', 'Asia/Shanghai' );
ini_set( 'memory_limit', '2048M' );
ini_set( 'max_execution_time', '0' );

# 初始化
$seri_dir = __DIR__ . '/serialize/';
if( !is_dir( $seri_dir ) ) {
    if( mkdir( $seri_dir )==false ) {
        exit( "Create directory ./serialize/ failed.\n" );
    }
}


# 1. 登录
$url = 'http://passport.cntcm.com.cn/http/formlogin.aspx';
$post_data = array(
    'name'      => 'renzhuang',
    'password'  => '111111',
    'submit.x'  => '89',
    'submit.y'  => '5',
    'url'       => '',
);
$ret = http_request( 'POST', $url, $post_data );
if( $ret['ret']!=1 ) {
    var_dump( $ret );
    exit;
}

# 2. 获取所有报纸记录列表
$list_fn = $seri_dir . 'list.serialize';
if( !file_exists( $list_fn ) ) {
    $start_year = 2006; // 开始年份
    $end_year   = 2018; // 结束年份
    $domDocument = new DOMDocument('1.0', 'utf-8');
    $list_data = array();
    for( $y=$start_year; $y<=$end_year; $y++ ) {
        for( $m=1; $m<=12; $m++ ) {
            $m = str_pad( $m, 2, '0', STR_PAD_LEFT );
            $date = sprintf( '%s-%s', $y, $m );
            echo $date . "\n";
            $tpl = "http://paper.cntcm.com.cn/html/content/%s/period.xml";
            $url = sprintf( $tpl, $date );
            $ret = http_request( 'GET', $url );
            if( $ret['ret']!=1 ) {
                var_dump( $ret );
                exit;
            }
            $data = trim( $ret['data'] );
            if( strlen( $data ) < 1 ) {
                continue;
            }
            $domDocument->loadXML( $data );
            $periodDomNodeList = $domDocument->getElementsByTagName('period');
            foreach( $periodDomNodeList as $periodDomElement ) {
                $row = array();
                $row['id']             = trim( $periodDomElement->getAttribute('id') );
                $row['period_name']    = trim( $periodDomElement->getElementsByTagName('period_name')->item(0)->nodeValue );
                $row['paper_id']       = trim( $periodDomElement->getElementsByTagName('paper_id')->item(0)->nodeValue );
                $row['period_date']    = trim( $periodDomElement->getElementsByTagName('period_date')->item(0)->nodeValue );
                $row['front_page']     = trim( $periodDomElement->getElementsByTagName('front_page')->item(0)->nodeValue );
                $row['rmp_exe_path']   = trim( $periodDomElement->getElementsByTagName('rmp_exe_path')->item(0)->nodeValue );
                $row['rmp_pic_path']   = trim( $periodDomElement->getElementsByTagName('rmp_pic_path')->item(0)->nodeValue );
                $row['rmp_xml_path']   = trim( $periodDomElement->getElementsByTagName('rmp_xml_path')->item(0)->nodeValue );
                $row['rmp_build_time'] = trim( $periodDomElement->getElementsByTagName('rmp_build_time')->item(0)->nodeValue );
                $list_data[] = $row;
            }
        }
    }
    file_put_contents( $list_fn, serialize( $list_data ) );

} else {
    $list_data = unserialize( file_get_contents( $list_fn ) );

}

# 3. 获取每一页的详细信息
$detail_fn = $seri_dir . 'detail.serialize';
$detail = array();
if( file_exists( $detail_fn ) ) {
    $handle = fopen( $detail_fn, 'r' );
    while (!feof($handle)) {
        $row = trim( fgets($handle) );
        if( strlen($row) < 1 ) {
            continue;
        }
        $row_arr = unserialize( $row );
        $detail[ $row_arr['id'] ] = $row_arr;
    }
    fclose( $handle );
}
foreach( $list_data as $item ) {
    $id = intval( $item['id'] );
    if( array_key_exists( $id, $detail ) ) {
        continue ;
    }
    list( $y, $m, $d) = explode( '-', $item['period_date'] );
    $tpl = "http://paper.cntcm.com.cn/html/content/%s/%s/%s";
    $url = sprintf( $tpl, sprintf('%s-%s',$y,$m), $d, $item['front_page'] );
    $ret = http_request( 'GET', $url );
    if( $ret['ret']!=1 ) {
        var_dump( $ret, $item, $url );
        exit;
    }
    if( strlen( trim($ret['data']) ) < 100 ) {
        continue;
    }
    $p1 = "/<table cellspacing=0 cellpadding=2 width=100% border=0><tbody>(.*)<\/tbody><\/table>/iUs";
    if( preg_match( $p1, $ret['data'], $m1 )==false ) {
        var_dump( $ret, $item, $url, $m1, 'p1' );
        exit;
    }
    $p2 = "/<tr.*> <td.*>&nbsp;<a id=pageLink href=.*>(.*)<\/a><\/td> <td nowrap align=middle width=55><a href=(.*)><img height=16 src=\".*\" width=16 border=0><\/a><\/td><\/tr>/iUs";
    if( preg_match_all( $p2, $m1[1], $m2 )==false ) {
        var_dump( $m1[1], 'p2' );
        exit;
    }
    if( !isset( $m2[1] ) || count( $m2[1] ) < 1 ) {
        var_dump( $m1[1], $m2 );
        exit;
    }
    $row = array();
    $row['id'] = $id;
    $row['date'] = $item['period_date'];
    $row['list'] = array();
    for( $i=0,$len=count($m2[1]); $i<$len; $i++ ) {
        if( trim( $m2[2][$i]=='/page/' ) ) {
            continue;
        }
        if( pathinfo(trim($m2[2][$i]),PATHINFO_EXTENSION)!='pdf' ) {
            var_dump( $ret['data'], $url, $m2[2][$i] );
            exit;
        }
        $row['list'][] = array(
            'title'     => trim( $m2[1][$i] ),
            'detail'    => trim( $m2[2][$i] ),
        );
    }
    file_put_contents( $detail_fn, serialize($row)."\n", FILE_APPEND );
    $detail[ $row['id'] ] = $row;
    echo $id . "\n";
}

# 4. 下载 pdf
$i = 0;
$handle = fopen( $detail_fn, 'r' );
while ( !feof($handle) ) {
    $row = trim( fgets( $handle ) );
    if( strlen( $row ) < 1 ) {
        continue ;
    }
    $row_arr = unserialize( $row );
    foreach( $row_arr['list'] as $item ) {
        $i++;
        echo $i . "\n";
        /* URL 和文件名 处理 */
        $detail_url = $item['detail'];
        $detail_url = str_replace( "../", '', $detail_url );
        $path = __DIR__ . '/' .pathinfo( $detail_url, PATHINFO_DIRNAME );
        if( !is_dir($path) && !mkdir($path, 0777, true) ) {
            exit("Create directory failed.\n");
        }
        $fn = $path . '/' . pathinfo( $detail_url, PATHINFO_BASENAME );
        $url = "http://paper.cntcm.com.cn/" . $detail_url;
        if( file_exists($fn) ) {
            continue ;
        }
        /* 下载 pdf 文件 */
        $ret = http_request( 'GET', $url );
        if( $ret['ret']!=1 ) {
            var_dump( $ret, $item, $url, 'download failed' );
            exit;
        }
        if( !file_put_contents( $fn, $ret['data'] ) ) {
            var_dump( $fn, $url, $item, 'put failed' );
            exit;
        }
        /* 主题和版本处理 */
        if( preg_match( "/^第(\d+)版：(.*)$/", $item['title'], $m)==false ) {
            var_dump( $item, $url );
            exit;
        }
        $order = intval( $m[1] );
        $title = trim( $m[2] );
        $tpl = "insert into CNTCM_PDF (m_CID, m_Date, m_Order, m_Title, m_Path) values('%s', '%s', '%s', '%s', '%s');\n";
        $sql = sprintf( $tpl, $row_arr['id'], $row_arr['date'], $order, $title, $detail_url );
        file_put_contents( __DIR__.'/pdf.data.sql', $sql, FILE_APPEND );
    }
}


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
    $ret['ret'] = 1;
    $ret['data'] = $data;
    return $ret;
}


/* 创建数据表来存储数据 */
$sql = <<<CREATE_SQL

CREATE TABLE IF NOT EXISTS `CNTCM_PDF` (
    m_ID INT NOT NULL AUTO_INCREMENT,
    m_CID INT DEFAULT 0                 COMMENT '关联ID',
    m_Date CHAR(12) DEFAULT ''          COMMENT '日期',
    m_Order TINYINT(2) DEFAULT 0        COMMENT '第几版',
    m_Title VARCHAR(255) DEFAULT ''     COMMENT '标题',
    m_Path VARCHAR(255) DEFAULT ''      COMMENT '文件路径',
    m_CreateTime TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    m_Delete TINYINT(2) DEFAULT 0,
    PRIMARY KEY( m_ID )
)ENGINE=MYISAM DEFAULT CHARSET=UTF8 COMMENT '中国中医药报PDF数据';

CREATE_SQL;
