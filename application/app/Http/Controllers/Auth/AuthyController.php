<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\OneTouch;
use Cartalyst\Sentinel\Laravel\Facades\Sentinel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

/**
 * @author Robert Matuszewski <robmatu@gmail.com>
 */
class AuthyController extends Controller
{
    /**
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * @return JsonResponse
     */
    public function status(): JsonResponse
    {
        $oneTouch = OneTouch::where('uuid', '=', Session::get('one_touch_uuid'))->firstOrFail();
        $status = $oneTouch->status;
        if ($status == 'approved') {
            Sentinel::login(Sentinel::findById(Session::get('id')));
        }

        return response()->json(['status' => $status]);
    }

    /**
     * @param Request $request
     * @return string
     */
    public function callback(Request $request): string
    {
        $uuid = $request->input('uuid');
        $oneTouch = OneTouch::where('uuid', '=', $uuid)->first();
        if ($oneTouch != null) {
            $oneTouch->status = $request->input('status');
            $oneTouch->save();

            return "ok";
        }

        return "invalid uuid: $uuid";
    }

}
