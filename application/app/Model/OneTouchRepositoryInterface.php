<?php

declare(strict_types = 1);

namespace App\Model;

use App\OneTouch;

/**
 * @author Robert Matuszewski <robmatu@gmail.com>
 */
interface OneTouchRepositoryInterface
{
    /**
     * @param string $uuid
     *
     * @return OneTouch
     */
    public function findByUuid(string $uuid): OneTouch;
}
