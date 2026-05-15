<?php

namespace Email\Incoming\Parser;

use stdClass;
use Email\Decoder;
use Email\ConfigManager;
use Email\Contracts\ParserFactory;

class HeaderParser implements ParserFactory
{
    /**
     * @var Decoder
     */
    protected $decoder;

    /**
     * 邮件日期
     * 
     * @var string|null
     */
    protected $date;

    /**
     * 邮件标识
     * 
     * @var string|null
     */
    protected $msgId;

    /**
     * 邮件接收人
     * 
     * @var array
     */
    protected $to = [];

    /**
     * 邮件抄送人
     * 
     * @var array
     */
    protected $cc = [];

    /**
     * 邮件密送人
     * 
     * @var array
     */
    protected $bcc = [];

    /**
     * 原发送人邮件
     * 
     * @var string|null
     */
    protected $from;

    /**
     * 原发送人名称
     * 
     * @var string|null
     */
    protected $fromName;

    /**
     * 发送人邮件
     * 
     * @var string|null
     */
    protected $sender;

    /**
     * 发送人名称
     * 
     * @var string|null
     */
    protected $senderName;

    /**
     * 邮件标题
     * 
     * @var string|null
     */
    protected $subject;

    /**
     * 回复人
     * 
     * @var array
     */
    protected $replyTo = [];

    /**
     * @param ConfigManager $config
     * @param Decoder $decoder
     * @param mixed $headers
     */
    public function __construct(ConfigManager $config, Decoder $decoder, $headers)
    {
        $this->decoder   = $decoder;
        $headClass       = $this->parser($headers);
        $this->headClass = $headClass;
        
        // $priority = preg_match("/Priority\:(.*)/i", $headers, $matches) ? trim($matches[1]) : '';
        // $importance = preg_match("/Importance\:(.*)/i", $headers, $matches) ? trim($matches[1]) : '';
        // $sensitivity = (preg_match("/Sensitivity\:(.*)/i", $headers, $matches)) ? trim($matches[1]) : '';
        // $autoSubmitted = (preg_match("/Auto-Submitted\:(.*)/i", $headers, $matches)) ? trim($matches[1]) : '';
        // $precedence = (preg_match("/Precedence\:(.*)/i", $headers, $matches)) ? trim($matches[1]) : '';
        // $failedRecipients = (preg_match("/Failed-Recipients\:(.*)/i", $headers, $matches)) ? trim($matches[1]) : '';

        $this->setHeaderSendDate($headClass)
            ->setMsgId($headClass)
            ->setFrom($headClass, $headers, $config)
            ->setTo($headClass, $config)
            ->setCc($headClass, $config)
            ->setBcc($headClass, $config)
            ->setSender($headClass)
            ->setReplyTo($headClass)
            ->setSubject($headClass, $config);
    }

    /**
     * headers 转换成数组输出
     * 
     * @return array
     */
    public function toArray()
    {
        return [
            'date' => $this->date,
            'msg_id' => $this->msgId,
            'subject' => $this->subject,
            'from' => [
                'email' => $this->from,
                'name' => $this->fromName,
            ],
            'to' => $this->to,
            'cc' => $this->cc,
            'bcc' => $this->bcc,
            'sender' => [
                'email' => $this->sender,
                'name' => $this->senderName
            ],
            'reply_to' => $this->replyTo,
        ];
    }

    /**
     * header解析器
     * 
     * @param string $value
     * 
     * @return stdClass
     */
    public function parser(string $value)
    {
        return imap_rfc822_parse_headers($value);
    }

    /**
     * 设置邮件发送日期
     * 
     * @param stdClass $headClass
     * 
     * @return $this
     */
    private function setHeaderSendDate(stdClass $headClass)
    {
        $dateString = null;
        
        if (property_exists($headClass, 'date')) {
            $dateString = $headClass->date;
        } elseif (property_exists($headClass, 'Date')) {
            $dateString = $headClass->Date;
        }
        
        if ($dateString) {
            $this->date = $this->parseDateWithTimezone($dateString);
        }

        return $this;
    }

    /**
     * 解析包含时区的日期字符串
     * 支持 RFC 822 格式，如 "Wed, 02 Jul 2025 08:23:19 +0800 (GMT+08:00)"
     * 对于带正时区偏移的时间，进行时区补偿（增加对应小时数）
     * 
     * @param string $dateString 日期字符串
     * @return string|null 格式化后的时间字符串 Y-m-d H:i:s
     */
    private function parseDateWithTimezone(string $dateString)
    {
        try {
            // 使用 DateTime 解析日期（原生支持 RFC 822 格式的时区偏移）
            $dateTime = new \DateTime($dateString);
            
            // 获取时区偏移（秒数）
            $offset = $dateTime->getOffset();
            
            // 如果时区偏移为正（正时区，如 +0800），进行时间补偿
            if ($offset > 0) {
                $hours = (int)($offset / 3600);
                $dateTime->add(new \DateInterval('PT' . $hours . 'H'));
            }
            
            return $dateTime->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            // 降级处理：使用 strtotime 兼容其他格式
            $timestamp = strtotime($dateString);
            return ($timestamp !== false) ? date('Y-m-d H:i:s', $timestamp) : null;
        }
    }

    /**
     * 设置邮件系统标识
     * 
     * @param stdClass $headClass
     * 
     * @return $this
     */
    private function setMsgId(stdClass $headClass)
    {
        if (property_exists($headClass, 'message_id')) {
            $this->msgId = $headClass->message_id;
        }

        return $this;
    }

    /**
     * 设置邮件标题
     * 
     * @param stdClass $headClass
     * @param ConfigManager $config
     * 
     * @return $this
     */
    private function setSubject(stdClass $headClass, ConfigManager $config)
    {
        if (property_exists($headClass, 'subject')) {
            $this->subject = $this->decoder->decodeMimeStr($headClass->subject, $config->get('encode'));
        }

        return $this;
    }

    /**
     * 发送至谁
     * 
     * @param stdClass $headClass
     * @param ConfigManager $config
     * 
     * @return $this
     */
    private function setTo(stdClass $headClass, ConfigManager $config)
    {
        $to = [];
        
        if (property_exists($headClass, 'to') && is_array($headClass->to)) {
            foreach ($headClass->to as $email) {
                if (!empty($email->mailbox) && !empty($email->host)) {
                    $toEmail = $email->mailbox . '@' . $email->host;
                    $name    = (isset($email->personal) and !empty(trim($email->personal))) ? $this->decoder->decodeMimeStr($email->personal, $config->get('encode')) : null;
                    $to[]    = [
                        'email' => $toEmail,
                        'name' => $name
                    ];
                }
            }
        }

        $this->to = $to;

        return $this;
    }

    /**
     * 抄送至谁
     * 
     * @param stdClass $headClass
     * @param ConfigManager $config
     * 
     * @return $this
     */
    private function setCc(stdClass $headClass, ConfigManager $config)
    {
        $cc = [];
        
        if (property_exists($headClass, 'cc') && is_array($headClass->cc)) {
            foreach ($headClass->cc as $email) {
                if (!empty($email->mailbox) && !empty($email->host)) {
                    $toEmail = $email->mailbox . '@' . $email->host;
                    $name    = (isset($email->personal) and !empty(trim($email->personal))) ? $this->decoder->decodeMimeStr($email->personal, $config->get('encode')) : null;
                    $cc[]    = [
                        'email' => $toEmail,
                        'name' => $name
                    ];
                }
            }
        }

        $this->cc = $cc;

        return $this;
    }

    /**
     * 密送至谁（发送给那些不需要知道其他收件人的人或者用于大量的邮件发送）
     * 
     * @param stdClass $headClass
     * @param ConfigManager $config
     * 
     * @return $this
     */
    private function setBcc(stdClass $headClass, ConfigManager $config)
    {
        $bcc = [];
        
        if (property_exists($headClass, 'bcc') && is_array($headClass->bcc)) {
            foreach ($headClass->bcc as $email) {
                if (!empty($email->mailbox) && !empty($email->host)) {
                    $toEmail = $email->mailbox . '@' . $email->host;
                    $name    = (isset($email->personal) and !empty(trim($email->personal))) ? $this->decoder->decodeMimeStr($email->personal, $config->get('encode')) : null;
                    $bcc[]   = [
                        'email' => $toEmail,
                        'name' => $name
                    ];
                }
            }
        }

        $this->bcc = $bcc;

        return $this;
    }

    /**
     * @param stdClass $headClass
     * @param mixed $headers
     * @param ConfigManager $config
     * 
     * @return $this
     */
    private function setFrom(stdClass $headClass, $headers, ConfigManager $config)
    {
        $from = null;
        
        if (property_exists($headClass, 'from') && is_array($headClass->from)) {
            foreach ($headClass->from as $email) {
                if (!empty($email->mailbox) && !empty($email->host)) {
                    $from = $email->mailbox . '@' . $email->host;
                }

                if (isset($email->personal)) {
                    $this->fromName = (isset($email->personal) and !empty(trim($email->personal))) ? $this->decoder->decodeMimeStr($email->personal, $config->get('encode')) : null;;
                }
            }
        } elseif (preg_match('/smtp.mailfrom=[-0-9a-zA-Z.+_]+@[-0-9a-zA-Z.+_]+.[a-zA-Z]{2,4}/', $headers, $matches)) {
            $from = substr($matches[0], 14);
        }

        $this->from = $from;

        return $this;
    }

    /**
     * @param stdClass $headClass
     * 
     * @return $this
     */
    private function setSender(stdClass $headClass)
    {
        $sender = null;
        
        if (property_exists($headClass, 'sender') && is_array($headClass->sender)) {
            foreach ($headClass->sender as $email) {
                if (!empty($email->mailbox) && !empty($email->host)) {
                    $sender = $email->mailbox . '@' . $email->host;
                }

                if (isset($email->personal)) {
                    $this->senderName = (isset($email->personal) and !empty(trim($email->personal))) ? $this->decoder->decodeMimeStr($email->personal) : null;
                }
            }
        }

        $this->sender = $sender;

        return $this;
    }

    /**
     * @param stdClass $headClass
     * 
     * @return $this
     */
    private function setReplyTo(stdClass $headClass)
    {
        $replyTo = [];
        
        if (property_exists($headClass, 'reply_to') && is_array($headClass->reply_to)) {
            foreach ($headClass->reply_to as $reply) {
                if (!empty($reply->mailbox) && !empty($reply->host)) {
                    $email     = $reply->mailbox . '@' . $reply->host;
                    $name      = (isset($reply->personal) and !empty(trim($reply->personal))) ? $this->decoder->decodeMimeStr($reply->personal) : null;
                    $replyTo[] = [
                        'email' => $email,
                        'name' => $name
                    ];
                }
            }
        }

        $this->replyTo = $replyTo;

        return $this;
    }
}