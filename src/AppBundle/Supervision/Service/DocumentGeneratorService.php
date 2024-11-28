<?php

namespace AppBundle\Supervision\Service;

use AppBundle\ToolBox\Utils\Utilities;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 17/02/2016
 * Time: 14:08
 */
class DocumentGeneratorService
{

    private $DATE_FORMAT = "Y-m-d H:i:s";

    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param $headers
     * @param $lines
     * @param null    $format
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
     * @param null    $format
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
     * @param array                                                                                                              $params    Tableau associatif doit contenir une case dont l'indice est 'criteria' et une autre dont l'indice est 'order'
     * @param array                                                                                                              $header    OPTIONNEL => ça sera la
     *                                                                                                                                      1ere ligne du fichier
     * @param null                                                                                                               $rendering OPTIONNEL personnalise le rendu des valeurs peut
     *                                                                                                                                      être : - un callback qui prend en paramètre la
     *                                                                                                                                      ligne à afficher et l'indice de la ligne et il DOIT
     *                                                                                                                                      retourner un tableau de valeur scalar ou bien
     *                                                                                                                                      d'object qu'on peut les convertir en string - un
     *                                                                                                                                      tableau de callback dont les parametres sont la
     *                                                                                                                                      valuer de la case, la ligne en cours et l'indice de
     *                                                                                                                                      la ligne courante/ ou des valeur avec les indices
     *                                                                                                                                      sont les mêmes de la méthode retournée.
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
            throw new \Exception($serviceName." service not found");
        }
        $service = $this->container->get($serviceName);

        $reflectionClass = new \ReflectionClass(get_class($service));

        if (!$reflectionClass->hasMethod($methodName)) {
            throw new \Exception($methodName." method not found");
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
                throw new \Exception("Param not passed ".$name);
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
                ->getParameter('kernel.root_dir')."/../web/exportedFiles/".hash(
                    'md5',
                    rand(0, 1000) * rand(1000, 5000)
                )."_".date('Y_m_d_H_i_s_').".csv";
        $file = fopen($filePath, 'a+');

        if ($file == false) {
            return null;
        }

        if ($header && count($header) > 0) {
            fputcsv($file, $header);
        }

        $dataExist = true;
        $i = 0;
        $j = 0;
        while ($dataExist) {
            $paramsReflection[$offsetIndex] = $i;
            $result = $reflectionMethod->invokeArgs($service, $paramsReflection);

            if (count($result) === 0) {
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
                    fputcsv($file, $line);
                }
            }
        }

        fclose($file);

        return $filePath;
    }
}
