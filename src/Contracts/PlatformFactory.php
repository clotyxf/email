<?php

namespace Email\Contracts;

interface PlatformFactory
{
    /**
     * @param string $criteria
     * @param int|null $options
     * @param string|null $charset
     * 
     * @return array
     */
    public function searchOption($criteria, $options = null, $charset = null);

    /**
     * 获取imap host地址
     * 
     * @return string
     */
    public function getImapHost();

    /**
     * 获取stmp host地址
     * 
     * @return array
     */
    public function getSmtpConf();

    /**
     * 返回平台名称
     * 
     * @return string
     */
    public function getName();
}