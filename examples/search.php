<?php

require_once "../vendor/autoload.php";

use Email\Mailbox;
use Email\Folders\Junk;
use Email\Folders\Deleted;
use Email\Folders\Sent;
use Email\Folders\Inbox;

$platform = 'default';
$username = '291555677@qq.com';
$password = 'xxxxxx';

$config = [
    'retry' => 2, 'close' => true, 'encode' => 'UTF-8',
    'file_driver' => 'qiNiu',
    'driver_config' => [
        'qiNiu' => [
            'accessKey' => 'xxx',
            'secretKey' => 'xxxx',
            'bucket' => 'xxxx',
            'host' => 'xxxx'
        ],
        'null' => [

        ]
    ]
];

$mailbox = Mailbox::connection($platform, $username, $password, $config);

// $response = $mailbox->status();
// dump($response, $response instanceof stdClass);
$response = $mailbox->ping();
dump($response);
// die();

// // 对邮件进行排序， 邮件ID会是乱序， 比如 616  615 590 610 612 50
// // $mailIds = $mailbox->sort(SORTARRIVAL, false);
$date  = date('j F Y', strtotime('-1 day'));
// $mailIds = $mailbox->search('SINCE "' . $date . '"', SE_FREE);
// $mailIds = $mailbox->setFolder(new Inbox())->search('ALL');
// dump($mailIds);

// die();

//  获取所有邮件， 邮件ID为有序
$mailIds = $mailbox->search();
// 
// dump(sizeof($mailIds), $mailIds);

//  获取昨天至今邮件, 邮件ID会是乱序， 比如 616  615 590 610 612 50
// $date  = date('j F Y', strtotime('-1 day'));
// $mailIds = $mailbox->search('SINCE "' . $date . '"');

// dump($mailIds);

// die();
// dump(sizeof($response), $response);

// $result = $mailbox->disconnect();

// $response = $mailbox->status();

// $response = $mailbox->check();

// 获取邮件内容
// $response = $mailbox->getMail($mailIds[0]);
// foreach ($mailIds as $key => $value) {
//     dump($value);
//     $response = $mailbox->getMail($value);
// }
$response = $mailbox->getMail($mailIds[sizeof($mailIds) - 4]);
// $response = $mailbox->getMail(1548);
$response = $response->toArray();
dump($response);

$emails = [];

// foreach ($response['headers']['to'] as $key => $value) {
//     if (!empty($value['email'])) {
//         $emails[] = $value['email'];
//     }
// }

// foreach ($response['headers']['cc'] as $key => $value) {
//     if (!empty($value['email'])) {
//         $emails[] = $value['email'];
//     }
// }

// foreach ($response['headers']['bcc'] as $key => $value) {
//     if (!empty($value['email'])) {
//         $emails[] = $value['email'];
//     }
// }

// ksort($emails);
// $emails = implode(',', $emails);

// if (!empty($response['headers']['sender']['email'])) {
//     $emails = $response['headers']['sender']['email'] . ',' . $emails;
// }

// if (!empty($response['headers']['date'])) {
//     $emails = $response['headers']['date'] . '-' . $emails;
// }

// if (!empty($response['headers']['msg_id'])) {
//     $emails = $response['headers']['msg_id'] . '-' . $emails;
// }

// $sign = hash_hmac('sha256', $emails, 'email', false);
// dump($emails, $sign);

// die();