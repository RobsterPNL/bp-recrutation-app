<?php

namespace App\Authy;


interface Service
{

    /**
     * Register a new user in authy
     *
     * @param $email
     * @param $phone_number
     * @param $country_code
     * @return string User Authy id
     */
    public function register($email, $phone_number, $country_code);

    /**
     * Verify if the user is registered in Authy (smartphone app installed)
     *
     * @param $authyId
     * @return mixed
     */
    public function verifyUserStatus($authyId);

    /**
     * Request a one touch verification
     *
     * @param $authyId
     * @param $message
     * @return string uuid
     */
    public function sendOneTouch($authyId, $message);

    /**
     * Check one touch verification status
     *
     * @param $uuid
     * @return string status
     */
    public function verifyOneTouch($uuid);

    /**
     * Send a verification token to user phone
     *
     * @param $authyId
     * @return bool `true` if token successful sent or ignored
     */
    public function sendToken($authyId);

    /**
     * Request token verification
     *
     * @param $authyId
     * @param $token
     * @return bool `true` if token is valid
     */
    public function verifyToken($authyId, $token);
}
