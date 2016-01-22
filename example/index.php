<?php
require_once(dirname(__FILE__) . '/VNoticeService.class.php');

$vnoticeService = VNoticeService::getInstance();

// see in http://www.vtongzhi.com/secure/token
$ak = 'ak_value';
$sk = 'sk_value'; 
$content = 'test from sdk';
$mid = $vnoticeService->sendMessage( $ak, $sk, $content );
var_dump($mid);
sleep(1);
$messageStatus = $vnoticeService->checkMessage($ak, $sk, $mid);
var_dump($messageStatus);
