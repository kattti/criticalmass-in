<?php

namespace Caldera\Bundle\CalderaBundle\Controller;

use Caldera\Bundle\CalderaBundle\Traits\RepositoryTrait;
use Caldera\Bundle\CalderaBundle\Traits\UtilTrait;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

abstract class AbstractController extends Controller
{
    use RepositoryTrait;
    use UtilTrait;

    protected function isLoggedIn(): bool
    {
        return $this
            ->get('security.authorization_checker')
            ->isGranted('IS_AUTHENTICATED_FULLY');
    }
}