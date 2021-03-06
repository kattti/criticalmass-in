<?php declare(strict_types=1);

namespace App\Traits;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

trait UtilTrait
{
    /** @deprecated  */
    protected function getSession(): Session
    {
        $session = new Session();

        return $session;
    }

    /** @deprecated  */
    protected function getManager(): EntityManagerInterface
    {
        return $this->getDoctrine()->getManager();
    }

    protected function saveReferer(Request $request): string
    {
        $referer = $request->headers->get('referer');

        $this->getSession()->set('referer', $referer);

        return $referer;
    }

    protected function getSavedReferer(): ?string
    {
        return $this->getSession()->get('referer');
    }

    protected function createRedirectResponseForSavedReferer(): RedirectResponse
    {
        $referer = $this->getSavedReferer();

        if (!$referer) {
            throw new \Exception('No saved referer found to redirect to.');
        }

        return new RedirectResponse($referer);
    }
}
