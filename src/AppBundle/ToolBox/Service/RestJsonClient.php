<?php
/**
 *
 * * *************************************************************
 **/
/**                                                                **/
/**
 *
 * This class defines inherit from the RestClient and it overrides
 **/
/**
 *
 * the communication format to JSON
 **/
/**                                                                **/
/**
 *
 * * *************************************************************
 **/

namespace AppBundle\ToolBox\Service;

class RestJsonClient extends RestClient
{


    protected function _createContext($pMethod, $pContent = null, $header = null)
    {
        $headers = [];
        $headers['Content-type'] = "application/json";
        $headers['Accept'] = "application/json";

        $headerString = "";

        foreach ($headers as $key => $value) {
            $headerString = $headerString.$key." : ".$value."\r\n";
        }

        if ($header && is_array($header)) {
            foreach ($header as $key => $value) {
                $headerString = $headerString.$key." : ".$value."\r\n";
            }
        }

        $opts = array(
            'http' => array(
                'method' => $pMethod,
                'header' => $headerString,
            ),
        );
        if ($pContent !== null) {
            $opts['http']['content'] = json_encode($pContent);
        }

        return stream_context_create($opts);
    }
}
