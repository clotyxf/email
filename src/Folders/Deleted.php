<?php

/**
 * -------------------------
 * 已删除
 * -------------------------
 */

namespace Email\Folders;

use Email\Contracts\FolderFactory;

class Deleted implements FolderFactory
{
    /**
     * @return string
     */
    public function __toString()
    {
        return 'Deleted Items';
    }
}