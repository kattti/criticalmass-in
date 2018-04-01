<?php

namespace Criticalmass\Bundle\AppBundle\Controller\City;

use Criticalmass\Bundle\AppBundle\Controller\AbstractController;
use Criticalmass\Bundle\AppBundle\Factory\CityListFactory;
use Criticalmass\Component\SeoPage\SeoPage;
use Symfony\Component\HttpFoundation\Response;

class CityListController extends AbstractController
{
    public function listAction(SeoPage $seoPage, CityListFactory $cityListFactory): Response
    {
        $seoPage->setDescription('Liste mit vielen weltweiten Critical-Mass-Radtouren.');

        return $this->render('AppBundle:CityList:list.html.twig', [
            'cityList' => $cityListFactory->getList(),
        ]);
    }
}