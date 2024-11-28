<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 24/05/2016
 * Time: 18:30
 */

namespace AppBundle\General\Service\Download;

use AppBundle\Administration\Entity\Parameter;
use AppBundle\Merchandise\Entity\CategoryGroup;
use AppBundle\Merchandise\Entity\ProductCategories;
use AppBundle\Merchandise\Entity\SoldingCanal;

class ExpenseLabels extends AbstractDownloaderService
{
    public function download($idSynCmd = null)
    {
        $this->logger->debug("Start Download Expense Labels ");
        $data = $this->startDownload($this->supervisionParams['expense_labels'], $idSynCmd);
        if (isset($data['data']) && is_array($data['data'])) {
            try {
                $this->em->beginTransaction();
                foreach ($data['data'] as $item) {
                    $this->logger->debug("Downloading Expense Labels ".$item['label']);
                    $param = $this->em->getRepository("Administration:Parameter")->findOneBy(
                        array(
                            'globalId' => $item['globalId'],
                            'type' => Parameter::EXPENSE_LABELS_TYPE,
                        )
                    );
                    if (is_null($param)) {
                        $this->logger->debug("New Expense Label ".$item['label']);
                        $param = new Parameter();
                        $param
                            ->setType(Parameter::EXPENSE_LABELS_TYPE)
                            ->setGlobalId($item['globalId']);
                        $this->em->persist($param);
                    }
                    $param
                        ->setLabel($item['label'])
                        ->addLabelTranslation('nl', $item['label_nl'])
                        ->addLabelTranslation('fr', $item['label_fr'])
                        ->setValue($item['value']);
                    $this->em->flush();
                }
                $this->em->commit();
            } catch (\Exception $e) {
                $this->em->rollback();
                throw $e;
            }
        }
    }
}
