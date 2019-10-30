<?php

namespace Email;

class ConfigManager
{
    /**
     * 配置项
     * 
     * @var array
     */
    public $config = [];

    /**
     * @param array $config
     */
    public function __construct($config)
    {
        $this->config = array_merge($this->defaultConfig(), $config);
    }

    /**
     * 获取默认配置项
     * 
     * @return array
     */
    protected function defaultConfig()
    {
        return [
            'retry' => 0, // 登录时，重试次数
            'encode' => 'UTF-8', // 设置邮件编码
            'file_driver' => 'default', // 文件存储驱动
            'imap_config' => [
                'host' => '',
                'encryption' => 'ssl',
                'port' => '993',
            ],
            'smtp_config' => [
                'host' => '',
                'encryption' => 'ssl',
                'port' => '465',
            ],
            'driver_config' => [ // 存储驱动配置
                'default' => [],
                'qiNiu' => [
                    'accessKey' => '',
                    'secretKey' => '',
                    'bucket' => '',
                    'host' => 'https://xxxx.com'
                ],
                'null' => [
        
                ]
            ]
        ];
    }

    /**
     * 获取配置
     * 
     * @param string $key
     * 
     * @return mixed
     */
    public function get($key, $default = null)
    {
        $keys = explode('.', $key);
        $value = $this->config;

        foreach ($keys as $k) {
            if (array_key_exists($k, $value)) {
                $value = $value[$k];
            } else {
                return $default;
            }
        }

        return $value;
    }

    /**
     * 设置配置
     * 
     * @param string $key
     * @param mixed $value
     * 
     * @return $this
     */
    public function set($key, $value)
    {
        $this->config[$key] = $value;

        return $this;
    }
}
