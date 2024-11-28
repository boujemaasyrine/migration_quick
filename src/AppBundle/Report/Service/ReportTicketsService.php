<?php
/**
 * Created by PhpStorm.
 * User: schabchoub
 * Date: 10/10/2016
 * Time: 10:27
 */

namespace AppBundle\Report\Service;


use AppBundle\Administration\Service\ParameterService;
use AppBundle\Financial\Entity\PaymentMethod;
use AppBundle\Financial\Entity\Ticket;
use AppBundle\Financial\Entity\TicketPayment;
use AppBundle\Merchandise\Service\RestaurantService;
use AppBundle\Staff\Entity\Employee;
use AppBundle\ToolBox\Utils\Utilities;
use Doctrine\ORM\EntityManager;
use Knp\Bundle\PaginatorBundle\Definition\PaginatorAware;
use PDO;
use Symfony\Component\Translation\Translator;
use Liuggio\ExcelBundle\Factory;

class ReportTicketsService extends PaginatorAware
{

    private $em;
    private $translator;
    private $paramService;
    private $restaurantService;
    private $phpExcel;

    /**
     * ReportDiscountService constructor.
     * @param $em
     * @param $translator
     * @param $paramService
     */
    public function __construct(
        EntityManager $em,
        Translator $translator,
        ParameterService $paramService,
        RestaurantService $restaurantService,
        Factory $factory
    ) {
        $this->em = $em;
        $this->translator = $translator;
        $this->paramService = $paramService;
        $this->restaurantService = $restaurantService;
        $this->phpExcel = $factory;
    }

    public function getHoursList()
    {
        $currentRestaurant = $this->restaurantService->getCurrentRestaurant();
        $openingHour = ($this->paramService->getRestaurantOpeningHour(
                $currentRestaurant
            ) == null)
            ? 0
            : $this->paramService->getRestaurantOpeningHour(
                $currentRestaurant
            );
        $closingHour = ($this->paramService->getRestaurantClosingHour(
                $currentRestaurant
            ) == null)
            ? 23
            : $this->paramService->getRestaurantClosingHour(
                $currentRestaurant
            );
        $hoursArray = array();
        if ($closingHour <= $openingHour) {
            ;
        }
        $closingHour += 24;
        for ($i = intval($openingHour); $i <= intval($closingHour); $i++) {
            $hoursArray[$i] = (($i >= 24) ? ($i - 24) : $i).":00";
        }

        return $hoursArray;
    }

    public function getTicketList($filter, $offset = null, $limit = null)
    {
        $restaurantId = $this->restaurantService->getCurrentRestaurant()->getId();
        $startHour = (is_null($filter['startHour'])) ? 0 : $filter['startHour'];
        $endHour = (is_null($filter['endHour'])) ? 23 : $filter['endHour'];
        $startDate = $filter['startDate'];
        $endDate = $filter['endDate'];
        $cashier = $filter['cashier'];
        $invoiceFrom = $filter['startInvoiceNumber'];
        $invoiceTo = $filter['endInvoiceNumber'];
        $soldingCanals = $filter['solding_canal'];
        $paymentMethods = $filter['paymentMethod'];
        $amountMin = $filter['amountMin'];
        $amountMax = $filter['amountMax'];

        $sql = "SELECT 
                      ticket.id                AS id, 
                      ticket.invoicecancelled  AS invoiceCancelled,
                      ticket.invoicenumber     AS invoiceNumber, 
                      ticket.type              AS type,
                      ticket.startdate         AS startdate,
                      ticket.enddate           AS enddate,
                      ticket.date              AS date,
                      ticket.status            AS status,
                      ticket.totalht           AS totalht,
                      ticket.totalttc          AS totalttc,
                      ticket_line.id           AS ticket_line_id,
                      ticket_line.qty          AS ticket_line_qty,
                      ticket_line.price        AS ticket_line_price,
                      ticket_line.totalht      AS ticket_line_totalht,
                      ticket_line.totaltva     AS ticket_line_totaltva,
                      ticket_line.totalttc     AS ticket_line_totalttc,
                      ticket_line.tva          AS ticket_line_tva,
                      ticket_line.description  AS ticket_line_product
                      FROM ticket
                      INNER JOIN ticket_line ON ticket.id = ticket_line.ticket_id
                      LEFT JOIN ticket_payment ON ticket.id = ticket_payment.ticket_id
                      WHERE 
                      ticket.origin_restaurant_id = :restaurant_id AND
                      ticket.status <> :canceled AND 
                      ticket.status <> :abondon  AND
                      ticket.counted_canceled <> TRUE AND
                      ticket.date BETWEEN :startDate AND :endDate ";

        if (!is_null($cashier)) {
            $sql .= " AND ticket.operator = :operator";
        }
        if (isset($filter['startHour'])) {
            $sql .= " AND date_part('HOUR',ticket.enddate) >= :startHour ";
        }
        if (isset($filter['endHour'])) {
            $sql .= " AND date_part('HOUR',ticket.enddate) <= :endHour ";
        }
        if (isset($filter['amountMin'])) {
            $sql .= " AND ticket.totalttc >= :amountMin ";
        }
        if (isset($filter['amountMax'])) {
            $sql .= " AND ticket.totalttc <= :amountMax ";
        }
        if (isset($filter['startInvoiceNumber'])) {
            $sql .= " AND ticket.invoicenumber >= :startInvoiceNumber ";
        }
        if (isset($filter['endInvoiceNumber'])) {
            $sql .= " AND ticket.invoicenumber <= :endInvoiceNumber ";
        }
        $sql .= " ORDER by ticket.id DESC ";

        if ($offset && $limit) {
            $sql .= " LIMIT :limit OFFSET :startFrom ";
        }

        $stm = $this->em->getConnection()->prepare($sql);

        $stm->bindParam('restaurant_id', $restaurantId);
        $start = $startDate->format('Y-m-d');
        $stm->bindParam('startDate', $start);
        $end = $endDate->format('Y-m-d');
        $stm->bindParam('endDate', $end);
        $canceled = Ticket::CANCEL_STATUS_VALUE;
        $stm->bindParam('canceled', $canceled);
        $abondon = Ticket::ABONDON_STATUS_VALUE;
        $stm->bindParam('abondon', $abondon);

        if ($offset && $limit) {

            $stm->bindParam('limit', $limit, PDO::PARAM_INT);
            $stm->bindParam('startFrom', $offset, PDO::PARAM_INT);

        }


        if ($cashier) {
            $wyndId = $cashier->getWyndId();
            $stm->bindParam('operator', $wyndId);
        }
        if (isset($filter['startHour'])) {
            $stm->bindParam('startHour', $startHour);
        }
        if (isset($filter['endHour'])) {
            $stm->bindParam('endHour', $endHour);
        }
        if (isset($filter['amountMin'])) {
            $stm->bindParam('amountMin', $amountMin);
        }
        if (isset($filter['amountMax'])) {
            $stm->bindParam('amountMax', $amountMax);
        }
        if (isset($filter['startInvoiceNumber'])) {
            $stm->bindParam('startInvoiceNumber', $invoiceFrom);
        }
        if (isset($filter['endInvoiceNumber'])) {
            $stm->bindParam('endInvoiceNumber', $invoiceTo);
        }

        $stm->execute();
        $result = $stm->fetchAll();
        $output = $this->serializeList($result, $filter);
        $output['filter'] = $filter;

        return $output;
    }

    public function getTicketListV2($filter, $page = null, $limit = null)
    {

        $restaurantId= array_key_exists('restaurantId', $filter) ? $filter['restaurantId'] :
            $this->restaurantService->getCurrentRestaurant()->getId();

        $startHour = array_key_exists('startHour', $filter) ? $filter['startHour'] : null;
        if($startHour >=24){
            $startHour=$startHour-24;
        }
        $endHour = array_key_exists('endHour', $filter) ? $filter['endHour'] : null;
        if($endHour >=24){
            $endHour=$endHour-24;
        }
        $startDate = array_key_exists('startDate', $filter) ? $filter['startDate'] : null;
        $endDate = array_key_exists('endDate', $filter) ? $filter['endDate'] : null;
        $tmpDateStart = clone new \DateTime($startDate->format('Y-m-d'));
        $tmpDateStart->setTime($startHour, 0, 0);
        $tmpDateEnd = clone new \DateTime($endDate->format('Y-m-d'));
        $tmpDateEnd->setTime($endHour, 0, 0);
        $cashier = array_key_exists('cashier', $filter) ? $filter['cashier'] : null;
        $invoiceFrom = array_key_exists('startInvoiceNumber', $filter) ? $filter['startInvoiceNumber'] : null;
        $invoiceTo = array_key_exists('endInvoiceNumber', $filter) ? $filter['endInvoiceNumber'] : null;
        $soldingCanals = array_key_exists('solding_canal', $filter) ? $filter['solding_canal'] : null;
        $paymentMethods = array_key_exists('paymentMethod', $filter) ? $filter['paymentMethod'] : null;
        $amountMin = array_key_exists('amountMin', $filter) ? $filter['amountMin'] : null;
        $amountMax = array_key_exists('amountMax', $filter) ? $filter['amountMax'] : null;

        $query = $this->em->getRepository(Ticket::class)->createQueryBuilder('t')
            ->addSelect('lines','payments', 'interventions', 'emp.firstName', 'emp.lastName')
            ->leftJoin('t.lines', 'lines')
            ->leftJoin(
                Employee::class,
                'emp',
                'WITH',
                'emp.wyndId = t.operator AND :restaurant MEMBER OF emp.eligibleRestaurants'
            )
            ->leftJoin('t.payments', 'payments')
            ->leftJoin('t.interventions', 'interventions')
            ->andWhere('t.date BETWEEN :startDate AND :endDate')
            ->andWhere('t.originRestaurant = :restaurant')
            ->andWhere('t.status != :canceled')
            ->andWhere('t.status != :abondon')
            ->andWhere('t.countedCanceled != TRUE')
            ->andWhere('lines.status != :canceled')
            ->andWhere('lines.status != :abondon')
            ->andWhere('lines.countedCanceled != TRUE')
            ->andWhere('lines.originRestaurantId = :restaurant')
            ->andWhere('lines.date BETWEEN :startDate AND :endDate')
            ->setParameter('restaurant', $restaurantId)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->setParameter('canceled', Ticket::CANCEL_STATUS_VALUE)
            ->setParameter('abondon', Ticket::ABONDON_STATUS_VALUE);

        if (!is_null($cashier)) {
            $query->andWhere('t.operator = :cashier')
                ->setParameter('cashier', $cashier->getWyndId());
            $filter['cashierId'] = $cashier->getId();
        }
        if (isset($filter['startHour'])) {
            $query->andWhere('(t.deliveryTime) >= :startHour')
                ->setParameter('startHour', $tmpDateStart);
        }
        if (isset($filter['endHour'])) {
            $query->andWhere('(t.deliveryTime) <= :endHour')
                ->setParameter('endHour', $tmpDateEnd);
        }
        if (isset($filter['amountMin'])) {
            $query->andWhere('t.totalTTC >= :amountMin')
                ->setParameter('amountMin', $amountMin);
        }
        if (isset($filter['amountMax'])) {
            $query->andWhere('t.totalTTC <= :amountMax')
                ->setParameter('amountMax', $amountMax);
        }
        if (isset($filter['startInvoiceNumber'])) {
            $query->andWhere('t.invoiceNumber >= :startInvoiceNumber')
                ->setParameter('startInvoiceNumber', $invoiceFrom);
        }
        if (isset($filter['endInvoiceNumber'])) {
            $query->andWhere('t.invoiceNumber <= :endInvoiceNumber')
                ->setParameter('endInvoiceNumber', $invoiceTo);
        }

        if (array_key_exists('invoiceCancelled', $filter)) {
            $query->andWhere('t.invoiceCancelled =:cancelled')
                ->setParameter('cancelled', $filter['invoiceCancelled'] );
        }


        if ($soldingCanals && !empty($soldingCanals)) {
            $orX = $query->expr()->orX();
            foreach ($soldingCanals as $canal) {
                switch (strtoupper($canal)) {
                    case "TAKEOUT":
                        $orX->add(
                            "LOWER(t.origin) = 'pos' AND LOWER(t.destination)='takeout'",
                            "LOWER(t.origin) = 'null' AND LOWER(t.destination)='take out'",
                            "LOWER(t.origin) = '' AND LOWER(t.destination)='take out'"
                        );
                        break;
                    case "EATIN":
                        $orX->add(
                            "LOWER(t.origin) = 'pos' AND LOWER(t.destination)='eatin'",
                            "LOWER(t.origin) = 'null' AND LOWER(t.destination)='take in'",
                            "LOWER(t.origin) = 'null' AND LOWER(t.destination)='null'"
                        );
                        break;
                    case "DRIVE":
                        $orX->add(
                            "LOWER(t.origin) = 'drivethru' AND LOWER(t.destination)='drivethru'",
                            "LOWER(t.origin) = 'null' AND LOWER(t.destination)='drive'",
                            "LOWER(t.origin) = '' AND LOWER(t.destination)='drive'"
                        );
                        $orX->add("LOWER(t.origin) = 'myquick' AND LOWER(t.destination)='mqdrive'");
                        $orX->add("LOWER(t.origin) = 'myquick' AND LOWER(t.destination)='mqcurbside'");
                        break;
                    case "DELIVERY":
                        $orX->add("LOWER(t.origin) = 'pos' AND LOWER(t.destination)='delivery'");

                        $orX->add("LOWER(t.origin) = 'myquick' AND LOWER(t.destination)='atoubereats'");

                        $orX->add("LOWER(t.origin) = 'myquick' AND LOWER(t.destination)='atodeliveroo'");

                        $orX->add("LOWER(t.origin) = 'myquick' AND LOWER(t.destination)='atotakeaway'");

                        $orX->add( "LOWER(t.origin) = 'myquick' AND LOWER(t.destination)='atohellougo'");

                        $orX->add( "LOWER(t.origin) = 'myquick' AND LOWER(t.destination)='atoeasy2eat'");

                        $orX->add("LOWER(t.origin) = 'myquick' AND LOWER(t.destination)='atogoosty'" );

                        $orX->add("LOWER(t.origin) = 'myquick' AND LOWER(t.destination)='atowolt'" );
                        break;
                    case "KIOSKIN":
                        $orX->add("LOWER(t.origin) = 'kiosk' AND LOWER(t.destination)='eatin'");
                        break;
                    case "KIOSKOUT":
                        $orX->add("LOWER(t.origin) = 'kiosk' AND LOWER(t.destination)='takeout'");
                        break;
                    case "E_ORDERING_IN":
                        $orX->add("LOWER(t.origin) = 'myquick' AND LOWER(t.destination)='myquickeatin'");
                        break;
                    case "E_ORDERING_OUT":
                        $orX->add("LOWER(t.origin) = 'myquick' AND LOWER(t.destination)='myquicktakeout'");
                        break;

                }
            }
            $query->andWhere($orX);
        }
        if ($paymentMethods && !$paymentMethods->isEmpty()) {
            $paymentIds = array();
            $filter['paymentMethodIds'] = array();
            foreach ($paymentMethods as $paymentMethod) {

                if ($paymentMethod->getValue() && array_key_exists('id', $paymentMethod->getValue())) {
                    $paymentIds[] = $paymentMethod->getValue()['id'];
                } else {
                    switch ($paymentMethod->getType()) {
                        case PaymentMethod::REAL_CASH_TYPE:
                            $paymentIds[] = TicketPayment::REAL_CASH;
                            break;
                        case PaymentMethod::CHECK_QUICK_TYPE:
                            $paymentIds[] = TicketPayment::CHECK_QUICK;
                            break;
                    }
                }
                $filter['paymentMethodIds'][] = $paymentMethod->getId();
            }
            $query->leftJoin('t.payments', 'p')
                ->andWhere("p.idPayment IN (:paymentsIds)")
                ->setParameter('paymentsIds', $paymentIds);
        }
        $query->orderBy('t.startDate', 'DESC');

        if (!is_null($page) && !is_null($limit)) {
            $paginator = $this->getPaginator();
            $result = $paginator->paginate(
                $query, /* query NOT result */
                $page/*page number*/,
                $limit/*limit per page*/
            );
            $result->setParam('filter', $filter);
        } else {
            $result = $query->getQuery()->getResult();
        }

        return $result;
    }

    public function serializeList($data, $filter)
    {
        $list = array();
        foreach ($data as $element) {
            if (is_null(Utilities::searchByKeyValue($list, 'id', $element['id']))) {
                $list[$element['id']] = $element['id'];
            }
        }

        return $list;
    }


    public function findCashier($wyndId)
    {
        $result = $this->em->getRepository('Staff:Employee')->findBy(array('wyndId' => $wyndId));
        if (is_null($result)) {
            $output['name'] = "";
            $output['matricule'] = "";
        } else {
            $output['name'] = $result[0]->getFirstName()." ".$result[0]->getLastName();
            $output['matricule'] = $result[0]->getGlobalEmployeeID();
        }

        return $output;
    }

    /**
     * @param $filter
     * @return bool|string
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Exception
     */
    public function getTicketsCount($filter)
    {

        $restaurantId = $this->restaurantService->getCurrentRestaurant()->getId();
        $startHour = (is_null($filter['startHour'])) ? 0 : $filter['startHour'];
        $endHour = (is_null($filter['endHour'])) ? 23 : $filter['endHour'];
        $startDate = $filter['startDate'];
        $endDate = $filter['endDate'];
        $cashier = $filter['cashier'];
        $invoiceFrom = $filter['startInvoiceNumber'];
        $invoiceTo = $filter['endInvoiceNumber'];
        $soldingCanals = $filter['solding_canal'];
        $paymentMethods = $filter['paymentMethod'];
        $amountMin = $filter['amountMin'];
        $amountMax = $filter['amountMax'];

        $sql = "SELECT count(*)
                      FROM ticket
                      WHERE 
                      ticket.origin_restaurant_id = :restaurant_id AND
                      ticket.status <> :canceled AND 
                      ticket.status <> :abondon  AND
                      ticket.counted_canceled <> TRUE AND
                      ticket.date BETWEEN :startDate AND :endDate ";

        if (!is_null($cashier)) {
            $sql .= " AND ticket.operator = :operator";
        }
        if (isset($filter['startHour'])) {
            $sql .= " AND date_part('HOUR',ticket.enddate) >= :startHour ";
        }
        if (isset($filter['endHour'])) {
            $sql .= " AND date_part('HOUR',ticket.enddate) <= :endHour ";
        }
        if (isset($filter['amountMin'])) {
            $sql .= " AND ticket.totalttc >= :amountMin ";
        }
        if (isset($filter['amountMax'])) {
            $sql .= " AND ticket.totalttc <= :amountMax ";
        }
        if (isset($filter['startInvoiceNumber'])) {
            $sql .= " AND ticket.invoicenumber >= :startInvoiceNumber ";
        }
        if (isset($filter['endInvoiceNumber'])) {
            $sql .= " AND ticket.invoicenumber <= :endInvoiceNumber ";
        }
        $stm = $this->em->getConnection()->prepare($sql);

        $stm->bindParam('restaurant_id', $restaurantId);
        $start = $startDate->format('Y-m-d');
        $stm->bindParam('startDate', $start);
        $end = $endDate->format('Y-m-d');
        $stm->bindParam('endDate', $end);
        $canceled = Ticket::CANCEL_STATUS_VALUE;
        $stm->bindParam('canceled', $canceled);
        $abondon = Ticket::ABONDON_STATUS_VALUE;
        $stm->bindParam('abondon', $abondon);

        if ($cashier) {
            $wyndId = $cashier->getWyndId();
            $stm->bindParam('operator', $wyndId);
        }
        if (isset($filter['startHour'])) {
            $stm->bindParam('startHour', $startHour);
        }
        if (isset($filter['endHour'])) {
            $stm->bindParam('endHour', $endHour);
        }
        if (isset($filter['amountMin'])) {
            $stm->bindParam('amountMin', $amountMin);
        }
        if (isset($filter['amountMax'])) {
            $stm->bindParam('amountMax', $amountMax);
        }
        if (isset($filter['startInvoiceNumber'])) {
            $stm->bindParam('startInvoiceNumber', $invoiceFrom);
        }
        if (isset($filter['endInvoiceNumber'])) {
            $stm->bindParam('endInvoiceNumber', $invoiceTo);
        }
        $stm->execute();

        return $stm->fetchColumn(0);
    }

}