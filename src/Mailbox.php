<?php

namespace Email;

use stdClass;
use Email\Decoder;
use Email\ConfigManager;
use Email\Incoming\IncomingMail;
use Email\Platforms\PlatformManager;
use Email\Platforms\PlatformParser;
use Email\Contracts\FolderFactory;
use Email\Contracts\PlatformFactory;
use Email\Exceptions\InvalidArgumentException;
use Email\Exceptions\BadRequestException;

class Mailbox
{
    /**
     * @var mixed
     */
    protected $imapStream;

    /**
     * imap地址
     * 
     * @var string|null
     */
    protected $imapHost;

    /**
     * 账号名称
     * 
     * @var string|null
     */
    protected $username;

    /**
     * 密码
     * 
     * @var string|null
     */
    protected $password;

    /**
     * 访问文件夹
     * 
     * @var string
     */
    protected $folder = '';

    /**
     * @var int
     */
    protected $imapSearchOption = SE_UID;

    /**
     * @var int
     */
    protected $imapOptions = 0;

    /**
     * @var array
     */
    protected $timeouts = [
        IMAP_OPENTIMEOUT => 30
    ];

    /**
     * 额外配置
     * 
     * @var ConfigManager
     */
    protected $config;

    /**
     * 字符解码器
     * 
     * @var Decoder
     */
    protected $decoder;

    /**
     * @var PlatformFactory
     */
    protected $platform;

    /**
     * @param PlatformFactory $platform
     * @param string $username
     * @param string $password
     * @param array $config
     */
    public function __construct(PlatformFactory $platform, $username, $password, $config = [])
    {
        $this->platform = $platform;
        $this->username = $username;
        $this->password = $password;
        $this->config   = new ConfigManager($config);
        $this->decoder  = new Decoder();
    }

    /**
     * 发起IMAP服务connect
     * 
     * @param string $imapHost
     * @param string $username
     * @param string $password
     * 
     * @return Mailbox
     */
    public static function connection($platform, $username, $password, $config = [])
    {
        $username = trim($username);
        $parser = PlatformParser::build();

        if (!in_array($platform, $parser->getPlatforms())) {
            $platform = 'default';
        }

        if ($platform === 'default' && empty($config['imap_config'])) {
            if (!is_null($autoPlatform = $parser->recogation($username))) {
                $platform = $autoPlatform;
            }
        }

        $platform = PlatformManager::switch($platform, $config);
        $email = new self($platform, $username, $password, $config);
        $email->imapHost = $platform->getImapHost();
        $email->getImapStream();

        return $email;
    }

    /**
     * 获取访问文件夹
     */
    public function getFolder()
    {
        return $this->folder;
    }

    /**
     * 设置访问文件夹
     * 
     * @param FolderFactory $folder
     * 
     * @return $this
     */
    public function setFolder(FolderFactory $folder)
    {
        $this->folder = $folder->__toString();

        if (!$this->disconnect()) {
            throw new BadRequestException('[imap_disconnect]断开连接异常');
        }

        return $this->initImapStream();
    }

    /**
     * 设置超时
     * 
     * @param int $timeout
     * @param array $types
     * 
     * @return $this
     */
    public function setTimeouts($timeout, $types = [])
    {
        $foundTimeoutTypes = array_intersect($types, [IMAP_OPENTIMEOUT, IMAP_READTIMEOUT, IMAP_WRITETIMEOUT, IMAP_CLOSETIMEOUT]);

        if (sizeof($types) != sizeof($foundTimeoutTypes)) {
            throw new InvalidArgumentException('types 不支持提供的参数，可选:IMAP_OPENTIMEOUT, IMAP_READTIMEOUT, IMAP_WRITETIMEOUT, IMAP_CLOSETIMEOUT');
        }

        $this->timeouts = array_fill_keys($types, $timeout);

        return $this;
    }

    /**
     * 发起imap协议请求
     * 
     * @param string $methodShortName
     * @param array $arguments
     * @param bool $fillImapStream
     * 
     * @return mixed
     */
    public function dispatch($methodShortName, $arguments = [], $fillImapStream = true)
    {
        if (!function_exists('imap_' . $methodShortName)) {
            throw new InvalidArgumentException("IMAP method imap_$methodShortName() not found.");
        }

        if (in_array($methodShortName, ['open'])) {
            if (is_string($arguments[0])) {
                if (preg_match("/^\{.*\}(.*)$/", $arguments[0], $matches)) {
                    $mailboxName = $matches[1];

                    if (!mb_detect_encoding($mailboxName, 'ASCII', true)) {
                        $arguments[0] = $this->decoder->encodeStringToUtf7Imap($mailboxName);
                    }
                }
            }
        } else {
            foreach ($arguments as &$arg) {
                if (is_string($arg)) {
                    $arg = $this->decoder->encodeStringToUtf7Imap($arg);
                }
            }
        }

        if ($fillImapStream) {
            array_unshift($arguments, $this->getImapStream());
        }

        imap_errors();
        $result = @call_user_func_array('imap_' . $methodShortName, $arguments);
        $errors = imap_errors();

        if (!$result) {
            if (is_array($errors)) {
                throw new BadRequestException("IMAP method imap_$methodShortName() failed with error: " . implode('. ', $errors));
            }
        }

        return $result;
    }

    /**
     * 初始化 imap_stream
     * 
     * @return $this
     */
    private function initImapStream()
    {
        if (!is_null($this->imapStream)) {
            return $this->imapStream;
        }

        $retry = intval($this->config->get('retry'));
        $email = clone ($this);

        $this->imapStream = retry($retry, function () use ($email) {
            return $email->open();
        }, 1);

        return $this;
    }

    /**
     * 连接imp_stream
     * 
     * @return mixed
     */
    protected function open()
    {
        foreach ($this->timeouts as $type => $timeout) {
            $this->dispatch('timeout', [$type, $timeout], false);
        }
        return $this->dispatch('open', [$this->imapHost . $this->getFolder(), $this->username, $this->password, $this->imapOptions, 0, []], false);
    }

    /**
     * 获取imap_stream流
     * 
     * @param bool $reconnect 是否重连
     * 
     * @return mixed
     */
    private function getImapStream($reconnect = true)
    {
        if ($reconnect) {
            if ($this->imapStream && (!is_resource($this->imapStream) || !$this->ping())) {
                $this->disconnect();
            }

            if (!$this->imapStream) {
                $this->initImapStream();
            }
        }

        return $this->imapStream;
    }

    /**
     * 验证登录情况
     * 
     * @return boolean
     */
    public function ping()
    {
        $imapStream = $this->getImapStream(false);

        if (is_null($imapStream) || !is_resource($imapStream)) {
            return false;
        }

        $retry = intval($this->config->get('retry'));
        $email = clone ($this);

        return retry($retry, function () use ($email, $imapStream) {
            return $email->dispatch('ping', [$imapStream], false);
        }, 0.5);
    }

    /**
     * 关闭服务connect
     * 
     * @return bool
     */
    public function disconnect()
    {
        $imapStream = $this->getImapStream(false);

        if (is_null($imapStream) || !is_resource($imapStream)) {
            return true;
        }

        $this->imapStream = null;

        return $this->dispatch('close', [$imapStream, CL_EXPUNGE], false);
    }

    /**
     * 获取邮件内容
     * 
     * @param string|int $mailId
     * @param array $only ['head', 'body']
     * 
     * @return IncomingMail
     */
    public function getMail($mailId, $only = ['head', 'body'])
    {
        $only = array_intersect(['head', 'body'], $only);

        $incomingMail  = new IncomingMail($this, $this->config, $mailId);

        if (in_array('head', $only)) {
            $incomingMail->setMailHeaders($this->getFetchHeader($mailId));
        }

        if (in_array('body', $only)) {
            $incomingMail->setMailBodys($this->dispatch('fetchstructure', [$mailId, $this->getSearchOption()]));
        }
    
        return $incomingMail;
    }

    /**
     * 获取搜索配置选项
     * 
     * @return int
     */
    public function getSearchOption()
    {
        return (SE_UID == $this->imapSearchOption) ? FT_UID : 0;
    }

    /**
     * 邮件搜索,返回邮件Id集合
     * 
     * @param string $criteria
     * 
     * @return array
     */
    public function search($criteria = 'ALL', $options = SE_UID)
    {
        $mailIds = $this->dispatch('search', $this->platform->searchOption($criteria, $options));

        if ($mailIds === false) {
            $mailIds = [];
        }

        return $mailIds;
    }

    /**
     * 对邮件进行排序,并返回邮件Id集合
     * 
     * @param mixed $criteria
     * @param bool $reverse
     * @param string $searchCriteria
     * 
     * @return array
     */
    public function sort($criteria = SORTARRIVAL, $reverse = true, $searchCriteria = 'ALL')
    {
        $mailIds = $this->dispatch('sort', [$criteria, $reverse, $this->imapSearchOption, $searchCriteria]);

        if ($mailIds === false) {
            $mailIds = [];
        }

        return $mailIds;
    }

    /**
     * 删除单个邮件
     * 
     * @param int $mailId
     * 
     * @return bool
     */
    public function deleteMail($mailId)
    {
        $result = $this->dispatch('delete', [$mailId . ':' . $mailId, $this->getSearchOption()]);
        
        if ($result && !$this->dispatch('expunge')) {
            throw new BadRequestException('删除邮件失败');
        }

        return $result;
    }

    /**
     * 获取邮件headers
     * 
     * @param string|int $mailId
     * 
     * @return IncomingMail
     */
    public function getFetchHeader($mailId)
    {
        return $this->dispatch('fetchheader', [$mailId, $this->getSearchOption()]);
    }

    /**
     * 获取邮件状态信息
     * 
     * @return stdClass
     */
    public function status()
    {
        return $this->dispatch('status', [$this->imapHost . $this->getFolder(), SA_ALL]);
    }

    /**
     * 验证服务器链接情况
     * 
     * @return stdClass
     */
    public function check()
    {
        return $this->dispatch('check', []);
    }

    /**
     * 返回账号信息
     * 
     * @return array
     */
    public function getMessage()
    {
        return [
            'username' => $this->username,
            'platform' => $this->platform->getName()
        ];
    }

    /**
     * 更新服务编码
     * 
     * @param string $encoding
     * 
     * @return $this
     */
    public function setEncoding($encoding = 'UTF-8')
    {
        $encoding = strtoupper(trim($encoding));
        $supportedEncodings = [];

        if (extension_loaded('mbstring')) {
            $supportedEncodings = mb_list_encodings();
        }

        if (!in_array($encoding, $supportedEncodings) && 'US-ASCII' != $encoding) {
            throw new InvalidArgumentException('"' . $encoding . '" is not supported by setEncoding(). Your system only supports these encodings: US-ASCII, ' . implode(', ', $supportedEncodings));
        }

        $this->config->set('encode', $encoding);

        return $this;
    }

    /**
     * 
     * @param string $methodShortName
     * @param array|string $arguments
     * 
     * @return mixed
     */
    public function __call($methodShortName, $arguments = [])
    {
        return $this->dispatch(toUnderScore($methodShortName), is_array($arguments) ? $arguments : [$arguments]);
    }
}
