<?php

namespace Email\Incoming\Parser;

use Email\ConfigManager;
use Email\Decoder;

class BodyParser
{
    /**
     * @var ConfigManager
     */
    protected $config;

    /**
     * @var Decoder
     */
    protected $decoder;

    /**
     * body 格式编码
     * 
     * @var string|null
     */
    protected $charset;

    /**
     * @var array
     */
    protected $partBody = [];

    /**
     * @var mixed
     */
    protected $body;

    /**
     * @var int
     */
    protected $part;

    /**
     * @param ConfigManager $config
     */
    public function __construct(ConfigManager $config, Decoder $decoder)
    {
        $this->config  = $config;
        $this->decoder = $decoder;
    }

    /**
     * 内容解析器
     * 
     * @param mixed $body
     */
    public function parser($data)
    {
        if (isset($this->charset) && !empty($this->charset)) {
            $data = $this->decoder->convertStringEncoding($data, $this->charset, $this->config->get('encode'));
        } else {
            $data = $this->decoder->convertStringEncoding($data, 'utf-8', $this->config->get('encode'));
        }

        $this->partBody[] = [
            'part' => $this->part,
            'body' => $data
        ];

        $this->body = $data;
        $this->setCharset(null);
    }

    /**
     * 设置body原字符串格式
     * 
     * @param string $charset
     * 
     * @return $this
     */
    public function setCharset($charset)
    {
        $this->charset = $charset;

        return $this;
    }

    /**
     * @param int $part
     * 
     * @return $this
     */
    public function setPart($part)
    {
        $this->part = $part;

        return $this;
    }

    /**
     * headers 转换成数组输出
     * 
     * @return array
     */
    public function toArray()
    {
        return $this->partBody;
    }
}