<?php

/**
 * -------------------------
 * 收信箱
 * -------------------------
 */

namespace Email\Folders;

use Email\Contracts\FolderFactory;

class Inbox implements FolderFactory
{
    /**
     * @return string
     */
    public function __toString()
    {
        return 'INBOX';
    }
}