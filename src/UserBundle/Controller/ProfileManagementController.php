<?php

namespace UserBundle\Controller;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use FOS\UserBundle\Model\UserManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;
use UserBundle\Form\Type\UserEmailType;
use UserBundle\Form\Type\UsernameEmailType;
use UserBundle\Form\Type\UsernameType;

class ProfileManagementController extends Controller
{
    public function manageAction(Request $request, UserInterface $user): Response
    {
        $participationCounter = $this->getDoctrine()->getRepository('AppBundle:Participation')->countByUser($user);
        $trackCounter = $this->getDoctrine()->getRepository('AppBundle:Track')->countByUser($user);
        $photoCounter = $this->getDoctrine()->getRepository('AppBundle:Photo')->countByUser($user);

        return $this->render(
            'UserBundle:ProfileManagement:manage.html.twig',
            [
                'participationCounter' => $participationCounter,
                'trackCounter' => $trackCounter,
                'photoCounter' => $photoCounter,
            ]
        );
    }

    public function editUsernameAction(Request $request, UserInterface $user): Response
    {
        $usernameForm = $this->createForm(
            UsernameType::class,
            $user,
            [
                'action' => $this->generateUrl(
                    'criticalmass_user_usermanagement_editusername'
                )
            ]
        );

        if ($request->isMethod(Request::METHOD_POST)) {
            $usernameForm->handleRequest($request);

            if ($usernameForm->isSubmitted() && $usernameForm->isValid()) {
                /** @var $userManager UserManagerInterface */
                $userManager = $this->get('fos_user.user_manager');

                try {
                    $userManager->updateUser($user);

                    return $this->redirectToRoute('criticalmass_user_usermanagement');
                } catch (UniqueConstraintViolationException $exception) {
                    $error = new FormError('Dieser Benutzername ist bereits vergeben.');

                    $usernameForm->get('username')->addError($error);
                }
            }
        }

        return $this->render(
            'UserBundle:ProfileManagement:edit_username.html.twig',
            [
                'usernameForm' => $usernameForm->createView()
            ]
        );
    }

    public function editEmailAction(Request $request, UserInterface $user): Response
    {
        $userEmailForm = $this->createForm(
            UserEmailType::class,
            $user,
            [
                'action' => $this->generateUrl(
                    'criticalmass_user_usermanagement_editemail'
                )
            ]
        );

        if ($request->isMethod(Request::METHOD_POST)) {
            $userEmailForm->handleRequest($request);

            if ($userEmailForm->isSubmitted() && $userEmailForm->isValid()) {
                /** @var $userManager UserManagerInterface */
                $userManager = $this->get('fos_user.user_manager');

                try {
                    $userManager->updateUser($user);

                    return $this->redirectToRoute('criticalmass_user_usermanagement');
                } catch (UniqueConstraintViolationException $exception) {
                    $error = new FormError('Diese E-Mail-Adresse ist bereits registriert worden.');

                    $userEmailForm->get('email')->addError($error);
                }
            }
        }

        return $this->render(
            'UserBundle:ProfileManagement:edit_email.html.twig',
            [
                'userEmailForm' => $userEmailForm->createView()
            ]
        );
    }
}
