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
 * Class CodeTicketExtension
 *
 * @package AppBundle\Administration\Twig
 */
class CodeTicketExtension extends \Twig_Extension
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * CodeTicketExtension constructor.
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return array|\Twig_SimpleFilter[]
     */
    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('ticket_code', array($this, 'ticketCode')),
        );
    }

    /**
     * @param $idPayment
     *
     * @return null
     *
     * @throws \Exception
     */
    public function ticketCode($idPayment)
    {
        $code = $this->container->get('paremeter.service')->getTicketRestaurantCode($idPayment);

        return $code;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'ticket_code_extension';
    }
}
