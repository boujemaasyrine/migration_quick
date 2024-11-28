<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 22/06/2016
 * Time: 16:18
 */

namespace AppBundle\General\Service\Remote\General;

use AppBundle\Administration\Entity\MissingPlu;
use AppBundle\General\Service\Remote\SynchronizerService;
use AppBundle\Merchandise\Entity\Restaurant;
use Doctrine\ORM\EntityManager;

class MissingPluNotification extends SynchronizerService
{

    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function saveMissingPlu($plus, Restaurant $restaurant)
    {
        foreach ($plus as $plu)
        {
            $findPlu = $this->em->getRepository(MissingPlu::class)->findOneBy([
                'plu' => $plu,
            ]);

            if (!$findPlu) {
                $missingPlu = new MissingPlu();
                $missingPlu->setPlu($plu)
                    ->setNotified(false)
                    ->addRestaurant($restaurant);
                $this->em->persist($missingPlu);
            } else {
                if ($findPlu && !$findPlu->getNotified() && !$findPlu->hasRestaurant($restaurant)) {
                    $findPlu->addRestaurant($restaurant);
                }
            }
        }
        $this->em->flush();

    }

    public function start($idCmd = null)
    {
    }
}
