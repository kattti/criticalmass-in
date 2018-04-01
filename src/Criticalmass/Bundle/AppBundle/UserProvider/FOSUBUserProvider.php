<?php

namespace Criticalmass\Bundle\AppBundle\UserProvider;

use Criticalmass\Bundle\AppBundle\Entity\User;
use HWI\Bundle\OAuthBundle\OAuth\Response\UserResponseInterface;
use HWI\Bundle\OAuthBundle\Security\Core\User\FOSUBUserProvider as BaseClass;
use Symfony\Component\Security\Core\User\UserInterface;

class FOSUBUserProvider extends BaseClass
{
    public function connect(UserInterface $user, UserResponseInterface $response): void
    {
        $property = $this->getProperty($response);
        $username = $response->getUsername();

        if (null !== $previousUser = $this->userManager->findUserBy(array($property => $username))) {
            $previousUser = $this->setServiceData($previousUser, $response, true);

            $this->userManager->updateUser($previousUser);
        }

        $user = $this->setServiceData($user, $response);

        $this->userManager->updateUser($user);
    }

    public function loadUserByOAuthUserResponse(UserResponseInterface $response): UserInterface
    {
        $user = $this->findUserByUsername($response);

        if (null === $user) {
            $user = $this->userManager->createUser();

            $user = $this->setUserData($user, $response);

            $user = $this->setServiceData($user, $response);

            $this->userManager->updateUser($user);

            return $user;
        }

        $user = parent::loadUserByOAuthUserResponse($response);

        $user = $this->setServiceData($user, $response);

        return $user;
    }

    protected function setUserData(UserInterface $user, UserResponseInterface $response): UserInterface
    {
        $username = $response->getNickname() ? $response->getNickname() : $response->getUsername();
        $email = $response->getEmail() ? $response->getEmail() : $response->getUsername();

        $user
            ->setUsername($username)
            ->setEmail($email)
            ->setPassword('')
            ->setEnabled(true);

        return $user;
    }

    protected function setServiceData(
        UserInterface $user,
        UserResponseInterface $response,
        bool $clear = false
    ): UserInterface {
        $username = $response->getUsername();
        $service = $response->getResourceOwner()->getName();

        $setter = sprintf('set%s', ucfirst($service));
        $setterId = sprintf('%sId', $setter);
        $setterToken = sprintf('%sAccessToken', $setter);

        if ($clear) {
            $user
                ->$setterId(null)
                ->$setterToken(null);
        } else {
            $user
                ->$setterId($username)
                ->$setterToken($response->getAccessToken());
        }

        return $user;
    }

    protected function findUserByUsername(UserResponseInterface $response): ?UserInterface
    {
        $service = $response->getResourceOwner()->getName();
        $serviceId = sprintf('%sId', strtolower($service));

        return $this->userManager->findUserBy([$serviceId => $response->getUsername()]);
    }

    protected function findUserByEmail(UserResponseInterface $response): ?UserInterface
    {
        return $this->userManager->findUserBy(['email' => $response->getEmail()]);
    }
}