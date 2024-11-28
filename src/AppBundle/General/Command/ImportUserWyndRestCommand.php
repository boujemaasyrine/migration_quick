<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 28/04/2016
 * Time: 10:47
 */

namespace AppBundle\General\Command;

use AppBundle\Administration\Entity\Parameter;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Security\Entity\Role;
use AppBundle\Staff\Entity\Employee;
use Doctrine\ORM\EntityManager;
use Httpful\Request;
use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportUserWyndRestCommand extends ContainerAwareCommand
{

    private $restaurant;
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var EntityManager $em
     */
    private $em;

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this->setName('quick:user:wynd:rest:import')
            ->addArgument('restaurantId', InputArgument::OPTIONAL)
            ->setDescription('Import Wynd User from the REST API.');
    }

    /**
     * {@inheritDoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->restaurant = $this->getContainer()->getParameter("api_user_code");
        $this->logger = $this->getContainer()->get('logger');
        parent::initialize($input, $output);
        $this->em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $this->logger->addInfo('Processing import Users', ['quick:user:wynd:rest:import']);


        $currentRestaurant = null;
        if ($input->hasArgument('restaurantId') && !empty($input->getArgument('restaurantId'))) {
            $restaurantId = $input->getArgument('restaurantId');
            $currentRestaurant = $this->em->getRepository(Restaurant::class)->find($restaurantId);
            if ($currentRestaurant == null) {
                $this->logger->addInfo('Restaurant not found with id: '.$restaurantId, ['quick:user:wynd:rest:import']);
                echo 'Restaurant not found with id: '.$restaurantId;

                return;
            }
        }

        if ($currentRestaurant == null) {
            $restaurants = $this->em->getRepository(Restaurant::class)->getOpenedRestaurants();
            foreach ($restaurants as $restaurant) {
                $this->updateRestaurantEmployees($restaurant);
            }
        } else {
            $this->updateRestaurantEmployees($currentRestaurant);
        }
    }

    private function updateRestaurantEmployees(Restaurant $restaurant)
    {
        try {
            $urlUsers = $this->em->getRepository(Parameter::class)->findOneBy(array("originRestaurant" => $restaurant, "type" => Parameter::USERS_URL_TYPE,));
            if (!$urlUsers) {
                $this->logger->addInfo('Url users for the restaurant with code: ' . $restaurant->getCode() . ' not found', ['quick:user:wynd:rest:import']);
                return;
            }
            $secretKey = $this->em->getRepository(Parameter::class)->findOneBy(array("originRestaurant" => $restaurant, "type" => Parameter::SECRET_KEY,));
            if (!$secretKey) {
                $this->logger->addInfo('Secret key for the restaurant with code: ' . $restaurant->getCode() . ' not found', ['quick:user:wynd:rest:import']);
                return;
            }
            $wyndUser = $this->em->getRepository(Parameter::class)->findOneBy(array("originRestaurant" => $restaurant, "type" => Parameter::WYND_USER,));
            if (!$wyndUser) {
                $this->logger->addInfo('User login for the restaurant with code: ' . $restaurant->getCode() . ' not found', ['quick:user:wynd:rest:import']);
                return;
            }
            $this->logger->addInfo('Processing import Users : ' . $urlUsers->getValue(), ['ImportUserWyndRestCommand']);

            $employeeRole=$this->em->getRepository(Role::class)->findOneBy(array('label'=>Role::ROLE_EMPLOYEE));
            echo 'Processing import Users : ' . $urlUsers->getValue() . "\n";
            $data = Request::get($urlUsers->getValue())->addHeaders(array('Api-User' => $wyndUser->getValue(), 'Api-Hash' => sha1($secretKey->getValue()),))->expectsJson()->send();
            $data = $data->body;
            // if ($data->result == 'success' ){
            $newUsersArray = array();
            foreach ($data->employees as $user) {
                $login = $restaurant->getCode() . '_user' . $user->id;
                $employee = null;
                $employees = $restaurant->getEligibleUsers()->filter(function ($emp) use ($user) {
                    return $emp->getWyndId() == $user->id;
                });
                if ($employees->count() > 0) {
                    $employee = $employees->first();
                } else {
                    $employees = $restaurant->getEligibleUsers()->filter(function ($emp) use ($login) {
                        return $emp->getUsername() == $login;
                    });
                    if ($employees->count() == 0) {
                        $employee = new Employee();
                        $employee->setFirstConnection(false)->setActive(false)->setDeleted(false)->setFromWynd(true)->addEligibleRestaurant($restaurant);
                        // set default locale according to the restaurant
                        $defaultLocale = in_array($restaurant->getLang(), ['fr', 'nl']) ? $restaurant->getLang() : 'fr';
                        $employee->setDefaultLocale($defaultLocale);
                        $employee->setRoles([Role::ROLE_EMPLOYEE])->addEmployeeRole($employeeRole);
                        $employeeRole->addUser($employee);
                    } else {
                        $employee = $employees->first();
                    }
                }
                if ($employee->getFromWynd() == true) {
                    if (isset($user->email) && $user->email != '') {
                        $employee->setEmail($user->email);
                    }
                    if(empty($employee->getUsername())){
                        $employee->setUsername($login);
                    }
                    $employee->setWyndId($user->id)
                        ->setFirstName($user->name);

                    /*
                     * ->setLastName($user->name)
                       ->setMatricule($user->matricule)
                       ->setTimeWork($user->time_work)
                      */
                    $this->em->persist($employee);
                    $this->em->flush();
                }
                $newUsersArray[] = $employee->getId();
                echo "user with WyndId " . $user->id . " updated with success \n";
            }
            $this->getContainer()->get('staff.service')->deleteUsers($newUsersArray, $restaurant);
        }
        catch (\Exception $e)
        {
            $this->logger->addError('Exception: ' . $e->getMessage(), ['quick:user:wynd:rest:import']);
        }
    }
}
