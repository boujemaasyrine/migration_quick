<?php
/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 01/03/2016
 * Time: 11:30
 */

namespace AppBundle\Supervision\Utils;

class DateUtilities
{
    static function validateDate($date)
    {
        $d = \DateTime::createFromFormat('d/m/Y', $date);

        return $d && $d->format('d/m/Y') == $date;
    }

    /*
     * Returns number of all days of the week between two dates
     * Return array with keys (Monday, Tuesday,..,Sunday, total) with its values and (total for number of days in the interval)
    */

    static function getNbrDays($begin, $end)
    {

        $dateBegin = new \DateTime($begin);
        $dateEnd = new \DateTime($end);
        $resultTab = array();
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        foreach ($days as $day) {
            $resultTab[$day] = 0;
        }
        $total = $dateBegin->diff($dateEnd)->format("%a");
        $resultTab['total'] = intval($total) + 1;

        while ($dateEnd->format('Y-m-d D') >= $dateBegin->format('Y-m-d D')) {
            if (array_key_exists($dateBegin->format('l'), $resultTab)) {
                $resultTab[$dateBegin->format('l')]++;
            } else {
                $resultTab[$dateBegin->format('l')] = 1;
            }
            $dateBegin->add(new \DateInterval('P1D'));
        }

        return $resultTab;
    }

    static function getWeeks($begin, $end)
    {

        $firstWeek = date_format(date_create_from_format('d/m/Y', $begin), 'W');
        $lastWeek = date_format(date_create_from_format('d/m/Y', $end), 'W');
        $nbrWeek = $lastWeek - $firstWeek + 1;
        if ($nbrWeek > 0) {
            switch (date_format(date_create_from_format('d/m/Y', $begin), 'l')) {
                case 'Monday':
                    $week[$firstWeek]['nbrDays'] = 7;
                    break;
                case 'Tuesday':
                    $week[$firstWeek]['nbrDays'] = 6;
                    break;
                case 'Wednesday':
                    $week[$firstWeek]['nbrDays'] = 5;
                    break;
                case 'Thursday':
                    $week[$firstWeek]['nbrDays'] = 4;
                    break;
                case 'Friday':
                    $week[$firstWeek]['nbrDays'] = 3;
                    break;
                case 'Saturday':
                    $week[$firstWeek]['nbrDays'] = 2;
                    break;
                case 'Sunday':
                    $week[$firstWeek]['nbrDays'] = 1;
                    break;
            }

            for ($i = 1; $i < $nbrWeek - 1; $i++) {
                $week[$firstWeek + $i]['nbrDays'] = 7;
            }

            switch (date_format(date_create_from_format('d/m/Y', $end), 'l')) {
                case 'Monday':
                    $week[$lastWeek]['nbrDays'] = 1;
                    break;
                case 'Tuesday':
                    $week[$lastWeek]['nbrDays'] = 2;
                    break;
                case 'Wednesday':
                    $week[$lastWeek]['nbrDays'] = 3;
                    break;
                case 'Thursday':
                    $week[$lastWeek]['nbrDays'] = 4;
                    break;
                case 'Friday':
                    $week[$lastWeek]['nbrDays'] = 5;
                    break;
                case 'Saturday':
                    $week[$lastWeek]['nbrDays'] = 6;
                    break;
                case 'Sunday':
                    $week[$lastWeek]['nbrDays'] = 7;
                    break;
            }
        }

        return $week;
    }

    /**
     * @param \DateTime $dateBegin
     * @param \DateTime $dateEnd
     * @return array
     */
    static function getDays($dateBegin, $dateEnd)
    {
        $startDate = clone $dateBegin;
        $days = array();
        while ($startDate <= $dateEnd) {
            $days[] = clone $startDate;
            $startDate->add(new \DateInterval('P1D'));
        }

        return $days;
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

    public static function isToday($date)
    {
        $today = new \DateTime(); // This object represents current date/time
        $today->setTime(0, 0, 0); // reset time part, to prevent partial comparison
        $date->setTime(0, 0, 0); // reset time part, to prevent partial comparison
        $diff = $today->diff($date);
        $diffDays = (integer) $diff->format("%R%a"); // Extract days count in interval

        return $diffDays === 0;
    }
}
