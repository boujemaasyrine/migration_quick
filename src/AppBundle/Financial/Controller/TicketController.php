<?php
/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 14/04/2016
 * Time: 09:40
 */

namespace AppBundle\Financial\Controller;

use AppBundle\Financial\Entity\CashboxBankCard;
use AppBundle\Financial\Entity\CashboxBankCardContainer;
use AppBundle\Financial\Entity\CashboxCheckRestaurantContainer;
use AppBundle\Financial\Entity\CashboxCount;
use AppBundle\Financial\Entity\CashboxForeignCurrency;
use AppBundle\Financial\Entity\CashboxForeignCurrencyContainer;
use AppBundle\Financial\Entity\CashboxRealCashContainer;
use AppBundle\Financial\Entity\CashboxTicketRestaurant;
use AppBundle\Financial\Entity\Envelope;
use AppBundle\Financial\Form\Cashbox\CashboxCountType;
use AppBundle\Financial\Form\Cashbox\DayIncomeType;
use AppBundle\Financial\Form\Envelope\EnvelopeType;
use AppBundle\Financial\Model\DayIncome;
use AppBundle\General\Exception\OperationCannotBeDoneException;
use AppBundle\Merchandise\Entity\InventoryLine;
use AppBundle\Merchandise\Entity\InventorySheet;
use AppBundle\Merchandise\Entity\SheetModel;
use AppBundle\Merchandise\Form\InventorySheet\InventorySheetType;
use AppBundle\Security\Exception\NotAllowedException;
use AppBundle\ToolBox\Utils\Utilities;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\CssSelector\Exception\InternalErrorException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * Class CashBoxController
 *
 * @Route("tickets")
 */
class TicketController extends Controller
{
    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("/retrieve_recent_tickets",name="retrieve_recent_tickets", options={"expose"=true})
     *
     * @Method({"GET"})
     */
    public function retrieveRecentTicketsAction(Request $request)
    {
        $response = null;
        if ($request->isXmlHttpRequest()) {
            $response = new JsonResponse();
            $data = [];
            try {
                $reload = $this->get('ticket.service')->importTickets(new \DateTime('now'));
                $data = [
                    "data" => ["reload" => $reload],
                ];
            } catch (\Exception $e) {
                $data = [
                    "errors" => [
                        $this->get('translator')->trans('Error.general.internal'),
                        $e->getLine()." : ".$e->getTraceAsString(),
                    ],
                ];
            }
            $response->setData($data);
        }

        return $response;
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/import_recent_tickets",name="import_recent_tickets", options={"expose"=true})
     * @Method({"GET"})
     */
    public function importRecentTicketsAction(Request $request)
    {
        $response = null;
        if ($request->isXmlHttpRequest()) {
            $response = new JsonResponse();
            $data = [];
            try {
                $this->get('ticket.service')->importTickets(new \DateTime('now'));

            } catch (\Exception $e) {
                $data = [
                    "errors" => [
                        $this->get('translator')->trans('Error.general.internal'),
                        $e->getLine() . " : " . $e->getTraceAsString(),
                    ],
                ];
            }
            $response->setData($data);
        }

        return $response;
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/import_last_working_tickets",name="import_last_working_date_tickets", options={"expose"=true})
     * @Method({"GET"})
     */
    public function importLastWorkingDateTicketsAction(Request $request)
    {
        $response = null;
        if ($request->isXmlHttpRequest()) {
            $response = new JsonResponse();
            $data = [];
            try {
                $date = $this->get('administrative.closing.service')->getLastWorkingEndDate();
                $this->get('ticket.service')->importTickets($date);

            } catch (\Exception $e) {
                $data = [
                    "errors" => [
                        $this->get('translator')->trans('Error.general.internal'),
                        $e->getLine() . " : " . $e->getTraceAsString(),
                    ],
                ];
            }
            $response->setData($data);
        }

        return $response;
    }
}
