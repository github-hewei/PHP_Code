<?php // CODE BY HW

class G
{
    /**
     * cURL
     * @param $method string GET|POST
     * @param $url string 请求地址
     * @param $data string|array 请求数据
     * @param $header array 请求头
     * @param $cookieFn string cookie 文件
     * @param $isAjax bool 是否模拟AJAX
     * @return $ret array ret 1 成功 0 失败，err 错误信息 data 返回数据
     */
    public static function cURL( $method='GET', 
                                 $url='', 
                                 $data='', 
                                 $header=array(), 
                                 $cookieFn='',
                                 $isAjax=false )
    {
        if( strlen( $url )==0 ) 
            return false;
        $ch = curl_init();
        if( strlen( $cookieFn ) > 0 ) {
            curl_setopt( $ch, CURLOPT_COOKIEJAR, $cookieFn );
            curl_setopt( $ch, CURLOPT_COOKIEFILE, $cookieFn );
        }
        $tmpHeader = array( 'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:60.0) Gecko/20100101 Firefox/60.0' );
        if( $isAjax ) 
            array_push( $tmpHeader, 'X-Requested-With: XMLHttpRequest' );
        $header = array_merge( $tmpHeader, $header );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, $header );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        if( strtoupper( $method )=='POST' ) {
            curl_setopt( $ch, CURLOPT_POST, true );
            curl_setopt( $ch, CURLOPT_POSTFIELDS, $data );
        }
        if( strtoupper( $method )=='GET' && !empty( $data ) ) {
            if( is_array( $data ) ) {
                $p = array();
                foreach( $data as $k => $v ) {
                    $p[] = sprintf( '%s=%s', $k, $v );
                }
                $url .= '?' . join( '&', $p );
            } else {
                $url .= '?' . $data;
            }
        }
        curl_setopt( $ch, CURLOPT_URL, $url );
        $ret = array( 'ret' => 0 );
        $data = curl_exec( $ch );
        if( curl_errno( $ch ) !== CURLE_OK ) {
            $ret['err'] = curl_error( $ch );
            return $ret;
        }
        $ret['ret'] = 1;
        $ret['data'] = $data;
        $ret['http_code'] = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
        curl_close( $ch );
        return $ret;
    }

    //-----------------------------------------------------------------------------------//







}
