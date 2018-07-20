<?php // CODE BY HW
# 抓取的健客网的药品数据已经存储到了文本文件
# 下面对数据进行整理清洗，以便后续使用
exit();
require_once 'GlobalUnit.php';

# 将分类写入数据表
try {
    
    $dsn = 'mysql:host=127.0.0.1;port=3306;dbname=hw_data;';
    $pdo = new PDO( $dsn, 'root', '1086379ybt' );
    $pdo->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
    $pdo->setAttribute( PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC );
    $pdo->exec('SET NAMES UTF8');

    if( false ) {
        # 将分类写入分类表
        $pdo->exec('truncate YBT_JKCategory');

        $h1 = opendir( __DIR__.'/details/' );
        while( ($f1=readdir($h1))!==FALSE ) {
            if( $f1=='.' || $f1=='..' )
                continue;
            $tpl = "INSERT INTO `YBT_JKCategory` (m_Name) VALUES ('%s')";
            $sql = sprintf( $tpl, $f1 );
            $r = $pdo->exec( $sql );
            $id1 = intval( $pdo->lastInsertId() );

            $h2 = opendir(__DIR__.'/details/'.$f1.'/');
            while ( ($f2=readdir($h2))!==FALSE ) {
                if( $f2=='.' || $f2=='..' )
                    continue;
                $tpl = "INSERT INTO `YBT_JKCategory` (m_PID, m_Name) VALUES ('%s', '%s')";
                $sql = sprintf( $tpl, $id1, $f2 );
                $r = $pdo->exec( $sql );
                $id2 = intval( $pdo->lastInsertId() );
            }
            closedir($h2);
        }
        closedir($h1);
        exit("Finish..\n");
    }


    $sql = "select * from YBT_JKCategory";
    $row = $pdo->query($sql)->fetchAll();
    $c1 = array();
    foreach( $row as $item ) {
        if( intval($item['m_PID'])!=0 )
            continue;
        $c1[ $item['m_ID'] ] = $item['m_Name'];
    }
    $cats = array();
    foreach( $row as $item ) {
        if( intval($item['m_PID'])==0 )
            continue;
        if( !isset($c1[$item['m_PID']]) )
            continue;
        $key = $c1[$item['m_PID']] . ',' . $item['m_Name'];
        $cats[ $key ] = $item['m_PID'] . ',' . $item['m_ID'];
    }

    $drug_cats = array(); # 药品的所属分类
    $files = GU::GetFilesFromDir('details/', true);
    foreach( $files as $file ) {
        list($void, $c1, $c2) = explode('/', $file);
        $key = $c1 . ',' . $c2;
        if( !isset($cats[$key]) ) {
            var_dump( $file, $key );
            exit;
        }
        $cat = $cats[$key];
        $id = pathinfo( $file, PATHINFO_FILENAME);
        $drug_cats[ $id ] = $cat;
    }

    $pdo->exec( 'truncate YBT_JKDrug' );

    $fn = __DIR__ . '/drugs.serialize.data';
    $handle = fopen( $fn, 'r' );

    $sql = "INSERT INTO YBT_JKDrug (m_CIDs, m_PIDs, m_Name, m_GoodsName, m_Vender, 
        m_Speci, m_ImageURL, m_Sms) VALUES ";

    $num = 0;
    while (!feof($handle)) {
        $row = fgets($handle);
        if( strlen($row) < 1 )
            continue;
        $drug = GU::unserialize( $row );

        // 获取分类
        $cids = isset($drug_cats[$drug['编码：']]) ? $drug_cats[$drug['编码：']] : '';

        // 处理图片
        $image = array();
        foreach( $drug['药品图片：'] as $val ) {
            $val = str_replace( array("&#39","#39;"), '', $val );
            $url = 'https:' . $val;
            $image[] = $url;
        } 

        // 处理说明书 把没用的标签去掉
        $sms = $drug['说明书：'];
        $p1 = '|<textarea style="display: none" id="tab21">.*</textarea>|iUs';
        $new_sms = preg_replace( $p1, '', $sms);
        $p2 = '|<div id="tb21"></div>.*</div>.*</div>|iUs';
        $new_sms = preg_replace( $p2, '', $new_sms);
        $p3 = '|<div class="contmdiv" id="b_2_2" style="display: block">.*<div class="bigfont">|iUs';
        $new_sms = preg_replace( $p3, '', $new_sms);

        $tpl = "('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'),";
        $sql.= sprintf( $tpl,
                        $cids,
                        intval($drug['编码：']),
                        addslashes($drug['通用名称：']),
                        addslashes($drug['商品名：']),
                        addslashes($drug['生产厂家：']),
                        addslashes($drug['产品规格：']),
                        addslashes( join('|', $image) ),
                        addslashes($new_sms) );
        $num++;
        if( $num%50===0 ) {
            $sql = substr( $sql, 0, -1 );
            $r = $pdo->exec( $sql );
            var_dump( $r );
            $sql = "INSERT INTO YBT_JKDrug (m_CIDs, m_PIDs, m_Name, m_GoodsName, m_Vender, 
                m_Speci, m_ImageURL, m_Sms) VALUES "; 
        }
    }
    fclose($handle);
    if( substr( $sql, -1)==',' ) {
        $sql = substr( $sql, 0, -1 );
        $r = $pdo->exec( $sql );
    }

    exit("Finish...\n");


} catch (Exception $e) {
    echo $e->getMessage() . "\n";
    exit;
}

/*

创建数据表
drop table if exists YBT_JKCategory;
create table YBT_JKCategory (
    m_ID int not null auto_increment,
    m_PID int default 0 comment '父级id',
    m_Name varchar(255) default '' comment '分类名',
    m_CreateTime timestamp not null default current_timestamp,
    m_Delete tinyint(2) default 0,  
    primary key( m_ID )
)engine=myisam default charset =utf8 comment '健客药品分类';

drop table if exists YBT_JKDrug;
create table YBT_JKDrug (
    m_ID int not null auto_increment,
    m_CIDs varchar(32) default '' comment '分类ID',
    m_PIDs varchar(255) default '' comment '产品ID,逗号隔开',
    m_Name varchar(512) default '' comment '通用名',
    m_GoodsName varchar(255) default '' comment '商品名',
    m_Vender varchar(512) default '' comment '厂商名',
    m_Speci varchar(512) default '' comment '规格',
    m_ImageURL varchar(1000) default '' comment '图片链接',
    m_Sms text comment '说明书',
    m_CreateTime timestamp not null default current_timestamp,  
    m_Delete tinyint(2) default 0,      
    primary key( m_ID )
)engine=myisam default charset=utf8 comment '健客药品';

*/


