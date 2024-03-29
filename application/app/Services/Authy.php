<?php

namespace App\Services;


use App\Authy\Service;
use Authy\AuthyApi;
use Illuminate\Support\Facades\Log;

class Authy implements Service
{

    /**
     * @var AuthyApi
     */
    private $api;

    public function __construct()
    {
        $this->api = new AuthyApi(config('app.authy_api_key'));
    }

    /**
     * @param $message
     * @param $errors
     * @param bool $throw
     * @throws \Exception
     */
    private static function failed($message, $errors, $throw = true)
    {
        foreach ($errors as $field => $message) {
            Log::error("Authy Error: {$field} = {$message}\n");
        }
        if ($throw) {
            throw new \Exception($message);
        }
        Log::error($message);
    }

    /**
     * @param $email
     * @param $phone_number
     * @param $country_code
     * @return int
     * @throws \Exception
     */
    function register($email, $phone_number, $country_code)
    {
        $user = $this->api->registerUser($email, $phone_number, $country_code);

        if ($user->ok()) {
            return $user->id();
        }

        self::failed("Could not register user in Authy", $user->errors());
    }

    /**
     * @param $authyId
     * @param $message
     * @return string
     * @throws \Exception
     */
    public function sendOneTouch($authyId, $message)
    {
        $response = $this->api->createApprovalRequest($authyId, $message);

        if ($response->ok()) {
            return $response->bodyvar('approval_request')->uuid;
        }
        self::failed("Could not request One Touch", $response->errors());
    }

    /**
     * @param $uuid
     * @return bool Verification status
     * @throws \Exception
     */
    public function verifyOneTouch($uuid)
    {
        $response = $this->api->getApprovalRequest($uuid);

        if ($response->ok()) {
            return (bool)$response->bodyvar('status');
        }
        self::failed("OneTouch.php verification failed", $response->errors());
    }

    /**
     * @param $authyId
     * @return bool
     * @throws \Exception
     */
    public function sendToken($authyId)
    {
        $response = $this->api->requestSms($authyId);
        if ($response->ok()) {
            return (bool)$response->bodyvar('success');
        }
        self::failed("OneTouch.php verification failed", $response->errors());
    }

    /**
     * @param $authyId
     * @param $token
     * @return bool
     * @throws \Exception Nothing will be thrown here
     */
    public function verifyToken($authyId, $token)
    {
        $response = $this->api->verifyToken($authyId, $token);

        if ($response->ok()) {
            return $response->ok();
        }
        self::failed("Token verification failed", $response->errors(), false);
    }

    /**
     * @param $authyId
     * @return \Authy\value status
     * @throws \Exception if request to api fails
     */
    public function verifyUserStatus($authyId)
    {
        $response = $this->api->userStatus($authyId);
        if ($response->ok()) {
            return $response->bodyvar('status');
        }
        self::failed("Status verification failed!", $response->errors());
    }
}
