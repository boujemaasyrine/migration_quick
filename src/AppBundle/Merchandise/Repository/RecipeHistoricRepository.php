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
use AppBundle\Merchandise\Entity\RecipeHistoric;
use AppBundle\Merchandise\Entity\SoldingCanal;
use AppBundle\Merchandise\Entity\SubSoldingCanal;
use Doctrine\ORM\EntityRepository;

class RecipeHistoricRepository extends EntityRepository
{

    public function getRecipeHistItemForAllCanals(ProductSold $item, SoldingCanal $allCanals,  $subSoldingCanal)
    {
        $queryBuilder = $this->createQueryBuilder('rh');

        $queryBuilder->where('rh.productSold = :item')
            ->setParameter('item', $item);
        $queryBuilder->andWhere('rh.soldingCanal = :allCanal')
            ->setParameter('allCanal', $allCanals->getId());
        if (in_array($allCanals->getId(), array(SoldingCanal::CANAL_ALL_CANALS, SoldingCanal::ON_SITE_CANAL, SoldingCanal::E_ORDERING_IN_CANAL))) {
            $queryBuilder->andWhere('rh.subSoldingCanal = :subSoldingCanal')
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




}
