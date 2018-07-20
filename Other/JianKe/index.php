<?php // CODE BY HW
// 爬健客网数据
ini_set( 'memory_limit', '2048M' );
ini_set( 'date.timezone', 'Asia/Shanghai' );
ini_set( 'max_execution_time', '0' );

require_once 'GlobalUnit.php';

// 获取分类列表
$list_fn = __DIR__ . '/list.txt';
if( ! file_exists( $list_fn ) ) {
    $r = GU::cURL('GET', 'https://search.jianke.com/list-010301.html');
    if( $r['ret'] != 1 ) {
        var_dump( $r );
        exit;
    }

    $p1 = '|<h3 class="no_bd_b">\s+<a href="javascript:void\(0\);" class="icon_btn ">(.*)</a>\s+</h3>\s+';
    $p1 .= '<ul class="list_ul" style="display: none">(.*)</ul>|iUs';
    if( intval(preg_match_all($p1, $r['data'], $m1 )) < 1 ) {
        var_dump( $r['data'] );
        exit;
    }

    for( $i=0,$l=count($m1[1]); $i < $l; $i++ ) {
        $v1 = trim( $m1[1][$i] );
        $p2 = '|<li id="(\d+)"><a\s+href="(.*)">(.*)</a>\s+</li>|iUs';
        if( intval( preg_match_all($p2, $m1[2][$i], $m2) ) < 1 ) {
            var_dump( $m1[2][$i] );
            exit;
        }
        for( $j=0,$l2=count($m2[0]); $j < $l2; $j++ ) {
            $row = sprintf( "%s\t%s\t%s\n", trim($m1[1][$i]), trim($m2[3][$j]), trim($m2[1][$j]) );
            file_put_contents( $list_fn, $row, FILE_APPEND );
        }
    }
}
$list_data = file_get_contents( $list_fn );

// 分别访问每个分类的药品列表
$data_dir = __DIR__ . '/data/';

$list_arr = explode( "\n", $list_data );
foreach( $list_arr as $item ) {
    if( strlen($item) < 1 )
        continue;
    list( $c1, $c2, $cid ) = explode( "\t", $item );
    $cur_dir = $data_dir . $c1 . '/' . $c2 . '/';
    if( !GU::CreatePath($cur_dir) ) {
        exit( "Error\n" );
    }

    # echo $c1 . ":" . $c2 . "\n";

    # 黑名单 没有数据的分类
    $black_list = array(
        '甲沟炎',
        '麻风病',
        '灰指甲',
        '静脉曲张',
    );
    if( in_array( $c2, $black_list) ) {
        continue ;
    }
    for( $p=1,$tp=1; $p<=$tp; $p++ ) {
        $cur_fn = sprintf( "%s%s_%s.txt", $cur_dir, $cid, $p );
        if( !file_exists($cur_fn) ) {
            $tpl = "https://www.jianke.com/list-%s-0-%s-0-1-0-0-0-0-0.html";
            $url = sprintf( $tpl, $cid, $p );
            $r = GU::cURL('GET', $url);
            if( $r['ret']!=1 ) {
                var_dump( $r );
                exit;
            }
            // 匹配药品详情链接
            $p3 = '|<div class="lihover">.*<p>\s+<a href="(.*)".*>.*</a>\s+</p>.*</div>|iUs';
            if( intval(preg_match_all($p3, $r['data'], $m3)) < 1 ) {
                var_dump( $r['data'] );
                exit;
            }
            $det = array();
            for( $i=0,$l=count($m3[0]); $i < $l; $i++ ) {
                $det[] = 'https:' . $m3[1][$i];
            }
            file_put_contents( $cur_fn, join("\n", $det) );
            // 匹配总页数
            $p4 = '|<span href=".*" id="goto_page_total_pages">共<strong>(\d+)</strong>|iUs';
            if( intval(preg_match($p4, $r['data'], $m4 )) < 1 ) {
                var_dump( $r['data'] );
                exit;
            }
            // 赋值给tp让循环这么多次
            $tp = intval( $m4[1] );
        }
    }
}

// 把所有的药品详情页下载下来
$loaded = array(); // 这个数组记录已经下载过的页面的ID
$loaded_fn = __DIR__ . '/loaded.txt';
if( file_exists( $loaded_fn ) ) {
    $text = trim( file_get_contents( $loaded_fn ) );
    $temp = explode( "\n", $text );
    foreach( $temp as $id ) {
        $loaded[ $id ] = 1;
    }
}
$num = 0; // 计数
$files = GU::GetFilesFromDir( $data_dir, true );
foreach( $files as $file ) {
    $text = trim( file_get_contents( $file ) );
    $temp = explode( "\n", $text );
    $path = str_replace( "/data/", "/details/", dirname( $file ) . '/' );
    if( !GU::CreatePath( $path ) ) {
        exit( "Error\n" );
    }
    foreach( $temp as $url ) {
        $num ++;
        $p = '|^https://www.jianke.com/product/(\d+)\.html$|';
        if( !preg_match( $p, $url, $m) ) {
            file_put_contents(__DIR__.'/E.log', $url."\n", FILE_APPEND );
            continue;
        }
        $id = intval( $m[1] );
        if( array_key_exists( $id, $loaded ) )
            continue ;
        $r = GU::cURL('GET', $url);
        if( $r['ret']!=1 ) {
            var_dump( $r );
            exit;
        }
        $det_fn = $path . $id . '.html';
        file_put_contents( $det_fn, $r['data'] );
        file_put_contents( $loaded_fn, $id."\n", FILE_APPEND );
        $loaded[ $id ] = 1;
        echo $id ."\t". $num . "\n";
    }
}

# 解析药品详情页面获取到药品信息
$saved_fn = __DIR__ . '/saved.txt';
$saved = array();
if( file_exists( $saved_fn ) ) {
    $text = trim( file_get_contents( $saved_fn ) );
    $temp = explode( "\n", $text );
    foreach( $temp as $id ) {
        $saved[ intval($id) ] = 1;
    }
}
$drugs_fn = __DIR__ . '/drugs.serialize.data';

$files = GU::GetFilesFromDir( __DIR__.'/details/', true );
$num = 0;
foreach( $files as $file ) {
    $num++;
    # 如果已经存储过了就不保存了
    $id = intval( pathinfo($file,PATHINFO_FILENAME) );
    if( array_key_exists( $id, $saved ) ) {
        continue ;
    }

    # 黑名单，页面有问题的
    $black_list = array(
        # 废的链接
        41933,
    );
    if( in_array( $id, $black_list ) ) {
        continue ;
    }

    echo $id . "\t" . $num . "\n";

    $text = file_get_contents( $file );
    $drug = array();

    $drug['编码：'] = $id;
    ########################### 匹配说明书开始 #####################################
    $p1 = '|<\!\-\- 说明书 开始 \-\->(.*)<\!\-\- 说明书 结束 \-\->|iUs';
    if( intval( preg_match( $p1, $text, $m1 ) ) < 1 ) {
        var_dump( $text, $file, 1 );
        exit;
    }
    $drug['说明书：'] = trim( $m1[0] );
    ########################### 匹配说明书结束 #####################################


    ########################### 匹配图片开始 #######################################
    $p5 = '|<div class=\"pro_pic_li\">\s+<ul>(.*)</ul>\s+</div>|iUs';
    if( preg_match( $p5, $text, $m5 ) === false ) {
        var_dump( $text, $file, 7 );
        exit;
    }
    $image_arr = array();
    if( count( $m5 ) > 0 ) {
        $p5_2 = '|<a.*rel="(.*)".*>.*</a>|iUs';
        if( preg_match_all( $p5_2, $m5[1], $m5_2 ) === false ) {
            var_dump( $m5[1], $file, 8 );
            exit;
        }
        for($i=0,$len=count($m5_2[0]); $i<$len; $i++) {
            $obj_str = trim( $m5_2[1][$i] );
            $str = substr( $obj_str, 1, -1 );
            $tmp_arr = explode( ',', $str );
            $rel_arr = array();
            foreach( $tmp_arr as $item ) {
                list($key, $val) = explode( ':', $item );
                $rel_arr[ trim($key) ] = substr( trim( $val ), 1, -1 );
            }
            $image_arr[] = $rel_arr['largeimage'];
        }
    }
    $drug['药品图片：'] = $image_arr;
    ########################### 匹配图片结束 #######################################

    ####################################################################################
    # 下架商品页面结构不同 特殊处理
    $pp = "|该产品已下架！|iUs";
    if( intval( preg_match( $pp, $text ) ) > 0  ) {
        // 匹配药品信息
        $pp1 = '|<div class=\"proinfo\">(.*)</div>|iUs';
        if( intval( preg_match( $pp1, $text, $mm1 ) ) < 1 ) {
            var_dump( $text, $file, 'pp1' );
            exit;
        }
        $pp2 = '|<dt>(.*)</dt>\s+<dd.*>(.*)</dd>|iUs';
        if( intval( preg_match_all( $pp2, $mm1[1], $mm2 ) ) < 1 ) {
            var_dump( $mm1[1], $file, 'pp2' );
            exit;
        }
        $info = array();
        for($i=0,$len=count($mm2[0]); $i<$len; $i++ ) {
            $key = trim( $mm2[1][$i] );
            $val = trim( $mm2[2][$i] );
            $info[ $key ] = $val;
        }
        // 匹配商品名
        $pp3 = '|<h1 class=\"pro_tt\">(.*)</h1>|iUs';
        if( intval( preg_match( $pp3, $text, $mm3 ) ) < 1 ) {
            var_dump( $text, $file, 'pp3' );
            exit;
        }
        $pp4 = '|\((.*)\)|iUs';
        if( preg_match( $pp4, $mm3[1], $mm4 ) === false ) {
            var_dump( $mm3[1], $file, 'pp4' );
            exit;
        }
        $info['商品名：'] = '';
        if( isset( $mm4[1] ) ) {
            $info['商品名：'] = trim( $mm4[1] );
        }

        # 合并药品数据
        $drug['通用名称：'] = isset($info['通用名称：']) ? $info['通用名称：'] : '';
        $drug['批准文号：'] = isset($info['批准文号：']) ? $info['批准文号：'] : '';
        $drug['产品规格：'] = isset($info['产品规格：']) ? $info['产品规格：'] : '';
        $drug['生产厂家：'] = isset($info['生产企业：']) ? $info['生产企业：'] : '';
        $drug['商品名：']   = isset($info['商品名：']) ? $info['商品名：'] : '';
        // ---------------------------------------------------------------------- //
        # 存储到文本文件
        $row = str_replace( array("\t","\n"), array("\\t", "\\n"), serialize( $drug ) );
        file_put_contents( $drugs_fn, $row."\n", FILE_APPEND );
        file_put_contents( $saved_fn, $id . "\n", FILE_APPEND );
        $saved[ $id ] = 1;

        continue ; # 不要往后执行了
    }
    ####################################################################################

    // 匹配药品信息
    $p2 = '|<\!\-\- 名称以及类别等 开始 \-\->(.*)<\!\-\- 名称以及类别等结束 \-\->|iUs';
    if( intval( preg_match( $p2, $text, $m2 ) ) < 1 ) {
        var_dump( $text, $file, 2 );
        exit;
    }
    $p2_2 = '|<dt>(.*)</dt>\s+<dd.*>(.*)</dd>|iUs';
    if( intval( preg_match_all( $p2_2, $m2[1], $m2_2 ) ) < 1 ) {
        var_dump( $m2[1], $file, 3 );
        exit;
    }
    for($i=0,$len=count($m2_2[0]); $i<$len; $i++) {
        $key = trim( $m2_2[1][$i] );
        $val = trim( strip_tags($m2_2[2][$i]) );
        if( $key=='批准文号：' ) {
            $val = str_replace( ' （国家食药总局查询）', '', $val );
        }
        $drug[ $key ] = $val;
    }

    // 匹配规格
    $p3 = '|<\!\-\- 规格 开始 \-\->(.*)<\!\-\- 规格 结束 \-\->|iUs';
    if( intval( preg_match( $p3, $text, $m3 ) ) < 1 ) {
        var_dump( $text, $file, 4 );
        exit;
    }
    $drug['产品规格：'] = '';
    $p3_2 = '|<dd class=\"specif\">.*<a class=\"active_a\".*>(.*)</a>.*</dd>|iUs';
    if( preg_match( $p3_2, $m3[1], $m3_2 ) === false ) {
        var_dump( $text, $file, 5 );
        exit;
    }
    $drug['产品规格：'] = isset( $m3_2[1] ) ? trim( $m3_2[1] ) : '';

    // 匹配厂商
    $p4 = '|<\!\-\- 规格 结束 \-\->\s+<dl class=\"assort \">.*<a.*>(.*)</a>.*</dl>|iUs';
    if( intval( preg_match( $p4, $text, $m4 ) ) < 1 ) {
        var_dump( $text, $file, 6 );
        exit;
    }
    $drug['生产厂家：'] = trim( $m4[1] );

    // 匹配商品名
    $p6 = '|<div class=\"widet\">\s+<h1>(.*)</h1>|iUs';
    if( intval(preg_match( $p6, $text, $m6 )) < 1 ) {
        var_dump( $text, $file, 9 );
        exit;
    }
    $p6_2 = '|\((.*)\)|iUs';
    if( preg_match( $p6_2, $m6[1], $m6_2 ) === false ) {
        var_dump( $m6[1], $file, 10 );
        exit;
    }
    $drug['商品名：'] = '';
    if( isset( $m6_2[1] ) ) {
        $drug['商品名：'] = trim( $m6_2[1] );
    }

    // ------------------------------------------------------------------ //
    # 存储到文本文件
    $row = str_replace( array("\t","\n"), array("\\t", "\\n"), serialize( $drug ) );
    file_put_contents( $drugs_fn, $row."\n", FILE_APPEND );
    file_put_contents( $saved_fn, $id . "\n", FILE_APPEND );
    $saved[ $id ] = 1;

}

