<?php // CODE BY HW
// 转换HTML中的图片地址

$dir = __DIR__ . '/html/';

R( $dir );
exit("Finish...\n");


function R( $d ) {
    $h = opendir( $d );
    while ( ($f=readdir($h))!==false ) {
        if( $f=='.' || $f=='..' ) {
            continue ;
        }
        if( is_dir( $d . $f ) ) {
            R( $d . $f . '/' );
        } elseif( is_file( $d . $f ) ) {
            F( $d . $f );
        } else {

        }
    }
    closedir( $h );
}

function F( $f ) {
    //echo $f . "\n";
    $content = file_get_contents( $f );
    if( strlen( $content ) < 1 ) {
        return ;
    }
    $p = "/(<img.*src=\"?)\.\.\/\.\.\/\.\.\/\.\.(.*\"?)/iUs";
    $new_content = preg_replace( $p, "$1http://paper.cntcm.com.cn$2", $content, -1, $count );
    file_put_contents( $f, $new_content );
    var_dump( $count."\t".$f );
    return ;
}
