<?php

namespace AppBundle\Command;

use AppBundle\Entity\City;
use AppBundle\StandardRideGenerator\StandardRideGenerator;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class StandardRideCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('criticalmass:standardrides')
            ->setDescription('Create rides for a parameterized year and month automatically')
            ->addArgument(
                'year',
                InputArgument::REQUIRED,
                'Year of the rides to create'
            )
            ->addArgument(
                'month',
                InputArgument::REQUIRED,
                'Month of the rides to create'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var int $year */
        $year = $input->getArgument('year');

        /** @var int $month */
        $month = $input->getArgument('month');

        $doctrine = $this->getContainer()->get('doctrine');
        $entityManager = $doctrine->getManager();

        $cities = $doctrine->getRepository('AppBundle:City')->findBy(
            [
                'isArchived' => false,
                'enabled' => true
            ],
            [
                'city' => 'ASC'
            ]
        );

        /** @var City $city */
        foreach ($cities as $city) {
            $output->writeln($city->getTitle());

            if ($city->getIsStandardable()) {
                $srg = new StandardRideGenerator($city, $year, $month);
                $ride = $srg->execute();

                if ($srg->isRideDuplicate()) {
                    $output->writeln('Tour existiert bereits.');
                } else {
                    $output->writeln('Lege folgende Tour an');

                    if ($ride->getHasTime()) {
                        $output->writeln('Datum und Uhrzeit: ' . $ride->getDateTime()->format('Y-m-d H:i'));
                    } else {
                        $output->writeln('Datum: ' . $ride->getDateTime()->format('Y-m-d') . ', Uhrzeit ist bislang unbekannt');
                    }

                    if ($ride->getHasLocation()) {
                        $output->writeln('Treffpunkt: ' . $ride->getLocation() . ' (' . $ride->getLatitude() . '/' . $ride->getLongitude() . ')');
                    } else {
                        $output->writeln('Treffpunkt ist bislang unbekannt');
                    }

                    $output->writeln('');
                    $output->writeln('');

                    $entityManager->persist($ride);
                }
            } else {
                $output->writeln('Lege keine Tourdaten für diese Stadt an.');
            }
        }

        $entityManager->flush();
    }
}