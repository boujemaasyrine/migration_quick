<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 28/07/2016
 * Time: 14:57
 */

namespace AppBundle\Report\Service;

use AppBundle\General\Entity\ImportProgression;
use AppBundle\Report\Entity\SyntheticFoodCostRapport;

interface ReportFoodCostInterface
{
    public function getSyntheticFoodCost(
        $currentRestaurantId,
        \DateTime $startDate,
        \DateTime $endDate,
        ImportProgression $progression = null,
        $force = 0
    );

    public function formatResultFoodCostSynthetic(SyntheticFoodCostRapport $rapportTmp);
}
