<?php


namespace AppBundle\Supervision\Service\ToolBox;

use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use AppBundle\ToolBox\Utils\ExcelUtilities;
use AppBundle\Supervision\Utils\Utilities;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 17/02/2016
 * Time: 14:08
 */
class SupervisionDocumentGeneratorService
{

    private $DATE_FORMAT = "Y-m-d H:i:s";

    private $container;

    private $cell;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param $headers
     * @param $lines
     * @param null $format
     * @return string Content of the csv file
     */
    public function generateCSVContentFile($headers, $lines, $format = null)
    {
        $fp = fopen('php://memory', 'r+');
        // Header
        fputcsv($fp, $headers);
        // Build up row
        foreach ($lines as $line) {
            $row = [];
            foreach ($line as $item) {
                if (Utilities::canBeString($item)) {
                    $row[] = $item;
                } elseif ($item instanceof \DateTime) {
                    if ($format) {
                        $row[] = date_format($item, $format);
                    } else {
                        $row[] = date_format($item, $this->DATE_FORMAT);
                    }
                }
            }
            fputcsv($fp, $row);
        }
        // Return the content
        rewind($fp);
        $csvContent = stream_get_contents($fp);
        fclose($fp);

        return $csvContent;
    }

    /**
     * @param $headers
     * @param $lines
     * @param null $format
     * @return string Content of the csv file
     */
    public function generateCSVContentFileAssociatedArray($array, $format = null)
    {
        // TODO Marwen: implement this method
    }

    /**
     * @param $serviceName  Nom du service inscrit dans le container du SF2 OBLIGATOIRE
     * @param $methodName Nom de la méthod du service passé OBLIGATOIRE, la méthode doit accepter 4 parametres dont le nom est
     * criteria, order, limit, offset ( l'ordre des paramètre n'est pas important)
     * @param array $params Tableau associatif doit contenir une case dont l'indice est 'criteria' et une autre dont l'indice est 'order'
     * @param array $header OPTIONNEL => ça sera la 1ere ligne du fichier
     * @param null $rendering OPTIONNEL personnalise le rendu des valeurs peut être :
     *    - un callback qui prend en paramètre la ligne à afficher et l'indice de la ligne
     *       et il DOIT retourner un tableau de valeur scalar ou bien d'object qu'on peut les convertir en string
     *    - un tableau de callback dont les parametres sont la valuer de la case, la ligne en cours et l'indice de la ligne courante/
     *       ou des valeur avec les indices sont les mêmes de la méthode retournée.
     * @return string|null : null si l'ouverture du fichier n'a pas eu lieu, le filepath du fichier
     * @throws \Exception : si
     *    - le service n'existe pas
     *    - la méthode n'existe pas
     *    - un des paramétre 'offset', 'limit', 'criteria' et 'order' n'existe pas
     *    - une valeur à inscrire dans le tableau ne peut pas être convertit en string
     */
    public function generateCsvFile($serviceName, $methodName, $params, $header = [], $rendering = null)
    {

        $step = 500;

        if (!$this->container->has($serviceName)) {
            throw new \Exception($serviceName . " service not found");
        }
        $service = $this->container->get($serviceName);

        $reflectionClass = new \ReflectionClass(get_class($service));

        if (!$reflectionClass->hasMethod($methodName)) {
            throw new \Exception($methodName . " method not found");
        }

        $reflectionMethod = new \ReflectionMethod(get_class($service), $methodName);

        $paramsReflection = [];

        $limitIndex = null;
        $offsetIndex = null;
        $i = 0;
        foreach ($reflectionMethod->getParameters() as $p) {
            $name = $p->getName();

            if ($name == 'limit') {
                $limitIndex = $i;
            } elseif ($name == 'offset') {
                $offsetIndex = $i;
            } elseif (array_key_exists($name, $params)) {
                $paramsReflection[$i] = $params[$name];
            } else {
                throw new \Exception("Param not passed " . $name);
            }

            $i++;
        }

        if ($limitIndex === null) {
            throw new \Exception("limit params doesn't exist");
        }

        if ($offsetIndex === null) {
            throw new \Exception("offset params doesn't exist");
        }

        $paramsReflection[$limitIndex] = $step;
        $paramsReflection[$offsetIndex] = 0;

        ksort($paramsReflection);

        $filePath = $this->container
                ->getParameter('kernel.root_dir') . "/../web/exportedFiles/" . hash('md5', rand(0, 1000) * rand(1000, 5000)) . "_" . date('Y_m_d_H_i_s_') . ".csv";
        $file = fopen($filePath, 'a+');

        if ($file == false) {
            return null;
        }

        if ($header && count($header) > 0) {
            fputs($file, implode(';', $header) . "\n");
        }

        $dataExist = true;
        $i = 0;
        $j = 0;
        while ($dataExist) {
            $paramsReflection[$offsetIndex] = $i;
            $result = $reflectionMethod->invokeArgs($service, $paramsReflection);

            if (!is_array($result) || count($result) === 0) {
                $dataExist = false;
            } else {
                $i += $step;
                foreach ($result as $r) {

                    $line = [];

                    //Construction de la ligne
                    if ($rendering != null) {
                        if (is_callable($rendering)) {
                            $line = $rendering($r, $j);
                        } elseif (is_array($rendering)) {
                            foreach ($r as $key => $value) {
                                if (isset($rendering[$key])) {
                                    if (is_callable($rendering[$key])) {
                                        $line[$key] = $rendering[$key]($value, $r, $j);
                                    } else {
                                        $line[$key] = $rendering[$key];
                                    }
                                } else {
                                    $line[$key] = $value;
                                }
                                if (!Utilities::isStringable($line[$key])) {
                                    throw new \Exception("Value cannot be converted to string");
                                }
                            }
                        }
                    } else {
                        $line = $r;
                    }
                    $j++;
                    fputs($file, implode(';', $line) . "\n");
                }
            }
        }

        fclose($file);
        return $filePath;
    }

    public function getFilePathFromSerializedResult($header = [], $result)
    {

        $filePath = $this->container
                ->getParameter('kernel.root_dir') . "/../data/tmp/" . hash('md5', rand(0, 1000) * rand(1000, 5000)) . "_" . date('Y_m_d_H_i_s_') . ".csv";
        $file = fopen($filePath, 'a+');

        if ($file == false) {
            return null;
        }

        if ($header && count($header) > 0 && !is_array($header[0])) {
            fputs($file, implode(';', $header) . "\n");
        } else if ($header && count($header) > 0 && is_array($header[0])) {
            foreach ($header as $head) {
                fputs($file, implode(';', $head) . "\n");
            }
        }

        foreach ($result as $r) {

            //Construction de la ligne
            $line = $r;

            fputs($file, implode(';', $line) . "\n");

        }


        fclose($file);
        return $filePath;
    }

    /**
     * @param string $serviceName Nom du service inscrit dans le container du SF2 OBLIGATOIRE
     * @param string $methodName Nom de la méthod du service passé OBLIGATOIRE, la méthode doit accepter 4 parametres dont le nom est
     * criteria, order, limit, offset ( l'ordre des paramètre n'est pas important)
     * @param array $params Tableau associatif doit contenir une case dont l'indice est 'criteria' et une autre dont l'indice est 'order'
     * @param array $header OPTIONNEL => ça sera la 1ere ligne du fichier
     * @param null $rendering OPTIONNEL personnalise le rendu des valeurs peut être :
     *    - un callback qui prend en paramètre la ligne à afficher et l'indice de la ligne
     *       et il DOIT retourner un tableau de valeur scalar ou bien d'object qu'on peut les convertir en string
     *    - un tableau de callback dont les parametres sont la valuer de la case, la ligne en cours et l'indice de la ligne courante/
     *       ou des valeur avec les indices sont les mêmes de la méthode retournée.
     * @param null $filename
     * @return string|null : null si l'ouverture du fichier n'a pas eu lieu, le filepath du fichier
     * @throws \Exception : si
     *    - le service n'existe pas
     *    - la méthode n'existe pas
     *    - un des paramétre 'offset', 'limit', 'criteria' et 'order' n'existe pas
     *    - une valeur à inscrire dans le tableau ne peut pas être convertit en string
     */
    public function generateXlsFile($serviceName, $methodName, $params, $header = [], $rendering = null, $filename = null)
    {
        $phpExcel = $this->container->get('phpexcel');

        $step = 500;

        if (!$this->container->has($serviceName)) {
            throw new \Exception($serviceName . " service not found");
        }
        $service = $this->container->get($serviceName);

        $reflectionClass = new \ReflectionClass(get_class($service));

        if (!$reflectionClass->hasMethod($methodName)) {
            throw new \Exception($methodName . " method not found");
        }

        $reflectionMethod = new \ReflectionMethod(get_class($service), $methodName);

        $paramsReflection = [];

        $limitIndex = null;
        $offsetIndex = null;
        $i = 0;
        foreach ($reflectionMethod->getParameters() as $p) {
            $name = $p->getName();

            if ($name == 'limit') {
                $limitIndex = $i;
            } elseif ($name == 'offset') {
                $offsetIndex = $i;
            } elseif (array_key_exists($name, $params)) {
                $paramsReflection[$i] = $params[$name];
            } else {
                throw new \Exception("Param not passed " . $name);
            }

            $i++;
        }

        if ($limitIndex === null) {
            throw new \Exception("limit params doesn't exist");
        }

        if ($offsetIndex === null) {
            throw new \Exception("offset params doesn't exist");
        }

        $paramsReflection[$limitIndex] = $step;
        $paramsReflection[$offsetIndex] = 0;

        ksort($paramsReflection);


        $phpExcelObject = $phpExcel->createPHPExcelObject();
        $phpExcelObject->setActiveSheetIndex(0);
        $sheet = $phpExcelObject->getActiveSheet();

        $row = 1;
        if ($header && count($header) > 0 && !is_array($header[0])) {
            $cell = $this->cellInit();
            foreach ($header as $headerCell) {
                ExcelUtilities::setTableHeader($sheet, $cell . $row, $cell . $row, $headerCell);
                $cell = $this->cellIncrement();
            }
            $row++;
            $countLines = 1;
            $countColumns = count($header);
        } else if ($header && count($header) > 0 && is_array($header[0])) {
            $columns = [];
            foreach ($header as $head) {
                $columns[] = count($head);
                $cell = $this->cellInit();
                foreach ($head as $headerCell) {
                    ExcelUtilities::setTableHeader($sheet, $cell . $row, $cell . $row, $headerCell);
                    $cell = $this->cellIncrement();
                }
                $row++;
            }
            $countLines = count($header);
            $countColumns = max($columns);
        }

        $dataExist = true;
        $i = 0;
        $j = 0;
        $lastColumn = chr($countColumns + 64);
        while ($dataExist) {
            $paramsReflection[$offsetIndex] = $i;
            $result = $reflectionMethod->invokeArgs($service, $paramsReflection);

            if (!is_array($result) || count($result) === 0) {
                $dataExist = false;
            } else {
                $countLines += count($result);
                $i += $step;
                foreach ($result as $r) {

                    $line = [];

                    //Construction de la ligne
                    if ($rendering != null) {
                        if (is_callable($rendering)) {
                            $line = $rendering($r, $j);
                        } elseif (is_array($rendering)) {
                            foreach ($r as $key => $value) {
                                if (isset($rendering[$key])) {
                                    if (is_callable($rendering[$key])) {
                                        $line[$key] = $rendering[$key]($value, $r, $j);
                                    } else {
                                        $line[$key] = $rendering[$key];
                                    }
                                } else {
                                    $line[$key] = $value;
                                }
                                if (!Utilities::isStringable($line[$key])) {
                                    throw new \Exception("Value cannot be converted to string");
                                }
                            }
                        }
                    } else {
                        $line = $r;
                    }
                    $j++;
                    $cell = $this->cellInit();
                    foreach ($line as $lineCell) {
                        ExcelUtilities::setOnlyValue($sheet, $cell . $row, $cell . $row, $lineCell);
                        $cell = $this->cellIncrement();
                    }
                    $row++;
                }
            }
        }
        $sheet
            ->getStyle('A1:'.$lastColumn.$countLines)
            ->getAlignment()
            ->setWrapText(true);
        ExcelUtilities::setBorder($sheet->getStyle('A1:'.$lastColumn.$countLines));

        // Response creation
        if ($filename == null)
            $filename = md5('xls_file_name') . (new \DateTime('now'))->format('d-m-Y--H-m-s');
        $filename = $filename . ".xls";
        // create the writer
        $writer = $phpExcel->createWriter($phpExcelObject, 'Excel5');
        // create the response
        $response = $phpExcel->createStreamedResponse($writer);
        // adding headers
        $dispositionHeader = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            basename($filename)
        );
        $response->headers->set('Content-Type', 'text/vnd.ms-excel; charset=utf-8');
        $response->headers->set('Pragma', 'public');
        $response->headers->set('Cache-Control', 'maxage=1');
        $response->headers->set('Content-Disposition', $dispositionHeader);
        return $response;
    }

    private function cellInit()
    {
        $this->cell = 1;
        return chr($this->cell + 64);
    }

    private function cellIncrement()
    {
        $return = '';
        if ($this->cell < 26) {
            $this->cell++;
            $return = chr($this->cell + 64);
        } else if ($this->cell >= 26 && $this->cell < 52) {
            $this->cell++;
            $return = chr(65) . chr($this->cell - 26 + 64);
        }

        return $return;
    }

    public function generateXlsFileMultipleSheet($serviceName, $methodNames, $params, $result, $filename = null)
    {
        $phpExcel = $this->container->get('phpexcel');

        $step = 500;

        if (!$this->container->has($serviceName)) {
            throw new \Exception($serviceName . " service not found");
        }
        $service = $this->container->get($serviceName);

        $reflectionClass = new \ReflectionClass(get_class($service));
        $reflectionMethod = array();

        foreach ($methodNames as $methodName) {
            if (!$reflectionClass->hasMethod($methodName)) {
                throw new \Exception($methodName . " method not found");
            }
            $reflectionMethod[] = new \ReflectionMethod(get_class($service), $methodName);
        }

        $paramsReflection = [];

        $limitIndex = [];
        $offsetIndex = [];
        for($i = 0; $i < count($methodNames); $i++){
            $limitIndex[$i] = null;
            $offsetIndex[$i] = null;
        }


        $index = 0;
        foreach($reflectionMethod as $method){
            /**
             * @var \ReflectionMethod $method
             */
            $i = 0;
            foreach ($method->getParameters() as $p) {
                $name = $p->getName();

                if ($name == 'limit') {
                    $limitIndex[$index] = $i;
                } elseif ($name == 'offset') {
                    $offsetIndex[$index] = $i;
                } elseif (array_key_exists($name, $params[$index])) {
                    $paramsReflection[$index][$i] = $params[$index][$name];
                } else {
                    throw new \Exception("Param not passed " . $name);
                }

                $i++;
            }
            $index++;
        }
        foreach($limitIndex as $index) {
            if ($index === null) {
                throw new \Exception("limit params doesn't exist");
            }
        }

        foreach ($offsetIndex as $index) {
            if ($index === null) {
                throw new \Exception("offset params doesn't exist");
            }
        }

        $index = 0;
        foreach($paramsReflection as &$param){
            $param[$limitIndex[$index]] = $step;
            $param[$offsetIndex[$index]] = 0;
            $index++;
        }

        foreach($paramsReflection as &$param){
            ksort($param);
        }

        $phpExcelObject = $phpExcel->createPHPExcelObject();
        $phpExcelObject->removeSheetByIndex();
        $indexSheet = 0;
        foreach($result as $resultSheet){
            $sheet = $phpExcelObject->createSheet();
            $sheet->setTitle($resultSheet['title']);

            $row = 1;
            if ($resultSheet['header'] && count($resultSheet['header']) > 0 && !is_array($resultSheet['header'][0])) {
                $cell = $this->cellInit();
                foreach ($resultSheet['header'] as $headerCell) {
                    ExcelUtilities::setTableHeader($sheet, $cell . $row, $cell . $row, $headerCell);
                    $cell = $this->cellIncrement();
                }
                $row++;
                $countLines = 1;
                $countColumns = count($resultSheet['header']);
            } else if ($resultSheet['header'] && count($resultSheet['header']) > 0 && is_array($resultSheet['header'][0])) {
                $columns = [];
                foreach ($resultSheet['header'] as $head) {
                    $columns[] = count($head);
                    $cell = $this->cellInit();
                    foreach ($head as $headerCell) {
                        ExcelUtilities::setTableHeader($sheet, $cell . $row, $cell . $row, $headerCell);
                        $cell = $this->cellIncrement();
                    }
                    $row++;
                }
                $countLines = count($resultSheet['header']);
                $countColumns = max($columns);
            }

            $dataExist = true;
            $i = 0;
            $j = 0;
            $lastColumn = chr($countColumns + 64);
            while ($dataExist) {
                $paramsReflection[$indexSheet][$offsetIndex[$indexSheet]] = $i;
                $result = $reflectionMethod[$indexSheet]->invokeArgs($service, $paramsReflection[$indexSheet]);
                //dump($result);
                if (!isset($result) || !is_array($result) || count($result) === 0) {
                    $dataExist = false;
                } else {
                    $countLines += count($result);
                    $i += $step;
                    foreach ($result as $r) {
                        $line = [];

                        //Construction de la ligne
                        if (isset($resultSheet['rendering'])) {
                            if (is_callable($resultSheet['rendering'])) {
                                $line = $resultSheet['rendering']($r, $j);
                            } elseif (is_array($resultSheet['rendering'])) {
                                foreach ($r as $key => $value) {
                                    if (isset($resultSheet['rendering'][$key])) {
                                        if (is_callable($resultSheet['rendering'][$key])) {
                                            $line[$key] = $resultSheet['rendering'][$key]($value, $r, $j);
                                        } else {
                                            $line[$key] = $resultSheet['rendering'][$key];
                                        }
                                    } else {
                                        $line[$key] = $value;
                                    }
                                    if (!Utilities::isStringable($line[$key])) {
                                        throw new \Exception("Value cannot be converted to string");
                                    }
                                }
                            }
                        } else {
                            $line = $r;
                        }
                        $j++;
                        $cell = $this->cellInit();
                        foreach ($line as $lineCell) {
                            ExcelUtilities::setOnlyValue($sheet, $cell . $row, $cell . $row, $lineCell);
                            $cell = $this->cellIncrement();
                        }
                        $row++;
                    }
                }
            }

            $sheet
                ->getStyle('A1:'.$lastColumn.$countLines)
                ->getAlignment()
                ->setWrapText(true);
            ExcelUtilities::setBorder($sheet->getStyle('A1:'.$lastColumn.$countLines));
            $indexSheet += 1;
        }

        $phpExcelObject->setActiveSheetIndex(0);

        // Response creation
        if ($filename == null)
            $filename = md5('xls_file_name') . (new \DateTime('now'))->format('d-m-Y--H-m-s');
        $filename = $filename . ".xls";
        // create the writer
        $writer = $phpExcel->createWriter($phpExcelObject, 'Excel5');
        // create the response
        $response = $phpExcel->createStreamedResponse($writer);
        // adding headers
        $dispositionHeader = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            basename($filename)
        );
        $response->headers->set('Content-Type', 'text/vnd.ms-excel; charset=utf-8');
        $response->headers->set('Pragma', 'public');
        $response->headers->set('Cache-Control', 'maxage=1');
        $response->headers->set('Content-Disposition', $dispositionHeader);
        return $response;
    }


}
