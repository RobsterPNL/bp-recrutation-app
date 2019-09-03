<?php

namespace App\Model;

use Cartalyst\Sentinel\Users\EloquentUser;

/**
 * Class CustomUser
 * @package App\Model
 */
class CustomUser extends EloquentUser
{
    /**
     * {@inheritDoc}
     */
    protected $fillable = [
        'email',
        'password',
        'last_name',
        'first_name',
        'country_code',
        'phone_number',
        'permissions',
    ];

    /**
     * @param $authyId string
     */
    public function updateAuthyId($authyId) {
        if($this->authy_id != $authyId) {
            $this->authy_id = $authyId;
            $this->save();
        }
    }

    /**
     * @param $status string
     */
    public function updateVerificationStatus($status) {
        // reset oneTouch status
        if ($this->authy_status != $status) {
            $this->authy_status = $status;
            $this->save();
        }
    }

    public function updateOneTouchUuid($uuid) {
        if ($this->authy_one_touch_uuid != $uuid) {
            $this->authy_one_touch_uuid = $uuid;
            $this->save();
        }
    }
}
