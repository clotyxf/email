<?php

/**
 * -------------------------
 * 垃圾箱
 * -------------------------
 */

namespace Email\Folders;

use Email\Contracts\FolderFactory;

class Junk implements FolderFactory
{
    /**
     * @return string
     */
    public function __toString()
    {
        return 'Junk';
    }
}