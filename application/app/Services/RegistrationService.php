<?php

declare(strict_types = 1);

namespace App\Services;

use App\Model\RegistrationServiceInterface;
use Cartalyst\Sentinel\Sentinel;
use Cartalyst\Sentinel\Users\UserInterface;
use Exception;
use LogicException;
use Illuminate\Session\Store;

/**
 * @author Robert Matuszewski <robmatu@gmail.com>
 */
class RegistrationService implements RegistrationServiceInterface
{
    /**
     * @var Sentinel
     */
    private $sentinel;

    /**
     * @var Store
     */
    private $sessionStore;

    /**
     * @var Authy
     */
    private $authyService;

    /**
     * @param Sentinel $sentinel
     * @param Store $sessionStore
     * @param Authy $authyService
     */
    public function __construct(Sentinel $sentinel, Store $sessionStore, Authy $authyService)
    {
        $this->sentinel = $sentinel;
        $this->sessionStore = $sessionStore;
        $this->authyService = $authyService;
    }

    /**
     * @param array $userData
     * @throws Exception
     */
    public function register(array $userData): void
    {
        $user = $this->sentinel->registerAndActivate($userData);

        if (false === $user instanceof UserInterface) {
            throw new LogicException();
        }

        $this->sessionStore->set('password_validated', true);
        $this->sessionStore->set('id', $user->id);

        $authyId = $this->authyService->register(
            $user->email,
            $user->phone_number,
            $user->country_code
        );
        $user->updateAuthyId($authyId);

        $authyData = $this->authyService->verifyUserStatus($authyId);
        if ($authyData->registered) {
            $this->sessionStore->flash('message', 'Open Authy app in your phone to see the verification code');
        } else {
            $this->authyService->sendToken($authyId);
            $this->sessionStore->flash('message', 'You will receive an SMS with the verification code');
        }
    }
}
