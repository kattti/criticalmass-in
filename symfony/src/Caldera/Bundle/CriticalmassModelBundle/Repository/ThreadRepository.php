<?php

namespace Caldera\Bundle\CriticalmassModelBundle\Repository;

use Caldera\Bundle\CriticalmassModelBundle\Entity\City;
use Doctrine\ORM\EntityRepository;

/**
 * @package Caldera\Bundle\CriticalmassModelBundle\Repository
 * @author maltehuebner
 * @since 2016-02-13
 */
class ThreadRepository extends EntityRepository
{
    public function findThreadsForCity(City $city)
    {
        $builder = $this->createQueryBuilder('thread');

        $builder->select('thread');

        $builder->where($builder->expr()->eq('thread.city', $city->getId()));
        $builder->andWhere($builder->expr()->eq('thread.enabled', 1));

        $query = $builder->getQuery();

        return $query->getResult();
    }
}
