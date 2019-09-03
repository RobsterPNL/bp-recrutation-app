<?php

declare(strict_types = 1);

namespace App\Services;

use App\Model\ReminderServiceInterface;
use Cartalyst\Sentinel\Laravel\Facades\Reminder;
use Cartalyst\Sentinel\Reminders\EloquentReminder;
use Cartalyst\Sentinel\Users\UserInterface;
use Illuminate\Mail\Mailer;
use Illuminate\Mail\Message;
use LogicException;

/**
 * @author Robert Matuszewski <robmatu@gmail.com>
 */
class ReminderService implements ReminderServiceInterface
{
    /**
     * @var Mailer
     */
    private $mail;

    /**
     * @param Mailer $mail
     */
    public function __construct(Mailer $mail)
    {
        $this->mail = $mail;
    }

    /**
     * @param UserInterface $user
     */
    public function remind(UserInterface $user): void
    {
        /** @var EloquentReminder $reminder */
        $reminder = Reminder::exists($user) ?: Reminder::create($user);

        if (false === $this->sentEmail($user, $reminder->code)) {
            throw new LogicException('Can\'t send email!');
        }
    }

    /**
     * @param UserInterface $user
     * @param string $code
     *
     * @return bool
     */
    private function sentEmail(UserInterface $user, string $code): bool
    {
        return (bool)$this->mail->send('emails.password', ['code' => $code], function (Message $message) use ($user) {
            $message
                ->to($user->email)
                ->subject('Reset account password');
        });
    }
}
