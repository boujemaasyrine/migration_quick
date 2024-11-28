<?php
/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 20/05/2016
 * Time: 09:50
 */

namespace AppBundle\Supervision\Service\WsBoAPI;

use AppBundle\Financial\Entity\RecipeTicket;
use AppBundle\General\Entity\RemoteHistoric;
use AppBundle\Merchandise\Entity\Restaurant;
use Doctrine\ORM\NoResultException;

class RecipeTicketsSyncService extends AbstractSyncService
{

    /**
     * @param $recipeTickets
     * @param $quickCode
     * @return array
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Exception
     */
    public function deserialize($recipeTickets, Restaurant $restaurant)
    {
        $result = [];
        foreach ($recipeTickets as $recipeTicket) {
            $recipeTicket = json_decode($recipeTicket, true);
            $newRecipeTicket = new RecipeTicket();
            try {
                $owner = $this->em->getRepository('AppBundle:Staff\Employee')
                    ->getEmployeeByGlobalId($recipeTicket['owner']);
            } catch (NoResultException $e) {
                $this->logger->addAlert(
                    'Uknown employee code ('.$recipeTicket['owner'].') '.$e->getMessage(),
                    ['RecipeTicketService', 'deserialize', 'UknownEmployee']
                );
                throw $e;
            }
            if (is_bool($recipeTicket['date'])) {
                throw new \Exception($recipeTicket['date']);
            }
            $existantRecipeTicket = $this->em->getRepository('AppBundle:Financial\RecipeTicket')->findOneBy(
                array(
                    'originalID' => $recipeTicket['id'],
                    'originRestaurant' => $restaurant,
                )
            );
            if (is_null($existantRecipeTicket)) {
                $newRecipeTicket
                    ->setOriginalID($recipeTicket['id'])
                    ->setDate($recipeTicket['date'], 'Y-m-d')
                    ->setOwner($owner)
                    ->setLabel($recipeTicket['label'])
                    ->setAmount($recipeTicket['amount'])
                    ->setCreatedAt($recipeTicket['createdAt'], 'Y-m-d H:i:s')
                    ->setUpdatedAt($recipeTicket['updatedAt'], 'Y-m-d H:i:s')
                    ->setOriginRestaurant($restaurant);
                $result[] = $newRecipeTicket;
            }
        }

        return $result;
    }

    public function importRecipeTickets($recipeTicketsData, Restaurant $restaurant)
    {
        $this->em->beginTransaction();
        try {
            $recipeTickets = $this->deserialize($recipeTicketsData, $restaurant);
            foreach ($recipeTickets as $recipeTicket) {
                $this->em->persist($recipeTicket);
                $this->em->flush();
            }
            $this->em->commit();
            $this->remoteHistoricService
                ->createSuccessEntry($restaurant, RemoteHistoric::RECIPE_TICKETS, []);
        } catch (\Exception $e) {
            $this->em->rollback();
            $this->logger->addAlert(
                'Exception occured when importing recipeTickets, import was rollback : '.$e->getMessage(),
                ['RecipeTicketService', 'ImportRecipeTickets']
            );
            throw new \Exception($e);
        }
    }
}
