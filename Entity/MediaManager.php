<?php

declare(strict_types=1);

namespace Symbio\OrangeGate\MediaBundle\Entity;

use Sonata\Doctrine\Entity\BaseEntityManager;
use Sonata\MediaBundle\Model\MediaManagerInterface;

/**
 * @extends BaseEntityManager<object>
 */
class MediaManager extends BaseEntityManager implements MediaManagerInterface
{
    /**
     * @param array<string, mixed> $criteria
     * @param array<string, string> $orderBy
     *
     * @return list<object>
     */
    public function getMediasByCriteria(array $criteria, array $orderBy = []): array
    {
        $qb = $this->getRepository()
            ->createQueryBuilder('m')
            ->select('m');

        if (isset($criteria['letter'])) {
            $qb->andWhere($qb->expr()->like('COLLATE(m.name, utf8_bin)', 'CAST(:name, _utf8)'))
                ->setParameter('name', $criteria['letter'].'%');
        }

        if (isset($criteria['category'])) {
            $qb->andWhere('m.category = :category')
                ->setParameter('category', $criteria['category']);
        }

        if ($orderBy !== []) {
            foreach ($orderBy as $field => $direction) {
                $qb->addOrderBy('m.'.$field, $direction);
            }
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return list<string>
     */
    public function getLettersByCategory(object $category): array
    {
        $query = $this->getEntityManager()->createQuery(
            'SELECT m.name
            FROM '.Media::class.' m
            WHERE m.category = :category
            ORDER BY m.name ASC'
        )->setParameter('category', $category);

        return array_values(array_unique(array_map(
            static fn (array $row): string => ucfirst(mb_substr((string) $row['name'], 0, 1, 'UTF-8')),
            $query->getResult(),
        )));
    }
}
