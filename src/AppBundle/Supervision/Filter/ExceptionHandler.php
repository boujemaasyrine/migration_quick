<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 26/05/2016
 * Time: 15:23
 */

namespace AppBundle\Supervision\Filter;


use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

class ExceptionHandler
{

    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getException();

        if ($exception instanceof NotAuthorizedException) {
            $event->setResponse($exception->getResponse());
        } else {
            return;
        }
    }
}
