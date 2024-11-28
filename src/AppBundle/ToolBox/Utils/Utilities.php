<?php
/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 17/02/2016
 * Time: 14:58
 */

namespace AppBundle\ToolBox\Utils;

use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Utilities
{
    const  D_FORMAT_DATE = 'd/m/Y';
    const  D_FORMAT_TIME = 'H:m:s';
    const  D_FORMAT_DATE_TIME = 'd/m/Y H:m:s';

    public static function canBeString($value)
    {
        if (is_object($value) && method_exists($value, '__toString')) {
            return true;
        }

        if (is_null($value)) {
            return true;
        }

        return is_scalar($value);
    }

    static function startsWith($haystack, $needle)
    {
        // search backwards starting from haystack length characters from the end
        return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== false;
    }

    public static function compareDates(\DateTime $date1 = null, \DateTime $date2 = null)
    {
        if ($date1 === null || $date2 === null) {
            return null;
        }

        if ($date1->format('Y/m/d') == $date2->format("Y/m/d")) {
            return 0;
        } elseif ($date1->format('Y/m/d') > $date2->format("Y/m/d")) {
            return 1;
        } else {
            return -1;
        }
    }


    public static function exist($value, $key)
    {
        if (isset($value[$key]) && trim($value[$key]) != '') {
            return true;
        }

        return false;
    }

    /**
     * @param Request $request
     * @param $orders
     * @return array
     */
    public static function getDataTableHeader(Request $request, $orders = [])
    {
        if ($request->request->has('criteria')) {
            $criteria = $request->request->get('criteria');
        } else {
            $criteria = null;
        }

        if ($request->request->has('draw')) {
            $draw = $request->request->get('draw');
        } else {
            $draw = 1;
        }

        $orderBy = null;
        if ($request->request->has('order')) {
            $order = $request->request->get('order');
            $order = $order[0];
            foreach ($orders as $key => $value) {
                if (intval($order['column']) === $key) {
                    $orderBy['col'] = $value;
                    $orderBy['dir'] = $order['dir'];
                    break;
                }
            }
        }


        $offset = null;
        if ($request->request->has('start')) {
            $offset = intval($request->request->get('start'));
        }

        $limit = null;
        if ($request->request->has('length')) {
            $limit = intval($request->request->get('length'));
        }

        if ($request->request->has('search')) {
            $search = $request->request->get('search')['value'];
        } else {
            $search = null;
        }

        return array(
            'criteria' => $criteria,
            'orderBy' => $orderBy,
            'offset' => $offset,
            'limit' => $limit,
            'draw' => $draw,
            'search' => $search,
        );
    }

    public static function isStringable($value)
    {
        if (is_object($value) && method_exists($value, '__toString')) {
            return true;
        }

        if (is_null($value)) {
            return true;
        }

        return is_scalar($value);
    }

    public static function createFileResponse($filePath, $filename = null)
    {
        
        if ($filename === null) {
            $filename = $filePath;
        }

        if (!file_exists($filePath)) {
            throw new \Exception($filePath." doesn't exist");
        }
        // Generate response
        $response = new Response();
        $response->headers->set('Cache-Control', 'private');
        $response->headers->set('Content-Disposition', 'attachment; filename="'.basename($filename).'";');
    
        // Handle csv scenario
        if(pathinfo($filePath)["extension"] == "csv") {
            $response->headers->set('Content-type', 'application/csv');
            $response->setContent(utf8_decode(file_get_contents($filePath)));
            return $response;
        }

        // Handle OTHER FORMATS
        $response->headers->set('Content-length', filesize($filePath));
        $response->headers->set('Content-type', mime_content_type($filePath));
        // Send headers before outputting anything
       $response->sendHeaders();

        if (mime_content_type($filePath) == 'application/pdf') {
            $response->setContent(file_get_contents($filePath));
        } else {
            $response->setContent(utf8_decode(file_get_contents($filePath)));
        }

        return $response;
    }

    /**
     * @param $week int
     * @param $year int
     * @return \DateTime
     */
    public static function getMondyForWeek($week, $year)
    {
        $firstDayInYear = date("N", mktime(0, 0, 0, 1, 1, $year));
        if ($firstDayInYear < 5) {
            $shift = -($firstDayInYear - 1) * 86400;
        } else {
            $shift = (8 - $firstDayInYear) * 86400;
        }
        if ($week > 1) {
            $weekInSeconds = ($week - 1) * 604800;
        } else {
            $weekInSeconds = 0;
        }
        $timestamp = mktime(0, 0, 0, 1, 1, $year) + $weekInSeconds + $shift;
        $date = new \DateTime();
        $date->setTimestamp($timestamp);

        return $date;
    }

    public static function getDateFromDate(\DateTime $date, $diff)
    {
        $ts = mktime(
            0,
            0,
            0,
            intval($date->format('m')),
            intval($date->format('d')) + $diff,
            intval($date->format('Y'))
        );
        $t = new \DateTime();
        $t->setTimestamp($ts);

        return $t;
    }

    /**
     * Tests if an input is valid PHP serialized string.
     *
     * Checks if a string is serialized using quick string manipulation
     * to throw out obviously incorrect strings. Unserialize is then run
     * on the string to perform the final verification.
     *
     * Valid serialized forms are the following:
     * <ul>
     * <li>boolean: <code>b:1;</code></li>
     * <li>integer: <code>i:1;</code></li>
     * <li>double: <code>d:0.2;</code></li>
     * <li>string: <code>s:4:"test";</code></li>
     * <li>array: <code>a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}</code></li>
     * <li>object: <code>O:8:"stdClass":0:{}</code></li>
     * <li>null: <code>N;</code></li>
     * </ul>
     *
     * @author    Chris Smith <code+php@chris.cs278.org>
     * @copyright Copyright (c) 2009 Chris Smith (http://www.cs278.org/)
     * @license   http://sam.zoy.org/wtfpl/ WTFPL
     * @param     string $value  Value to test for serialized form
     * @param     mixed  $result Result of unserialize() of the $value
     * @return    boolean            True if $value is serialized data, otherwise false
     */
    public static function is_serialized($value, &$result = null)
    {
        // Bit of a give away this one
        if (!is_string($value) || strlen($value) == 0) {
            return false;
        }
        // Serialized false, return true. unserialize() returns false on an
        // invalid string or it could return false if the string is serialized
        // false, eliminate that possibility.
        if ($value === 'b:0;') {
            $result = false;

            return true;
        }
        $length = strlen($value);
        $end = '';
        switch ($value[0]) {
            case 's':
                if ($value[$length - 2] !== '"') {
                    return false;
                }
            case 'b':
            case 'i':
            case 'd':
                // This looks odd but it is quicker than isset()ing
                $end .= ';';
            case 'a':
            case 'O':
                $end .= '}';
                if ($value[1] !== ':') {
                    return false;
                }
                switch ($value[2]) {
                    case 0:
                    case 1:
                    case 2:
                    case 3:
                    case 4:
                    case 5:
                    case 6:
                    case 7:
                    case 8:
                    case 9:
                        break;
                    default:
                        return false;
                }
            case 'N':
                $end .= ';';
                if ($value[$length - 1] !== $end[0]) {
                    return false;
                }
                break;
            default:
                return false;
        }
        if (($result = @unserialize($value)) === false) {
            $result = null;

            return false;
        }

        return true;
    }

    public static function isValidDateFormat($date, $format)
    {
        switch ($format) {
            case "Y-m-d":
                if (preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $date)) {
                    return true;
                } else {
                    return false;
                }
                break;
            default:
                throw new \Exception('This format is not supported');
        }
    }

    public static function is_json($str)
    {
        return json_decode($str) != null;
    }

    public static function moveFileFromFtpToPath($filename, $path, $ftpHost, $ftpPort, $ftpUser, $ftpPw)
    {
        $moved = false;
        //Connect to the ftp
        $conId = ftp_connect($ftpHost, $ftpPort);

        if (!$conId) {
        } else {//Connexion établie
            $login = ftp_login($conId, $ftpUser, $ftpPw);
            if (!$login) {
            } else {//Connexion reussite
                //Turning passive mode
                ftp_pasv($conId, true);

                //test if a file exist
                $existingFiles = ftp_nlist($conId, '.');
                if (in_array($filename, $existingFiles)) {
                    $moved = ftp_get($conId, $path, $filename, FTP_ASCII);
                }
                // Fermeture de la connexion
                ftp_close($conId);
            }//End Cnx reusssite
        }

        return $moved;
    }

    public static function sendFileToFtp($localFile, $ftpHost, $ftpPort, $ftpUser, $ftpPw)
    {
        $moved = false;
        //Connect to the ftp
        $conId = ftp_connect($ftpHost, $ftpPort);

        if (!$conId) {
        } else {//Connexion établie
            $login = ftp_login($conId, $ftpUser, $ftpPw);
            if (!$login) {
            } else {//Connexion reussite
                //Turning passive mode
                ftp_pasv($conId, true);

                $moved = ftp_put($conId, basename($localFile), $localFile, FTP_ASCII);

                // Fermeture de la connexion
                ftp_close($conId);
            }//End Cnx reusssite
        }

        return $moved;
    }

    public static function removeEvents($class, EntityManager $manager)
    {
        // temporarily stores lifecycle events
        $events = $manager->getClassMetadata($class)->lifecycleCallbacks;

        // removes lifecycle events
        $manager->getClassMetadata($class)->setLifecycleCallbacks(array());

        return $events;
    }

    public static function returnEvents($class, EntityManager $manager, $events)
    {
        $manager->getClassMetadata($class)->setLifecycleCallbacks($events);
    }

    public static function generateRandomString($length = 10)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }

        return $randomString;
    }

    /**
     * @param \DateTime $date
     * @param string $format
     * @return bool|string
     */
    public static function formatDate($date, $format = Utilities::D_FORMAT_DATE_TIME)
    {

        return date_format($date, $format);
    }

    /**
     * function to search element in multidimensional array by key/ value pair
     * @param $array
     * @param $key
     * @param $value
     * @return int|null|string
     */
    public static function searchByKeyValue($array,$key,$value) {
        foreach ($array as  $index => $val) {
            if ($val[$key] === $value) {
                return $index;
            }
        }
        return null;
    }

    /**
     * @param $legnth
     * @param $precision
     * @param $value
     * @return string
     */
    public static function digitMask($length,$precision,$value){
        if(!is_int($precision) || !is_int($length)){
            return "Parameters input error";
        }
        $value=number_format(str_replace(",",".",strval($value)), $precision, '.', '');
        if (!is_numeric($value)) {
            return "Input error";
        }
        $p=1;
        for($i=1;$i<=$precision;$i++){
            $p*=10;
        }
        $result=round($value*$p);
        
        return str_pad($result, $length, "0", STR_PAD_LEFT);
    }


}
