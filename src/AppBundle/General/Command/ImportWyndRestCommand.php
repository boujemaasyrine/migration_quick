<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 28/03/2016
 * Time: 11:10
 */

namespace AppBundle\General\Command;

use AppBundle\Administration\Entity\Parameter;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\ToolBox\Service\RestJsonClient;
use AppBundle\ToolBox\Utils\Utilities;
use Doctrine\ORM\EntityManager;
use Httpful\Request;
use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportWyndRestCommand extends ContainerAwareCommand
{

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
        $this->setName('quick:wynd:rest:import')
            ->addArgument('startDate', InputArgument::OPTIONAL)
            ->addArgument('endDate', InputArgument::OPTIONAL)
            //if restaurantId is set, only this restaurant tickets are imported
            ->addArgument('restaurantId', InputArgument::OPTIONAL)
			 ->addArgument('asynch', InputArgument::OPTIONAL)
            ->setDescription('Import Aloha Tickets from the REST API.');
    }

    /**
     * {@inheritDoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->logger = $this->getContainer()->get('monolog.logger.app_commands');
        $this->em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        parent::initialize($input, $output);
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $currentRestaurant = null;
        if ($input->hasArgument('restaurantId') && !empty($input->getArgument('restaurantId'))) {
            $restaurantId = $input->getArgument('restaurantId');
            $currentRestaurant = $this->em->getRepository(Restaurant::class)->find($restaurantId);
            if ($currentRestaurant == null) {
                $this->logger->addAlert('Restaurant not found with id: '.$restaurantId, ['quick:wynd:rest:import']);

                    return;
                }
            }

            $startDate = $input->getArgument('startDate');
            $endDate = $input->getArgument('endDate');
            $supportedFormat = "Y-m-d";

            if (!is_null($startDate) && !is_null($endDate) && Utilities::isValidDateFormat(
                    $startDate,
                    $supportedFormat
                ) && Utilities::isValidDateFormat($endDate, $supportedFormat)
            ) {
                $startDate = date_create_from_format($supportedFormat, $startDate);
                $endDate = date_create_from_format($supportedFormat, $endDate);
            } else {
                $startDate = null;
                $endDate = null;
            }

            if ($currentRestaurant == null) {
                $restaurants = $this->em->getRepository(Restaurant::class)->getOpenedRestaurants();

                // import tickets for each restaurant
                foreach ($restaurants as $restaurant) {

                $this->ImportRestaurantTickets($restaurant, $startDate, $endDate);
            }
        } else {
            // treatment for only one restaurant
            $this->ImportRestaurantTickets($currentRestaurant, $startDate, $endDate);
        }
    }


    private function ImportRestaurantTickets(Restaurant $restaurant, $startDate, $endDate)
    {
        echo " \n";
        $start = date("h:i:sa");
        echo "Started at " . $start;
        echo " \n";
        try {
            $apiUser = $this->em->getRepository(Parameter::class)->findOneBy(array(
                "type" => Parameter::WYND_USER,
                "originRestaurant" => $restaurant
            ));
            if ($apiUser == null) {
                $this->logger->addAlert('Parameter login not found for the restaurant: ' . $restaurant->getCode(), ['quick:wynd:rest:import']);
                return;
            }
            $secretKey = $this->em->getRepository(Parameter::class)->findOneBy(array(
                "type" => Parameter::SECRET_KEY,
                "originRestaurant" => $restaurant
            ));
            if ($secretKey == null) {
                $this->logger->addAlert('Parameter secret key not found for the restaurant: ' . $restaurant->getCode(), ['quick:wynd:rest:import']);
                return;
            }
            $this->logger->addInfo(
                'Processing import tickets for restaurant ' . $restaurant->getName(),
                ['quick:wynd:rest:import']
            );
            $wyndActive = $this->em->getRepository(Parameter::class)->findParameterByTypeAndRestaurant(
                Parameter::WYND_ACTIVE,
                $restaurant
            );
            if ($wyndActive->getValue()) {
                $url = $this->em->getRepository(Parameter::class)->findOneBy(
                    array(
                        "originRestaurant" => $restaurant,
                        "type" => Parameter::ORDERS_URL_TYPE,
                    )
                );
                if (!$url) {
                    $this->logger->addInfo('Orders Url not found', ['quick:wynd:rest:import']);

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

                $supportedFormat = "Y-m-d";
                $url = $url->getValue();
                $url .= "?date_start=" . $startDate->format($supportedFormat) . "&" . "date_end=" . $endDate->format(
                        $supportedFormat
                    );

                echo $url;

                $this->logger->addInfo('Processing import tickets : ' . $url, ['ImportWyndRestCommand']);
                try {
                $data = Request::get($url)
                    ->addHeaders(
                        array(
                            'Api-User' => $apiUser->getValue(),
                            'Api-Hash' => sha1($secretKey->getValue()),
                        )
                    )
                    ->expectsJson()
                    ->timeout(40)
                    ->send();

                $data = $data->body;

                    if ($data !== null && property_exists($data, 'result') && $data->result == 'success') {
                        $this->logger->addInfo("Data received with success", ['ImportWyndRestCommand']);
                        $filename = $this->getContainer()->getParameter('tmp_directory') . "/aloha_" . str_replace(
                                '/',
                                '_',
                                $startDate->format($supportedFormat)
                            ) . "_" . str_replace(
                                '/',
                                '_',
                                $endDate->format($supportedFormat)
                            ) . "restaurant_" . $restaurant->getCode() . ".json";
    
                        $this->logger->addInfo(
                            "Writing json file :" . file_put_contents($filename, json_encode($data)),
                            ['ImportWyndRestCommand']
                        );
                        $this->logger->addInfo('File imported with success. ', ['ImportWyndRestCommand']);
                        $this->getContainer()->get('toolbox.command.launcher')->execute(
                            'quick:wynd:import ' . $restaurant->getId() . " " . $filename. " ".$startDate->format($supportedFormat)." ".$endDate->format($supportedFormat)
                        );
                        $this->logger->addInfo(
                            'Processing import tickets terminated with success. ',
                            ['ImportWyndRestCommand']
                        );
                    } else {
                        echo "ERROR ";
                        $this->logger->addError('Processing import tickets failed.', ['ImportWyndRestCommand']);
                    }
                } catch (\Exception $e) {
                    throw new \Exception("Request timed out. Please try again later.");
                }
            } else {
                $this->logger->addInfo('Aloha POS is disabled in this restaurant.', ['quick:wynd:rest:import']);
            }
        } catch (\Exception $e) {
            echo "Exception " . $e->getMessage() . "\n";
            $this->logger->addError('Importing tickets exception : ' . $e->getMessage(), ['ImportWyndRestCommand']);
        }
        echo " \n";
        $end = date("h:i:sa");
        echo "Ended at " . $end;
        echo " \n";
    }
}
