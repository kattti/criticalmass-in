<?php

namespace Criticalmass\Bundle\AppBundle\Controller\City;

use Criticalmass\Bundle\AppBundle\Entity\City;
use Criticalmass\Bundle\AppBundle\Entity\CityCycle;
use Criticalmass\Bundle\AppBundle\Form\Type\CityCycleType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Criticalmass\Bundle\AppBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;

class CityCycleController extends AbstractController
{
    /**
     * @Security("has_role('ROLE_USER')")
     * @ParamConverter("city", class="AppBundle:City")
     */
    public function listAction(UserInterface $user, City $city): Response
    {
        return $this->render('AppBundle:CityCycle:list.html.twig', [
            'cycles' => $this->getCityCycleRepository()->findByCity($city),
            'city' => $city,
        ]);
    }

    /**
     * @Security("has_role('ROLE_USER')")
     * @ParamConverter("city", class="AppBundle:City")
     */
    public function addAction(Request $request, UserInterface $user, City $city): Response
    {
        $cityCycle = new CityCycle();
        $cityCycle
            ->setCity($city)
            ->setUser($user);

        $form = $this->createForm(CityCycleType::class, $cityCycle, [
            'action' => $this->generateUrl('caldera_criticalmass_citycycle_add', [
                'citySlug' => $city->getMainSlugString(),
            ])
        ]);

        if (Request::METHOD_POST === $request->getMethod()) {
            return $this->addPostAction($request, $user, $cityCycle, $form);
        } else {
            return $this->addGetAction($request, $user, $cityCycle, $form);
        }
    }

    protected function addGetAction(Request $request, UserInterface $user, CityCycle $cityCycle, Form $form): Response
    {
        return $this->render('AppBundle:CityCycle:edit.html.twig', [
            'city' => $cityCycle->getCity(),
            'cityCycle' => $cityCycle,
            'form' => $form->createView(),
        ]);
    }

    protected function addPostAction(Request $request, UserInterface $user, CityCycle $cityCycle, Form $form): Response
    {
        $city = $cityCycle->getCity();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($cityCycle);
            $em->flush();

            return $this->redirectToRoute('caldera_criticalmass_citycycle_list', [
                'citySlug' => $city->getMainSlugString(),
            ]);
        }

        return $this->render('AppBundle:CityCycle:edit.html.twig', [
            'city' => $city,
            'cityCycle' => $cityCycle,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Security("has_role('ROLE_USER')")
     * @ParamConverter("cityCycle", class="AppBundle:CityCycle", options={"id" = "cycleId"})
     */
    public function editAction(Request $request, UserInterface $user, CityCycle $cityCycle): Response
    {
        $cityCycle->setUser($user);

        $form = $this->createForm(CityCycleType::class, $cityCycle, [
            'action' => $this->generateUrl('caldera_criticalmass_citycycle_edit', [
                'citySlug' => $cityCycle->getCity()->getMainSlugString(),
                'cycleId' => $cityCycle->getId(),
            ])
        ]);

        if (Request::METHOD_POST == $request->getMethod()) {
            return $this->addPostAction($request, $user, $cityCycle, $form);
        } else {
            return $this->addGetAction($request, $user, $cityCycle, $form);
        }
    }

    protected function editGetAction(Request $request, UserInterface $user, CityCycle $cityCycle, Form $form): Response
    {
        return $this->render('AppBundle:CityCycle:edit.html.twig', [
            'city' => $cityCycle->getCity(),
            'cityCycle' => $cityCycle,
            'form' => $form->createView(),
        ]);
    }

    protected function editPostAction(Request $request, UserInterface $user, CityCycle $cityCycle, Form $form): Response
    {
        $city = $cityCycle->getCity();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($cityCycle);
            $em->flush();

            $form = $this->createForm(CityCycleType::class, $cityCycle, [
                'action' => $this->generateUrl('caldera_criticalmass_citycycle_edit', [
                    'citySlug' => $city->getMainSlugString(),
                    'cycleId' => $cityCycle->getId(),
                ])
            ]);

            return $this->render('AppBundle:CityCycle:edit.html.twig', [
                'city' => $city,
                'cityCycle' => $cityCycle,
                'form' => $form->createView(),
            ]);
        } elseif ($form->isSubmitted()) {
            $hasErrors = true;
        }

        return $this->render('AppBundle:CityCycle:edit.html.twig', [
            'city' => $city,
            'cityCycle' => $cityCycle,
            'form' => $form->createView(),
        ]);
    }
}
