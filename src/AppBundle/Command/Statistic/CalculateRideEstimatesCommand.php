<?php declare(strict_types=1);

namespace AppBundle\Command\Statistic;

use AppBundle\Criticalmass\Statistic\RideEstimateHandler\RideEstimateHandler;
use AppBundle\Criticalmass\Statistic\RideEstimateHandler\RideEstimateHandlerInterface;
use AppBundle\Entity\Ride;
use AppBundle\Criticalmass\Statistic\RideEstimate\RideEstimateService;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CalculateRideEstimatesCommand extends Command
{
    /** @var RegistryInterface $registry */
    protected $registry;

    /** @var RideEstimateHandlerInterface $rideEstimateHandler */
    protected $rideEstimateHandler;

    public function __construct(?string $name = null, RideEstimateHandlerInterface $rideEstimateHandler, RegistryInterface $registry)
    {
        $this->registry = $registry;
        $this->rideEstimateHandler = $rideEstimateHandler;

        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this
            ->setName('criticalmass:rideestimate:recalculate')
            ->setDescription('');
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $rides = $this->registry->getRepository(Ride::class)->findEstimatedRides();

        $progressBar = new ProgressBar($output, count($rides));
        $table = new Table($output);
        $table->setHeaders([
            'City',
            'DateTime',
            'participations',
            'distance',
            'duration',
        ]);

        /** @var Ride $ride */
        foreach ($rides as $ride) {
            $table->addRow([
                $ride->getCity()->getCity(),
                $ride->getDateTime()->format('Y-m-d H:i'),
                $ride->getEstimatedParticipants(),
                $ride->getEstimatedDistance(),
                $ride->getEstimatedDuration(),
            ]);

            $progressBar->advance();

            $this->rideEstimateHandler
                ->setRide($ride)
                ->flushEstimates(false)
                ->calculateEstimates(false);
        }

        $this->registry->getManager()->flush();
        
        $table->render();
        $progressBar->finish();
    }
}
