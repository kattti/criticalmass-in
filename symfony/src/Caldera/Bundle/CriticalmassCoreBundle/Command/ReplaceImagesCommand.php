<?php

namespace Caldera\Bundle\CriticalmassCoreBundle\Command;

use Caldera\Bundle\CriticalmassCoreBundle\Gps\LatLngListGenerator\RangeLatLngListGenerator;
use Caldera\Bundle\CriticalmassCoreBundle\Image\PhotoGps\PhotoGps;
use Caldera\Bundle\CriticalmassModelBundle\Entity\Track;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ReplaceImagesCommand extends ContainerAwareCommand
{
    /**
     * @var Registry $doctrine
     */
    protected $doctrine;

    /**
     * @var PhotoGps $photoGps
     */
    protected $photoGps;

    /**
     * @var EntityManager $manager
     */
    protected $manager;

    protected function configure()
    {
        $this
            ->setName('criticalmass:images:replaces')
            ->setDescription('Regenerate LatLng Tracks')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->doctrine = $this->getContainer()->get('doctrine');
        $this->photoGps = $this->getContainer()->get('caldera.criticalmass.image.photogps');
        $this->manager = $this->doctrine->getManager();

        $ride = $this->doctrine->getRepository('CalderaCriticalmassModelBundle:Ride')->find(1565);
        $user = $this->doctrine->getRepository('ApplicationSonataUserBundle:User')->find(1);
        $track = $this->doctrine->getRepository('CalderaCriticalmassModelBundle:Track')->findByUserAndRide($ride, $user);
        $photos = $this->doctrine->getRepository('CalderaCriticalmassModelBundle:Photo')->findPhotosByRide($ride);

        foreach ($photos as $photo) {
            $this->photoGps->setPhoto($photo);
            $this->photoGps->setTrack($track);

            $this->photoGps->execute();

            $this->manager->merge($photo);
            $this->manager->flush();
        }
    }
}