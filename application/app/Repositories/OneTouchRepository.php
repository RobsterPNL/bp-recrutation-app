<?php

declare(strict_types = 1);

namespace App\Repositories;

use App\Model\OneTouchRepositoryInterface;
use App\OneTouch;

/**
 * @author Robert Matuszewski <robmatu@gmail.com>
 */
class OneTouchRepository implements OneTouchRepositoryInterface
{
    /**
     * @param string $uuid
     *
     * @return OneTouch
     */
    public function findByUuid(string $uuid): OneTouch
    {
        return OneTouch::where('uuid', '=', $uuid)->firstOrFail();
    }

    /**
     * @param array $data
     */
    public function create(array $data): void
    {
        OneTouch::create($data);
    }
}
