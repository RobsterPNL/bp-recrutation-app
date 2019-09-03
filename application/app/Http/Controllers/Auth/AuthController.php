<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Auth;

use App\Authy\Service;
use App\Http\Controllers\Controller;
use App\Repositories\OneTouchRepository;
use Cartalyst\Sentinel\Laravel\Facades\Sentinel;
use Cartalyst\Sentinel\Users\UserInterface;
use Exception;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\Registrar;
use Illuminate\Contracts\View\Factory;
use Illuminate\Foundation\Auth\AuthenticatesAndRegistersUsers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;
use LogicException;

/**
 * @author Robert Matuszewski <robmatu@gmail.com>
 */
class AuthController extends Controller
{
    use AuthenticatesAndRegistersUsers;

    /**
     * @var Guard
     */
    private $auth;

    /**
     * @var Registrar
     */
    private $registrar;

    /**
     * @var Service
     */
    private $authy;

    /**
     * @var OneTouchRepository
     */
    private $oneTouchRepository;

    /**
     * @param Guard $auth
     * @param Registrar $registrar
     * @param Service $authy
     * @param OneTouchRepository $oneTouchRepository
     */
    public function __construct(
        Guard $auth,
        Registrar $registrar,
        Service $authy,
        OneTouchRepository $oneTouchRepository
    ) {
        $this->auth = $auth;
        $this->registrar = $registrar;
        $this->authy = $authy;
        $this->oneTouchRepository = $oneTouchRepository;
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function postLogin(Request $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');
        $user = Sentinel::stateless($credentials);

        if (false === $user instanceof UserInterface) {
            return response()->json(['status' => 'failed', 'message' => 'The email and password combination you entered is incorrect.']);
        }

        Session::set('password_validated', true);
        Session::set('id', $user->id);

        if (false === $this->authy->verifyUserStatus($user->authy_id)->registered) {
            return response()->json(['status' => 'verify']);
        }

        $uuid = $this->authy->sendOneTouch($user->authy_id, 'Request to Login to Twilio demo app');
        $this->oneTouchRepository->create(['uuid' => $uuid]);
        Session::set('one_touch_uuid', $uuid);

        return response()->json(['status' => 'ok']);
    }

    /**
     * @return Factory|View
     */
    public function getTwoFactor()
    {
        $message = Session::get('message');

        return view('auth/two-factor', ['message' => $message]);
    }

    /**
     * @param Request $request
     * @return RedirectResponse|Redirector
     */
    public function postTwoFactor(Request $request)
    {
        if (!Session::get('password_validated') || !Session::get('id') || false === isset($_POST['token'])) {
            return redirect()->route('auth.login');
        }

        $user = Sentinel::findById(Session::get('id'));
        if (false === $this->authy->verifyToken($user->authy_id, $request->input('token'))) {
            return redirect()->route('auth.two.factor')->withErrors([
                'token' => 'The token you entered is incorrect',
            ]);
        }

        Sentinel::login($user);

        return redirect()->intended('home');
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function postRegister(Request $request): RedirectResponse
    {
        $validator = $this->registrar->validator($request->all());

        if ($validator->fails()) {
            $this->throwValidationException(
                $request, $validator
            );
        }

        DB::beginTransaction();
        try {
            $user = Sentinel::registerAndActivate($request->all());
            if (false === $user instanceof UserInterface) {
                throw new LogicException();
            }

            Session::set('password_validated', true);
            Session::set('id', $user->id);

            $authyId = $this->authy->register($user->email, $user->phone_number, $user->country_code);
            $user->updateAuthyId($authyId);

            if ($this->authy->verifyUserStatus($authyId)->registered) {
                $message = "Open Authy app in your phone to see the verification code";
            } else {
                $this->authy->sendToken($authyId);
                $message = "You will receive an SMS with the verification code";
            }

            DB::commit();

            return redirect()->route('auth.two.factor')->with('message', $message);
        } catch (Exception $exception) {
            DB::rollBack();
            Session::flash('message', 'Error, please try again.');

            return redirect()->route('auth.register');
        }
    }

    /**
     * @return RedirectResponse|Redirector
     */
    public function getLogout()
    {
        Sentinel::logout(null, true);

        return redirect()->route('home');
    }
}
