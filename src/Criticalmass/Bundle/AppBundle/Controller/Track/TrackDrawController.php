<?php

namespace Criticalmass\Bundle\AppBundle\Controller\Track;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Criticalmass\Bundle\AppBundle\Controller\AbstractController;
use Criticalmass\Bundle\AppBundle\Entity\Ride;
use Criticalmass\Bundle\AppBundle\Entity\Track;
use Criticalmass\Bundle\AppBundle\Traits\TrackHandlingTrait;
use Symfony\Component\HttpFoundation\Request;

class TrackDrawController extends AbstractController
{
    use TrackHandlingTrait;

    /**
     * @Security("has_role('ROLE_USER')")
     */
    public function drawAction(Request $request, $citySlug, $rideDate)
    {
        $ride = $this->getCheckedCitySlugRideDateRide($citySlug, $rideDate);

        if ('POST' == $request->getMethod()) {
            return $this->drawPostAction($request, $ride);
        } else {
            return $this->drawGetAction($request, $ride);
        }
    }

    protected function drawGetAction(Request $request, Ride $ride)
    {
        return $this->render(
            'AppBundle:Track:draw.html.twig',
            [
                'ride' => $ride
            ]
        );
    }

    protected function drawPostAction(Request $request, Ride $ride)
    {
        $polyline = $request->request->get('polyline');
        $geojson = $request->request->get('geojson');

        $track = new Track();

        $track->setCreationDateTime(new \DateTime());
        $track->setPolyline($polyline);
        $track->setGeoJson($geojson);
        $track->setRide($ride);
        $track->setSource(Track::TRACK_SOURCE_DRAW);
        $track->setUser($this->getUser());
        $track->setUsername($this->getUser()->getUsername());
        $track->setTrackFilename('foo');

        $em = $this->getDoctrine()->getManager();
        $em->persist($track);
        $em->flush();

        return $this->redirectToRoute('caldera_criticalmass_track_list');
    }

    /**
     * @Security("has_role('ROLE_USER')")
     */
    public function editAction(Request $request, int $trackId)
    {
        /** @var Track $track */
        $track = $this->getTrackRepository()->find($trackId);
        $ride = $track->getRide();

        if ($track->getUser() != $track->getUser()) {
            return $this->createAccessDeniedException();
        }

        if ('POST' == $request->getMethod()) {
            return $this->editPostAction($request, $ride, $track);
        } else {
            return $this->editGetAction($request, $ride, $track);
        }
    }

    protected function editGetAction(Request $request, Ride $ride, Track $track)
    {
        return $this->render(
            'AppBundle:Track:draw.html.twig',
            [
                'ride' => $ride,
                'track' => $track
            ]
        );
    }

    protected function editPostAction(Request $request, Ride $ride, Track $track)
    {
        $polyline = $request->request->get('polyline');
        $geojson = $request->request->get('geojson');

        $track->setPolyline($polyline);
        $track->setGeoJson($geojson);

        $em = $this->getDoctrine()->getManager();
        $em->persist($track);
        $em->flush();

        return $this->redirectToRoute('caldera_criticalmass_track_list');
    }
}
