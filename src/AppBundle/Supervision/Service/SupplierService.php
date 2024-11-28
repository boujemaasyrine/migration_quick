<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 02/03/2016
 * Time: 17:07
 */

namespace AppBundle\Supervision\Service;

use AppBundle\Merchandise\Entity\Supplier;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SupplierService
{
    private $em;

    public function __construct(EntityManager $entityManager)
    {
        $this->em = $entityManager;
    }

    public function saveSupplier(Supplier $supplier)
    {
        $supplier->setActive(true);
        $this->em->persist($supplier);
        $this->em->flush();
        /*$this->syncCmdCreateEntryService
            ->createAllRestaurantSupplierSyncCommands();*/
    }

    public function deleteSupplier(Supplier $supplier)
    {
        $supplier->setActive(false);
        $this->em->flush();
        /* this section to be verified as the synchronize mechanism won't be used anymore*/

        /*$this->syncCmdCreateEntryService
            ->createAllRestaurantSupplierSyncCommands();*/

        return true;
    }

    public function setSupplierPlannings(Supplier $supplier)
    {
        foreach ($supplier->getPlannings() as $planning) {
            $planning->setSupplier($supplier);
        }
        $this->em->persist($supplier);
        $this->em->flush();
    }

    public function deleteLinePlanning($line)
    {
        if (!$line) {
            throw new NotFoundHttpException('No line found');
        }
        $this->em->remove($line);
        $this->em->flush();

        return true;
    }

    public function getSuppliers($criteria, $order, $limit, $offset, $onlyList = false)
    {
        $suppliers = $this->em->getRepository(Supplier::class)->getSupplierOrderedForSupervision(
            $criteria,
            $order,
            $offset,
            $limit,
            $onlyList
        );

        return $this->serializeSuppliers($suppliers);
    }

    public function serializeSuppliers($suppliers)
    {
        $result = [];
        foreach ($suppliers as $s) {
            /**
             * @var Supplier $s
             */
            $result[] = array(
                'id' => $s->getId(),
                'code' => (string) $s->getCode(),
                'name' => $s->getName(),
                'designation' => $s->getDesignation(),
                'address' => $s->getAddress(),
                'phone' => $s->getPhone(),
                'mail' => $s->getEmail(),
            );
        }

        return $result;
    }
}
