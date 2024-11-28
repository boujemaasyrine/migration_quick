<?php

namespace AppBundle\General\Service\Remote\Financial;

use AppBundle\Financial\Entity\RecipeTicket;
use AppBundle\General\Entity\RemoteHistoric;
use AppBundle\General\Service\Remote\SynchronizerService;
use AppBundle\Merchandise\Entity\LossSheet;
use AppBundle\ToolBox\Utils\Utilities;
use Doctrine\ORM\EntityManager;
use Httpful\Httpful;
use Httpful\Request;
use Monolog\Logger;
use RestClient\CurlRestClient;

class RecipeTickets extends SynchronizerService
{

    public function __construct()
    {
        $this->remoteHistoricType = RemoteHistoric::RECIPE_TICKETS;
    }

    /**
     * @param RecipeTicket[] $recipeTickets
     */
    public function serialize($recipeTickets)
    {
        //Create the data
        foreach ($recipeTickets as $recipeTicket) {
            /**
             * @var RecipeTicket $recipeTicket
             */
            $oData = array(
                'id' => $recipeTicket->getId(),
                'date' => $recipeTicket->getDate('Y-m-d'),
                'label' => $recipeTicket->getLabel(),
                'amount' => $recipeTicket->getAmount(),
                'owner' => $recipeTicket->getOwner()->getGlobalEmployeeID(),
                'createdAt' => $recipeTicket->getCreatedAt('Y-m-d H:i:s'),
                'updatedAt' => $recipeTicket->getUpdatedAt('Y-m-d H:i:s'),
            );
            $data['data'][] = json_encode($oData);
        }
        $data['token'] = 'yyy';

        return $data;
    }

    public function uploadRecipeTickets($idCmd = null, $rawResponse = false)
    {
        parent::preUpload();
        //Get inventories not uploaded
        $recipeTickets = $this->em->getRepository("Financial:RecipeTicket")->createQueryBuilder('recipeTicket')
            ->where("recipeTicket.synchronized = false")
            ->orWhere("recipeTicket.synchronized is NULL")
            ->getQuery()
            ->getResult();
        $success = null;
        $response = null;
        if (count($recipeTickets)) {
            $data = $this->serialize($recipeTickets);
            $response = parent::startUpload($this->params[$this->remoteHistoricType], $data, $idCmd);
            if (!is_null($response) && count($response['error']) === 0) {
                $events = Utilities::removeEvents(RecipeTicket::class, $this->em);
                foreach ($recipeTickets as $recipeTicket) {
                    /**
                     * @var RecipeTicket $recipeTicket
                     */
                    $recipeTicket->setSynchronized(true);
                }
                $this->em->flush();
                Utilities::returnEvents(RecipeTicket::class, $this->em, $events);
                $this->uploadFinishWithSuccess();
                $success = true;
            } else {
                $this->uploadFinishWithFail();
                $success = false;
            }
        } else {
            $success = true;
        }

        if ($rawResponse) {
            return $response;
        } else {
            return $success;
        }
    }

    /**
     *
     * +
     *
     * @return array
     */
    public function start($idCmd = null)
    {
        return $this->uploadRecipeTickets($idCmd);
    }
}
