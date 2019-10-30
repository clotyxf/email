<?php

/**
 * -------------------------
 * 已发箱
 * -------------------------
 */

namespace Email\Folders;

use Email\Contracts\FolderFactory;

class Sent implements FolderFactory
{
    /**
     * @return string
     */
    public function __toString()
    {
        return 'Sent Items';
    }
}