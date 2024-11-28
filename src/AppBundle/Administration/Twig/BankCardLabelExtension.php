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
 * Class BankCardLabelExtension
 */
class BankCardLabelExtension extends \Twig_Extension
{
    private $container;

    /**
     * BankCardLabelExtension constructor
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
            new \Twig_SimpleFilter('b_card_label', array($this, 'bankCardLabel')),
        );
    }

    /**
     * @param $idPayment
     *
     * @return string
     *
     * @throws \Exception
     */
    public function bankCardLabel($idPayment)
    {
        $code = $this->container->get('paremeter.service')->getBankCardLabel($idPayment);

        return $code;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'b_card_label_extension';
    }
}
