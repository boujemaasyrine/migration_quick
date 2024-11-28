<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 31/03/2016
 * Time: 10:24
 */

namespace AppBundle\Supervision\Service\WsBiAPI;

use AppBundle\Merchandise\Entity\InventorySheet;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Supervision\Service\ProductService;
use AppBundle\Supervision\Service\Reports\MarginFoodCostService;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Translation\Translator;
use AppBundle\ToolBox\Utils\Utilities;

class InventoryService
{

    private $em;
    private $translator;
    private $productService;
    private $marginFoodCost;

    public function __construct(EntityManager $entityManager, Translator $translator, ProductService $productService, MarginFoodCostService $marginFoodCost)
    {
        $this->em = $entityManager;
        $this->translator = $translator;
        $this->productService = $productService;
        $this->marginFoodCost = $marginFoodCost;
    }

    public function getInventory($criteria, $limit, $offset)
    {
        if ($offset == 0) {
            if (isset($criteria['restaurants'])) {
                /**
                 * @var Restaurant[] $restaurants
                 */
                $restaurants = $criteria['restaurants'];
            } else {
                return null;
            }
            $returnData = null;
            foreach ($restaurants as $restaurant) {

                $beginDate = \DateTime::createFromFormat('d/m/Y', $criteria['startDate']);
                $endDate = \DateTime::createFromFormat('d/m/Y', $criteria['endDate']);
                $period = $endDate->diff($beginDate)->days;
                $treatedDays = [];
                for ($i = 0; $i <= $period; $i++) {
                    $testDate = Utilities::getDateFromDate($beginDate, $i);
                    if (!in_array($testDate->format('Y-m-d'), $treatedDays)) {
                        $filter = ['beginDate' => $testDate, 'endDate' => $testDate, 'restaurant' => $restaurant];
                        $result = $this->marginFoodCost->getMarginFoodCostResult($filter);
                        $data['codeRest'] = $restaurant->getCode();
                        $data['date'] = $testDate->format('d/m/Y');
                        $data['initial'] = number_format($result['initial'], 2, ',', '');
                        $data['in'] = number_format($result['in'], 2, ',', '');
                        $data['out'] = number_format($result['out'], 2, ',', '');
                        $data['final'] = number_format($result['final'], 2, ',', '');
                        $data['caNetHt'] = number_format($result['caNetHt'], 2, ',', '');
                        $data['revenuePrice'] = number_format($result['revenuePrice'], 2, ',', '');
                        $data['caVoucherMeal'] = number_format($result['caVoucherMeal'], 2, ',', '');
                        $data['known_loss'] = number_format($result['inventoryLossVal'] + $result['soldLossVal'], 2, ',', '');
                        $data['unknown_loss'] = number_format($result['unknown_loss'], 2, ',', '');
                        $returnData[] = $data;
                    }
                }

            }
            return $returnData;
        }

        return null;
    }

    /**
     * @param InventorySheet[] $expenses
     * @return array
     */
    public
    function serializeInventories($expenses)
    {
        $result = [];
        foreach ($expenses as $e) {
            $result[] = $this->serializeInventory($e);
        }
        return $result;
    }

    /**
     * @param InventorySheet $e
     * @return array
     */
    public function serializeInventory(InventorySheet $e)
    {
        $result = array('id' => $e->getId(),);
        return $result;
    }
}
