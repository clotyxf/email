<?php

namespace Email\Store\Filesystem;

use Qiniu\Auth;
use Qiniu\Storage\UploadManager;
use Email\Exceptions\InvalidArgumentException;
use Email\Exceptions\BadRequestException;
use Email\Contracts\Filesystem\Factory;

class QiNiuBuilder implements Factory
{
    /**
     * @var UploadManager
     */
    protected $upload;

    /**
     * 七牛token
     * 
     * @var string
     */
    protected $token;

    /**
     * 七牛bucket
     * 
     * @var string
     */
    protected $bucket;

    /**
     * 文件访问域名地址
     * 
     * @var string
     */
    protected $host;

    /**
     * 存储基础路径
     * 
     * @var string
     */
    protected $path = 'uploads/emails/attachments';

    /**
     * 连接七牛上传服务
     * 
     * @param array $config
     * 
     * @return $this
     */
    public function connect(array $config)
    {
        $accessKey = $this->getAccessKey($config);
        $secretKey = $this->getSecretKey($config);
        $this->bucket = $this->getBucket($config);
        $this->host = $this->getHost($config);

        $returnBody = '{"key":"$(key)","hash":"$(etag)","fsize":$(fsize),"name":"$(x:name)"}';
        $policy = array(
            'returnBody' => $returnBody
        );

        $auth = new Auth($accessKey, $secretKey);
        $this->token = $auth->uploadToken($this->bucket, null, 3600, $policy);
        $this->upload = new UploadManager();

        return $this;
    }

    /**
     * 上传文件
     * 
     * @param string $path
     * @param string $contents
     * 
     * @return string
     */
    public function put($path, $contents)
    {
        $path = $this->getPath($path);
        list($response, $err) = $this->upload->put($this->token, $path, $contents);

        if (is_null($err)) {
            return $this->host . '/' . $response['key'];
        } else {
            throw new BadRequestException("上传文件失败", 1);
        }
    }

    /**
     * 删除文件
     * 
     * @param array|string $paths
     */
    public function delete($paths)
    {
        //
    }

    /**
     * 返回文件路径
     * 
     * @param string $path
     * 
     * @return string
     */
    public function getPath($path)
    {
        $hasLimiter = false;

        if (strpos($path, '/') === 0) {
            $hasLimiter = true;
        }

        $paths = explode('.', $path);
        $ext = end($paths);

        if ($hasLimiter) {
            $path = $this->path . '/' . $ext . $path;
        } else {
            $path = $this->path . '/' . $ext . '/' . $path;
        }

        return $path;
    }

    /**
     * 获取accessKey
     * 
     * @param array $config
     * 
     * @return string
     */
    protected function getAccessKey(array $config)
    {
        return isset($config['accessKey']) ? $config['accessKey'] : '';
    }

    /**
     * 获取secretKey
     * 
     * @param array $config
     * 
     * @return string
     */
    protected function getSecretKey(array $config)
    {
        return isset($config['secretKey']) ? $config['secretKey'] : '';
    }

    /**
     * 获取 bucket
     * 
     * @param array $config
     * 
     * @return string
     */
    protected function getBucket(array $config)
    {
        return isset($config['bucket']) ? $config['bucket'] : '';
    }

    /**
     * 获取访问host
     * 
     * @param array $config
     * 
     * @return string
     */
    public function getHost(array $config)
    {
        if (empty($config['host'])) {
            throw new InvalidArgumentException('store_driver_config.qiNiu.host 不能为空.');
        }

        return trim($config['host'], '/');
    }
}
