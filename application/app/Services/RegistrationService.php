<?php

declare(strict_types = 1);

namespace App\Services;

use App\Model\RegistrationServiceInterface;
use Cartalyst\Sentinel\Sentinel;
use Cartalyst\Sentinel\Users\UserInterface;
use Exception;
use Illuminate\Support\Facades\Session;
use LogicException;

/**
 * @author Robert Matuszewski <robmatu@gmail.com>
 */
class RegistrationService implements RegistrationServiceInterface
{
    /**
     * @var Sentinel
     */
    private $setinel;

    /**
     * @var Session
     */
    private $session;

    /**
     * @var Authy
     */
    private $authyService;

    /**
     * @param Sentinel $setinel
     * @param Session $session
     * @param Authy $authyService
     */
    public function __construct(Sentinel $setinel, Session $session, Authy $authyService)
    {
        $this->setinel = $setinel;
        $this->session = $session;
        $this->authyService = $authyService;
    }

    /**
     * @param array $userData
     * @throws Exception
     */
    public function register(array $userData): void
    {
        $user = $this->setinel->registerAndActivate($userData);

        if (false === $user instanceof UserInterface) {
            throw new LogicException();
        }

        Session::set('password_validated', true);
        Session::set('id', $user->id);

        $authyId = $this->authyService->register(
            $user->email,
            $user->phone_number,
            $user->country_code
        );
        $user->updateAuthyId($authyId);

        if ($this->authyService->verifyUserStatus($authyId)->registered) {
            Session::flash('message', 'Open Authy app in your phone to see the verification code');
        } else {
            $this->authyService->sendToken($authyId);
            Session::flash('message', 'You will receive an SMS with the verification code');
        }
    }
}
