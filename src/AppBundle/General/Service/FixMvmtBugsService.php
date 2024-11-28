<?php
/**
 * Created by PhpStorm.
 * User: schabchoub
 * Date: 16/12/2016
 * Time: 14:05
 */

namespace AppBundle\General\Service;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Translation\Translator;

class FixMvmtBugsService
{
    private $em;
    private $translator;


    public function __construct(EntityManager $em, Translator $translator)
    {
        $this->em = $em;
        $this->translator = $translator;
    }

    public function clearMvmt($startDate, $endDate, $type,$restaurant)
    {
        $this->em->getRepository('Merchandise:ProductPurchasedMvmt')->createQueryBuilder('ppm')
            ->update('Merchandise:ProductPurchasedMvmt', 'ppm')
            ->set('ppm.deleted', ':true')
            ->set('ppm.synchronized', ':false')
            ->where('ppm.dateTime >= :startDate')
            ->andWhere('ppm.dateTime <=:endDate')
            ->andWhere('ppm.type =:type')
            ->andWhere('ppm.originRestaurant = :restaurant')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->setParameter('true', true)
            ->setParameter('false', false)
            ->setParameter('type', $type)
            ->setParameter('restaurant',$restaurant)
            ->getQuery()
            ->execute();
        $this->em->flush();

        return;
    }
}
