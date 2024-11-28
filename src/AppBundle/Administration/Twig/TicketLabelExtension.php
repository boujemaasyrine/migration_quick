<?php
/**
 * Created by PhpStorm.
 * User: bbarhoumi
 * Date: 09/05/2016
 * Time: 08:51
 */

namespace AppBundle\Administration\Twig;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class TicketLabelExtension
 */
class TicketLabelExtension extends \Twig_Extension
{
    private $container;

    /**
     * TicketLabelExtension constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return array
     */
    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('ticket_label', array($this, 'ticketLabel')),
        );
    }

    /**
     * @param $idPayment
     *
     * @return null
     */
    public function ticketLabel($idPayment)
    {
        $code = $this->container->get('paremeter.service')->getTicketRestaurantLabel($idPayment);

        return $code;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'ticket_label_extension';
    }
}
