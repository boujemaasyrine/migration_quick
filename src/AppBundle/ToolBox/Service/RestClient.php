<?php
/**
 *
 * * *************************************************************
 **/
/**                                                                **/
/**
 *
 * This class defines generic method to invoke any rest ws
 **/
/**
 *
 * The communication is based on application/x-www-form-urlencode
 **/
/**                                                                **/
/**
 *
 * * *************************************************************
 **/


namespace AppBundle\ToolBox\Service;

class RestClient
{

    private $_url;

    public function setUrl($pUrl)
    {
        $this->_url = $pUrl;

        return $this;
    }


    /**
     * @param array $pParams
     * @param array $header
     * @return array|bool
     * Invoke Get Service
     */
    public function get($pParams = array(), $header = null)
    {
        return $this->_launch(
            $this->_makeUrl($pParams),
            $this->_createContext('GET', null, $header)
        );
    }


    /**
     * @param array $pPostParams
     * @param array $pGetParams
     * @param array $header
     * @return array|bool
     * Invoke Post service
     */
    public function post($pPostParams = array(), $pGetParams = array(), $header = null)
    {
        return $this->_launch(
            $this->_makeUrl($pGetParams),
            $this->_createContext('POST', $pPostParams, $header)
        );
    }


    /**
     * @param null  $pContent
     * @param array $pGetParams
     * @param array $header
     * @return array|bool
     * Invoke Put Service
     */
    public function put($pContent = null, $pGetParams = array(), $header = null)
    {
        return $this->_launch(
            $this->_makeUrl($pGetParams),
            $this->_createContext('PUT', $pContent, $header)
        );
    }


    /**
     * @param null  $pContent
     * @param array $pGetParams
     * @param array $header
     * @return array|bool
     * Invoke Delete Service
     */
    public function delete($pContent = null, $pGetParams = array(), $header = null)
    {
        return $this->_launch(
            $this->_makeUrl($pGetParams),
            $this->_createContext('DELETE', $pContent, $header)
        );
    }

    protected function _createContext($pMethod, $pContent = null, $header = null)
    {
        $headers = [];
        $headers['Content-type'] = "application/x-www-form-urlencoded";

        $headerString = "";

        foreach ($headers as $key => $value) {
            $headerString = $headerString.$key.$value."\r\n";
        }

        if ($header && is_array($header)) {
            foreach ($header as $key => $value) {
                $headerString = $headerString.$key.$value."\r\n";
            }
        }

        $opts = array(
            'http' => array(
                'method' => $pMethod,
                'header' => $headerString,
            ),
        );
        if ($pContent !== null) {
            if (is_array($pContent)) {
                $pContent = http_build_query($pContent);
            }
            $opts['http']['content'] = $pContent;
        }

        return stream_context_create($opts);
    }

    protected function _makeUrl($pParams)
    {
        $url = $this->_url.(strpos($this->_url, '?') ? '' : '?').http_build_query($pParams);

        return $url;
    }

    protected function _launch($pUrl, $context)
    {
        if (($stream = fopen($pUrl, 'r', false, $context)) !== false) {
            $content = stream_get_contents($stream);
            $header = stream_get_meta_data($stream);
            fclose($stream);

            return array('content' => $content, 'header' => $header);
        } else {
            return false;
        }
    }
}
