<?php

namespace Email\Contracts;

interface ParserFactory
{
    /**
     * 解析器
     * 
     * @param string $value
     */
    public function parser(string $value);
}