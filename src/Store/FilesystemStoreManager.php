<?php

namespace Email\Store;

use Email\Store\Filesystem\NullBuilder;
use Email\Store\Filesystem\QiNiuBuilder;
use Email\ConfigManager;
use Email\Exceptions\InvalidArgumentException;
use Email\Contracts\Filesystem\Factory;

class FilesystemStoreManager
{
    /**
     * @var ConfigManager
     */
    public $config;

    /**
     * 已创建的drivers列表
     *
     * @var array
     */
    protected $drivers = [];

    /**
     * @param ConfigManager $config
     */
    public function __construct(ConfigManager $config)
    {
        $this->config = $config;
    }

    /**
     * 启动驱动
     * 
     * @param string|null $driver
     * 
     * @return mixed
     */
    public function driver($driver = null)
    {
        $driver = $driver ? $driver : $this->getDefaultDriver();

        if (!isset($this->drivers[$driver])) {
            $this->drivers[$driver] = $this->resolve($driver);
        }

        return $this->drivers[$driver];
    }

    /**
     * 获取默认存储驱动
     * 
     * @return string
     */
    public function getDefaultDriver()
    {
        return 'default';
    }

    /**
     * resolve
     * 
     * @param string $namestore_driver
     * 
     * @return Factory
     */
    protected function resolve($name)
    {
        $config = $this->getConfig($name);

        if (is_null($config) && $name != 'default') {
            throw new InvalidArgumentException("store_driver [{$name}] 没有定义相关配置.");
        }

        if ($name == 'default') {
            $config = [];
            $driverMethod = 'createNullDriver';
        } else {
            $driverMethod = 'create' . ucfirst($name) . 'Driver';
        }

        if (method_exists($this, $driverMethod)) {
            return $this->{$driverMethod}($config);
        } else {
            throw new InvalidArgumentException("store_driver [{$name}] 不存在.");
        }
    }

    /**
     * 空存储驱动
     * 
     * @param array $config
     * 
     * @return Factory
     */
    public function createNullDriver(array $config)
    {
        $null = new NullBuilder($config);

        return $null;
    }

    /**
     * 七牛云存储驱动
     * 
     * @param array $config
     * 
     * @return Factory
     */
    public function createQiNiuDriver(array $config)
    {
        $qiniu = new QiNiuBuilder();
        $qiniu->connect($config);

        return $qiniu;
    }

    /**
     * 获取存储驱动对应配置.
     *
     * @param string $name
     * 
     * @return array
     */
    protected function getConfig($name)
    {
        return $this->config->get("driver_config.{$name}");
    }
}
