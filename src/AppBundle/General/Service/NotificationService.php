<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 16/05/2016
 * Time: 11:09
 */

namespace AppBundle\General\Service;

use AppBundle\General\Entity\Notification;
use AppBundle\General\Entity\NotificationInstance;
use AppBundle\General\Service\Remote\General\MissingPluNotification;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Staff\Entity\Employee;
use AppBundle\ToolBox\Utils\DateUtilities;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Symfony\Bridge\Twig\TwigEngine;
use Symfony\Component\Translation\Translator;

class NotificationService
{

    private $em;
    private $translator;
    private $mailer;
    private $missingPluService;
    private $twig;
    private $mailUser;

    public function __construct(
        EntityManager $em,
        Translator $translator,
        \Swift_Mailer $mailer,
        MissingPluNotification $missingPluRemoteService,
        TwigEngine $twig,
        $mailUser
    ) {
        $this->em = $em;
        $this->translator = $translator;
        $this->mailer=$mailer;
        $this->missingPluService = $missingPluRemoteService;
        $this->twig=$twig;
        $this->mailUser = $mailUser;
    }

    /**
     * @param $type
     * @param ArrayCollection [Role] $roles
     */
    public function addNotificationByRoles($type, $roles = null)
    {
        $users = array();
        if ($roles != null) {
            foreach ($roles as $role) {
                array_push(
                    $users,
                    $this->em->getRepository('Staff:Employee')->findByRole()
                );
            }
        }

        $this->addNotificationByUsers($type, $users);
    }

    /**
     * @param $type
     * @param $data
     *                        MetaData contains details about notification: array with some keys as keywords
     *                        Key "parameters": an array for parameters to generate the route destination
     *                        Key "ModalId": Id of an object that allows to show a modal when redirecting to notification page, the case of a rejected order.
     *                        Key "idIsAParameter": when this value is set to true, the id of the notification is pushed to parameters array for route generation
     * @param $route
     * @param ArrayCollection [Employee] $users
     */
    public function addNotificationByUsers(
        $type,
        $restaurant,
        $data = null,
        $route = null,
        $users = null
    ) {

        $notification = new Notification();
        $notification
            ->setType($type)
            ->setData($data)
            ->setRoute($route)
            ->setOriginRestaurant($restaurant);

        $this->em->persist($notification);

        if (!$users) {
            $users = $this->em->getRepository('Staff:Employee')
                ->createQueryBuilder('e')
                ->where(':restaurant MEMBER OF e.eligibleRestaurants')
                ->setParameter('restaurant', $restaurant)
                ->getQuery()->getResult();
        }

        foreach ($users as $user) {
            $this->setNewNotification($notification, $user);
        }
        $this->em->flush();
    }

    public function setNewNotification($notification, Employee $user)
    {

        $notificationInstance = new NotificationInstance();
        $notificationInstance
            ->setNotification($notification)
            ->setEmployee($user)
            ->setSeen(false);

        $this->em->persist($notificationInstance);
    }

    /**
     * @param NotificationInstance $notificationInstance
     *
     * @return ArrayCollection $parameters
     */
    public function accessNotification($notificationInstance)
    {
        $notificationInstance->setSeen(true);
        $parameters = array();
        if (array_key_exists(
            'parameters',
            $notificationInstance->getNotification()->getData()
        )
        ) {
            $parameters = $notificationInstance->getNotification()->getData(
            )['parameters'];
        }
        if (array_key_exists(
                'idIsAParameter',
                $notificationInstance->getNotification()->getData()
            )
            and $notificationInstance->getNotification()->getData(
            )['idIsAParameter']
        ) {
            $parameters['instance'] = $notificationInstance->getId();
        }
        $this->em->persist($notificationInstance);
        $this->em->flush();

        return $parameters;
    }

    public function generatePluSNotification($arrayPLUs, Restaurant $restaurant)
    {
        $allPlus = $this->em->getRepository('Merchandise:ProductSold')
            ->retrieveAllPlus($restaurant);
        $newPluS = array_diff($arrayPLUs, $allPlus);
        $newUniquePlus = array_unique($newPluS);
        if (count($newUniquePlus) > 0) {
            $data = [
                'pluS'           => $newUniquePlus,
                'idIsAParameter' => true,
            ];
            $this->addNotificationByUsers(
                Notification::NONEXISTENT_PLU_CODE_NOTIFICATION,
                $restaurant,
                $data,
                Notification::MISSING_PLUS_PATH
            );
            $this->missingPluService->saveMissingPlu(
                $newUniquePlus,
                $restaurant
            );
        }
    }

    public function generatePreviousLossNotification(
        $type,
        $date,
        $notificationType,
        $pathParams
    ) {
        $data = [
            'type'       => $type,
            'date'       => $date,
            'parameters' => [
                'type' => $pathParams,
            ],
        ];

        $this->addNotificationByUsers(
            $notificationType,
            $data,
            Notification::PREVIOUS_LOSS_PATH
        );
    }

    public function serializeNotifications($notifications)
    {
        $result = [];
        foreach ($notifications as $n) {
            /**
             * @var NotificationInstance $n
             */
            switch ($n->getNotification()->getType()) {
                case Notification::PREPARED_NOT_SEND_ORDER_NOTIFICATION:
                    $type = $this->translator->trans(
                        'title.not_sent_order',
                        [],
                        'notifications'
                    );
                    $message = $this->translator->trans(
                        'body.prepared',
                        [
                            '%numero%' => $n->getNotification()->getData(
                            )['orderNum'],
                        ],
                        'notifications'
                    );
                    $icon = "fa-file-text-o";
                    break;
                case Notification::REJECTED_ORDER_NOTIFICATION:
                    $type = $this->translator->trans(
                        'title.rejected_order',
                        [],
                        'notifications'
                    );
                    $message = $this->translator->trans(
                        'body.rejected',
                        [
                            '%date%' => $n->getNotification()->getData(
                            )['orderDate'],
                        ],
                        'notifications'
                    );
                    $icon = "fa-file-text-o";
                    break;
                case Notification::SCHEDULE_DELIVERY_CHANGED_NOTIFICATION:
                    $type = $this->translator->trans(
                        'title.schedule_delivery_changed',
                        [],
                        'notifications'
                    );
                    $message = $this->translator->trans(
                        'body.schedule',
                        [
                            '%user%'     => $n->getNotification()->getData(
                            )['user'],
                            '%supplier%' => $n->getNotification()->getData(
                            )['supplier'],
                        ],
                        'notifications'
                    );
                    $icon = "fa-truck";
                    break;
                case Notification::NONEXISTENT_PLU_CODE_NOTIFICATION:
                    $type = $this->translator->trans(
                        'title.missing_pluS',
                        [],
                        'notifications'
                    );
                    $message = $this->translator->trans(
                        'body.pluS',
                        [
                        ],
                        'notifications'
                    );
                    foreach ($n->getNotification()->getData()['pluS'] as $plu) {
                        $message .= ' '.$plu;
                    }
                    $icon = "fa-shopping-cart";
                    break;
                case Notification::NOT_DELIVERED_ORDER_NOTIFICATION:
                    $type = $this->translator->trans(
                        'title.not_delivered_order',
                        [],
                        'notifications'
                    );
                    $message = $this->translator->trans(
                        'body.not_delivered',
                        [
                            '%numero%' => $n->getNotification()->getData(
                            )['orderNum'],
                        ],
                        'notifications'
                    );
                    $icon = "fa-truck";
                    break;
                case Notification::PREVIOUS_INVENTORY_LOSS_NOTIFICATION:
                    $type = $this->translator->trans(
                        'title.previous_inventory_loss',
                        [],
                        'notifications'
                    );
                    $message = $this->translator->trans(
                        'body.previous_ loss',
                        [
                            '%date%' => $n->getNotification()->getData(
                            )['date']->format('d-m-Y'),
                        ],
                        'notifications'
                    );
                    $icon = "fa-trash";
                    break;
                case Notification::PREVIOUS_SOLD_LOSS_NOTIFICATION:
                    $type = $this->translator->trans(
                        'title.previous_sold_loss',
                        [],
                        'notifications'
                    );
                    $message = $this->translator->trans(
                        'body.previous_ loss',
                        [
                            '%date%' => $n->getNotification()->getData(
                            )['date']->format('d-m-Y'),
                        ],
                        'notifications'
                    );
                    $icon = "fa-trash";
                    break;
            }

            $result[] = array(
                'id'      => $n->getId(),
                'type_'   => $n->getNotification()->getType(),
                'type'    => $type,
                'icon'    => $icon,
                'message' => $message,
                'seen'    => $n->iSeen(),
                'created' => DateUtilities::timeElapsedString(
                    $n->getNotification()->getCreatedAt()->getTimestamp()
                ),
            );
        }

        return $result;
    }


    public function notifyByMailMissingPlu($missingPlus)
    {
        $restaurants = array();

        foreach ($missingPlus as $plu) {
            foreach ($plu->getRestaurants() as $restaurant) {
                $restaurants[] = $restaurant->getName();
            }
        }

        $uniqueRestaurants = array_unique($restaurants);

        try {
            $body = $this->twig->render(
                "@Supervision/mails/Missing_plu_notification.html.twig",
                array(
                    'missingPluS' => $missingPlus,
                    'restaurants' => $uniqueRestaurants,
                )
            );


//            $coordinationUsersMails=$this->em->getRepository(Employee::class)->getCoordinationMails();


            $mail = \Swift_Message::newInstance()
                ->setSubject($this->translator->trans('missing_pluS.subject_mail',[],'supervision'))
                ->setFrom(array($this->mailUser))
                ->setTo('coordination@burgerbrands.be')
//                ->setTo($coordinationUsersMails)
                ->setBody($body, 'text/html');

            $sended=$this->mailer->send($mail);

            if($sended){
                foreach ($missingPlus as $plu) {
                    $plu->setNotified(true);
                }
                $this->em->flush();
                $result = [
                    'pluS' => count($missingPlus),
                    'restaurants' => count($uniqueRestaurants),
                ];


                return $result;


            }

            else {
                return false;
            }

        }

        catch (\Exception $e){
            echo $e->getMessage();
            return false;

        }







}

}
