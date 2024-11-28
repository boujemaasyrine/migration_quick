<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 23/06/2016
 * Time: 14:20
 */

namespace AppBundle\Supervision\Service\General;

use AppBundle\Security\Entity\Role;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\TwigBundle\TwigEngine;
use Symfony\Component\Translation\Translator;

class NotificationService
{

    private $em;
    private $twig;
    private $mailer;
    private $mailerUser;
    private $translator;

    public function __construct(
        EntityManager $em,
        TwigEngine $twig,
        \Swift_Mailer $mailer,
        $mailerUser,
        Translator $translator
    ) {
        $this->em = $em;
        $this->twig = $twig;
        $this->mailer = $mailer;
        $this->mailerUser = $mailerUser;
        $this->translator = $translator;
    }

    public function notifyByMailMissingPlu($missingPluS)
    {

        $restaurants = array();
        foreach ($missingPluS as $plu) {
            foreach ($plu->getRestaurants() as $restaurant) {
                $restaurants[] = $restaurant->getName();
            }
        }
        $uniqueRestaurants = array_unique($restaurants);

        try {
            $body = $this->twig->render(
                "@App/mails/Missing_plu_notification.html.twig",
                array(
                    'missingPluS' => $missingPluS,
                    'restaurants' => $uniqueRestaurants,
                )
            );

            $coordinationUsersEmails = $this->em->getRepository('AppBundle:Security\User')->findUsersByRoleLabel(
                Role::ROLE_COORDINATION
            );

            $to = array();
            foreach ($coordinationUsersEmails as $email) {
                $to[] = $email['mail'];
            }
            $mail = \Swift_Message::newInstance()
                ->setSubject($this->translator->trans('missing_pluS.subject_mail'))
                ->setFrom(array($this->mailerUser))
                ->setTo($to)
                ->setBody($body, 'text/html');

            $this->mailer->send($mail);

            foreach ($missingPluS as $plu) {
                $plu->setNotified(true);
            }
            $this->em->flush();
            $result = [
                'pluS' => count($missingPluS),
                'restaurants' => count($uniqueRestaurants),
            ];

            return $result;
        } catch (\Exception $e) {
            return false;
        }
    }
}
