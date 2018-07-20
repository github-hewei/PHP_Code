<?php // CODE BY HW

CLASS GU {

    # cURL 发送网络请求
    PUBLIC STATIC FUNCTION cURL($_met='GET',
                                $_url='',
                                $_data='',
                                $_cofn='',
                                $_header=NULL,
                                $_ajax=FALSE )
    {
        if( strlen($_url) < 1 )
            return array( 'ret' => 0, 'errmsg'=>'Error' );
        $ch = curl_init();
        if( strtoupper($_met)=='POST' ) {
            curl_setopt( $ch, CURLOPT_POST, TRUE );
            curl_setopt( $ch, CURLOPT_POSTFIELDS, $_data );
        } else {
            if( is_array($_data) && count($_data) > 0 ) {
                $param = array();
                foreach($_data as $key => $item) {
                    array_push( $param, sprintf("%s=%s", $key, $item) );
                }
                $_url = $_url . '?' . urlencode( join(',',$param) );
            } elseif( is_string($_data) && strlen($_data) > 0 ) {
                $_url = $_url . '?' . urlencode( $_data );
            }
        }
        curl_setopt( $ch, CURLOPT_URL, $_url );
        if( !is_null($_cofn) && strlen($_cofn) > 0 ) {
            curl_setopt( $ch, CURLOPT_COOKIEJAR, $_cofn );
            curl_setopt( $ch, CURLOPT_COOKIEFILE, $_cofn );
        }
        $header = array();
        $header[] = 'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:60.0) Gecko/20100101 Firefox/60.0';
        if( $_ajax==TRUE ) {
            $header[] = 'X-Requested-With: XMLHttpRequest';
        }
        if( is_array($_header) ) {
            $header = array_merge( $header, $_header );
        } elseif( is_string($_header) && strlen($_header) > 0 ) {
            array_push( $header, $_header );
        }
        curl_setopt( $ch, CURLOPT_HTTPHEADER, $header );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, TRUE );
        curl_setopt( $ch, CURLOPT_TIMEOUT, 60 );
        $data = curl_exec( $ch );
        $code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
        if( curl_errno($ch)!==CURLE_OK ) {
            return array( 'ret'=>0, 'errmsg'=>curl_error($ch) );
        }
        curl_close( $ch );
        return array( 'ret'=>1, 'code'=>$code, 'data'=>$data );
    }

    // 返回JSON字符串
    PUBLIC STATIC FUNCTION JReturn( $var=NULL )
    {
        if( is_null( $var ) ) {
            echo json_encode( array( 'ret' => 1 ) );
        } else {
            echo json_encode( $var );
        }
        exit;
    }

    // 返回JSON错误
    PUBLIC STATIC FUNCTION JError( $errmsg="Error" )
    {
        $ret['ret'] = 0;
        $ret['errmsg'] = $errmsg;
        echo json_encode( $ret );
        exit;
    }

    // 接收参数
    PUBLIC STATIC FUNCTION GetParam( $key, $def='', $type='all' )
    {
        if( $type=='all' || $type=='post' ) {
            if( isset( $_POST[$key] ) ) {
                return $_POST[$key];
            }
        }
        if( $type=='all' || $type=='get' ) {
            if( isset( $_GET[$key] ) ) {
                return $_GET[$key];
            }
        }
        return $def;
    }

    // 创建文件夹
    PUBLIC STATIC FUNCTION CreatePath( $path )
    {
        if( !is_dir( $path ) ) {
            GU::CreatePath( dirname( $path ) );
            return mkdir( $path, 0777 );
        }
        return TRUE;
    }

    // 遍历文件夹返回所有文件名
    // $r 是否递归
    PUBLIC STATIC FUNCTION GetFilesFromDir( $dir='', $r=FALSE )
    {
        if( substr($dir, -1, 1) != '/' ) {
            $dir .= '/';
        }
        if( is_dir( $dir ) ) {
            $files = array();
            $handle = opendir( $dir );
            while ( ($f=readdir($handle))!==FALSE ) {
                if( $f=='.' || $f=='..' )
                    continue;
                $f = $dir . $f;
                if( is_file( $f ) ) {
                    $files[] = $f;
                } else {
                    if( $r==TRUE ) {
                        $temp = GU::GetFilesFromDir($f, TRUE);
                        $files = array_merge( $files, $temp );
                    }
                }
            }
            closedir($handle);
            return $files;
        }
        return FALSE;
    }

    // 由于编码问题 serialize 函数计算字符长度会有错误
    // 此函数重新计算字符长度并解码
    PUBLIC STATIC FUNCTION unserialize( $str )
    {
        # s:12:"壳脂胶囊";
        $str = preg_replace_callback( '|s:(\d+):"(.*?)";|s', function($match) {
            return sprintf('s:%d:"%s";', strlen($match[2]), $match[2]);
        }, $str );
        return unserialize( $str );
    }
}