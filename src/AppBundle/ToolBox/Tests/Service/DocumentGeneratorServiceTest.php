<?php

namespace AppBundle\ToolBox\Tests\Service;

use AppBundle\ToolBox\Service\DocumentGeneratorService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 17/02/2016
 * Time: 14:09
 */
class DocumentGeneratorServiceTest extends \PHPUnit_Framework_TestCase
{
    private $container;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct();
        $this->container=$container;

    }

    public function testgenerateCSVFile()
    {
        $service = new DocumentGeneratorService($this->container);
        $result = $service->generateCSVContentFile(
            [
                "HEADER1",
                "HEADER2",
                "HEADER3",
                "HEADER4",
            ],
            [
                [
                    "Cell11",
                    "Cell12",
                    "Cell13",
                    "Cell14",
                ],
                [
                    new \DateTime('2016/02/17'),
                    ["alpha", "beta"],
                    "Cell23",
                    "Cell24",
                ],
                [
                    "Cell31",
                    "Cell32",
                    "Cell33",
                    "Cell34",
                ],
                [
                    "Cell21",
                    "Cell21",
                    "Cell21",
                    "Cell21",
                ],
            ]
        );

        // assert that your calculator added the numbers correctly!
        $this->assertEquals(152, strlen($result));
    }
}
