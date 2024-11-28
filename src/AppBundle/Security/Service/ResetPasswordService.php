<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 17/06/2016
 * Time: 15:58
 */

namespace AppBundle\Security\Service;

use AppBundle\Security\Entity\User;
use AppBundle\Staff\Entity\Employee;
use AppBundle\ToolBox\Utils\Utilities;
use Doctrine\ORM\EntityManager;
use Symfony\Bridge\Twig\TwigEngine;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoder;

class ResetPasswordService
{
    /**
     * @var UserPasswordEncoder
     */
    private $encoder;

    private $em;

    /**
     * @var \Swift_Mailer
     */
    private $mailer;

    /**
     * @var TwigEngine
     */
    private $twig;

    private $mailerUser;

    public function __construct(
        EntityManager $entityManager,
        UserPasswordEncoder $encoder,
        \Swift_Mailer $mail,
        TwigEngine $twigEngine,
        $mailerUser
    ) {
        $this->encoder = $encoder;
        $this->em = $entityManager;
        $this->mailer = $mail;
        $this->twig = $twigEngine;
        $this->mailerUser = $mailerUser;
    }


    public function resetPassword(Employee $user)
    {
        $newPw = Utilities::generateRandomString(8);
        $newPwEncoded = $this->encoder->encodePassword($user, $newPw);
        $user->setPassword($newPwEncoded);
        $user->setFirstConnection(false);
        $this->em->flush();

        return $newPw;
    }

    public function sendUserNewPassword(Employee $user, $password)
    {

        $mailBody = $this->twig->render(
            "@Security/password_mail.html.twig",
            array(
                'passowrd' => $password,
                'user' => $user,
            )
        );
        try {
            $mail = \Swift_Message::newInstance()
                ->setSubject("[QUICK] Réinitialisation du mot votre mot de passe")
                ->setFrom(array($this->mailerUser))
                ->setTo(array($user->getEmail()))
                ->setBody($mailBody, 'text/html');
            $this->mailer->send($mail);
        } catch (\Swift_RfcComplianceException $e) {
            return false;
        } catch (\Exception $ee) {
            return false;
        }

        return true;
    }
}
