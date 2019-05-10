<?php // CODE BY HW 


function telInfo( $tel = '', $utf8 = false ) {
    $urltpl = "https://tcc.taobao.com/cc/json/mobile_tel_segment.htm?tel=%s";
    $url = sprintf( $urltpl, $tel );
    $content = @file_get_contents( $url );
    if( !$content ) {
        return false;
    }
    $utf8 && $content = iconv('gb18030', 'utf-8', $content);
    if( strpos($content, "__GetZoneResult_ = ") !== false ) {
        $content = str_replace("__GetZoneResult_ = ", '', $content);
    }
    $data = array();
    $tmpArr = explode("\n", $content);
    foreach( $tmpArr as $item ) {
        $p = "/([a-z]+):\'(.*)\'/i";
        if( preg_match($p, trim($item), $matches) == 1 ) {
            $data[ $matches[1] ] = $matches[2];
        }
    }
    return $data;
}

var_dump( telInfo( '13718690000' ) );

