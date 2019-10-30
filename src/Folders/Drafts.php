<?php

/**
 * -------------------------
 * 草稿箱
 * -------------------------
 */

namespace Email\Folders;

use Email\Contracts\FolderFactory;

class Drafts implements FolderFactory
{
    /**
     * @return string
     */
    public function __toString()
    {
        return 'Drafts';
    }
}