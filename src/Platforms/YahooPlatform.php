<?php

namespace Email\Platforms;

use Email\Contracts\PlatformFactory;

class YahooPlatform implements PlatformFactory
{
    /**
     * @var string
     */
    public $charset;

    public function __construct($charset = 'US-ASCII')
    {
        $this->charset = $charset;    
    }
    
    /**
     * 搜索启用参数
     * 
     * @return array
     */
    public function searchOption($criteria, $options = SE_UID, $charset = null)
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
        return '{imap.mail.yahoo.com:993/imap/ssl}';
    }

    /**
     * @return array
     */
    public function getSmtpConf()
    {
        return [
            'host' => 'smtp.mail.yahoo.com',
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
        return 'yahoo';
    }
}