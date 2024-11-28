<?php
/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 20/05/2016
 * Time: 09:50
 */

namespace AppBundle\Supervision\Service\WsBiAPI;

use AppBundle\Financial\Entity\AdministrativeClosing;
use AppBundle\Merchandise\Entity\Restaurant;
use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use Symfony\Component\DependencyInjection\Container;

class ParameterCheckService
{

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var Container
     */
    private $container;

    public function __construct(EntityManager $entityManager, Logger $logger, Container $container)
    {
        $this->em = $entityManager;
        $this->logger = $logger;
        $this->container = $container;
    }

    public function verifyDate()
    {
    }

    /**
     * @param null $restaurantCode
     * @param null $startDate
     * @param null $endDate
     * @return string
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function verifyRestaurant($restaurantCode = null, $startDate = null, $endDate = null)
    {
        $return = null;

        if ($startDate) {
            $startDate = \DateTime::createFromFormat('d/m/Y', $startDate);
        }
        if ($endDate) {
            $endDate = \DateTime::createFromFormat('d/m/Y', $endDate);
        }

        if ((!$endDate or !$startDate) or $endDate < $startDate) {
            return 'Invalid date period!';
        }

        if ($restaurantCode != null) {
            $restaurants[] = $this->em->getRepository(Restaurant::class)->findOneBy(
                array('code' => $restaurantCode)
            );
            if (is_null($restaurants[0])) {
                return 'Invalid restaurant code!';
            }
        } else {
            $restaurants = $this->em->getRepository(Restaurant::class)->findAll();
        }
        foreach ($restaurants as $restaurant) {
            $qb = $this->em->getRepository(AdministrativeClosing::class)->createQueryBuilder('ac');
            $qb->join('ac.originRestaurant', 'o')
                ->where('o.id = :id')
                ->setParameter('id', $restaurant->getId())
                ->setMaxResults(1)
                ->orderBy('ac.date', 'desc');
            $closing = $qb->getQuery()->getOneOrNullResult();
            /**
             * @var AdministrativeClosing $closing
             */
            if (!is_null($closing)) {
                $closingDate = \DateTime::createFromFormat('d/m/Y', $closing->getDate()->format('d/m/Y'));
                if (!($endDate > $closingDate)) {
                    $return[] = $restaurant;
                }
            }
        }
        if (sizeof($return) > 0) {
            return $return;
        } else {
            if ($restaurantCode != null) {
                return 'No result for non closed day!';
            }
        }

        return 'No result is available!';
    }
}
