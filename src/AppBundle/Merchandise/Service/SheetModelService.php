<?php
/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 27/02/2016
 * Time: 12:48
 */

namespace AppBundle\Merchandise\Service;

use AppBundle\Merchandise\Entity\SheetModel;
use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use Symfony\Component\Form\Form;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

class SheetModelService
{
    private $em;
    private $tokenStorage;
    private $logger;
    private $restaurantService;

    public function __construct(
        EntityManager $entityManager,
        TokenStorage $tokenStorage,
        Logger $logger,
        RestaurantService $restaurantService
    ) {
        $this->em = $entityManager;
        $this->tokenStorage = $tokenStorage;
        $this->logger = $logger;
        $this->restaurantService = $restaurantService;
    }

    public function saveSheetModel(SheetModel $sheetModel, $type)
    {
        $this->em->beginTransaction();
        try {
            $sheetModel->setEmployee($this->tokenStorage->getToken()->getUser());
            $sheetModel->setType($type);
            $sheetModel->setOriginRestaurant($this->restaurantService->getCurrentRestaurant());
            if (!is_null($sheetModel->getId())) {
                $this->em->detach($sheetModel);
                $temp = $this->em->getRepository('Merchandise:SheetModel')->find($sheetModel->getId());
                $temp->setLabel($sheetModel->getLabel());
                $order = 0;
                foreach ($sheetModel->getLines() as $line) {
                    //                    $line->setOrderInSheet($order);
                    if ($temp->getLines()->contains($line)) {
                        $key = $temp->getLines()->indexOf($line);
                        $temp->getLines()->set($key, $line);
                    } else {
                        $temp->addLine($line);
                    }
                    $order++;
                }
                foreach ($temp->getLines() as $line) {
                    if (!$sheetModel->getLines()->contains($line)) {
                        $temp->removeLine($line);
                        $this->em->remove($line);
                    }
                }
                $this->em->persist($temp);
            } else {
                $order = 0;
                foreach ($sheetModel->getLines() as $line) {
                    $line->setOrderInSheet($order);
                    $order++;
                }
                $this->em->persist($sheetModel);
            }
            $this->em->flush();
            $this->em->clear();
            $this->em->commit();
        } catch (\Exception $e) {
            $this->em->rollback();
            $this->logger->addError('Error saving sheet model', $e->getTrace());
        }
    }

    /**
     * @param $search
     * @param $order
     * @param $start
     * @param $pageSize
     * @param $type
     * @return array
     */
    public function getSheets($search, $order, $start, $pageSize, $type = null)
    {
        $restaurant = $this->restaurantService->getCurrentRestaurant();
        $results = [
            "recordsTotal" => 0,
            "recordsFiltered" => 0,
            "data" => [],
        ];
        $sheets = $this->em->getRepository('Merchandise:SheetModel')->getSheets(
            $restaurant,
            $search,
            $order,
            $start,
            $pageSize,
            $type
        );
        $results['recordsTotal'] = $this->em
            ->getRepository('Merchandise:SheetModel')->getSheetsCount($restaurant, $type);

        $results['recordsFiltered'] = count($sheets);
        $results['data'] = $sheets;

        return $results;
    }

    public function removeSheetModel(SheetModel $sheetModel)
    {
        // get Inventory sheet pointed to this sheet model
        $sheets = $this->em->getRepository('Merchandise:InventorySheet')->findBy(
            [
                'sheetModel' => $sheetModel,
            ]
        );
        foreach ($sheets as $sheet) {
            $sheet->setSheetModel(null);
        }
        // get Loss sheet pointed to this sheet model
        $sheets = $this->em->getRepository('Merchandise:LossSheet')->findBy(
            [
                'model' => $sheetModel,
            ]
        );
        foreach ($sheets as $sheet) {
            $sheet->setModel(null);
        }
        $this->em->flush();
        $this->em->remove($sheetModel);
        $this->em->flush();
    }
}
