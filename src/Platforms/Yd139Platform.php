<?php

namespace Email\Platforms;

use Email\Contracts\PlatformFactory;

class Yd139Platform implements PlatformFactory
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
        return '{imap.139.com:993/imap/ssl}';
    }

    /**
     * @return array
     */
    public function getSmtpConf()
    {
        return [
            'host' => 'smtp.139.com',
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
        return 'yd139';
    }
}