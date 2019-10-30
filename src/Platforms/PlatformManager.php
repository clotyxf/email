<?php

/**
 * ---------------------------------------------------
 * 已接入平台统一管理
 * ---------------------------------------------------
 */

namespace Email\Platforms;

use Email\Contracts\PlatformFactory;
use Email\Exceptions\InvalidArgumentException;

class PlatformManager
{
    /**
     * 平台平常
     * 
     * @var string
     */
    protected $name;

    /**
     * 平台切换
     * 
     * @param string $name 平台名称
     * @param array $config 其它通用配置
     * 
     * @return PlatformFactory
     */
    public static function switch($name, $config = [])
    {
        $platform = new self();
        $platform->name = $name;

        return $platform->resolve($name, $config);
    }

    /**
     * 实列化平台
     * 
     * @param string $name
     * @param array $config
     * 
     * @return PlatformFactory
     */
    public function resolve($name, $config = [])
    {
        $className = '\Email\Platforms\\' . ucfirst($name) . 'Platform';
        
        try {
            $platform = new $className();
        } catch (\Throwable $ex) {
            throw new InvalidArgumentException("[{$name}] 平台暂未接入");
        }

        if ($name == 'default') {
            if (
                empty($config['imap_config'])
                || empty($config['imap_config']['host'])
                || empty($config['imap_config']['encryption'])
                || empty($config['imap_config']['port'])
            ) {
                throw new InvalidArgumentException("imap配置错误");
            }

            if (
                empty($config['smtp_config'])
                || empty($config['smtp_config']['host'])
                || empty($config['smtp_config']['encryption'])
                || empty($config['smtp_config']['port'])
            ) {
                throw new InvalidArgumentException("smtp配置错误");
            }

            $imapHost = '{' . $config['imap_config']['host'] . ':' . $config['imap_config']['port'] . '/imap/' . $config['imap_config']['encryption'] . '}';
            $platform = $platform->setImapHost($imapHost)->setSmtpConf($config['smtp_config']);
        }

        return $platform;
    }

    /**
     * 返回平台名称
     * 
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
}