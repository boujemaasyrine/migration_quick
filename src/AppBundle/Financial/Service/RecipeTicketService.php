<?php
/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 14/04/2016
 * Time: 09:20
 */

namespace AppBundle\Financial\Service;

use AppBundle\Administration\Service\ParameterService;
use AppBundle\Financial\Entity\RecipeTicket;
use AppBundle\Merchandise\Service\RestaurantService;
use AppBundle\ToolBox\Service\CommandLauncher;
use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

class RecipeTicketService
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
     * @var TokenStorage
     */
    private $tokenStorage;

    /**
     * @var ParameterService
     */
    private $paramService;

    private $restaurantService;

    public function __construct(
        EntityManager $entityManager,
        Logger $logger,
        TokenStorage $tokenStorage,
        ParameterService $parameterService,
        RestaurantService $restaurantService
    ) {
        $this->em = $entityManager;
        $this->logger = $logger;
        $this->tokenStorage = $tokenStorage;
        $this->paramService = $parameterService;
        $this->restaurantService = $restaurantService;
    }

    public function saveRecipeTicket(RecipeTicket $recipeTicket)
    {
        try {
            $recipeTicket->setOwner($this->tokenStorage->getToken()->getUser());
            $recipeTicket->setOriginRestaurant($this->restaurantService->getCurrentRestaurant());
            $this->em->persist($recipeTicket);
            $this->em->flush();
        } catch (\Exception $e) {
            $this->logger->addAlert($e->getMessage(), ['RecipeTicketService:SaveReciptTicket']);
            throw new \Exception($e);
        }
    }

    public function serializeRecipeTickets($recipeTickets)
    {
        $result = [];
        foreach ($recipeTickets as $recipeTicket) {
            /**
             * @var RecipeTicket $recipeTicket
             */
            $result[] = array(
                'id' => $recipeTicket->getId(),
                'label' => $this->paramService->getRecipeTicketLabel($recipeTicket->getLabel()),
                'date' => $recipeTicket->getDate()->format('d/m/Y'),
                'amount' => $recipeTicket->getAmount() ? number_format($recipeTicket->getAmount(), 2, ',', '') : 0,
                'owner' => $recipeTicket->getOwner() ? $recipeTicket->getOwner()->getFirstName(
                ).' '.$recipeTicket->getOwner()->getLastName() : '',
                "createdAt" => $recipeTicket->getCreatedAt(),
            );
        }

        return $result;
    }

    public function getRecipeTickets($criteria, $order, $limit, $offset, $search = null, $onlyList = false)
    {
        $withdrawals = $this->em->getRepository("Financial:RecipeTicket")->getRecipeTicketsFilteredOrdered(
            $criteria,
            $order,
            $offset,
            $limit,
            $search,
            $onlyList
        );

        return $this->serializeRecipeTickets($withdrawals);
    }
}
