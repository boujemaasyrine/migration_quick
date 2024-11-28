<?php

namespace AppBundle\Command;

use AppBundle\Administration\Entity\Action;
use AppBundle\Administration\Entity\Parameter;
use AppBundle\Administration\Entity\Procedure;
use AppBundle\Administration\Entity\ProcedureInstance;
use AppBundle\Administration\Entity\ProcedureStep;
use AppBundle\Financial\Entity\PaymentMethod;
use AppBundle\General\Entity\Notification;
use AppBundle\General\Entity\NotificationInstance;
use AppBundle\Merchandise\Entity\Product;
use AppBundle\Merchandise\Entity\ProductCategories;
use AppBundle\Merchandise\Entity\ProductPurchased;
use AppBundle\Merchandise\Entity\ProductSold;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Merchandise\Entity\Supplier;
use AppBundle\Merchandise\Entity\SupplierPlanning;
use AppBundle\Security\Entity\Role;
use AppBundle\Staff\Entity\Employee;
use AppBundle\Supervision\Entity\ProductPurchasedSupervision;
use AppBundle\Supervision\Entity\ProductSoldSupervision;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Translation\Translator;

class ImportRestaurantCommand extends ContainerAwareCommand
{
    /**
     * @var EntityManager
     */
    private $em;

    private $dataDir;

    private $logger;

    /**
     * @var Translator
     */
    private $translator;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('saas:import:restaurant')
            ->addArgument('restaurantCode', InputArgument::OPTIONAL)
            ->setDescription('Import restaurant form json file exported by a BO instance.');
    }

    /**
     * {@inheritDoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->dataDir = $this->getContainer()->getParameter('kernel.root_dir') . "/../data/import/saas/";
        $this->em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $this->logger = $this->getContainer()->get('monolog.logger.import_commands');
        $this->translator = $this->getContainer()->get("translator");
        $this->syncService = $this->getContainer()->get('sync.create.entry.service');

        parent::initialize($input, $output);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        if ($input->getArgument('restaurantCode')) {
            $restaurantCode = trim($input->getArgument('restaurantCode'));
        } else {
            $helper = $this->getHelper('question');
            $question = new Question(
                'Please enter restaurant code (found at the end of the json file name : restaurant_xxxx.json ) :'
            );
            $question->setValidator(
                function ($answer) {
                    if (!is_string($answer) || strlen($answer) < 1) {
                        throw new \RuntimeException(
                            'Please enter the restaurnat code!'
                        );
                    }
                    return trim($answer);
                }
            );
            $restaurantCode = $helper->ask($input, $output, $question);
        }
        $filename = "restaurant_" . $restaurantCode . ".json";
        $filePath = $this->dataDir . $filename;

        if (!file_exists($filePath)) {
            $output->writeln("No import file with the '" . $restaurantCode . "' restaurant code found !");

            return;
        }
        try {
            $fileData = file_get_contents($filePath);
            $restaurantData = json_decode($fileData, true);
        } catch (\Exception $e) {
            $output->writeln($e->getMessage());
            return;
        }


        /************ Start the import process *****************/
        $progress = new ProgressBar($output, 100);

        try {
            $restaurant = $this->em->getRepository(Restaurant::class)->findOneByCode($restaurantData['code']);

            if ($restaurant) {
                $output->writeln("A restaurant with the '" . $restaurantData['code'] . "' already exist : ");
                $output->writeln("Updating current restaurant...");
            } else {
                $output->writeln("====> Import restaurant started...");
                $restaurant = new Restaurant();
            }
            $progress->start();

            //Set the restaurant data
            $restaurant
                ->setName($restaurantData['name'])
                ->setCode($restaurantData['code'])
                ->setLang($restaurantData['lang'])
                ->setCustomerLang($restaurantData['customerLang'])
                ->setManager($restaurantData['manager'])
                ->setManagerEmail($restaurantData['managerEmail'])
                ->setEmail($restaurantData['email'])
                ->setManagerPhone($restaurantData['managerPhone'])
                ->setDmCf($restaurantData['dmCf'])
                ->setPhoneDmCf($restaurantData['phoneDmCf'])
                ->setAddress($restaurantData['address'])
                ->setZipCode($restaurantData['zipCode'])
                ->setCity($restaurantData['city'])
                ->setPhone($restaurantData['phone'])
                ->setBtwTva($restaurantData['btwTva'])
                ->setCompanyName($restaurantData['companyName'])
                ->setAddressCompany($restaurantData['addressCompany'])
                ->setZipCodeCompany($restaurantData['zipCodeCompany'])
                ->setCityCorrespondance($restaurantData['cityCorrespondance'])
                ->setCyFtFpLg($restaurantData['cyFtFpLg'])
                ->setCluster($restaurantData['cluster'])
                ->setType($restaurantData['type'])
                ->setActive($restaurantData['active'])
                ->setFirstOpenning(new \DateTime($restaurantData['firstOpenning']['date']))
                ->setTypeCharte($restaurantData['typeCharte'])
                ->setEft($restaurantData['EFT_ACTIVATED_TYPE']);
            $this->em->persist($restaurant);
            $this->em->flush();
            $progress->advance(3);

            //import restaurant users
            foreach ($restaurantData['users'] as $u) {
                if(empty($u['wyndId'])){
                    $user = $this->em->getRepository(Employee::class)->findOneBy(array('username' => $u['username']));
                    if($user){
                        $this->logger->info('Central user skipped beacause it exist : ', array("username"=>$u['username'],"Restaurant" => $restaurant->getName()));
                        continue;
                    }
                    $importUserName=$u['username'];
                }else{
                    $user = $this->em->getRepository(Employee::class)->findOneBy(array('username' =>  $restaurantCode."_".$u['username']));
                    $importUserName=$restaurantCode."_".$u['username'];
                }

                if (!$user) {
                    $user = new Employee();
                    $user
                        ->setUsername($importUserName)
                        ->setPassword($u['password'])
                        ->setActive($u['active'])
                        ->setFirstConnection($u['firstConnection'])
                        ->setFirstName($u['firstName'])
                        ->setLastName($u['lastName'])
                        ->setEmail($u['email'])
                        ->addEligibleRestaurant($restaurant)
                        ->setDeleted($u['deleted'])
                        ->setGlobalEmployeeID($u['globalEmployeeID'])
                        ->setFromCentral($u['fromCentral'])
                        ->setFromWynd($u['fromWynd'])
                        ->setWyndId($u['wyndId']);
                    /*foreach ($u['roles'] as $r) {
                        $role = $this->em->getRepository('Security:Role')->findOneBy(array('label' => $r));
                        if ($role) {
                            $user->addRole($role);
                            $role->addUser($user);
                        }
                    }*/

                } else {
                    if (!$user->getEligibleRestaurants()->contains($restaurant)) {
                        $user->addEligibleRestaurant($restaurant);
                    }
                }
                foreach ($u['employeeRoles'] as $r) {
                    $role = $this->em->getRepository('Security:Role')->findOneBy(array('label' => $r['label']));
                    if ($role) {
                        if (!$user->hasEmployeeRole($role)) {
                            $user->addEmployeeRole($role);
                            $role->addUser($user);
                        }
                    }
                }
                $this->em->persist($user);
            }
            $this->em->flush();
            $progress->advance(10);
            //add restaurant elligibility for central user
            $centralUsers=$this->em->getRepository(Employee::class)->createQueryBuilder('e')
                ->where("e.wyndId IS NULL")
                ->getQuery()->getResult();
            foreach ($centralUsers as $centralUser){
                if($centralUser->isSuperAdmin()){
                    if(!$centralUser->getEligibleRestaurants()->contains($restaurant)){
                        $centralUser->addEligibleRestaurant($restaurant);
                    }
                    if(!$restaurant->getEligibleUsers()->contains($centralUser)){
                        $restaurant->addEligibleUser($centralUser);
                    }
                }
            }
            $this->em->flush();

            //import procedures for restaurant
            foreach ($restaurantData['procedures'] as $pro) {

                $procedure = $this->em->getRepository(Procedure::class)->findOneBy(array(
                    'name' => $pro['name'],
                    'originRestaurant' => $restaurant
                ));
                if (!$procedure) {
                    $procedure = new Procedure();
                }
                $procedure
                    ->setName($pro['name'])
                    ->setCanBeDeleted(boolval($pro['canBeDeleted']))
                    ->setOnlyOnceAtDay(boolval($pro['onlyOnceAtDay']))
                    ->setOnlyOnceForAll(boolval($pro['onlyOnceForAll']))
                    ->setAtSameTime(boolval($pro['atSameTime']))
                    ->setAutorizeAbandon(boolval($pro['autorizeAbandon']))
                    ->setOriginRestaurant($restaurant);

                foreach ($procedure->getSteps() as $s) {
                    $this->em->remove($s);
                    $this->em->flush();
                }
                foreach ($pro['steps'] as $s) {
                    $step = new ProcedureStep();
                    $action = $this->em->getRepository(Action::class)->findOneBy(
                        array(
                            'name' => $s['action']['name']
                        )
                    );
                    if ($action) {
                        $step
                            ->setAction($action)
                            ->setOrder($s['order'])
                            ->setDeletable(boolval($s['deletable']))
                            ->setProcedure($procedure);
                        $this->em->persist($step);
                        $procedure->addStep($step);
                    } else {
                        $this->logger->info('Procedure step skipped because action doesn\'t exist : ', array("stepId" => $s['id'], "actionName" => $s['action']['name'], "Restaurant" => $restaurant->getName()));
                    }
                }

                foreach ($pro['instances'] as $ins) {
                    $instance = $this->em->getRepository(ProcedureInstance::class)->findOneBy(
                        array(
                            "importId" => $ins['id'] . "_" . $restaurantCode
                        )
                    );
                    if (!$instance) {
                        $instance = new ProcedureInstance();
                        if ($ins['user']) {
                            if(empty($ins['user']['wyndId'])){
                                $userName=$ins['user']['username'];
                            }else{
                                $userName=$restaurantCode."_".$ins['user']['username'];
                            }
                            $user = $this->em->getRepository(Employee::class)->findOneBy(
                                array(
                                    'username' => $userName
                                )
                            );
                            if ($user) {
                                $instance->setUser($user);
                            } else {
                                $this->logger->info('User not found for procedure instance : ', array("ProcedureInstanceId" => $ins['id'], "username" => $ins['user']['username'], "Restaurant" => $restaurant->getName()));
                            }
                        }
                        $createdAt = $ins['createdAt'] ? new \DateTime($ins['createdAt']['date']) : null;
                        $instance
                            ->setCurrentStep($ins['currentStep'])
                            ->setSubStep($ins['subStep'])
                            ->setStatus($ins['status'])
                            ->setCreatedAt($createdAt)
                            ->setImportId($ins['id'] . "_" . $restaurantCode);
                        $instance->setProcedure($procedure);
                        $procedure->addInstance($instance);
                        $this->em->persist($instance);
                    }

                }

                foreach ($pro['eligibleRoles'] as $r) {
                    $role = $this->em->getRepository(Role::class)->findOneBy(
                        array(
                            "label" => $r['label']
                        )
                    );
                    if ($role) {
                        if (!$procedure->getEligibleRoles() || !$procedure->getEligibleRoles()->contains($role)) {
                            $procedure->addEligibleRole($role);
                        }
                    } else {
                        $this->logger->info('Procedure Role skipped because it doesn\'t exist : ', array("procedureId" => $pro['id'], "roleLabel" => $r['label'], "Restaurant" => $restaurant->getName()));
                    }

                }

                $this->em->persist($procedure);
                $this->em->flush();
            }

            //opening
            $ouverture = $this->em->getRepository(Procedure::class)->findOneBy(array(
                'name' => 'ouverture',
                'originRestaurant' => $restaurant
            ));

            if (!$ouverture) {
                $ouverture = new Procedure();
                $ouverture->setName('ouverture');
                $this->em->persist($ouverture);

                $ouverture
                    ->setAtSameTime(false)
                    ->setOnlyOnceAtDay(false)
                    ->setOnlyOnceForAll(false)
                    ->setAutorizeAbandon(false)
                    ->setCanBeDeleted(false);
                $ouverture->setOriginRestaurant($restaurant);
                $this->em->flush();

                $ouvertureActionNames = ['administrative_closing'];
                $ouvertureActionFixed = [1];
                foreach ($ouvertureActionNames as $key => $a) {
                    $action = $this->em->getRepository(Action::class)->findOneBy(array(
                        'name' => $a,
                    ));
                    if ($action) {
                        $step = new ProcedureStep();
                        $step->setAction($action)
                            ->setOrder($key + 1)
                            ->setDeletable(($ouvertureActionFixed[$key] == 0) ? true : false)
                            ->setProcedure($ouverture);
                        $this->em->persist($step);
                        $this->em->flush();
                    }
                }
            }

            //closing
            $fermeture = $this->em->getRepository(Procedure::class)->findOneBy(array(
                'name' => 'fermeture',
                'originRestaurant' => $restaurant
            ));

            if (!$fermeture) {
                $fermeture = new Procedure();
                $fermeture->setName('fermeture');
                $this->em->persist($fermeture);

                $fermeture
                    ->setAtSameTime(false)
                    ->setOnlyOnceAtDay(true)
                    ->setOnlyOnceForAll(true)
                    ->setAutorizeAbandon(false)
                    ->setCanBeDeleted(false);
                $fermeture->setOriginRestaurant($restaurant);
                $this->em->flush();

                $fermetureActionNames = ['verify_opened_table'];
                $fermetureActionFixed = [1];
                foreach ($fermetureActionNames as $key => $a) {
                    $action = $this->em->getRepository(Action::class)->findOneBy(array(
                        'name' => $a,
                    ));
                    if ($action) {
                        $step = new ProcedureStep();
                        $step->setAction($action)
                            ->setOrder($key + 1)
                            ->setDeletable(($fermetureActionFixed[$key] == 0) ? true : false)
                            ->setProcedure($fermeture);
                        $this->em->persist($step);
                        $this->em->flush();
                    }
                }
            }
            $progress->advance(2);

            //set restaurant parameters
            $ordersUrl = $this->em->getRepository(Parameter::class)->findOneBy(
                array('type' => Parameter::ORDERS_URL_TYPE, 'originRestaurant' => $restaurant)
            );
            $usersUrl = $this->em->getRepository(Parameter::class)->findOneBy(
                array('type' => Parameter::USERS_URL_TYPE, 'originRestaurant' => $restaurant)
            );
            $wyndActive = $this->em->getRepository(Parameter::class)->findOneBy(
                array('type' => Parameter::WYND_ACTIVE, 'originRestaurant' => $restaurant)
            );
            $secretKey = $this->em->getRepository(Parameter::class)->findOneBy(
                array('type' => Parameter::SECRET_KEY, 'originRestaurant' => $restaurant)
            );
            $api_user = $this->em->getRepository(Parameter::class)->findOneBy(
                array('type' => Parameter::WYND_USER, 'originRestaurant' => $restaurant)
            );
            $cashboxes = $this->em->getRepository(Parameter::class)->findOneBy(
                array('type' => Parameter::NUMBER_OF_CASHBOXES, 'originRestaurant' => $restaurant)
            );
            $startDayFundsParam = $this->em->getRepository(Parameter::class)->findOneBy(
                array('type' => Parameter::START_DAY_CASHBOX_FUNDS_TYPE, 'originRestaurant' => $restaurant)
            );
            $restaurantOpeningHourParam = $this->em->getRepository(Parameter::class)->findOneBy(
                array('type' => Parameter::RESTAURANT_OPENING_HOUR, 'originRestaurant' => $restaurant)
            );
            $restaurantClosingHourParam = $this->em->getRepository(Parameter::class)->findOneBy(
                array('type' => Parameter::RESTAURANT_CLOSING_HOUR, 'originRestaurant' => $restaurant)
            );
            $dateFiscal = $this->em->getRepository(Parameter::class)->findOneBy(
                array('type' => 'date_fiscale', 'originRestaurant' => $restaurant)
            );
            $eft = $this->em->getRepository(Parameter::class)->findOneBy(
                array('type' => Parameter::EFT_ACTIVATED_TYPE, 'originRestaurant' => $restaurant)
            );

            if (!$ordersUrl) {
                $ordersUrl = $restaurantData['ORDERS_URL_TYPE'];
                $param = new Parameter();
                $param->setValue($ordersUrl);
                $param->setLabel($this->translator->trans('label.orders_url', [], 'supervision'));
                $param->setType(Parameter::ORDERS_URL_TYPE);
                $restaurant->addParameter($param);
            } else {
                $ordersUrl->setValue($restaurantData['ORDERS_URL_TYPE']);
                $this->em->persist($ordersUrl);
            }
            if (!$usersUrl) {
                $usersUrl = $restaurantData['USERS_URL_TYPE'];
                $param = new Parameter();
                $param->setValue($usersUrl);
                $param->setLabel($this->translator->trans('label.users_url', [], 'supervision'));
                $param->setType(Parameter::USERS_URL_TYPE);
                $restaurant->addParameter($param);
            } else {
                $usersUrl->setValue($restaurantData['USERS_URL_TYPE']);
                $this->em->persist($usersUrl);
            }

            if (!$secretKey) {
                $secretKey = $restaurantData['API_SECRET_KEY'];
                $param = new Parameter();
                $param->setValue($secretKey);
                $param->setLabel($this->translator->trans('label.secret_key', [], 'supervision'));
                $param->setType(Parameter::SECRET_KEY);
                $restaurant->addParameter($param);
            } else {
                $secretKey->setValue($restaurantData['API_SECRET_KEY']);
                $this->em->persist($secretKey);
            }
            if (!$api_user) {
                $api_user = $restaurantData['API_USER'];
                $param = new Parameter();
                $param->setValue($api_user);
                $param->setLabel($this->translator->trans('label.wynd_user', [], 'supervision'));
                $param->setType(Parameter::WYND_USER);
                $restaurant->addParameter($param);
            } else {
                $api_user->setValue($restaurantData['API_USER']);
                $this->em->persist($api_user);
            }

            if (!$wyndActive) {
                $wyndActive = $restaurantData['WYND_ACTIVE'];
                $param = new Parameter();
                $param->setValue((int)$wyndActive);
                $param->setLabel($this->translator->trans('label.wynd_active', [], 'supervision'));
                $param->setType(Parameter::WYND_ACTIVE);
                $restaurant->addParameter($param);
            } else {
                $wyndActive->setValue((int)$restaurantData['WYND_ACTIVE']);
                $this->em->persist($wyndActive);
            }

            if (!$cashboxes) {
                $cashboxes = $restaurantData['NUMBER_OF_CASHBOXES'];
                $param = new Parameter();
                $param->setValue($cashboxes);
                $param->setLabel("Nombre de caisses");
                $param->setType(Parameter::NUMBER_OF_CASHBOXES);
                $restaurant->addParameter($param);
            } else {
                $cashboxes->setValue((int)$restaurantData['NUMBER_OF_CASHBOXES']);
                $this->em->persist($cashboxes);
            }

            if (!$startDayFundsParam) {
                $startDayFundsParam = $restaurantData['START_DAY_CASHBOX_FUNDS_TYPE'];
                $param = new Parameter();
                $param->setValue((int)$startDayFundsParam);
                $param->setLabel('Fond de caisse ');
                $param->setType(Parameter::START_DAY_CASHBOX_FUNDS_TYPE);
                $restaurant->addParameter($param);
            } else {
                $startDayFundsParam->setValue((int)$restaurantData['START_DAY_CASHBOX_FUNDS_TYPE']);
                $this->em->persist($startDayFundsParam);
            }

            if (!$restaurantOpeningHourParam) {
                $restaurantOpeningHourParam = $restaurantData['RESTAURANT_OPENING_HOUR'];
                $param = new Parameter();
                $param->setValue((int)$restaurantOpeningHourParam);
                $param->setLabel("Heure d'ouverture");
                $param->setType(Parameter::RESTAURANT_OPENING_HOUR);
                $restaurant->addParameter($param);
            } else {
                $restaurantOpeningHourParam->setValue((int)$restaurantData['RESTAURANT_OPENING_HOUR']);
                $this->em->persist($restaurantOpeningHourParam);
            }

            if (!$restaurantClosingHourParam) {
                $restaurantClosingHourParam = $restaurantData['RESTAURANT_CLOSING_HOUR'];
                $param = new Parameter();
                $param->setValue((int)$restaurantClosingHourParam);
                $param->setLabel("Heure de fermeture");
                $param->setType(Parameter::RESTAURANT_CLOSING_HOUR);
                $restaurant->addParameter($param);
            } else {
                $restaurantClosingHourParam->setValue((int)$restaurantData['RESTAURANT_CLOSING_HOUR']);
                $this->em->persist($restaurantClosingHourParam);
            }

            if (!$dateFiscal) {
                $dateFiscal = new Parameter();
                $dateFiscal->setType('date_fiscale')
                    ->setOriginRestaurant($restaurant)
                    ->setValue(date('d/m/Y'));
                $restaurant->addParameter($dateFiscal);
            } else {
                $dateFiscal->setValue(date('d/m/Y'));
                $this->em->persist($dateFiscal);
            }

            if (!$eft) {
                $eft = new Parameter();
                $eft->setType(Parameter::EFT_ACTIVATED_TYPE)
                    ->setValue($restaurantData['EFT_ACTIVATED_TYPE'])
                    ->setOriginRestaurant($restaurant);
                $restaurant->addParameter($eft);
            } else {
                $eft->setValue($restaurantData['EFT_ACTIVATED_TYPE']);
                $this->em->persist($eft);
            }
            $progress->advance(4);

            //create the restaurant suppliers and affect them to the restaurant if not exist
            // or if the suplier exist , affect it to the restaurant
            foreach ($restaurantData['suppliers'] as $s) {
                $supplier = $this->em->getRepository("Merchandise:Supplier")->findOneBy(array('code'=>$s['code'],'name' => rtrim($s['name'],'-')));// rtrim to handle special case in cocacola supplier
                if (!$supplier) {//create the supplier and affect it
                    $supplier = new Supplier();
                    $supplier
                        ->setActive(true)
                        ->setName($s['name'])
                        ->setDesignation($s['designation'])
                        ->setEmail($s['email'])
                        ->setCode($s['code'])
                        ->setAddress($s['address'])
                        ->setPhone($s['phone'])
                        ->setZone($s['zone']);
                    $this->em->persist($supplier);
                }
                foreach ($s['planning'] as $p) {
                    $planning = $this->em->getRepository(SupplierPlanning::class)->findOneBy(
                        array('orderDay' => $p['orderDay'], 'deliveryDay' => $p['deliveryDay'], 'supplier' => $supplier, 'originRestaurant' => $restaurant)
                    );
                    if (!$planning) {
                        $planning = new SupplierPlanning();
                        $planning->setOrderDay($p['orderDay']);
                        $planning->setDeliveryDay($p['deliveryDay']);
                        $planning->setStartTime($p['startTime']);
                        $planning->setEndTime($p['endTime']);
                        $planning->setOriginRestaurant($restaurant);

                        $supplier->addPlanning($planning);
                        $planning->setSupplier($supplier);
                    }
                    foreach ($p['categories'] as $cat) {

                        $category = $this->em->getRepository(ProductCategories::class)->findOneBy(array('name' => $cat));
                        if ($category) {
                            if (!$planning->getCategories()->contains($category)) {
                                $planning->addCategory($category);
                            }
                        }
                    }
                    $this->em->persist($planning);
                    $this->em->flush();
                }
                if (!$supplier->getRestaurants()->contains($restaurant) && $s['active']) {
                    $supplier->addRestaurant($restaurant);
                }
                if (!$restaurant->getSuppliers()->contains($supplier) && $s['active']) {
                    $restaurant->addSupplier($supplier);
                }
                if ($supplier->getRestaurants()->contains($restaurant) && !$s['active']) {
                    $supplier->removeRestaurant($restaurant);
                }
                if ($restaurant->getSuppliers()->contains($supplier) && !$s['active']) {
                    $restaurant->removeSupplier($supplier);
                }
                $this->em->persist($supplier);

            }
            $progress->advance(10);

            //set optikitchen parameters
            $sql = "UPDATE product set eligible_for_optikitchen = FALSE WHERE origin_restaurant_id= :restaurantId ; ";
            $stm = $this->em->getConnection()->prepare($sql);
            $stm->bindValue('restaurantId', $restaurant->getId());
            $stm->execute();
            foreach ($restaurantData['optikitchen']['purchased_items'] as $purchasedItem) {
                $p = $this->em->getRepository(Product::class)->findOneByName($purchasedItem['item']);
                if ($p) {
                    $p->setEligibleForOptikitchen(true);
                    $this->em->persist($p);
                }
            }
            foreach ($restaurantData['optikitchen']['sold_items'] as $soldItem) {
                $p = $this->em->getRepository(Product::class)->findOneByName($soldItem['item']);
                if ($p) {
                    $p->setEligibleForOptikitchen(true);
                    $this->em->persist($p);
                }
            }
            $progress->advance(2);

            ////////////////////////////////////////////////////////
            //add payment methods

           // $criteria = new Criteria();
            //$criteria->where(Criteria::expr()->neq('type', PaymentMethod::REAL_CASH_TYPE));
            //$criteria->andWhere(Criteria::expr()->neq('type', PaymentMethod::FOREIGN_CURRENCY_TYPE));
            //$paymentMethods = $this->em->getRepository(PaymentMethod::class)->matching($criteria);
            $paymentMethods = $this->em->getRepository(PaymentMethod::class)->findAll();
            foreach ($paymentMethods as $paymentMethod) {

                if($paymentMethod->getType()!=PaymentMethod::FOREIGN_CURRENCY_TYPE && $paymentMethod->getType() != PaymentMethod::REAL_CASH_TYPE){
                    $parameter = $this->em->getRepository(Parameter::class)
                        ->createQueryBuilder('p')
                        ->where('p.type = :type')
                        ->andWhere('p.originRestaurant = :restaurant')
                        ->andWhere('p.label = :label')
                        ->setParameter('type', $paymentMethod->getType())
                        ->setParameter('label', $paymentMethod->getLabel())
                        ->setParameter('restaurant', $restaurant)
                        ->setMaxResults(1)
                        ->getQuery()->getOneOrNullResult();
                    if (!$parameter) {
                        $parameter = new Parameter();
                    }
                    $parameter
                        ->setValue($paymentMethod->getValue())
                        ->setType($paymentMethod->getType())
                        ->setLabel($paymentMethod->getLabel())
                        ->setGlobalId($paymentMethod->getGlobalId())
                        ->setOriginRestaurant($restaurant);
                    $this->em->persist($parameter);
                }

                if (!$restaurant->getPaymentMethods()->contains($paymentMethod) ) { //condition deleted for fix purpuse : && in_array($paymentMethod->getLabel(), $restaurantData['payment_methods']['active_methods'])
                    $restaurant->addPaymentMethod($paymentMethod);
                    $paymentMethod->addRestaurant($restaurant);
                }

                $this->em->persist($paymentMethod);
            }
            $progress->advance(2);
            $this->em->flush();

            //set payment methods parameter foreach type

            // Ticket Restaurant Values
            if (key_exists('TICKET_RESTAURANT_TYPE', $restaurantData['payment_methods'])) {
                $tmp = array();
                foreach ($restaurantData['payment_methods']['TICKET_RESTAURANT_TYPE'] as $ticket) {
                    $tmp = $ticket['value'];
                    unset($tmp['values']);
                    $i = 0;
                    $parameter = $this->em
                        ->getRepository(Parameter::class)
                        ->createQueryBuilder('p')
                        ->where('p.label = :label')
                        ->andWhere('p.originRestaurant =:restaurant')
                        ->setParameter('label', $ticket['ticketName'])
                        ->setParameter('restaurant', $restaurant)
                        ->setMaxResults(1)
                        ->getQuery()->getOneOrNullResult();

                    if (!$parameter) {
                        continue;
                    }

                    foreach ($ticket['value']['values'] as $value) {
                        $tmp['values'][$i] = $value['unitValue'];
                        $i++;
                    }
                    $parameter->setValue($tmp);
                    $this->em->persist($parameter);
                    $this->em->flush();
                }
            }
            $progress->advance(10);

            //Foreign Currency Values
            if (key_exists('FOREIGN_CURRENCY_TYPE', $restaurantData['payment_methods'])) {
                foreach ($restaurantData['payment_methods']['FOREIGN_CURRENCY_TYPE'] as $currency) {
                    if ($currency['id']) {
                        $parameter = $this->em->getRepository(Parameter::class)
                            ->createQueryBuilder('p')
                            ->where('p.type = :type')
                            ->andWhere('p.originRestaurant = :restaurant')
                            ->andWhere('p.label = :label')
                            ->setParameter('type', Parameter::FOREIGN_CURRENCY_TYPE)
                            ->setParameter('label', $currency['foreignCurrencyLabel'])
                            ->setParameter('restaurant', $restaurant)
                            ->setMaxResults(1)
                            ->getQuery()->getOneOrNullResult();
                        if (!$parameter) {
                            $parameter = new Parameter();
                            $parameter->setType(Parameter::FOREIGN_CURRENCY_TYPE)->setOriginRestaurant($restaurant);
                        }
                        $parameter->setLabel($currency['foreignCurrencyLabel']);
                        $parameter->setValue(str_replace(',', '.', $currency['exchangeRate']));
                        $this->em->persist($parameter);
                        $this->em->flush();
                    }
                }
            }
            $progress->advance(5);

            //Check Quick Values
            if (key_exists('CHECK_QUICK_TYPE', $restaurantData['payment_methods'])) {
                $tmp = array();
                if (isset($restaurantData['payment_methods']['CHECK_QUICK_TYPE']['id'])) {
                    $parameter = $this->em
                        ->getRepository(Parameter::class)
                        ->createQueryBuilder('p')
                        ->where('p.type = :type')
                        ->andWhere('p.originRestaurant =:restaurant')
                        ->setParameter('type', Parameter::CHECK_QUICK_TYPE)
                        ->setParameter('restaurant', $restaurant)
                        ->setMaxResults(1)
                        ->getQuery()->getOneOrNullResult();
                    if (!$parameter) {
                        $parameter = new Parameter();
                        $parameter->setType(Parameter::CHECK_QUICK_TYPE)->setOriginRestaurant($restaurant);
                    }
                    foreach ($restaurantData['payment_methods']['CHECK_QUICK_TYPE']['checkQuickCounts'] as $value) {
                        $tmp[] = $value['unitValue'];
                    }
                    $parameter->setValue($tmp);
                    $this->em->persist($parameter);
                    $this->em->flush();
                }
            }
            $progress->advance(2);

            // add restaurant additional emails
            if (key_exists('RESTAURANT_ADDITIONAL_EMAILS', $restaurantData['RESTAURANT_ADDITIONAL_EMAILS'])) {

                foreach ($restaurantData['RESTAURANT_ADDITIONAL_EMAILS'] as $mail) {
                    $parameter = $this->em
                        ->getRepository(Parameter::class)
                        ->createQueryBuilder('p')
                        ->where('p.type = :type')
                        ->andWhere('p.originRestaurant =:restaurant')
                        ->andWhere('p.value =: value')
                        ->setParameter('type', Parameter::RESTAURANT_ADDITIONAL_EMAILS)
                        ->setParameter('restaurant', $restaurant)
                        ->setParameter('value', $mail['mail'])
                        ->setMaxResults(1)
                        ->getQuery()->getOneOrNullResult();
                    if (!$parameter) {
                        $parameter = new Parameter();
                        $parameter->setType(Parameter::RESTAURANT_ADDITIONAL_EMAILS)->setOriginRestaurant($restaurant);
                        $parameter->setValue($mail['mail']);
                        $this->em->persist($parameter);
                    }
                }
            }
            $progress->advance(10);

            $progress->finish();

            $output->writeln("");
            $output->writeln("Importing restaurant notifications ...");
            $progress = new ProgressBar($output, count($restaurantData['notifications']));
            $progress->start();
            $i=0;
            //import notifications
            foreach ($restaurantData['notifications'] as $notif) {
                $progress->advance();
                if (empty($notif) || !array_key_exists('id', $notif)) {
                    continue;
                }
                $i++;
                $notification = $this->em->getRepository(Notification::class)->findOneBy(
                    array(
                        "importId" => $notif['id'] . "_" . $restaurantCode,
                        "originRestaurant" => $restaurant
                    )
                );
                if (!$notification) {
                    $notification = new Notification();
                }

                  if(array_key_exists('date',$notif['data']) ){
                      $notif['data']['date']= new \DateTime($notif['data']['date']['date']);
                  }

                $notification
                    ->setType($notif['type'])
                    ->setData($notif['data'])
                    ->setRoute($notif['route'])
                    ->setOriginRestaurant($restaurant)
                    ->setImportId($notif['id'] . "_" . $restaurantCode);


                foreach ($notif['roles'] as $r) {
                    $role = $this->em->getRepository('Security:Role')->findOneBy(array('label' => $r['label']));
                    if ($role) {
                        if (!$notification->getRoles()->contains($role)) {
                            $notification->addRole($role);
                        }
                    }
                }

                foreach ($notif['notificationInstance'] as $instance) {
                    $notificationInstance = $this->em->getRepository(NotificationInstance::class)->findOneBy(
                        array(
                            "importId" => $instance['id'] . "_" . $restaurantCode,
                        )
                    );
                    if (!$notificationInstance) {
                        $notificationInstance = new NotificationInstance();
                    }
                    $notificationInstance
                        ->setSeen(boolval($instance['seen']))
                        ->setNotification($notification)
                        ->setImportId($instance['id'] . "_" . $restaurantCode);

                    if ($instance['employee']) {
                        if(empty($instance['employee']['wyndId'])){
                            $userName=$instance['employee']['username'];
                        }else{
                            $userName=$restaurantCode."_".$instance['employee']['username'];
                        }
                        $employee = $this->em->getRepository(Employee::class)->findOneByUsername($userName);
                        if ($employee) {
                            $notificationInstance->setEmployee($employee);
                        } else {
                            $this->logger->info('User not found for Notification Instance :', array("notificationInstanceId" => $instance['id'], "username" => $instance['employee']['username'], "Restaurant" => $restaurant->getName()));
                        }
                    }
                    if (!$notification->getNotificationInstance()->contains($notificationInstance)) {
                        $notification->addNotificationInstance($notificationInstance);
                    }
                    $this->em->persist($notificationInstance);
                }

                $this->em->persist($notification);
                if (($i % 100) === 0) {
                    $this->em->flush();
                    $this->em->clear(); // Detaches all objects from Doctrine!
                    $restaurant = $this->em->getRepository(Restaurant::class)->findOneByCode($restaurantCode);
                }

            }

            $this->em->flush();
            $this->em->clear();
            $progress->finish();
            $restaurant = $this->em->getRepository(Restaurant::class)->findOneByCode($restaurantData['code']);

            $output->writeln("");
            $output->writeln("Creating syncCmd for not synchronized products ...");
            $progress = new ProgressBar($output, count($restaurantData['ProductPurchased']) + count($restaurantData['ProductSold']));
            //create sync cmd for product purchased
            foreach ($restaurantData['ProductPurchased'] as $product) {
                $progress->advance();
                $p = $this->em->getRepository(ProductPurchased::class)->findOneBy(
                    array('externalId' => $product['external_id'], 'originRestaurant' => $restaurant)//globalId will be the same
                );
                if ($p) {
                    $this->logger->info('ProductPurchased already exist in this restaurant :', array("productExternalId" => $product['external_id'], "Restaurant" => $restaurant->getName()));
                    continue;
                }
                $itemsCount=$this->em->getRepository(ProductPurchasedSupervision::class)->createQueryBuilder('p')
                    ->select('COUNT(p)')
                    ->where("p.externalId = :external_id")
                    ->setParameter("external_id",$product['external_id'])
                    ->getQuery()
                    ->getSingleScalarResult();
                if($itemsCount>1){
                    $item = $this->em->getRepository(ProductPurchasedSupervision::class)->findOneBy(
                        array('externalId' => $product['external_id'],'globalProductID'=>$product['globalProductID'])
                    );
                }else{
                    $item = $this->em->getRepository(ProductPurchasedSupervision::class)->findOneBy(
                        array('externalId' => $product['external_id'])
                    );
                }

                if ($item) {
                    $this->syncService->createProductPurchasedEntry($item, true, $restaurant, true);
                    $this->logger->info('SyncCmd created for product purchased  :', array("productExternalId" => $product['external_id'], "Restaurant" => $restaurant->getName()));
                } else {
                    $this->logger->info('Cannot create syncCmd for product purchased because its not found :', array("productExternalId" => $product['external_id'], "Restaurant" => $restaurant->getName()));
                }
            }

            //create sync cmd for product sold
            foreach ($restaurantData['ProductSold'] as $product) {
                $progress->advance();
                $p = $this->em->getRepository(ProductSold::class)->findOneBy(
                    array('codePlu' => $product['codePlu'], 'originRestaurant' => $restaurant)//globalId will be the id of the supervision product
                );
                if ($p) {
                    $this->logger->info('ProductSold already exist in this restaurant :', array("productPlu" => $product['codePlu'], "Restaurant" => $restaurant->getName()));
                    continue;
                }
                $itemsCount=$this->em->getRepository(ProductSoldSupervision::class)->createQueryBuilder('p')
                    ->select('COUNT(p)')
                    ->where("p.codePlu = :codePlu")
                    ->setParameter("codePlu",$product['codePlu'])
                    ->getQuery()
                    ->getSingleScalarResult();
                if($itemsCount>1){
                    $item = $this->em->getRepository(ProductSoldSupervision::class)->findOneBy(
                        array('codePlu' => $product['codePlu'], 'globalProductID'=>$product['globalProductID'])
                    );
                }else{
                    $item = $this->em->getRepository(ProductSoldSupervision::class)->findOneBy(
                        array('codePlu' => $product['codePlu'])
                    );
                }
                if ($item) {
                    $this->syncService->createProductSoldEntry($item, true, true, $restaurant);
                    $this->logger->info('SyncCmd created for product sold  :', array("productPlu" => $product['codePlu'], "Restaurant" => $restaurant->getName()));
                } else {
                    $this->logger->info('Cannot create syncCmd for product sold because its not found :', array("productPlu" => $product['codePlu'], "Restaurant" => $restaurant->getName()));
                }
            }


        } catch (\Exception $e) {
            $output->writeln("");
            $output->writeln("Command failed ! ");
            $output->writeln($e->getMessage());
            return;
        }

        $progress->finish();
        $output->writeln("");
        $output->writeln("\nRestaurant " . $restaurant->getName() . " imported successfully.");

    }


}
