<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 24/05/2016
 * Time: 18:30
 */

namespace AppBundle\General\Service\Download;

use AppBundle\Merchandise\Entity\CategoryGroup;
use AppBundle\Merchandise\Entity\ProductCategories;

class DownloadCategories extends AbstractDownloaderService
{
    public function download($idSynCmd = null)
    {
        echo "Start Download Categories \n";
        $data = $this->startDownload($this->supervisionParams['categories'], $idSynCmd);
        if (isset($data['data']) && is_array($data['data'])) {
            foreach ($data['data'] as $item) {
                echo "Downloading Category ".$item['name']." \n";
                $groupCategory = $this->em->getRepository("Merchandise:CategoryGroup")->findOneBy(
                    array(
                        'globalId' => $item['globalId'],
                    )
                );

                if (!$groupCategory) {
                    echo "New Group Category ".$item['name']." \n";
                    $groupCategory = new CategoryGroup();
                    $groupCategory->setGlobalId($item['globalId']);
                    $this->em->persist($groupCategory);
                }

                $groupCategory->setActive($item['active'])
                    ->setName($item['name_fr'])
                    ->addNameTranslation('fr', $item['name_fr'])
                    ->addNameTranslation('nl', $item['name_nl']);

                foreach ($item['productCategories'] as $pc) {
                    echo "   Downloading Product  Category ".$pc['name']." \n";
                    $pCat = $this->em->getRepository("Merchandise:ProductCategories")->findOneBy(
                        array(
                            'globalId' => $pc['globalId'],
                        )
                    );

                    if ($pCat == null) {
                        echo "   New Product Category ".$pc['name']." \n";
                        $pCat = new ProductCategories();
                        $pCat
                            ->setGlobalId($pc['globalId']);
                        $this->em->persist($pCat);
                    }

                    $pCat->setCategoryGroup($groupCategory)
                        ->setName($pc['name_fr'])
                        ->addNameTranslation('fr', $pc['name_fr'])
                        ->addNameTranslation('nl', $pc['name_nl'])
                        ->setEligible($pc['eligible'])
                        ->setTaxLetter($pc['taxLetter'])
                        ->setOrder($pc['order'])
                        ->setReference($pc['reference'])
                        ->setTaxBe($pc['taxBe'])
                        ->setTaxLux($pc['taxLux']);
                    $this->em->flush();
                }

                $this->em->flush();
            }
        }
    }
}
