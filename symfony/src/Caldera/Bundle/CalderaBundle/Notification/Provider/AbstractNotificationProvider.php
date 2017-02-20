<?php

namespace Caldera\Bundle\CalderaBundle\Notification\Provider;

use Caldera\Bundle\CalderaBundle\Entity\User;
use Caldera\Bundle\CalderaBundle\Notification\Notification\AbstractNotification;

abstract class AbstractNotificationProvider
{
    /** @var AbstractNotification $notification */
    protected $notification;

    protected $userList = [];

    public function setNotification(AbstractNotification $notification)
    {
        $this->notification = $notification;
    }

    public function addUser(User $user)
    {
        array_push($this->userList, $user);
    }

    abstract public function send();

    protected function createArchiveNotification()
    {
    }
}