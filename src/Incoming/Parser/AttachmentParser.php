<?php

namespace Email\Incoming\Parser;

use Email\Contracts\ParserFactory;
use Email\Contracts\Filesystem\Factory;
use Email\Store\FilesystemStoreManager;
use Email\ConfigManager;

class AttachmentParser implements ParserFactory
{
    /**
     * @var Factory
     */
    public $store;

    /**
     * 驱动名称
     * 
     * @var string
     */
    protected $driverName = 'null';

    /**
     * 文件集合
     * 
     * @var array
     */
    protected $attachments = [];

    /**
     * 文件存储路径
     * 
     * @var string
     */
    protected $path = '';

    /**
     * 文件名称
     * 
     * @var string
     */
    protected $fileName = '';

    /**
     * 原文件文件名称
     * 
     * @var string
     */
    protected $originFileName = '';

    /**
     * @var int|null
     */
    protected $id = null;

    /**
     * @var int|null
     */
    protected $port = null;

    /**
     * @var string|null
     */
    protected $encoding = null;

    /**
     * @var int|null
     */
    protected $ifdisposition = null;

    /**
     * @var string|null
     */
    protected $disposition = null;

    /**
     * @var int
     */
    protected $fileSize = 0;
    
    /**
     * @param ConfigManager $config
     */
    public function __construct(ConfigManager $config)
    {
        $this->driverName = $config->get('file_driver', 'null');
        $this->store = (new FilesystemStoreManager($config))->driver($this->driverName);
    }

    /**
     * 文件解析器
     * 
     * @param string $value
     * 
     * @return string
     */
    public function parser(string $value)
    {
        $filePath = $this->store->put($this->fileName, $value);
        
        if ($filePath != false) {
            $this->attachments[] = [
                'id' => $this->id,
                'file_path' => $filePath,
                'file_name' => $this->fileName,
                'origin_file_name' => $this->originFileName,
                'ifdisposition' => $this->ifdisposition,
                'disposition' => $this->disposition,
                'port' => $this->port,
                'encoding' => $this->encoding,
                'file_size' => $this->fileSize
            ];
            return $filePath;
        }

        return '';
    }

    /**
     * 输出为数组
     * 
     * @return array
     */
    public function toArray()
    {
        return $this->attachments;
    }

    /**
     * 获取驱动名称
     * 
     * @return string
     */
    public function getDriverName()
    {
        return $this->driverName;
    }

    /**
     * 设置文件名称
     * 
     * @param string $path
     * @param string $fileName
     * @return $this
     */
    public function setFileName($fileName)
    {
        $this->fileName = $fileName;
        
        return $this;
    }

    /**
     * 设置原文件名称
     * 
     * @param string $path
     * @param string $fileName
     * @return $this
     */
    public function setOriginFileName($fileName)
    {
        $this->originFileName = $fileName;
        
        return $this;
    }

    /**
     * 设置文件存储路径
     * 
     * @param string $path
     * @return $this
     */
    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * 设置邮件ID
     * 
     * @param int $value
     * @return $this
     */
    public function setId($value)
    {
        $this->id = $value;
        
        return $this;
    }

    /**
     * 设置邮件附件port
     * 
     * @param int $value
     * @return $this
     */
    public function setPort($value)
    {
        $this->port = $value;
        
        return $this;
    }

    /**
     * 设置邮件附件encoding
     * 
     * @param int $value
     * @return $this
     */
    public function setEncoding($value)
    {
        $this->encoding = $value;
        
        return $this;
    }

    /**
     * 设置文件Ifdisposition
     * 
     * @param int|unll $value
     * @return $this
     */
    public function setIfdisposition($value)
    {
        $this->ifdisposition = $value;

        return $this;
    }

    /**
     * 设置文件展示位置
     * 
     * @param string|null $value
     * @return $this
     */
    public function setDisposition($value)
    {
        $this->disposition = $value;

        return $this;
    }

    /**
     * 设置文件大小
     * 
     * @param string|null $value
     * @return $this
     */
    public function setFileSize($value)
    {
        $this->fileSize = $value;

        return $this;
    }

    /**
     * 获取随机文件名
     * 
     * @param int $length
     * @return string
     */
    public function randomFileName($length = 16)
    {
        $string = '';

        while (($len = strlen($string)) < $length) {
            $size = $length - $len;

            $bytes = random_bytes($size);

            $string .= substr(str_replace(['/', '+', '='], '', base64_encode($bytes)), 0, $size);
        }

        return $string;
    }
}