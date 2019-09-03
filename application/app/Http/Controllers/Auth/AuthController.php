<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Auth;

use App\Authy\Service;
use App\Http\Controllers\Controller;
use App\Model\RegistrationServiceInterface;
use App\Repositories\OneTouchRepository;
use App\Services\RegistrationService;
use Cartalyst\Sentinel\Laravel\Facades\Sentinel;
use Cartalyst\Sentinel\Users\UserInterface;
use Exception;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\View\Factory;
use Illuminate\Foundation\Auth\AuthenticatesAndRegistersUsers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;

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
     * @var Service
     */
    private $authy;

    /**
     * @var OneTouchRepository
     */
    private $oneTouchRepository;

    /**
     * @var RegistrationServiceInterface
     */
    private $registrationService;

    /**
     * @param Guard $auth
     * @param Service $authy
     * @param OneTouchRepository $oneTouchRepository
     * @param RegistrationService $registrationService
     */
    public function __construct(
        Guard $auth,
        Service $authy,
        OneTouchRepository $oneTouchRepository,
        RegistrationService $registrationService
    ) {
        $this->auth = $auth;
        $this->authy = $authy;
        $this->oneTouchRepository = $oneTouchRepository;
        $this->registrationService = $registrationService;
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
        $this->validate($request, [
            'first_name' => 'required|max:50',
            'last_name' => 'required|max:50',
            'email' => 'required|email|max:255|unique:users',
            'country_code' => 'required',
            'phone_number' => 'required|min:7|unique:users',
            'password' => 'required|confirmed|min:6',
        ]);

        DB::beginTransaction();
        try {
            $this->registrationService->register($request->all());
            DB::commit();

            return redirect()->route('auth.two.factor');
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
