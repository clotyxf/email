<?php

/**
 * --------------------------------
 * 自动识别平台解析器
 * --------------------------------
 */

namespace Email\Platforms;

class PlatformParser
{
    /**
     * 已接入平台
     * 
     * @var array
     */
    private $platformNames = [
        'qq.com' => 'qq',
        'outlook.com' => 'outlook',
        'office365.com' => 'outlook',
        'hotmail.com' => 'outlook',
        'live.com' => 'outlook',
        'gmail.com' => 'gmail',
        'yahoo.com' => 'yahoo',
        'yahoo.com.cn' => 'yahoo',
        '163.com' => 'wy163',
        '126.com' => 'wy126',
        '189.cn' => 'ty189',
        'sina.com' => 'sina',
        '139.com' => 'yd139',
        '21cn.com' => 'cn21',
        'sohu.com' => 'sohu',
        'chinaren.com' => 'chinaren',
        'elong.com' => 'elong',
        'etang.com' => 'etang',
        'exmail.qq.com' => 'exmailQQ',
    ];

    /**
     * 构建解析器
     */
    public static function build()
    {
        return new self();
    }

    /**
     * 自动识别邮箱账号平台
     * 
     * @param string $email
     * 
     * @return string|null
     */
    public function recogation($email)
    {
        $platform = null;

        if (!email_validator($email)) {
            return $platform;
        }

        $email = explode('@', $email);

        if (sizeof($email) != 2 || !array_key_exists($email[1], $this->getPlatforms())) {
            return $platform;
        }

        return $this->getPlatforms()[$email[1]];
    }

    /**
     * 获取已确认过imap/smtp可正常使用平台
     * 
     * @return array
     */
    public function getPlatforms()
    {
        return $this->platformNames;
    }
}