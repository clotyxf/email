<?php

namespace Email\Store\Filesystem;

use Email\Contracts\Filesystem\Factory;

class NullBuilder implements Factory
{
    /**
     * 创建服务连接
     * 
     * @param array $config
     */
    public function connect(array $config)
    {
        
    }

    /**
     * 文件存储
     * 
     * @param string $path
     * @param string $contents
     * 
     * @return mixed
     */
    public function put($path, $contents)
    {
        return false;
    }

    /**
     * 删除文件
     * 
     * @param array|string $paths
     * 
     * @return int
     */
    public function delete($paths)
    {

    }

    /**
     * 拼接存储路径
     * 
     * @param string $path
     * 
     * @return string
     */
    public function getPath($path)
    {
        
    }
}