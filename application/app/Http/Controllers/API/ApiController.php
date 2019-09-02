<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Cartalyst\Sentinel\Laravel\Facades\Sentinel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @author Robert Matuszewski <robmatu@gmail.com>
 */
class ApiController extends Controller
{
    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getUserData(Request $request): JsonResponse
    {
        if ($user = Sentinel::check()) {
            return response()->json($user->toArray());
        }

        return response()->json(['error' => 'true', 'message' => 'You must be logged in.']);
    }
}
