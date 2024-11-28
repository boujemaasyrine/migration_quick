<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 26/05/2016
 * Time: 15:24
 */

namespace AppBundle\Supervision\Filter;

use Symfony\Component\HttpFoundation\Response;

class NotAuthorizedException extends \Exception
{

    private $response;

    public function __construct(Response $response)
    {   parent::__construct();
        $this->response = $response;
    }

    public function setResponse(Response $response)
    {
        $this->response = $response;
    }

    public function getResponse()
    {
        return $this->response;
    }
}
