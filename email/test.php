<?php 
/**
 * 测试发送邮件
 */
header("Content-type:text/html; charset=utf-8");
require("smtp.php");
// 163 邮箱服务器
$smtpserver = "ssl://smtp.exmail.qq.com";
// 端口号
$smtpserverport = 465;
// 我的邮箱
$smtpusermail = "ybt@yibaotongapp.com";
// 收件人
$smtpemailto = "563242292@qq.com";
// 我的账号
$smtpuser = "ybt@yibaotongapp.com";
// smtp 授权码
$smtppass = "lszhen0416Ybt";
// 主题
$mailsubject = "测试邮件发送类";
// 内容
$mailbody = "<h1>SUCCESS</h1>";
// 类型(html/txt)
$mailtype = "HTML";
// 是否进行身份验证
$auth = true;
// cc 抄送
$cc = "";
// bcc 暗抄送
$bcc = "";//"563242292@qq.com";

$smtp = new smtp($smtpserver,$smtpserverport,$auth,$smtpuser,$smtppass);
// 开启调试模式
$smtp->open_debug();
$smtp->sendmail($smtpemailto, $smtpusermail, $mailsubject, $mailbody, $mailtype, $cc, $bcc);
