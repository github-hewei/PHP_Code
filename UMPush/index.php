<?php 
/**
 * 测试推送类
 */
require_once(dirname(__FILE__).'/Push.php');

$upush = new Umeng_Push('iOS');


$result = $upush->push()
				->setParam('type','unicast')
				->setParam('device_tokens','f5ec53e4f3a17d33cad4ff9337a9813625d488ab94e4e66e6079eb40bd29e035')
				->setParam('description','后台API推送')
				->setParam('payload.aps.alert','后台测试推送')
				->send();

var_dump($result);






# AvYe4IPjYXRUr6gbu9BVPSZQSy_ThnFixrxGVgoftTUI