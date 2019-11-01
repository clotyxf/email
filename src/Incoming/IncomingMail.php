<?php

namespace Email\Incoming;

use stdClass;
use Email\Incoming\Parser\HeaderParser;
use Email\Incoming\Parser\BodyParser;
use Email\Incoming\Parser\AttachmentParser;
use Email\Decoder;
use Email\ConfigManager;
use Email\Mailbox;

class IncomingMail
{
    /**
     * @var Mailbox
     */
    public $mailbox;

    /**
     * @var ConfigManager
     */
    public $config;
    
    /**
     * @var Decoder
     */
    protected $decoder;

    /**
     * @var HeaderParser
     */
    public $headerParser;

    /**
     * @var BodyParser
     */
    public $bodyParser;

    /**
     * @var AttachmentParser
     */
    public $attachmentParser;

    /**
     * 平台邮件ID
     * 
     * @var mixed
     */
    public $mailId;

    /**
     * 
     */
    public function __construct(Mailbox $mailbox, ConfigManager $config, $mailId, $platform = '')
    {
        $this->mailId  = $mailId;
        $this->mailbox = $mailbox;
        $this->config  = $config;
        $this->decoder = new Decoder();
    }

    /**
     * @param string $headers
     * 
     * @return $this
     */
    public function setMailHeaders($headers)
    {
        $this->headerParser = new HeaderParser($this->config, $this->decoder, $headers);

        return $this;
    }

    /**
     * @param stdClass $structure
     * 
     * @return $this
     */
    public function setMailBodys(stdClass $structure)
    {
        $this->bodyParser = new BodyParser($this->config, $this->decoder);

        if (empty($structure->parts)) {
            $this->initBodyRequest($structure, 0, true);
        } else {
            $flattenedParts = $this->flattenParts($structure->parts);

            foreach ($flattenedParts as $partNum => $partStructure) {
                $this->initBodyRequest($partStructure, $partNum, true);
            }
        }

        return $this;
    }

    /**
     * headers\body\attachments 格式化为数组输出
     * 
     * @return array
     */
    public function toArray()
    {
        $data = [
            'headers' => !is_null($this->headerParser) ? $this->headerParser->toArray() : [],
            'body' => !is_null($this->bodyParser) ? $this->bodyParser->toArray() : [],
            'attachments' => !is_null($this->attachmentParser) ? $this->attachmentParser->toArray() : [],
            'message' => $this->mailbox->getMessage(),
            'mail_id' => $this->mailId
        ];

        if ($data['attachments']) {
            foreach ($data['attachments'] as $key => $attachment) {
                if (empty($attachment['id'])) {
                    continue;
                }

                foreach ($data['body'] as $bodyKey => $body) {
                    preg_match('/([^"][\w:]+?' . $attachment['id'] . ')/', $body['body'], $result);

                    if ($result) {
                        $data['body'][$bodyKey]['body'] = str_replace($result[1], $attachment['file_path'], $body['body']);
                        unset($data['attachments'][$key]);
                        break;
                    }
                }
            }
        }

        return $data;
    }

    /**
     * taken from https://www.electrictoolbox.com/php-imap-message-parts/.
     * 
     * @param mixed $messageParts
     * @param array $flattenedParts
     * @param string $prefix
     * @param int $index
     * @param bool $fullPrefix
     * 
     * @return array
     */
    private function flattenParts($messageParts, $flattenedParts = [], $prefix = '', $index = 1, $fullPrefix = true)
    {
        foreach ($messageParts as $part) {
            $flattenedParts[$prefix . $index] = $part;
            if (isset($part->parts)) {
                if (2 == $part->type) {
                    $flattenedParts = $this->flattenParts($part->parts, $flattenedParts, $prefix . $index . '.', 0, false);
                } elseif ($fullPrefix) {
                    $flattenedParts = $this->flattenParts($part->parts, $flattenedParts, $prefix . $index . '.');
                } else {
                    $flattenedParts = $this->flattenParts($part->parts, $flattenedParts, $prefix);
                }
                unset($flattenedParts[$prefix . $index]->parts);
            }
            ++$index;
        }

        return $flattenedParts;
    }

    /**
     * 获取body内容
     * 
     * @param mixed $structure
     * @param int $partNum
     * @param bool $markAsSeen
     * @param bool $emlParse
     */
    private function initBodyRequest($structure, $partNum, $markAsSeen = true, $emlParse = false)
    {
        $options = $this->mailbox->getSearchOption();

        if (!$markAsSeen) {
            $options |= FT_PEEK;
        }

        $params = [];

        if (!empty($structure->parameters)) {
            foreach ($structure->parameters as $param) {
                $params[strtolower($param->attribute)] = (!isset($param->value) || empty($param->value)) ? '' : $this->decoder->decodeMimeStr($param->value);
            }
        }

        if (!empty($structure->dparameters)) {
            foreach ($structure->dparameters as $param) {
                $paramName = strtolower(preg_match('~^(.*?)\*~', $param->attribute, $matches) ? $matches[1] : $param->attribute);

                if (isset($params[$paramName])) {
                    $params[$paramName] .= $param->value;
                } else {
                    $params[$paramName] = $param->value;
                }
            }
        }

        $isAttachment = isset($params['filename']) || isset($params['name']);

        if (!$partNum && TYPETEXT === $structure->type) {
            $isAttachment = false;
        }

        if ('RFC822' == $structure->subtype && isset($structure->disposition) && 'attachment' == $structure->disposition) {
            // Although we are downloading each part separately, we are going to download the EML to a single file
            //incase someone wants to process or parse in another process
            $this->downloadAttachment($partNum, $structure, $params, $options);
        }

        if ($emlParse) {
            $isAttachment = true;
        }

        if ($isAttachment) {
            $this->downloadAttachment($partNum, $structure, $params, $options);
        } else {
            if (!empty($params['charset'])) {
                $this->bodyParser->setCharset($params['charset']);
            }
        }

        if (!empty($structure->parts)) {
            foreach ($structure->parts as $subPartNum => $subPartStructure) {
                if (TYPEMESSAGE === $structure->type && 'RFC822' == $structure->subtype && (!isset($structure->disposition) || 'attachment' !== $structure->disposition)) {
                    return $this->initBodyRequest($subPartStructure, $partNum, $markAsSeen);
                } elseif (TYPEMULTIPART === $structure->type && 'ALTERNATIVE' == $structure->subtype && (!isset($structure->disposition) || 'attachment' !== $structure->disposition)) {
                    // https://github.com/barbushin/php-imap/issues/198
                    return $this->initBodyRequest($subPartStructure, $partNum, $markAsSeen);
                } elseif ('RFC822' == $structure->subtype && isset($structure->disposition) && 'attachment' == $structure->disposition) {
                    //If it comes from am EML attachment, download each part separately as a file
                    return $this->initBodyRequest($subPartStructure, $partNum . '.' . ($subPartNum + 1), $markAsSeen, true);
                } else {
                    return $this->initBodyRequest($subPartStructure, $partNum . '.' . ($subPartNum + 1), $markAsSeen);
                }
            }
        } else {
            $body = null;
            if (TYPETEXT === $structure->type) {
                if ('plain' == strtolower($structure->subtype)) {
                    $body = $this->pullBody($partNum, $structure->encoding, $options);
                } else {
                    $body = $this->pullBody($partNum, $structure->encoding, $options);
                }
            } elseif (TYPEMESSAGE === $structure->type) {
                $body = $this->pullBody($partNum, $structure->encoding, $options);
            }

            $body && $this->bodyParser->setPart($partNum)->parser($body);
        }
    }

    /**
     * 下载附件内容
     * 
     * @param mixed $part
     * @param stdClass $structure
     * @param array $params
     * @param int $options
     */
    private function downloadAttachment($part, $structure, $params, $options)
    {
        if (is_null($this->attachmentParser)) {
            $this->attachmentParser = new AttachmentParser($this->config);
        }

        if ($this->attachmentParser->getDriverName() == 'null') {
            return;
        }

        if ('RFC822' == $structure->subtype && isset($structure->disposition) && $structure->disposition == 'attachment') {
            $fileExt = strtolower($structure->subtype) . '.eml';
        } elseif ('ALTERNATIVE' == $structure->subtype) {
            $fileExt = strtolower($structure->subtype) . '.eml';
        } elseif (empty($params['filename']) && empty($params['name'])) {
            $fileExt = strtolower($structure->subtype);
        } else {
            $fileName = !empty($params['filename']) ? $params['filename'] : $params['name'];
            $fileName = $this->decoder->decodeMimeStr($fileName, $this->config->get('encode'));
            $fileName = $this->decoder->decodeRFC2231($fileName, $this->config->get('encode'));
        }

        $id = isset($structure->ifid) && isset($structure->id) ? trim($structure->id, ' <>') : null;
        $disposition = (isset($structure->disposition) ? $structure->disposition : null);
        $ifdisposition = (isset($structure->ifdisposition) ? $structure->ifdisposition : null);
        $bytes = (isset($structure->bytes) ? intval($structure->bytes) : 0);
        $subtype = isset($structure->subtype) ? $structure->subtype : null;

        if ($id && $subtype) {
            $subtype = strtoupper($subtype);
            switch ($subtype) {
                case 'JPG':
                case 'JPEG':
                    $fileExt = 'jpg';
                    break;
                case 'PNG':
                    $fileExt = 'png';
                    break;
                case 'GIF':
                    $fileExt = 'gif';
                    break;
            }
        }

        if (isset($fileExt) && isset($fileName)) {
            $fileName = $fileName . '.' . $fileExt;
        } elseif (isset($fileExt) && !isset($fileName)) {
            $fileName = $fileExt;
        } elseif (!isset($fileExt) && isset($fileName)) {
            $fileName = $fileName;
        }

        $replace = [
            '/\s/' => '_',
            '/[^\w\.]/iu' => '',
            '/_+/' => '_',
            '/(^_)|(_$)/' => '',
        ];
        $newFileName = preg_replace('~[\\\\/]~', '', $this->mailId . '_' . $this->attachmentParser->randomFileName() . '_' . preg_replace(array_keys($replace), $replace, $fileName));

        if ($bytes > (31457280 * 2)) {
            return false;
        }

        // 134217728
        $stream = $this->pullBody($part, $structure->encoding, $options, true);

        $this->attachmentParser->setId($id)
            ->setIfdisposition($ifdisposition)
            ->setDisposition($disposition)
            ->setFileName($newFileName)
            ->setOriginFileName($fileName)
            ->setPort($part)->setEncoding($structure->encoding)->setFileSize($bytes)->parser($stream);
    }

    /**
     * 拉取邮件内容
     * 
     * @param string $part
     * @param string $encoding
     * @param mixed $options
     * 
     * @return mixed
     */
    public function pullBody($part, $encoding, $options)
    {
        if (0 === $part) {
            $data = $this->mailbox->dispatch('body', [$this->mailId, $options]);
        } else {
            $data = $this->mailbox->dispatch('fetchbody', [$this->mailId, $part, $options]);
        }

        switch ($encoding) {
            case ENC7BIT:
            case ENCOTHER:
                $data = $data;
                break;
            case ENC8BIT:
                $data = imap_utf8($data);
                break;
            case ENCBINARY:
                $data = imap_binary($data);
                break;
            case ENCBASE64:
                $data = preg_replace('~[^a-zA-Z0-9+=/]+~s', '', $data); // https://github.com/barbushin/php-imap/issues/88
                $data = imap_base64($data);
                break;
            case ENCQUOTEDPRINTABLE:
                $data = quoted_printable_decode($data);
                break;
            default:
                $data = $data;
                break;
        }

        return $data;
    }
}
