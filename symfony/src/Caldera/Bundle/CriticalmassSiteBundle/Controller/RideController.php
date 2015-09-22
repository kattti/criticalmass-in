<?php

namespace Caldera\Bundle\CriticalmassSiteBundle\Controller;

use Caldera\Bundle\CriticalmassCoreBundle\Form\Type\RideType;
use Caldera\Bundle\CriticalmassModelBundle\Entity\City;
use Caldera\Bundle\CriticalmassModelBundle\Entity\Ride;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RideController extends AbstractController
{
    public function listAction(Request $request)
    {
        $ridesResult = $this->getRideRepository()->findRidesInInterval();

        $rides = array();

        foreach ($ridesResult as $ride) {
            $rides[$ride->getFormattedDate()][] = $ride;
        }

        return $this->render(
            'CalderaCriticalmassSiteBundle:Ride:list.html.twig', 
            array(
                'rides' => $rides
            )
        );
    }

    public function showAction(Request $request, $citySlug, $rideDate)
    {
        $city = $this->getCheckedCity($citySlug);
        $rideDateTime = $this->getCheckedDateTime($rideDate);
        $ride = $this->getCheckedRide($city, $rideDateTime);
        
        $nextRide = $this->getRideRepository()->getNextRide($ride);
        $previousRide = $this->getRideRepository()->getPreviousRide($ride);
        
        return $this->render(
            'CalderaCriticalmassSiteBundle:Ride:show.html.twig', 
            array(
                'city' => $city, 
                'ride' => $ride,
                'nextRide' => $nextRide,
                'previousRide' => $previousRide,
                'dateTime' => new \DateTime()
            )
        );
    }

    public function addAction(Request $request, $citySlug)
    {
        $city = $this->getCheckedCity($citySlug);

        $ride = new Ride();
        $ride->setCity($city);

        $form = $this->createForm(new RideType(), $ride, array('action' => $this->generateUrl('caldera_criticalmass_desktop_ride_add', array('citySlug' => $city->getMainSlugString()))));

        if ('POST' == $request->getMethod()) {
            return $this->editPostAction($request, $ride, $city, $form);
        } else {
            return $this->editGetAction($request, $ride, $city, $form);
        }
    }
    
    protected function addPostAction(Request $request, Ride $ride, City $city, FormTypeInterface $form)
    {
        $form->handleRequest($request);

        // TODO: remove this shit and test the validation in the template
        $hasErrors = null;

        if ($form->isValid() && !$city->hasRideAtMonthDay($ride->getDateTime())) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($form->getData());
            $em->flush();

            // TODO: remove also this
            $hasErrors = false;

            /* As we have created our new ride, we serve the user the new "edit ride form". Normally it would be enough
            just to change the action url of the form, but we are far to stupid for this hack. */
            $form = $this->createForm(new RideType(), $ride, array('action' => $this->generateUrl('caldera_criticalmass_desktop_ride_edit', array('citySlug' => $city->getMainSlugString(), 'rideDate' => $ride->getFormattedDate()))));
        }
        elseif ($form->isSubmitted())
        {
            // TODO: remove even more shit
            $hasErrors = true;
        }

        return $this->render('CalderaCriticalmassDesktopBundle:Ride:edit.html.twig', array('hasErrors' => $hasErrors, 'ride' => null, 'form' => $form->createView(), 'city' => $city, 'dateTime' => new \DateTime()));
    }


    public function editAction(Request $request, $citySlug, $rideDate)
    {
        $citySlugObj = $this->getDoctrine()->getRepository('CalderaCriticalmassCoreBundle:CitySlug')->findOneBySlug($citySlug);

        if (!$citySlugObj)
        {
            throw new NotFoundHttpException('Wir haben leider keine Stadt in der Datenbank, die sich mit '.$citySlug.' identifiziert.');
        }

        $city = $citySlugObj->getCity();

        try {
            $rideDateTime = new \DateTime($rideDate);
        }
        catch (\Exception $e)
        {
            throw new NotFoundHttpException('Mit diesem Datum können wir leider nichts anfangen. Bitte gib ein Datum im Format YYYY-MM-DD an.');
        }

        $ride = $this->getDoctrine()->getRepository('CalderaCriticalmassCoreBundle:Ride')->findCityRideByDate($city, $rideDateTime);

        if (!$ride)
        {
            throw new NotFoundHttpException('Wir haben leider keine Tour in '.$city->getCity().' am '.$rideDateTime->format('d. m. Y').' gefunden.');
        }

        $archiveRide = clone $ride;
        $archiveRide->setArchiveUser($this->getUser());
        $archiveRide->setArchiveParent($ride);

        $form = $this->createForm(new RideType(), $ride, array('action' => $this->generateUrl('caldera_criticalmass_desktop_ride_edit', array('citySlug' => $city->getMainSlugString(), 'rideDate' => $ride->getDateTime()->format('Y-m-d')))));

        $form->handleRequest($request);

        // TODO: remove this shit and test the validation in the template
        $hasErrors = null;

        if ($form->isValid())
        {
            $em = $this->getDoctrine()->getManager();
            $em->persist($form->getData());
            $em->persist($archiveRide);
            $em->flush();

            // TODO: remove also this
            $hasErrors = false;
        }
        elseif ($form->isSubmitted())
        {
            // TODO: remove even more shit
            $hasErrors = true;
        }

        return $this->render('CalderaCriticalmassDesktopBundle:Ride:edit.html.twig', array('ride' => $ride, 'city' => $city, 'form' => $form->createView(), 'hasErrors' => $hasErrors, 'dateTime' => new \DateTime()));
    }

    public function renderPhotosTabAction(Request $request, Ride $ride)
    {
        $photos = $this->getPhotoRepository()->getPhotosForRide($ride);

        return $this->render(
            'CalderaCriticalmassSiteBundle:Ride:Tabs/GalleryTab.html.twig',
            [
                'ride' => $ride,
                'photos' => $photos,
                'dateTime' => new \DateTime()
            ]
        );
    }

    public function renderTracksTabAction(Request $request, Ride $ride)
    {
        $tracks = $this->getTrackRepository()->getTracksForRide($ride);

        return $this->render(
            'CalderaCriticalmassSiteBundle:Ride:Tabs/TracksTab.html.twig',
            [
                'ride' => $ride,
                'tracks' => $tracks,
                'dateTime' => new \DateTime()
            ]
        );
    }

    public function renderPostsTabAction(Request $request, Ride $ride)
    {
        return $this->render(
            'CalderaCriticalmassSiteBundle:Ride:Tabs/PostsTab.html.twig',
            [
                'ride' => $ride,
            ]
        );
    }

    public function renderSubridesTabAction(Request $request, Ride $ride)
    {
        $subrides = $this->getSubrideRepository()->getSubridesForRide($ride);

        return $this->render(
            'CalderaCriticalmassSiteBundle:Ride:Tabs/SubridesTab.html.twig',
            [
                'ride' => $ride,
                'subrides' => $subrides,
                'dateTime' => new \DateTime()
            ]
        );
    }

    public function renderStatisticTabAction(Request $request, Ride $ride)
    {
        return $this->render(
            'CalderaCriticalmassSiteBundle:Ride:Tabs/StatisticTab.html.twig',
            [
                'ride' => $ride,
                'dateTime' => new \DateTime()
            ]
        );
    }

    public function renderDetailsTabAction(Request $request, Ride $ride)
    {
        return $this->render(
            'CalderaCriticalmassSiteBundle:Ride:Tabs/DetailsTab.html.twig',
            [
                'ride' => $ride,
                'dateTime' => new \DateTime()
            ]
        );
    }
    
}