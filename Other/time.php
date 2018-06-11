<?php 

// 将 00:01 转成 60


$time = '00:01';

echo strtotime( $time ) - strtotime( date('Y-m-d') );


