<?php

namespace AppBundle\General\Tests\Service;

use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

class RemoveTicketTest extends WebTestCase
{

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var EntityManager
     */
    private $em;

    public function setUp()
    {
        static::$kernel = static::createKernel();
        static::$kernel->boot();

        $this->container = static::$kernel->getContainer();
        $this->em = $this->container->get("doctrine.orm.entity_manager");
    }

    public function testRemoveTicket()
    {
        try {
            $synchronizedTicket = $this->em->getRepository('Financial:Ticket')
                ->findOneBy(['synchronized' => true]);
            $response = $this->container->get('sync.remove_ticket.service')
                ->removeTicket(null, $synchronizedTicket);
            $this->assertTrue($response, 'Getting response from central.');
        } catch (\Exception $e) {
            $this->assertTrue(false, $e->getMessage());
        }
    }
}
