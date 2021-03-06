<?php

namespace App\Manager;

use Doctrine\Bundle\DoctrineBundle\Registry;
use FOS\ElasticaBundle\Elastica\Index;
use FOS\ElasticaBundle\Manager\RepositoryManagerInterface;

/**
 * @deprecated
 */
abstract class AbstractElasticManager extends AbstractManager
{
    /** @var RepositoryManagerInterface $elasticManager */
    protected $elasticManager;

    public function __construct(Registry $doctrine, RepositoryManagerInterface $elasticManager)
    {
        parent::__construct($doctrine);

        $this->elasticManager = $elasticManager;
    }
}
