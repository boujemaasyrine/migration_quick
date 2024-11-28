<?php

namespace AppBundle\Financial\Service;

use AppBundle\Administration\Entity\Parameter;
use AppBundle\Financial\Entity\WithdrawalTmp;
use AppBundle\Financial\Repository\WithdrawalTmpRepository;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Staff\Entity\Employee;
use Doctrine\ORM\EntityManager;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Translation\Translator;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Translation\DataCollectorTranslator;
use Httpful\Request;
use Symfony\Component\Process\Process;
use Symfony\Component\DependencyInjection\Container;


/**
 * Ce service assuré la synchronisation des prélèvements entre api wynd et l'application.
 * Class WithdrawalSynchronization
 * @package AppBundle\Financial\Service
 */
class WithdrawalSynchronizationService
{


    const MEMBER_NOT_EXIST = 2;
    const RESPONSIBLE_NOT_EXIST = 3;
    const WITHDRAWAL_Exist = 4;
    const WITHDRAWAL_CREATED = 5;
    const WITHDRAWAL_IS_VALIDATED = 6;
    const JSON_EMPLOYEE_ID = "employeeID";
    const JSON_EMPLOYEE_NAME = "employeeName";
    const JSON_MANEGER_ID = "managerID";
    const JSON_MANEGER_NAME = "managerName";
    const JSON_AMOUNT = "amount";
    const JSON_TIME = "time";
    const JSON_PETTY_CASH_NAME = "pettyCashName";
    const JSON_PETTY_CASH_ID = "pettyCashID";

    /**
     * @var EntityManager $em
     */
    private $em;
    /**
     * @var Logger $logger
     */
    private $logger;
    /**
     * @var Session $session
     */
    private $session;
    /**
     * @var DataCollectorTranslator
     */
    private $translator;
    /**
     * @var WithdrawalTmpRepository $wTmpRepos
     */
    private $wTmpRepos;
    /**
     * @var Container
     */
    private $container;

    /**
     * WithdrawalSynchronizationService constructor.
     *
     * @param EntityManager $em
     * @param Translator $translator
     * @param Logger $logger
     * @param Session $session
     * @param Container $container
     */

    public function __construct(
        EntityManager $em, 
        Translator $translator, 
        Logger $logger, 
        Session $session, 
        Container $container
    )
    {
        $this->em = $em;
        $this->logger = $logger;
        $this->session = $session;
        $this->translator = $translator;
        $this->container = $container;
        $this->wTmpRepos = $this->em
            ->getRepository(WithdrawalTmp::class);
    }

    public function synchApiWithdrawalTmp(Restaurant $restaurant, $startDate = null, $endDate = null, $fromCommand = false,$asynch=false)
    {
        $data = $this->getWithdrawalsFromApi($restaurant, $startDate, $endDate, $fromCommand,$asynch);
        if ($data != false) {
            $this->importWithdrawalsIntoDB($restaurant, $data);
        }
    }

    /**
     * Récupérer les nouveaux prélèvements à partir d'API.
     * @param Restaurant $restaurant
     * @param null $startDate
     * @param null $endDate
     * @param $fromCommand
     * @return bool|void
     */
    public function getWithdrawalsFromApi(Restaurant $restaurant, $startDate = null, $endDate = null, $fromCommand=false,$asynch=false)
    {
        try {
            $apiUser = $this->em->getRepository(Parameter::class)->findOneBy(array(
                "type" => Parameter::WYND_USER,
                "originRestaurant" => $restaurant
            ));
            if ($apiUser == null) {
                $this->logger->addAlert('Parameter login not found for the restaurant: ' . $restaurant->getCode(), ['ImportWithdrawal']);
                return;
            }
            $secretKey = $this->em->getRepository(Parameter::class)->findOneBy(array(
                "type" => Parameter::SECRET_KEY,
                "originRestaurant" => $restaurant
            ));
            if ($secretKey == null) {
                $this->logger->addAlert('Parameter secret key not found for the restaurant: ' . $restaurant->getCode(), ['ImportWithdrawal']);
                return;
            }
            $this->logger->addInfo(
                'Processing import withdrawal for restaurant ' . $restaurant->getName(),
                ['import.withdrawal']
            );
            $wyndActive = $this->em->getRepository(Parameter::class)->findParameterByTypeAndRestaurant(
                Parameter::WYND_ACTIVE,
                $restaurant
            );
            if ($wyndActive->getValue()) {
                $url = $this->em->getRepository(Parameter::class)->findOneBy(
                    array(
                        "originRestaurant" => $restaurant,
                        "type" => Parameter::WITHDRAWAL_URL_TYPE,
                    )
                );
                if (!$url) {
                    $this->logger->addInfo('withdrawal Url not found', ['ImportWithdrawal']);

                    return;
                }

                if ($startDate == null && $endDate == null) {
                    $fiscalDate = $this->getContainer()->get('administrative.closing.service')->getLastWorkingEndDate($restaurant);
                    $fiscalDate->setTime(0, 0, 0);
                    $today = new \DateTime();
                    $today->setTime(0, 0, 0);
                    $diff = $today->diff($fiscalDate);
                    $diffDays = (integer)$diff->format("%R%a"); // Extract days count in interval

                    if ($diffDays == 0) {
                        $startDate = $today;
                        $endDate = new \DateTime();
                    } else {
                        $startDate = $fiscalDate;
                        $endDate = clone $startDate;
                        $endDate = $endDate->add(new \DateInterval('P1D'));
                    }

                }
                $supportedFormat = 'Y-m-d';
                $url = $url->getValue();
                $url .= "?date_start=" . $startDate->format($supportedFormat) . "&" . "date_end=" . $endDate->format(
                        $supportedFormat
                    );
                if ($fromCommand) {
                    echo $url;
                }
                $this->logger->addInfo('Processing import withdrawal : ' . $url, ['ImportWithdrawal']);
                $t1 = time();
                if($asynch==true){
                    $k="'".$url."'";
                    $commande=" curl -X GET " .$k ."  -H 'Accept: application/json'  -H 'Api-Hash: ".sha1($secretKey->getValue())."' -H 'Api-User: ".$apiUser->getValue()."'";

                    $process=new Process($commande);
                    $process->setTimeout(30);
                    $process->start();

                  return;
                }
                $data = Request::get($url)
                    ->addHeaders(
                        array(
                            'Api-User' => $apiUser->getValue(),
                            'Api-Hash' => sha1($secretKey->getValue()),
                        )
                    )
                    ->expectsJson()
                    ->send();
                $t2 = time();
                $data = $data->body;

                if ($data->result == 'success') {
                    $this->logger->addInfo("Data received with success in time= " . ($t2 - $t1) . " seconds", ['ImportWithdrawal']);
                    $this->logger->addInfo(
                        'Processing import withdrawal terminated with success. ',
                        ['ImportWithdrawal']
                    );
                    return $data;
                } else {
                    if ($fromCommand) {
                        echo "ERROR \n";
                    }
                    $this->logger->addError('Processing import withdrawal failed.', ['ImportWithdrawal']);
                }
            } else {
                $this->logger->addInfo('Aloha POS is disabled in this restaurant.', ['ImportWithdrawal']);
            }
        } catch (\Exception $e) {
            if ($fromCommand) {
                echo "Exception " . $e->getMessage() . "\n";
            }
            $this->logger->addError('Importing withdrawal exception : ' . $e->getMessage(), ['ImportWithdrawal']);
        }
        return false;
    }

    /**
     * Importer les prélèvements dans une table temporaire
     * @param Restaurant $restaurant
     * @param $data
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function importWithdrawalsIntoDB(Restaurant $restaurant, $data)
    {
        $array_data = json_decode(json_encode($data), true);
        foreach ($array_data['data'] as $d) {
            if ($this->isWithdrawalType($d)) {
                if (!$this->wTmpRepos->isExist($restaurant, $d)) {
                    $this->addLogInfoAfterCreateNewWithdrawalTmp(
                        $this->wTmpRepos->createWithdrawalTmp($restaurant, $d),
                        $d
                    );
                } else {
                    $this->addLogInfoAfterCreateNewWithdrawalTmp(self::WITHDRAWAL_Exist, $d);
                }
            }

        }
        $this->em->flush();
    }

    /**
     * Vérifier si les données est de type prélèvement
     * type prélèvement si pettyCashID=2
     * @param $data
     * @return bool
     */
    private function isWithdrawalType($data)
    {           
        $withdrawal_petty_cash_id = $this->container->getParameter('withdrawal_petty_cash_id');
        extract($data, EXTR_OVERWRITE);
        $dataType = (int)${self::JSON_PETTY_CASH_ID};
        if ($dataType == $withdrawal_petty_cash_id) {
            return true;
        }
        return false;
    }

    /**
     * Ajoute dans le log des informations sur la création d'un nouveau prélèvement(dans la table temporaire)
     * @param $result
     * @param $data
     */
    private function addLogInfoAfterCreateNewWithdrawalTmp($result, $data)
    {
        extract($data, EXTR_OVERWRITE);
        switch ($result) {
            case self::WITHDRAWAL_CREATED:
                $this->logger->addInfo(
                    sprintf("Le prélèvement crée avec succès,
                           (pettyCashName= %s,time= %s,employeeName= %s,managerName=%s,amount=%s)",
                        ${self::JSON_PETTY_CASH_NAME},
                        ${self::JSON_TIME},
                        ${self::JSON_EMPLOYEE_NAME},
                        ${self::JSON_MANEGER_NAME},
                        ${self::JSON_AMOUNT}
                    ),
                    ['CreateNewWithdrawal']);
                break;
            case self::MEMBER_NOT_EXIST:
                $this->logger->addAlert(
                    sprintf("Problème lors de creation d'un prélèvement, L'équipière n'est pas existé.
                           (pettyCashName= %s,time= %s,employeeName= %s,managerName=%s,amount=%s)",
                        ${self::JSON_PETTY_CASH_NAME},
                        ${self::JSON_TIME},
                        ${self::JSON_EMPLOYEE_NAME},
                        ${self::JSON_MANEGER_NAME},
                        ${self::JSON_AMOUNT}
                    ),
                    ['CreateNewWithdrawal']
                );
                break;
            case self::RESPONSIBLE_NOT_EXIST:
                $this->logger->addAlert(
                    sprintf("Problème lors de création d'un prélèvement, le responsable n'est pas existé.
                           (pettyCashName= %s,time= %s,employeeName= %s,managerName=%s,amount=%s)",
                        ${self::JSON_PETTY_CASH_NAME},
                        ${self::JSON_TIME},
                        ${self::JSON_EMPLOYEE_NAME},
                        ${self::JSON_MANEGER_NAME},
                        ${self::JSON_AMOUNT}
                    ),
                    ['CreateNewWithdrawal']
                );
                break;
            case self::WITHDRAWAL_Exist:
                $this->logger->addAlert(
                    sprintf("Problème lors de création d'un prélèvement, prélèvement déjà existé.
                           (pettyCashName= %s,time= %s,employeeName=%s,managerName=%s,amount=%s)",
                        ${self::JSON_PETTY_CASH_NAME},
                        ${self::JSON_TIME},
                        ${self::JSON_EMPLOYEE_NAME},
                        ${self::JSON_MANEGER_NAME},
                        ${self::JSON_AMOUNT}
                    ),
                    ['CreateNewWithdrawal']
                );
                break;
        }
    }

    private function setUpdateError()
    {
        $this->session->getFlashBag()->set('error', $this->translator->trans('withdrawal.entry.update_error'));
    }

    /**
     * Retourne les prélèvements non-valide entre startdate et endDate.
     * @param Employee $responsable
     * @param Restaurant $restaurant
     * @param $startDate
     * @param $endDate
     * @return mixed
     */
    public function getInvalidWithdrawalsTmp(Employee $responsible = null, Restaurant $restaurant, $startDate = null, $endDate = null)
    {
        return $this->wTmpRepos
            ->getWithdrawalsTmp($restaurant, $responsible, $startDate, $endDate, false);
    }

    /**
     * Retourne la date de dernière synchronisation des prélèvements avec l'api.
     * @param Restaurant $restaurant
     * @return bool
     */
    public function getLatestUpdateDateFromApi(Restaurant $restaurant)
    {
        return $this->wTmpRepos->getLatestUpdateDateFromApi($restaurant);
    }

    public function setLogger(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Vérifier si l'équipier avoir encore des prélèvement temporaire non-valide ou non
     * @param Restaurant $restaurant
     * @param $userID
     * @param $date
     * @return bool
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function hasInvalidWithdrawalsTmp(Restaurant $restaurant, $userID, $date)
    {
        return $this->wTmpRepos->hasInvalidWithdrawalsTmp($restaurant, $userID, $date);
    }
}

