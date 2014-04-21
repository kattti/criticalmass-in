<?php

namespace Caldera\CriticalmassMobileBundle\Controller;

use Caldera\CriticalmassStatisticBundle\Utility\Trackable;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class MultiPageController extends Controller implements Trackable
{
    public function mainAction()
    {
        return $this->render('CalderaCriticalmassMobileBundle:MultiPage:main.html.twig');
    }
}
