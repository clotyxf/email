<?php

namespace Email;

use Exception;

class Decoder
{
    /**
     * Decodes a mime string.
     *
     * @param string $string
     *
     * @return string
     *
     * @throws Exception
     */
    public function decodeMimeStr($string, $toCharset = 'utf-8')
    {
        if (empty(trim($string))) {
            return '';
        }

        $newString = '';

        foreach (imap_mime_header_decode($string) as $element) {
            if (isset($element->text)) {
                $fromCharset = !isset($element->charset) ? 'iso-8859-1' : $element->charset;
                
                if ($fromCharset === 'default') {
                    //qq 邮箱默认字符串
                    $fromCharset = 'gb18030';
                }

                if ($element->text == 'UTF-8') {
                    continue;
                }
                
                $toCharset = isset($element->charset) && preg_match('/(UTF\-8)|(default)/i', $element->charset) ? 'UTF-8' : $toCharset;
                $newString .= $this->convertStringEncoding($element->text, $fromCharset, $toCharset);
            }
        }

        return $newString;
    }

    /**
     * 验证链接是否编码
     * 
     * @param string $string
     * 
     * @return bool
     */
    public function isUrlEncoded($string)
    {
        $hasInvalidChars = preg_match('#[^%a-zA-Z0-9\-_\.\+]#', $string);
        $hasEscapedChars = preg_match('#%[a-zA-Z0-9]{2}#', $string);

        return !$hasInvalidChars && $hasEscapedChars;
    }

    /**
     * RFC2231 编码转换
     * 
     * @param string $string
     * @param string $charset
     * 
     * @return string
     */
    public function decodeRFC2231($string, $charset = 'utf-8')
    {
        if (preg_match("/^(.*?)'.*?'(.*?)$/", $string, $matches)) {
            $encoding = $matches[1];
            $data = $matches[2];
            if ($this->isUrlEncoded($data)) {
                $string = $this->convertStringEncoding(urldecode($data), $encoding, $charset);
            }
        }

        return $string;
    }

    /**
     * 将字符串编码设置为UTF7-IMAP格式
     * 
     * @param string $str
     * 
     * @return string
     */
    public function encodeStringToUtf7Imap($str)
    {
        if (is_string($str)) {
            return mb_convert_encoding($str, 'UTF7-IMAP', mb_detect_encoding($str, 'UTF-8, ISO-8859-1, ISO-8859-15', true));
        }

        return $str;
    }

    /**
     * 编码间转换
     *
     * @param string $string       
     * @param string $fromEncoding
     * @param string $toEncoding
     *
     * @return string
     *
     * @throws Exception
     */
    public function convertStringEncoding($string, $fromEncoding, $toEncoding)
    {
        if (preg_match('/default|ascii/i', $fromEncoding) || !$string || $fromEncoding == $toEncoding) {
            return $string;
        }

        $mbLoaded = extension_loaded('mbstring');
        $supportedEncodings = [];

        if ($mbLoaded) {
            $supportedEncodings = array_map('strtolower', mb_list_encodings());
        }

        if ($mbLoaded && in_array(strtolower($fromEncoding), $supportedEncodings) && in_array(strtolower($toEncoding), $supportedEncodings)) {
            $convertedString = mb_convert_encoding($string, $toEncoding, $fromEncoding);
        } elseif ($mbLoaded && in_array(strtolower($fromEncoding), ['gb2312', 'gbk'])) {
            $convertedString = mb_convert_encoding($string, $toEncoding, $fromEncoding);
        } elseif (function_exists('iconv')) {
            $convertedString = iconv($fromEncoding, $toEncoding . '//IGNORE', $string);
        }

        if (!isset($convertedString)) {
            throw new Exception('转换编码失败.');
        }

        return $convertedString;
    }
}
