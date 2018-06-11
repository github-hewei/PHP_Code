<?php // CODE BY HW
# 抓取中国中医药报的数据 V2 版

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

define( 'DOWNLOAD_PDF', FALSE ); # 是否下载PDF


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
$details_fn = $seri_dir . 'details.serialize';
$details = array();
if( file_exists( $details_fn ) ) {
    $handle = fopen( $details_fn, 'r' );
    while (!feof($handle)) {
        $row = trim( fgets($handle) );
        if( strlen( $row ) < 1 ) {
            continue;
        }
        $row_arr = unserialize( base64_decode( $row ) );
        $details[ $row_arr['date'] ] = 1;
    }
    fclose($handle);
}

$num = 0;
foreach( $list_data as $item ) {
    $num++;
    $id = intval( $item['id'] );
    if( array_key_exists( $item['period_date'], $details ) ) {
        continue;
    }

    list( $year, $month, $day) = explode( '-', $item['period_date'] );
    $tpl = "http://paper.cntcm.com.cn/html/content/%s-%s/%s/%s";
    $url = sprintf( $tpl, $year, $month, $day, $item['front_page'] );
    $ret = http_request( 'GET', $url );
    if( $ret['ret']!=1 ) {
        var_dump( $ret, $item, $url );
        exit;
    }
    if( strlen( trim($ret['data']) ) < 100 ) {
        continue;
    }
    $line = array();
    $line['id'] = $id;
    $line['date'] = $item['period_date'];
    /* 匹配版面导航 */
    $p3 = "/<table cellspacing=0 cellpadding=2 width=100% border=0><tbody>(.*)<\/tbody><\/table>/iUs";
    if( preg_match( $p3, $ret['data'], $m3 )==false ) {
        var_dump( $ret, $item, $url, $m3, 'p3' );
        exit;
    }
    $p4 = "/<tr.*> <td.*>&nbsp;<a id=pageLink href=(.*)>(.*)<\/a><\/td> <td nowrap align=middle width=55><a href=(.*)><img height=16 src=\".*\" width=16 border=0><\/a><\/td><\/tr>/iUs";
    if( preg_match_all( $p4, $m3[1], $m4 )==false ) {
        var_dump( $m3[1], 'p4' );
        exit;
    }

    for( $i=0,$len=count($m4[0]); $i<$len; $i++ ) {
        $m4[1][$i] = str_replace( "./", '', $m4[1][$i] );
        $line['list'][ $m4[1][$i] ] = array(
            'URL'   => $m4[1][$i],
            'TITLE' => $m4[2][$i],
            'PDF'   => $m4[3][$i],
        );
    }

    /* 匹配子栏目列表 */
    $p1 = "/<TABLE cellSpacing=0 cellPadding=1 border=0> <TBODY>(.*)<\/TBODY> <\/TABLE>/iUs";
    if( preg_match( $p1, $ret['data'], $m1 )==false ) {
        var_dump( $ret, $item, $url, $m1, 'p1' );
        exit;
    }
    if( strlen( trim( $m1[1] ) ) > 1 ) {
        $p2 = "/<tr> <TD class=px12 valign=\"top\"> <img src=\".*gif\" width=\"10\" height=\"10\"> <\/TD> <td class=px12 valign=\"top\"> <a href=(.*)><div style=\"display:inline\" id=.*>(.*)<\/div><\/a> <\/td> <\/TR>/iUs";
        if( preg_match_all( $p2, $m1[1], $m2 )==false ) {
            var_dump( $ret, $item, $url, $m2, 'p2' );
            exit;
        }
    }

    for( $i=0,$len=count($m2[0]); $i<$len; $i++ ) {
        $line['list'][ $item['front_page'] ]['ARTICLE'][ $m2[2][$i] ] = $m2[1][$i];
    }

    /* 匹配其他页面的子栏目列表 */
    foreach( $line['list'] as $key => $value ) {
        if( isset( $value['ARTICLE'] ) ) {
            continue;
        }
        $tpl = "http://paper.cntcm.com.cn/html/content/%s-%s/%s/%s";
        $url = sprintf( $tpl, $year, $month, $day, $value['URL'] );
        $ret = http_request( 'GET', $url );
        if( $ret['ret']!=1 ) {
            var_dump( $ret, $item, $url );
            exit;
        }
        if( strlen( trim($ret['data']) ) < 100 ) {
            continue;
        }
        $p5 = "/<TABLE cellSpacing=0 cellPadding=1 border=0> <TBODY>(.*)<\/TBODY> <\/TABLE>/iUs";
        if( preg_match( $p5, $ret['data'], $m5 )==false ) {
            var_dump( $ret, $item, $url, $m5, 'p5' );
            exit;
        }
        if( strlen( trim( $m5[1] ) ) < 1 ) {
            continue ;
        }
        $p6 = "/<tr> <TD class=px12 valign=\"top\"> <img src=\".*gif\" width=\"10\" height=\"10\"> <\/TD> <td class=px12 valign=\"top\"> <a href=(.*)><div style=\"display:inline\" id=.*>(.*)<\/div><\/a> <\/td> <\/TR>/iUs";
        if( preg_match_all( $p6, $m5[1], $m6 )==false ) {
            var_dump( $ret, $m5, $url, $m6, 'p6' );
            exit;
        }
        for( $i=0,$len=count($m6[0]); $i<$len; $i++ ) {
            $line['list'][ $value['URL'] ]['ARTICLE'][ $m6[2][$i] ] = $m6[1][$i];
        }
    }
    echo $num . "\n";
    file_put_contents( $details_fn, base64_encode( serialize($line) )."\n", FILE_APPEND );
    $details[ $item['period_date'] ] = 1;
}

# 4. 下载 pdf 并且抓取各个栏目的数据
$sql_fn = __DIR__ . '/cntcm_pdf.sql';
$flag_fn = __DIR__ . '/cntcm_pdf.flag';
$flag = array();
if( file_exists( $flag_fn ) ) {
    $handle = fopen( $flag_fn, 'r' );
    while ( !feof($handle) ) {
        $row = trim( fgets($handle) );
        if( strlen( $row ) < 1 ) {
            continue ;
        }
        $flag[ $row ] = 1;
    }
    fclose($handle);
}

$handle = fopen( $details_fn, 'r' );
while ( !feof($handle) ) {
    $row = trim( fgets($handle) );
    if( strlen( $row ) < 1 ) {
        continue;
    }
    $row_arr = unserialize( base64_decode($row) );
    list( $year, $month, $day ) = explode( '-', $row_arr['date'] );
    echo $row_arr['date'] . "\n";
    if( array_key_exists($row_arr['date'], $flag ) ) {
        continue ;
    }
    foreach( $row_arr['list'] as $item ) {
        /* 下载 PDF */
        /* URL 和 文件名处理 */
        $pdf_url = $item['PDF'];
        $pdf_url = str_replace( '../', '', $pdf_url );
        if( DOWNLOAD_PDF ) {
            $path = __DIR__ . '/' .pathinfo( $pdf_url, PATHINFO_DIRNAME );
            if( !is_dir($path) && !mkdir($path, 0777, true) ) {
                exit("Create directory failed.\n");
            }
            $fn = $path . '/' . pathinfo( $pdf_url, PATHINFO_BASENAME );
            $url = "http://paper.cntcm.com.cn/{$pdf_url}";

            $ret = http_request( 'GET', $url );
            if( $ret['ret']!=1 ) {
                var_dump( $ret, $item, $url, 'download failed' );
                exit;
            }
            if( !file_put_contents( $fn, $ret['data'] ) ) {
                var_dump( $fn, $url, $item, 'put failed' );
                exit;
            }
        }

        /* 主题和版本处理 */
        if( preg_match( "/^第(\d+)版：(.*)$/", $item['TITLE'], $m)==false ) {
            var_dump( $item );
            exit;
        }
        $order = intval( $m[1] );
        $title = trim( $m[2] );

        /* 下载每一版的子栏目 */
        /* 子栏目类型 文章 图片 图片新闻 */
        $article = array();
        if( isset( $item['ARTICLE'] ) && count( $item['ARTICLE'] ) > 0 ) {
            foreach( $item['ARTICLE'] as $key => $value ) {
                $html_path = 'html/content/%s-%s/%s/';
                $html_path = sprintf( $html_path, $year, $month, $day );
                $html_fn = __DIR__ . '/' .$html_path . $value;
                if( !file_exists( $html_fn ) ) {
                    $tpl = "http://paper.cntcm.com.cn/html/content/%s-%s/%s/%s";
                    $url = sprintf( $tpl, $year, $month, $day, $value );
                    $ret = http_request( 'GET', $url );
                    if( $ret['ret']!=1 ) {
                        var_dump( $ret, $url );
                        exit;
                    }
                    if( !is_dir( __DIR__ . '/' . $html_path ) ) {
                        if( !mkdir(__DIR__ . '/' . $html_path, 0777, true) ) {
                            exit("mkdir failed...\n");
                        }
                    }
                    file_put_contents( $html_fn, $ret['data'] );
                }
                $article[] = array(
                    't' => $key,
                    'u' => $html_path . $value,
                );
                // if( FALSE ) { # MLGB 放弃使用正则匹配了
                //     /* 匹配标题和副标题 */
                //     // var_dump( $ret['data'], $url );
                //     // exit;
                //     $p1 = "/<table cellspacing=0 cellpadding=5 width=572 border=0> <tbody>(.*)<\/tbody> <\/table>/iUs";
                //     if( preg_match( $p1, $ret['data'], $m1 ) == false ) {
                //         var_dump( $ret['data'], $url );
                //         exit;
                //     }
                //     //var_dump( $m1[1] ); 
                //     //echo "----------------------------------------------\n";
                //     $p2 = "/<tr valign=top> <td style=\"padding-left: 6px;padding-top:10px;padding-bottom:10px;\" align=center width=572> <span style=\"font-size:14px;line-height:23px;\">(.*)<\/span><br> <strong style=\"font-size:23px;font-family:黑体;line-height:30px;\">(.*)<\/strong><br> <span style=\"font-size:14px;line-height:30px;\"><\/span><br> <span style=\"font-size:12px;\">(.*)<\/span><\/td><\/tr> /iUs";
                //     if( preg_match( $p2, $m1[1], $m2 ) == false ) {
                //         var_dump( $m1[1], $url );
                //         exit;
                //     }
                //     $title     = trim( $m2[2] ); # 主标题
                //     $subtitle1 = trim( $m2[1] ); # 副标题1
                //     $subtitle2 = trim( $m2[3] ); # 副标题2
                //     //var_dump( $m2[1], $m2[2], $m2[3] );
                //     //echo "-------------------------------------------------------\n";

                //     /* 匹配主内容 */
                //     // ...

                // }

                
            }
        }

        // 写入SQL文件
        $tpl = "INSERT INTO CNTCM_PDF ( m_CID, m_Date, m_Order, m_Title, m_Path, m_Article ) VALUES ('%s', '%s', '%s', '%s', '%s', '%s' );\n";
        $sql = sprintf( $tpl, 
                        $row_arr['id'], 
                        $row_arr['date'], 
                        $order, $title, 
                        $pdf_url,
                        serialize( $article )
        );
        file_put_contents( $sql_fn, $sql, FILE_APPEND );
    }
    file_put_contents( $flag_fn, $row_arr['date']."\n", FILE_APPEND );
}
fclose( $handle );

exit("Finish...\n");


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