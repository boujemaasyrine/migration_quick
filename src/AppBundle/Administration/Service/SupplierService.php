<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 02/03/2016
 * Time: 17:07
 */

namespace AppBundle\Administration\Service;

use AppBundle\Merchandise\Entity\Supplier;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class SupplierService
 */
class SupplierService
{
    private $em;

    /**
     * SupplierService constructor.
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->em = $entityManager;
    }

    /**
     * @param Supplier $supplier
     */
    public function saveSupplier(Supplier $supplier)
    {
        $supplier->setActive(true);
        $this->em->persist($supplier);
        $this->em->flush();
    }

    /**
     * @param Supplier $supplier
     * @return bool
     */
    public function deleteSupplier(Supplier $supplier)
    {
        $supplier->setActive(false);
        $this->em->flush();

        return true;
    }

    /**
     * @param Supplier $supplier
     */
    public function setSupplierPlannings(Supplier $supplier)
    {
        foreach ($supplier->getPlannings() as $planning) {
            $planning->setSupplier($supplier);
        }
        $this->em->persist($supplier);
        $this->em->flush();
    }

    /**
     * @param $line
     * @return bool
     */
    public function deleteLinePlanning($line)
    {
        if (!$line) {
            throw new NotFoundHttpException('No line found');
        }
        $this->em->remove($line);
        $this->em->flush();

        return true;
    }
}
