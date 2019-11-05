<?php

namespace Email;

use Swift_Mailer;
use Swift_SmtpTransport as SmtpTransport;
use Email\Platforms\PlatformManager;
use Email\Platforms\PlatformParser;
use Email\Contracts\PlatformFactory;
use Email\Exceptions\BadRequestException;
use Email\Message;

class Mailer
{
    /**
     * @var PlatformFactory
     */
    protected $platform;

    /**
     * @var Swift_Mailer
     */
    protected $swift;

    /**
     * 账号名称
     * 
     * @var string|null
     */
    protected $username;

    /**
     * 密码
     * 
     * @var string|null
     */
    protected $password;

    /**
     * Array of failed recipients.
     *
     * @var array
     */
    protected $failedRecipients = [];

    /**
     * @param PlatformFactory $platform
     * @param string $username
     * @param string $password
     * @param array $config
     */
    public function __construct(PlatformFactory $platform, $username, $password, $config = [])
    {
        $this->platform = $platform;
        $this->username = $username;
        $this->password = $password;
        $this->config   = new ConfigManager($config);
    }

    /**
     * 连接邮件发送服务
     * 
     * @param string $paltform
     * @param string $username
     * @param string $password
     * @param array $config
     * 
     * @return $this
     */
    public static function connection($platform, $username, $password, $config = [])
    {
        $username = trim($username);
        $parser = PlatformParser::build();

        if (!in_array($platform, $parser->getPlatforms())) {
            $platform = 'default';
        }

        if ($platform === 'default' && empty($config['smtp_config'])) {
            if (!is_null($autoPlatform = $parser->recogation($username))) {
                $platform = $autoPlatform;
            }
        }

        $platform = PlatformManager::switch($platform, $config);
        $emailer = new self($platform, $username, $password, $config);
        $transport = $emailer->createSmtpDriver();
        $emailer->swift = new Swift_Mailer($transport);
        return $emailer;
    }

    /**
     *
     * @return \Swift_SmtpTransport
     */
    protected function createSmtpDriver()
    {
        $config = $this->platform->getSmtpConf();
  
        $transport = new SmtpTransport($config['host'], $config['port']);

        if (isset($config['encryption'])) {
            $transport->setEncryption($config['encryption']);
        }
        
        $transport->setUsername($this->username);
        $transport->setPassword($this->password);
     
        return $transport;
    }
    
    /**
     * 创建 Message构建器
     * 
     * @return Message
     */
    public function createMessager()
    {
        return new Message($this->swift->createMessage('message'));
    }

    /**
     * The Transport used to send messages.
     *
     * @return Swift_Transport
     */
    public function getTransport()
    {
        return $this->swift->getTransport();
    }

    /**
     * 验证登录情况
     * 
     * @return bool
     */
    public function ping()
    {
        try {
            return $this->getTransport()->ping();
        } catch (\Exception $ex) {
            throw new BadRequestException($ex->getMessage(), $ex->getCode());
        } finally {
            $this->forceReconnection();
        }
    }

    /**
     * 发送消息
     * 
     * @param \Swift_Message $message
     * 
     * @return mixed
     */
    public function send(\Swift_Message $message)
    {
        try {
            return $this->swift->send($message, $this->failedRecipients);
        } finally {
            $this->forceReconnection();
        }
    }

    /**
     *
     * @return void
     */
    protected function forceReconnection()
    {
        $this->swift->getTransport()->stop();
    }
}