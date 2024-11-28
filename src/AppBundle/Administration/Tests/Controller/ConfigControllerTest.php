<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 27/04/2016
 * Time: 10:22
 */
namespace AppBundle\Administration\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

Class ConfigControllerTest extends WebTestCase{
    /**
     *
     */
    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertContains('Welcome to Symfony', $crawler->filter('#container h1')->text());
    }
}
