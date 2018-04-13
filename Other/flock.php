<?php
ini_set( 'date.timezone', 'Asia/Shanghai' );
// 文件锁
$st = microtime_float();
$p = isset( $_GET['p'] ) ? intval( $_GET['p'] ) : 0;

$fn = './lock/' . $p . '.lck';

$handle = fopen( $fn, 'w+' );
flock( $handle, LOCK_EX );

sleep( 5 );
flock( $handle, LOCK_UN );
fclose( $handle );

$et = microtime_float();

echo '<p>' . ($et - $st) . '</p>';
exit;

function microtime_float()
{
    list( $usec, $sec ) = explode(' ', microtime() );
    return floatval( $usec ) + floatval( $sec );
}
