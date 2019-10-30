<?php

namespace Email\Platforms;

use Email\Contracts\PlatformFactory;

class DefaultPlatform implements PlatformFactory
{
    /**
     * @var string
     */
    public $charset;

    /**
     * imap_host 地址
     * 
     * @var string|null
     */
    protected $imapHost = null;

    /**
     * smtp conf 配置
     * 
     * @var array
     */
    protected $smtpConf = [
        'host' => null,
        'encryption' => 'ssl',
        'port' => '465',
    ];

    /**
     * @param string $charset
     */
    public function __construct($charset = 'UTF-8')
    {
        $this->charset = $charset;    
    }
    
    /**
     * 搜索启用参数
     * 
     * @param string $criteria
     * @param int $options
     * @param string|null $charset
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
        return $this->imapHost;
    }

    /**
     * 设置imap_host
     * 
     * @param string $host
     * 
     * @return $this
     */
    public function setImapHost($host)
    {
        $this->imapHost = $host;

        return $this;
    }

    /**
     * 获取SMTP 配置
     * 
     * @return array
     */
    public function getSmtpConf()
    {
        return $this->smtpConf;
    }

    /**
     * 设置SMTP配置
     * 
     * @param array $conf
     */
    public function setSmtpConf($conf)
    {
        $this->smtpConf = $conf;

        return $this;
    }

    /**
     * 返回平台名称
     * 
     * @return string
     */
    public function getName()
    {
        return 'default';
    }
}