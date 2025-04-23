<?php

namespace App\Repository;

use App\Entity\Sweatshirt;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Sweatshirt>
 */
class SweatshirtRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Sweatshirt::class);
    }

    /**
     * @param array $criteria Tableau de critères (ex: [['price' => ['>=', 50]], ['price' => ['<=', 100]]])
     * @return Sweatshirt[]
     */
    public function findByCriteria(array $criteria): array
    {
        $qb = $this->createQueryBuilder('s');

        foreach ($criteria as $criterion) {
            foreach ($criterion as $field => $condition) {
                $operator = $condition[0];
                $value = $condition[1];
                $parameter = str_replace('.', '_', $field) . '_' . uniqid();

                $qb->andWhere("s.$field $operator :$parameter")
                   ->setParameter($parameter, $value);
            }
        }

        return $qb->getQuery()->getResult();
    }
}