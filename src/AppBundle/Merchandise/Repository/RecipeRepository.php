<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 20/04/2016
 * Time: 10:48
 */

namespace AppBundle\Merchandise\Repository;

use AppBundle\General\Entity\RemoteHistoric;
use AppBundle\Merchandise\Entity\ProductSold;
use AppBundle\Merchandise\Entity\Recipe;
use AppBundle\Merchandise\Entity\SoldingCanal;
use AppBundle\Merchandise\Entity\SubSoldingCanal;
use Doctrine\ORM\EntityRepository;

class RecipeRepository extends EntityRepository
{

    public function getRecipeItemForAllCanals(ProductSold $item, SoldingCanal $allCanals,  $subSoldingCanal)
    {

        $queryBuilder = $this->createQueryBuilder('r');

        $queryBuilder->where('r.productSold = :item')
            ->setParameter('item', $item);
        $queryBuilder->andWhere('r.soldingCanal = :allCanal')
            ->setParameter('allCanal', $allCanals->getId());
        if (in_array($allCanals->getId(), array(SoldingCanal::CANAL_ALL_CANALS, SoldingCanal::ON_SITE_CANAL, SoldingCanal::E_ORDERING_IN_CANAL))) {
            $queryBuilder->andWhere('r.subSoldingCanal = :subSoldingCanal')
                ->setParameter('subSoldingCanal', $subSoldingCanal);
        }

        try {
            $queryBuilder->setMaxResults(1);
            $result = $queryBuilder->getQuery()->getSingleResult();
        } catch (\Exception $e) {
            $result = null;
        }

        return $result;
    }

    public function getRevenuePrice(ProductSold $item = null, $label = null)
    {

        $queryBuilder = $this->createQueryBuilder('r');

        $queryBuilder
            ->select('r.revenuePrice')
            ->where('r.productSold = :item')
            ->setParameter('item', $item)
            ->leftJoin('r.soldingCanal', 'sc')
            ->andWhere('sc.wyndMppingColumn = :label OR sc.wyndMppingColumn = :allCanals')
            ->setParameter('label', $label)
            ->setParameter('allCanals', Recipe::ALL_CANALS);
        try {
            $queryBuilder->setMaxResults(1);
            $result = $queryBuilder->getQuery()->getSingleScalarResult();
        } catch (\Exception $e) {
            $result = null;
        }

        return $result;
    }
}
