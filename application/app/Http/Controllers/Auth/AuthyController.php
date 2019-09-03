<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Repositories\OneTouchRepository;
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
     * @var OneTouchRepository
     */
    private $oneTouchRepository;

    /**
     * @param OneTouchRepository $oneTouchRepository
     */
    public function __construct(OneTouchRepository $oneTouchRepository)
    {
        $this->oneTouchRepository = $oneTouchRepository;
    }
    /**
     * @return JsonResponse
     */
    public function status(): JsonResponse
    {
        $oneTouch = $this->oneTouchRepository->findByUuid(Session::get('one_touch_uuid'));
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
        $oneTouch = $this->oneTouchRepository->findByUuid($uuid);

        if ($oneTouch != null) {
            $oneTouch->status = $request->input('status');
            $oneTouch->save();

            return "ok";
        }

        return "invalid uuid: $uuid";
    }
}
