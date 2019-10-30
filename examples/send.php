<?php

require_once "../vendor/autoload.php";

use Email\Mailer;

$platform = 'qq';
$username = '291555677@qq.com';
$password = 'xxxxxx';

$mailer = Mailer::connection($platform, $username, $password);
// $result = $mailer->ping();
$message = $mailer->createMessager();
$message->to(['291555677@qq.com' => 'cloty']);
$message->from($username, 'cloty');
$message->priority(1);
$message->subject('title test send 2019-10-17 - ' . rand(10000, 1000000));

$body = '123';
$message->body($body, 'text/html');
$message = $message->getSwiftMessage();
$msg_id = $message->getId();
$result = $mailer->send($message);

dump($result, $msg_id);