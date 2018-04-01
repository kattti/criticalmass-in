<?php

namespace Criticalmass\Bundle\AppBundle\Controller\City;

use Criticalmass\Bundle\AppBundle\Controller\AbstractController;
use Criticalmass\Bundle\AppBundle\Entity\City;
use Criticalmass\Component\SeoPage\SeoPage;
use Criticalmass\Component\ViewStorage\ViewStorageCache;
use FOS\ElasticaBundle\Finder\FinderInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CityController extends AbstractController
{
    /**
     * @ParamConverter("city", class="AppBundle:City")
     */
    public function missingStatsAction(City $city): Response
    {
        return $this->render('AppBundle:City:missing_stats.html.twig', [
            'city' => $city,
            'rides' => $this->getRideRepository()->findRidesWithoutStatisticsForCity($city),
        ]);
    }

    protected function findNearCities(City $city)
    {
        /** @var FinderInterface $finder */
        $finder = $this->container->get('fos_elastica.finder.criticalmass_city.city');

        $enabledQuery = new \Elastica\Query\Term(['isEnabled' => true]);

        $selfTerm = new \Elastica\Query\Term(['id' => $city->getId()]);
        $selfQuery = new \Elastica\Query\BoolQuery();
        $selfQuery->addMustNot($selfTerm);

        $geoQuery = new \Elastica\Query\GeoDistance('pin', [
            'lat' => $city->getLatitude(),
            'lon' => $city->getLongitude(),
        ],
            '50km'
        );

        $boolQuery = new \Elastica\Query\BoolQuery();
        $boolQuery
            ->addMust($geoQuery)
            ->addMust($enabledQuery)
            ->addMust($selfQuery);

        $query = new \Elastica\Query($boolQuery);

        $query->setSize(15);
        $query->setSort([
            '_geo_distance' => [
                'pin' => [
                    $city->getLatitude(),
                    $city->getLongitude(),
                ],
                'order' => 'desc',
                'unit' => 'km',
            ]
        ]);

        $results = $finder->find($query);

        return $results;
    }

    /**
     * @ParamConverter("city", class="AppBundle:City")
     */
    public function listRidesAction(City $city): Response
    {
        return $this->render('AppBundle:City:ride_list.html.twig', [
            'city' => $city,
            'rides' => $this->getRideRepository()->findRidesForCity($city),
        ]);
    }

    /**
     * @ParamConverter("city", class="AppBundle:City")
     */
    public function listGalleriesAction(Request $request, SeoPage $seoPage, City $city): Response
    {
        $seoPage->setDescription('Übersicht über Fotos von Critical-Mass-Touren aus ' . $city->getCity());

        $result = $this->getPhotoRepository()->findRidesWithPhotoCounter($city);

        return $this->render('AppBundle:City:gallery_list.html.twig', [
            'city' => $city,
            'result' => $result,
        ]);
    }

    /**
     * @ParamConverter("city", class="AppBundle:City")
     */
    public function showAction(SeoPage $seoPage, ViewStorageCache $viewStorageCache, City $city): Response
    {
        $viewStorageCache->countView($city);

        $blocked = $this->getBlockedCityRepository()->findCurrentCityBlock($city);

        if ($blocked) {
            return $this->render('AppBundle:City:blocked.html.twig', [
                'city' => $city,
                'blocked' => $blocked
            ]);
        }

        $nearCities = $this->findNearCities($city);

        $currentRide = $this->getRideRepository()->findCurrentRideForCity($city);

        $rides = $this->getRideRepository()->findRidesForCity($city, 'DESC', 6);

        $dateTime = null;

        if ($city->getTimezone()) {
            $dateTime = new \DateTime();
            $dateTime->setTimezone(new \DateTimeZone($city->getTimezone()));
        }

        $locations = $this->getLocationRepository()->findLocationsByCity($city);

        $photos = $this->getPhotoRepository()->findSomePhotos(8, null, $city);

        $seoPage
            ->setDescription('Informationen, Tourendaten, Tracks und Fotos von der Critical Mass in ' . $city->getCity())
            ->setPreviewPhoto($city)
            ->setCanonicalForObject($city)
            ->setTitle($city->getTitle());

        return $this->render('AppBundle:City:show.html.twig', [
            'city' => $city,
            'currentRide' => $currentRide,
            'dateTime' => $dateTime,
            'nearCities' => $nearCities,
            'locations' => $locations,
            'photos' => $photos,
            'rides' => $rides,
        ]);
    }

    /**
     * @ParamConverter("city", class="AppBundle:City")
     */
    public function liveAction(City $city): Response
    {
        return $this->render('CalderaCriticalmassDesktopBundle:City:live.html.twig', [
            'city' => $city,
        ]);
    }

    /**
     * @ParamConverter("city", class="AppBundle:City")
     */
    public function getlocationsAction(City $city): Response
    {
        return new Response(json_encode($this->getRideRepository()->getLocationsForCity($city)), 200, [
            'Content-Type' => 'text/json',
        ]);
    }
}