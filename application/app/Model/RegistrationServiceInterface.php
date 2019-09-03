<?php

declare(strict_types = 1);

namespace App\Model;

/**
 * @author Robert Matuszewski <robmatu@gmail.com>
 */
interface RegistrationServiceInterface
{
    /**
     * @param array $userData
     */
    public function register(array $userData): void;
}
