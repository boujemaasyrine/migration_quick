<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 31/03/2016
 * Time: 10:24
 */

namespace AppBundle\Supervision\Service\WsBiAPI;

use AppBundle\Merchandise\Entity\LossLine;
use AppBundle\Merchandise\Entity\LossSheet;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Supervision\Entity\ProductPurchasedSupervision;
use AppBundle\Supervision\Entity\ProductSoldSupervision;
use AppBundle\Supervision\Service\Reports\MarginFoodCostService;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Translation\Translator;
use AppBundle\ToolBox\Utils\Utilities;

class LossService
{

    private $em;
    private $translator;
    private $marginFoodCost;

    public function __construct(EntityManager $entityManager, Translator $translator, MarginFoodCostService $marginFoodCost)
    {
        $this->em = $entityManager;
        $this->translator = $translator;
        $this->marginFoodCost = $marginFoodCost;
    }

    /**
     * @param $criteria
     * @param $limit
     * @param $offset
     * @return array
     */
    public function getLoss($criteria, $limit, $offset)
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
                        $filter = ['beginDate' => $testDate->format('Y-m-d'), 'endDate' => $testDate->format('Y-m-d'), 'restaurants' => [$restaurant],];
                        $soldLoss = $this->em->getRepository(LossLine::class)->getSupervisionFiltredLossLineSold($filter, true);
                        $inventoryLossVal = $this->em->getRepository(LossLine::class)->getSupervisionFiltredLossLine($filter, true);
                        $data['restCode'] = $restaurant->getCode();
                        $data['date'] = $testDate->format('d/m/Y');
                        $data['totalPurchased'] = number_format($inventoryLossVal, 2, ',', '');
                        $data['totalSold'] = number_format($soldLoss['lossvalorization'], 2, ',', '');
                        $returnData[] = $data;
                    }
                }
            }
            return $returnData;
        }
        return null;
    }

    /**
     * @param LossSheet[] $lossSheets
     * @return array
     */
    public function serializeLossSheets($lossSheets)
    {
        $data = [];
        foreach ($lossSheets as $lossSheet) {
            $sheet = $this->serializeLossSheet($lossSheet);
            if (isset($data[$sheet['restCode'] . $sheet['date']])) {
                if (is_null($data[$sheet['restCode'] . $sheet['date']]['totalPurchased'])) {
                    $data[$sheet['restCode'] . $sheet['date']]['totalPurchased'] = $sheet['totalPurchased'];
                } elseif (is_null($data[$sheet['restCode'] . $sheet['date']]['totalSold'])) {
                    $data[$sheet['restCode'] . $sheet['date']]['totalSold'] = $sheet['totalSold'];
                }
            } else {
                $data[$sheet['restCode'] . $sheet['date']] = $sheet;
            }
        }
        return $data;
    }

    /**
     * @param LossSheet $lossSheet
     * @return array
     */
    public function serializeLossSheet($lossSheet)
    {
        $valPurchased = $valSold = null;
        /**
         * @var LossLine $lossLine
         */
        foreach ($lossSheet->getLossLines() as $lossLine) {
            $product = $lossLine->getProduct();
            if ($lossSheet->getType() == LossSheet::ARTICLE) {
                $product = $this->em->getRepository(ProductPurchasedSupervision::class)->find($product->getId());
                $valPurchased += $lossLine->getTotalLoss() * $product->getBuyingCost();
            } elseif ($lossSheet->getType() == LossSheet::FINALPRODUCT) {
                $product = $this->em->getRepository(ProductSoldSupervision::class)->find($product->getId());
                $valSold += $lossLine->getTotalLoss() * $product->calculateDefaultRevenu();
            }
        }
        $data = ['restCode' => $lossSheet->getOriginRestaurant()->getCode(), 'date' => Utilities::formatDate($lossSheet->getEntryDate(), Utilities::D_FORMAT_DATE), 'totalPurchased' => $valPurchased, 'totalSold' => $valSold,];
        return $data;
    }

    /**
     * @param LossSheet $lossSheet
     * @return array
     */
    public function serializeLossLines($lossSheet)
    {
        $data = [];
        if ($lossSheet->getType() == LossSheet::ARTICLE) {
            foreach ($lossSheet->getLossLines() as $lossLine) {
                $data[] = $this->serializeLossLine($lossLine);
            }
        }
        return $data;
    }

    /**
     * @param LossLine $lossLine
     * @return array
     */
    public function serializeLossLine($lossLine)
    {
        $data = ['restCode' => $lossLine->getLossSheet()->getOriginRestaurant()->getCode(), 'date' => Utilities::formatDate($lossLine->getLossSheet()->getEntryDate()), 'totalInventaire' => $lossLine->getTotalLoss(),];
        return $data;
    }
}
