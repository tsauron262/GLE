<?php

class BCT_Tools
{

    public static function getPeriodEndDate($date_start, $duration, $with_hours = false, $unit = 'M')
    {
        $dt = new DateTime($date_start);
        $dt->add(new DateInterval('P' . $unit . 'M'));
        $dt->sub(new DateInterval('P1D'));
        return $dt->format('Y-m-d') . ($with_hours ? ' 23:59:59' : '');
    }
}
