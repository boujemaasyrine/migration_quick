<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 01/06/2016
 * Time: 08:20
 */

namespace AppBundle\General\Service\Download;

class Ping extends AbstractDownloaderService
{

    public function download($idSynCmd = null)
    {
        $return = $this->startDownload($this->supervisionParams['ping'], null);

        return $return;
    }
}
