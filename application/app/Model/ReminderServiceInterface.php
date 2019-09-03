<?php

declare(strict_types = 1);

namespace App\Model;

use Cartalyst\Sentinel\Users\UserInterface;

/**
 * @author Robert Matuszewski <robmatu@gmail.com>
 */
interface ReminderServiceInterface
{
    /**
     * @param UserInterface $user
     */
    public function remind(UserInterface $user): void;
}
