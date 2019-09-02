<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Cartalyst\Sentinel\Laravel\Facades\Reminder;
use Cartalyst\Sentinel\Native\Facades\Sentinel;
use Cartalyst\Sentinel\Reminders\EloquentReminder;
use Cartalyst\Sentinel\Users\UserInterface;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\PasswordBroker;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use LogicException;
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
     * @param Guard $auth
     * @param PasswordBroker $passwords
     */
    public function __construct(Guard $auth, PasswordBroker $passwords)
    {
        $this->auth = $auth;
        $this->passwords = $passwords;

        $this->middleware('guest');
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
            /** @var EloquentReminder $reminder */
            $reminder = Reminder::exists($user) ?: Reminder::create($user);

            if (false === $this->sentEmail($user, $reminder->code)) {
                throw new LogicException('Can\'t send email!');
            }

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

            return redirect()->away('/password/email');
        }

        Session::flash('message', 'Your password was changed successfully!');

        return redirect()->away('/auth/login');
    }

    /**
     * @param UserInterface $user
     * @param string $code
     *
     * @return bool
     */
    private function sentEmail(UserInterface $user, string $code): bool
    {
        return (bool) Mail::send('emails.password', ['code' => $code], function (Message $message) use ($user) {
            $message
                ->to($user->email)
                ->subject('Reset account password');
        });

    }
}
