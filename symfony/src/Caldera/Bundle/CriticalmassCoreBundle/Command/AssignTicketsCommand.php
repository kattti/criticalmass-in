<?php

namespace Caldera\Bundle\CriticalmassCoreBundle\Command;

use Caldera\Bundle\CalderaBundle\Entity\Ticket;
use Caldera\Bundle\CriticalmassCoreBundle\Gps\LatLngListGenerator\RangeLatLngListGenerator;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AssignTicketsCommand extends ContainerAwareCommand
{
    /**
     * @var Registry $doctrine
     */
    protected $doctrine;

    /**
     * @var RangeLatLngListGenerator $generator
     */
    protected $generator;

    /**
     * @var EntityManager $manager
     */
    protected $manager;

    protected function configure()
    {
        $this
            ->setName('criticalmass:assign:tickets');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->doctrine = $this->getContainer()->get('doctrine');
        $em = $this->doctrine->getManager();

        $ticketRepository = $this->doctrine->getRepository('CalderaBundle:Ticket');
        $rideRepository = $this->doctrine->getRepository('CalderaBundle:Ride');

        $tickets = $ticketRepository->findBy(['ride' => null]);

        foreach ($tickets as $ticket) {
            /**
             * @var Ticket $ticket
             */
            if ($ticket->getCity()) {
                $rideDate = $ticket->getCreationDateTime()->format('Y-m-d');

                $ride = $rideRepository->findByCityAndRideDate($ticket->getCity(), $rideDate);

                if ($ride) {
                    $ticket->setRide($ride);

                    $em->persist($ticket);
                }
            }
        }

        $em->flush();

    }


}