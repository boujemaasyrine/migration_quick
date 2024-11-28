<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 24/05/2016
 * Time: 18:30
 */

namespace AppBundle\General\Service\Download;

use AppBundle\Administration\Entity\Action;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Merchandise\Service\RestaurantService;
use AppBundle\Security\Entity\Role;
use Doctrine\Common\Collections\ArrayCollection;

class DownloadRoles extends AbstractDownloaderService
{
    /**
     * @var RestaurantService
     */
    private $restaurantService;

    public function setRestaurantService(RestaurantService $restaurantService)
    {
        $this->restaurantService = $restaurantService;
    }

    public function download($idSynCmd = null)
    {
        echo "Start Download Roles \n";
        $data = $this->startDownload($this->supervisionParams['roles'], $idSynCmd);
        if (isset($data['data']) && is_array($data['data'])) {
            $currentRestruant = $this->restaurantService->getCurrentRestaurant();
            $newRolesId = [];
            foreach ($data['data'] as $item) {
                echo "Downloading Role ".$item['label']." \n";
                $obj = $this->em->getRepository("Security:Role")->findOneBy(
                    array(
                        'globalId' => $item['globalId'],
                    )
                );

                if (!$obj) {
                    echo "New Role ".$item['label']." \n";
                    $obj = new Role();
                    $obj->setGlobalId($item['globalId']);
                    $this->em->persist($obj);
                } else {
                    if ($currentRestruant->getType() != Restaurant::FRANCHISE) {
                        foreach ($obj->getActions() as $a) {
                            $obj->removeAction($a);
                            $a->removeRole($obj);
                        }
                        $this->em->flush();
                    }
                }

                $obj->setLabel($item['label'])
                    ->setType($item['type'])
                    ->setTextLabel($item['textLabel_fr'])
                    ->addTextLabelTranslation('fr', $item['textLabel_fr'])
                    ->addTextLabelTranslation('nl', $item['textLabel_nl']);

                if ($currentRestruant->getType() != Restaurant::FRANCHISE) {
                    echo "SETTING ACTIONS \n";
                    //Gestion des droits
                    foreach ($item['actions'] as $a) {
                        $action = $this->em->getRepository("Administration:Action")->findOneBy(array('globalId' => $a));
                        if ($action) {
                            $obj->addAction($action);
                            $action->addRole($obj);
                        }

                        $this->em->flush();
                    }
                } else {
                    echo "NO SETTING ACTIONS \n";
                }
                $this->em->flush();
                $newRolesId[] = $obj->getId();
            }

            //Deleting Deleted Roles
            $allRoles = $this->em->getRepository("Security:Role")->findAll();
            echo "DELETING DELETED ROLES \n";
            foreach ($allRoles as $r) {
                if (!in_array($r->getId(), $newRolesId)) {
                    $this->logger->addInfo("DELETE ROLE : ".$r->getLabel());
                    echo "DELETING ROLE".$r->getLabel()." \n";
                    $this->em->remove($r);
                    $this->em->flush();
                }
            }
        }
    }
}
