<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 24/05/2016
 * Time: 18:30
 */

namespace AppBundle\General\Service\Download;

use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Merchandise\Entity\Supplier;
use Doctrine\ORM\EntityManager;

class DownloadRestaurants extends AbstractDownloaderService
{
    // TODO : to update parameter email restaurant
    public function download($idSynCmd = null)
    {
        echo "Start Download Restaurants \n";
        $data = $this->startDownload($this->supervisionParams['restaurants'], $idSynCmd);
        if (isset($data['data']) && is_array($data['data'])) {
            foreach ($data['data'] as $item) {
                echo "Downloading restaurant ".$item['name']." \n";
                $restaurant = $this->em->getRepository("Merchandise:Restaurant")->findOneBy(
                    array(
                        'code' => $item['code'],
                    )
                );

                if (!$restaurant) {
                    echo "New Restaurant ".$item['name']." \n";
                    $restaurant = new Restaurant();
                    $restaurant->setCode($item['code']);
                    $this->em->persist($restaurant);
                }

                $restaurant
                    ->setActive($item['active'])
                    ->setOrderable($item['orderable'])
                    ->setAddress($item['address'])
                    ->setEmail($item['email'])
                    ->setName($item['name'])
                    ->setPhone($item['phone'])
                    ->setManager($item['manager'])
                    ->setType($item['type'])
                    ->setLang($item['lang'])
                    ->setCustomerLang($item['customerLang'])
                    ->setManagerEmail($item['managerEmail'])
                    ->setManagerPhone($item['managerPhone'])
                    ->setDmCf($item['dmCf'])
                    ->setPhoneDmCf($item['phoneDmCf'])
                    ->setZipCode($item['zipCode'])
                    ->setCity($item['city'])
                    ->setBtwTva($item['btwTva'])
                    ->setCompanyName($item['companyName'])
                    ->setAddressCompany($item['addressCompany'])
                    ->setZipCodeCompany($item['zipCodeCompany'])
                    ->setCityCorrespondance($item['cityCorrespondance'])
                    ->setCyFtFpLg($item['cyFtFpLg'])
                    ->setTypeCharte($item['typeCharte'])
                    ->setCluster($item['cluster'])
                    ->setFirstOpenning(
                        ($item['firstOpenning'] != null) ? \DateTime::createFromFormat(
                            'Y-m-d',
                            $item['firstOpenning']
                        ) : null
                    );

                $this->em->flush();
            }
        }
    }
}
