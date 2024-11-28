<?php
/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 10/03/2016
 * Time: 15:01
 */

namespace AppBundle\ToolBox\Service;

use Knp\Snappy\Pdf;
use Symfony\Bundle\TwigBundle\TwigEngine;

class PdfGeneratorService
{
    private $twig;
    private $tmpDir;
    private $pdfGenerator;

    public function __construct(TwigEngine $twig, Pdf $pdfGenerator, $tempDir)
    {
        $this->twig = $twig;
        $this->tmpDir = $tempDir;
        $this->pdfGenerator = $pdfGenerator;
    }

    public function generatePdfFromTwig($filename, $twigPath, $params, $options = [], $overwrite = false)
    {
        $html = $this->twig->render($twigPath, $params);
        $file_path = $this->tmpDir."/".$filename;
        #TEMPORARY FIX ( TRY CATCH )
        try {
            $this->pdfGenerator->generateFromHtml($html, $file_path, $options, $overwrite);
        } catch (\Exception $e) {}
        return $file_path;
    }
}
