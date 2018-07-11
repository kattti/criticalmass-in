<?php

namespace AppBundle\Controller;

use AppBundle\Event\Track\TrackUploadedEvent;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use AppBundle\Entity\Position;
use AppBundle\Entity\Ride;
use AppBundle\Entity\Track;
use AppBundle\Criticalmass\Gps\GpxExporter\GpxExporter;
use Pest;
use Strava\API\Client;
use Strava\API\OAuth as OAuth;
use Strava\API\Service\REST;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class StravaController extends AbstractController
{
    protected function initOauthForRide(Request $request, Ride $ride)
    {
        $redirectUri = $request->getUriForPath($this->generateObjectUrl($ride, 'caldera_criticalmass_strava_token'));

        /* avoid double app_dev.php in uri */
        $redirectUri = str_replace('app_dev.php/app_dev.php/', 'app_dev.php/', $redirectUri);

        try {
            $oauthOptions = [
                'clientId' => $this->getParameter('strava.client_id'),
                'clientSecret' => $this->getParameter('strava.secret'),
                'redirectUri' => $redirectUri,
                'scopes' => ['view_private']
            ];

            return new OAuth($oauthOptions);

        } catch (\Exception $e) {
            print $e->getMessage();
        }
    }

    /**
     * @Security("has_role('ROLE_USER')")
     * @ParamConverter("ride", class="AppBundle:Ride")
     */
    public function authAction(Request $request, Ride $ride): Response
    {
        $oauth = $this->initOauthForRide($request, $ride);

        $authorizationOptions = [
            'state' => '',
            'approval_prompt' => 'force'
        ];

        $authorizationUrl = $oauth->getAuthorizationUrl($authorizationOptions);

        return $this->render('AppBundle:Strava:auth.html.twig', [
            'authorizationUrl' => $authorizationUrl,
            'ride' => $ride,
        ]);
    }

    /**
     * @Security("has_role('ROLE_USER')")
     * @ParamConverter("ride", class="AppBundle:Ride")
     */
    public function tokenAction(Request $request, Ride $ride): Response
    {
        $error = $request->get('error');

        if ($error) {
            return $this->redirectToObject($ride, 'caldera_criticalmass_strava_auth');
        }

        $oauth = $this->initOauthForRide($request, $ride);

        try {
            $token = $oauth->getAccessToken('authorization_code', [
                'code' => $request->get('code')
            ]);

            $session = $this->getSession();
            $session->set('strava_token', $token);

            return $this->redirectToObject($ride, 'caldera_criticalmass_strava_list');
        } catch (\Exception $e) {
            return $this->redirectToObject($ride, 'caldera_criticalmass_strava_auth');
        }
    }

    /**
     * @Security("has_role('ROLE_USER')")
     * @ParamConverter("ride", class="AppBundle:Ride")
     */
    public function listridesAction(Ride $ride): Response
    {
        $afterDateTime = new \DateTime($ride->getDateTime()->format('Y-m-d') . ' 00:00:00');
        $beforeDateTime = new \DateTime($ride->getDateTime()->format('Y-m-d') . ' 23:59:59');

        $token = $this->getSession()->get('strava_token');

        $adapter = new Pest('https://www.strava.com/api/v3');
        $service = new REST($token, $adapter);

        $client = new Client($service);

        $activities = $client->getAthleteActivities($beforeDateTime->getTimestamp(), $afterDateTime->getTimestamp());

        return $this->render('AppBundle:Strava:list.html.twig', [
            'activities' => $activities,
            'ride' => $ride
        ]);
    }

    /**
     * @Security("has_role('ROLE_USER')")
     * @ParamConverter("ride", class="AppBundle:Ride")
     */
    public function importAction(Request $request, EventDispatcherInterface $eventDispatcher, GpxExporter $exporter, Ride $ride): Response
    {
        $activityId = $request->get('activityId');

        $token = $this->getSession()->get('strava_token');

        $adapter = new Pest('https://www.strava.com/api/v3');
        $service = new REST($token, $adapter);

        $client = new Client($service);

        /* Catch the activity to retrieve the start dateTime */
        $activity = $client->getActivity($activityId, true);

        $startDateTime = new \DateTime($activity['start_date']);
        $startDateTime->setTimezone(new \DateTimeZone($activity['timezone']));
        $startTimestamp = $startDateTime->getTimestamp();

        /* Now fetch all the gpx data we need */
        $activityStream = $client->getStreamsActivity($activityId, 'time,latlng,altitude', 'high');

        $length = count($activityStream[0]['data']);

        $latLngList = $activityStream[0]['data'];
        $timeList = $activityStream[1]['data'];
        $altitudeList = $activityStream[2]['data'];

        $positionArray = [];

        for ($i = 0; $i < $length; ++$i) {
            $altitude = round($i > 0 ? $altitudeList[$i] - $altitudeList[$i - 1] : $altitudeList[$i], 2);

            $position = new Position();

            $position->setLatitude($latLngList[$i][0]);
            $position->setLongitude($latLngList[$i][1]);
            $position->setAltitude($altitude);
            $position->setTimestamp($startTimestamp + $timeList[$i]);
            $position->setCreationDateTime(new \DateTime());

            $positionArray[] = $position;
        }

        $exporter->setPositionArray($positionArray);

        $exporter->execute();

        $filename = uniqid() . '.gpx';

        $fp = fopen('../web/tracks/' . $filename, 'w');
        fwrite($fp, $exporter->getGpxContent());
        fclose($fp);

        $track = new Track();
        $track->setSource(Track::TRACK_SOURCE_STRAVA);
        $track->setStravaActivityId($activityId);
        $track->setUser($this->getUser());
        $track->setTrackFilename($filename);
        $track->setUsername($this->getUser()->getUsername());
        $track->setRide($ride);

        $eventDispatcher->dispatch(TrackUploadedEvent::NAME, new TrackUploadedEvent($track));

        $em = $this->getDoctrine()->getManager();
        $em->persist($track);
        $em->flush();

        return $this->redirectToRoute('caldera_criticalmass_track_view', [
            'trackId' => $track->getId()
        ]);
    }
}
