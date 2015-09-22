<?php

namespace Caldera\Bundle\CriticalmassSiteBundle\Controller;

use Caldera\Bundle\CriticalmassCoreBundle\Gps\TrackChecker\TrackChecker;
use Caldera\Bundle\CriticalmassCoreBundle\Uploader\TrackUploader\TrackUploader;
use Caldera\Bundle\CriticalmassModelBundle\Entity\Ride;
use Caldera\Bundle\CriticalmassModelBundle\Entity\Track;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\File\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackController extends AbstractController
{
    public function gpxgenerateAction($rideId)
    {
        $ride = $this->getRideRepository()->find($rideId);

        $tickets = $this->getDoctrine()->getRepository('CalderaCriticalmassGlympseBundle:Ticket')->findBy(array('city' => $ride->getCity()));

        foreach ($tickets as $ticket) {
            if ($ticket->belongsToRide($ride)) {
                $positionArray = $this->getDoctrine()->getRepository('CalderaCriticalmassCoreBundle:Position')->findBy(array('ride' => $rideId, 'ticket' => $ticket->getId()), array('timestamp' => 'ASC'));

                $gpx = new GpxWriter();
                $gpx->setPositionArray($positionArray);
                $gpx->execute();

                $gpxContent = $gpx->getGpxContent();

                $track = new Track();
                $track->setRide($ride);
                $track->setTicket($ticket);
                $track->setUsername($ticket->getDisplayname());
                $track->setCreationDateTime(new \DateTime());
                $track->setGpx($gpxContent);
                $track->setActivated(1);
                $track->generateMD5Hash();

                $startDateTime = new \DateTime();
                $startDateTime->setTimestamp($positionArray[0]->getTimestamp());
                $track->setStartDateTime($startDateTime);

                $endDateTime = new \DateTime();
                $endDateTime->setTimestamp($positionArray[count($positionArray) - 1]->getTimestamp());
                $track->setEndDateTime($endDateTime);

                $track->setPoints(count($positionArray));
                $track->setDistance(0);

                $manager = $this->getDoctrine()->getManager();
                $manager->persist($track);
                $manager->flush();
            }
        }

        return new Response();
    }

    public function listAction()
    {
        $tracks = $this->getTrackRepository()->findBy(array('user' => $this->getUser()->getId()), array('startDateTime' => 'DESC'));

        foreach ($tracks as $track) {
            $track->setStartDateTime($track->getStartDateTime()->add(new \DateInterval('PT2H')));
            $track->setEndDateTime($track->getEndDateTime()->add(new \DateInterval('PT2H')));
        }

        return $this->render('CalderaCriticalmassSiteBundle:Track:list.html.twig', 
            array(
                'tracks' => $tracks
            )
        );
    }

    public function uploadAction(Request $request, $citySlug, $rideDate)
    {
        $ride = $this->getCheckedCitySlugRideDateRide($citySlug, $rideDate);
        $track = new Track();

        $form = $this->createFormBuilder($track)
            ->setAction($this->generateUrl('caldera_criticalmass_track_track_upload', array(
                'citySlug' => $ride->getCity()->getMainSlugString(), 
                'rideDate' => $ride->getFormattedDate())))
            ->add('file')
            ->getForm();
        
        if ('POST' == $request->getMethod()) {
            return $this->uploadPostAction($request, $track, $ride, $form);
        } else {
            return $this->uploadGetAction($request, $form);
        }
    }
    
    protected function uploadGetAction(Request $request, Form $form)
    {
        return $this->render('CalderaCriticalmassSiteBundle:Track:upload.html.twig', array('form' => $form->createView()));
    }
    
    public function uploadPostAction(Request $request, Track $track, Ride $ride, Form $form)
    {
        $form->handleRequest($request);

        if ($form->isValid()) {
            $trackUploaderOptions = [
                'trackDirectory' => $this->container->getParameter('directory.track')
            ];

            $trackUploader = new TrackUploader($this->getDoctrine(), $trackUploaderOptions);
            $trackUploader->setUser($this->getUser())
                ->setRide($ride)
                ->setTrack($track)
                ->processUpload();
            
            $estimateService = $this->get('caldera.criticalmass.statistic.rideestimate');
            $estimateService->addEstimate($track);
            $estimateService->calculateEstimates($ride);

            return $this->redirect($this->generateUrl('caldera_criticalmass_track_track_list'));

        }

        return $this->render(
            'CalderaCriticalmassSiteBundle:Track:upload.html.twig', 
            array(
                'form' => $form->createView()
            )
        );
    }

    public function viewAction(Request $request, $trackId)
    {
        $track = $this->getTrackRepository()->findOneById($trackId);

        if ($track && $track->getUser()->equals($this->getUser())) {
            return $this->render('CalderaCriticalmassSiteBundle:Track:view.html.twig', array('track' => $track));
        }

        throw new AccessDeniedException('');
    }

    public function downloadAction(Request $request, $trackId)
    {
        $track = $this->getTrackRepository()->find($trackId);

        if ($track && $track->getUser()->equals($this->getUser()))
        {
            header('Content-disposition: attachment; filename=track.gpx');
            header('Content-type: text/plain');

            $track->loadTrack();

            echo $track->getGpx();
        }

        return $this->redirect($this->generateUrl('caldera_criticalmass_track_track_list'));
    }

    /**
     * Activate or deactivate the user’s track. Deactivating a track will hide it from public ride overviews.
     *
     * @param Request $request
     * @param $trackId
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     *
     * @author swahlen
     */
    public function toggleAction(Request $request, $trackId)
    {
        $track = $this->getTrackRepository()->find($trackId);
        $ride = $track->getRide();

        if ($track && $track->getUser()->equals($this->getUser()))
        {
            $em = $this->getDoctrine()->getManager();
            $track->setActivated(!$track->getActivated());
            $em->merge($track);
            $em->flush();

            $this->get('caldera.criticalmass.statistic.rideestimate')->calculateEstimates($ride);
        }

        return $this->redirect($this->generateUrl('caldera_criticalmass_track_track_list'));
    }

    public function deleteAction(Request $request, $trackId)
    {
        $track = $this->getTrackRepository()->find($trackId);
        $ride = $track->getRide();
        
        if ($track && $track->getUser()->equals($this->getUser()))
        {
            $em = $this->getDoctrine()->getManager();
            $em->remove($track);
            $em->flush();

            $this->get('caldera.criticalmass.statistic.rideestimate')->calculateEstimates($ride);
        }

        return $this->redirect($this->generateUrl('caldera_criticalmass_track_track_list'));
    }
}