<?php

namespace Email\Contracts\Filesystem;

interface Factory
{
    /**
     * 连接配置
     *
     * @param array $config
     * @return \PDO
     */
    public function connect(array $config);

    /**
     * 文件存储
     * 
     * @param string $path
     * @param string $contents
     * @return mixed
     */
    public function put($path, $contents);

    /**
     * 删除文件
     * 
     * @param string|array $paths
     * @return bool
     */
    public function delete($paths);

    /**
     * 拼接存储路径
     * 
     * @param string $path
     * @return string
     */
    public function getPath($path);
}
