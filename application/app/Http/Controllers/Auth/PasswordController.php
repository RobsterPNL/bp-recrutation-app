<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Model\ReminderServiceInterface;
use Cartalyst\Sentinel\Laravel\Facades\Reminder;
use Cartalyst\Sentinel\Native\Facades\Sentinel;
use Cartalyst\Sentinel\Users\UserInterface;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\PasswordBroker;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Throwable;

/**
 * @author Robert Matuszewski <robmatu@gmail.com>
 */
class PasswordController extends Controller
{
    use ResetsPasswords;

    /**
     * @var Guard
     */
    private $auth;

    /**
     * @var PasswordBroker
     */
    private $passwords;

    /**
     * @var ReminderServiceInterface
     */
    private $reminderService;

    /**
     * @param Guard $auth
     * @param PasswordBroker $passwords
     * @param ReminderServiceInterface $reminderService
     */
    public function __construct(Guard $auth, PasswordBroker $passwords, ReminderServiceInterface $reminderService)
    {
        $this->auth = $auth;
        $this->passwords = $passwords;
        $this->reminderService = $reminderService;
    }

    /**
     * @param Request $request
     *
     * @return RedirectResponse
     */
    public function postEmail(Request $request): RedirectResponse
    {
        $this->validate($request, ['email' => 'required|email']);

        $credentials = $request->only('email');

        $user = Sentinel::findByCredentials($credentials);
        if (false === $user instanceof UserInterface) {
            Session::flash('message', 'If your email is in our database, a password reset link has been sent to it.');

            return redirect()->back()->withInput();
        }

        DB::beginTransaction();
        try {
            $this->reminderService->remind($user);
            DB::commit();
            Session::flash('message', 'If your email is in our database, a password reset link has been sent to it.');

            return redirect()->back();
        } catch (Throwable $e) {
            DB::rollBack();
            Session::flash('message', 'Error, please try again.');

            return redirect()->back()->withInput();
        }
    }

    /**
     * @param Request $request
     *
     * @return RedirectResponse
     */
    public function postReset(Request $request): RedirectResponse
    {
        $this->validate($request, [
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|confirmed|min:6',
        ]);

        $credentials = $request->only('email');

        $user = Sentinel::findByCredentials($credentials);

        if (false === $user instanceof UserInterface) {
            Session::flash('message', 'Email was not found!');

            return redirect()->back()->withInput($request->email);
        }

        if (false === Reminder::complete($user, $request->token, $request->password)) {
            Session::flash('message', 'Invalid or expire reset token.');

            return redirect()->route('password.email');
        }

        Session::flash('message', 'Your password was changed successfully!');

        return redirect()->route('auth.login');
    }
}
