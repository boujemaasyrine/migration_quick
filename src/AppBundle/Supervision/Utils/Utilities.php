<?php
/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 17/02/2016
 * Time: 14:58
 */

namespace AppBundle\Supervision\Utils;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Utilities
{

    public static function canBeString($value)
    {
        if (is_object($value) and method_exists($value, '__toString')) {
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

        $search = null;
        if ($request->request->has('search')) {
            $search = $request->request->get('search')['value'];
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
        if (is_object($value) and method_exists($value, '__toString')) {
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

        // Set headers
        $response->headers->set('Cache-Control', 'private');
        $response->headers->set('Content-type', mime_content_type($filePath));
        $response->headers->set('Content-Disposition', 'attachment; filename="'.basename($filename).'";');
        $response->headers->set('Content-length', filesize($filePath));

        // Send headers before outputting anything
        $response->sendHeaders();

        $response->setContent(file_get_contents($filePath));

        return $response;
    }

    public static function createCsvFileResponse($filePath, $filename = null)
    {
        if ($filename === null) {
            $filename = $filePath;
        }

        if (!file_exists($filePath)) {
            throw new \Exception($filePath . " doesn't exist");
        }
        // Generate response
        $response = new Response();
        // Set headers
        $response->headers->set('Cache-Control', 'private');
        $response->headers->set('Content-type', 'application/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . basename($filename) . '";');
        $response->headers->set('Content-length', filesize($filePath));
        $response->setCharset('UTF-8');

        // Send headers before outputting anything
        $response->sendHeaders();

        $response->setContent(file_get_contents($filePath));

        return $response;

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
}
