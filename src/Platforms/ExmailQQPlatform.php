<?php

namespace Email\Platforms;

use Email\Contracts\PlatformFactory;

class ExmailQQPlatform implements PlatformFactory
{
    /**
     * @var string
     */
    public $charset;

    public function __construct($charset = 'UTF-8')
    {
        $this->charset = $charset;    
    }
    
    /**
     * 搜索启用参数
     * @param string $criteria
     * @param int|null $options
     * @param string|null $charset
     * @return array
     */
    public function searchOption($criteria, $options = null, $charset = null)
    {
        return [$criteria, $options, is_null($charset) ? $this->charset : $charset];
    }

    /**
     * 获取imap host地址
     * 
     * @return string
     */
    public function getImapHost()
    {
        return '{imap.exmail.qq.com:993/imap/ssl}';
    }

    /**
     * @return array
     */
    public function getSmtpConf()
    {
        return [
            'host' => 'smtp.exmail.qq.com',
            'encryption' => 'ssl',
            'port' => '465',
        ];
    }

    /**
     * 返回平台名称
     * 
     * @return string
     */
    public function getName()
    {
        return 'exmailQQ';
    }
}