<?php
/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 13/04/2016
 * Time: 10:40
 */

namespace AppBundle\Financial\Service;

use AppBundle\Administration\Entity\Parameter;
use AppBundle\Financial\Entity\PaymentMethod;
use AppBundle\Merchandise\Service\RestaurantService;
use Doctrine\ORM\EntityManager;

class PaymentMethodStatus
{

    /**
     * @var EntityManager
     */
    private $em;

    public function __construct(EntityManager $em, RestaurantService $restaurantService)
    {
        $this->em = $em;
        $currentRestaurant = $restaurantService->getCurrentRestaurant();
        // Load payment methods
        $this->pms = $currentRestaurant->getPaymentMethods();
    }

    /**
     * @var PaymentMethod[]
     */
    private $pms;

    /**
     * @var boolean
     */
    private $cashActive = false;

    /**
     * @var boolean
     */
    private $ticketRestaurantActive = false;

    /**
     * @var boolean
     */
    private $ticketRestaurantElectronicActive = false;

    /**
     * @var boolean
     */
    private $cbActive = false;

    /**
     * @var boolean
     */
    private $checkQuickActive = false;

    /**
     * @var boolean
     */
    private $foreignCurrencyActive = false;

    /**
     * @return \AppBundle\Financial\Entity\PaymentMethod[]
     */
    public function getPms()
    {
        return $this->pms;
    }

    /**
     * @param \AppBundle\Financial\Entity\PaymentMethod[] $pms
     * @return PaymentMethodStatus
     */
    public function setPms($pms)
    {
        $this->pms = $pms;

        foreach ($pms as $paymentMethod) {
            /**
             * @var PaymentMethod $paymentMethod
             */
            switch ($paymentMethod->getType()) {
                case PaymentMethod::REAL_CASH_TYPE:
                    $this->cashActive = $this->cashActive ? true : $paymentMethod->isActive();
                    break;
                case PaymentMethod::CHECK_QUICK_TYPE:
                    $this->checkQuickActive = $this->checkQuickActive ? true : $paymentMethod->isActive();
                    break;
                case PaymentMethod::TICKET_RESTAURANT_TYPE:
                    if ($paymentMethod->getValue()['electronic']) {
                        $this->ticketRestaurantElectronicActive = $this->ticketRestaurantElectronicActive ? true : $paymentMethod->isActive(
                        );
                    } else {
                        $this->ticketRestaurantActive = $this->ticketRestaurantActive ? true : $paymentMethod->isActive(
                        );
                    }
                    break;
                case PaymentMethod::BANK_CARD_TYPE:
                    $this->cbActive = $this->cbActive ? true : $paymentMethod->isActive();
                    break;
                case PaymentMethod::FOREIGN_CURRENCY_TYPE:
                    $this->foreignCurrencyActive = $this->foreignCurrencyActive ? true : $paymentMethod->isActive();
                    break;
            }
        }

        return $this;
    }

    /**
     * @return boolean
     */
    public function isCashActive()
    {
        return $this->cashActive;
    }

    /**
     * @return boolean
     */
    public function isTicketRestaurantActive()
    {
        return $this->ticketRestaurantActive;
    }

    /**
     * @return boolean
     */
    public function isTicketRestaurantElectronicActive()
    {
        return $this->ticketRestaurantElectronicActive;
    }

    /**
     * @return boolean
     */
    public function isCbActive()
    {
        return $this->cbActive;
    }

    /**
     * @return boolean
     */
    public function isCheckQuickActive()
    {
        return $this->checkQuickActive;
    }

    /**
     * @return boolean
     */
    public function isForeignCurrencyActive()
    {
        return $this->foreignCurrencyActive;
    }

    /**
     * @param $idPayment
     * @return bool
     * @throws \Exception
     */
    public function isIdPaymentActive($idPayment)
    {
        foreach ($this->pms as $pm) {
            if (isset($pm->getValue()['id'])) {
                if ($pm->getValue()['id'] === $idPayment) {
                    return $pm->isActive();
                }
            }
        }

        throw new \Exception('Idpayment Not found : '.$idPayment);
    }

    /**
     * @param $type
     * @return bool
     * @throws \Exception
     */
    public function isPaymentMethodActive($type)
    {
        $active = true;
        $atLeastOne = false;
        foreach ($this->pms as $pm) {
            if ($pm->getType() == $type) {
                $atLeastOne = true;
                if ($active) {
                    $active = $pm->isActive();
                } else {
                    break;
                }
            }
        }
        if (!$atLeastOne) {
            throw new \Exception('Type Payment Method Not found : '.$type);
        }

        return $active;
    }

    /**
     * @param $type
     * @return bool
     * @throws \Exception
     */
    public function isCheckRestaurantdActive($electronic = false)
    {
        $active = false;
        $atLeastOne = false;
        foreach ($this->pms as $pm) {
            if ($pm->getType() == Parameter::TICKET_RESTAURANT_TYPE && isset($pm->getValue()['electronic'])) {
                if ($electronic == boolval($pm->getValue()['electronic'])) {
                    $atLeastOne = true;
                    if ($pm->isActive()) {
                        $active = true;
                        break;
                    }
                }
            }
        }
        if (!$atLeastOne) {
            throw new \Exception('No check '.$electronic.' restaurant found.');
        }

        return $active;
    }
}
