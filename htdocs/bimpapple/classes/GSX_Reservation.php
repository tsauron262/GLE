<?php

require_once DOL_DOCUMENT_ROOT . '/bimpapple/classes/GSX_v2.php';

class GSX_Reservation
{

    public static $mode = 'prod';
    public static $products_codes = array('IPOD', 'IPAD', 'IPHONE', 'WATCH', 'APPLETV', 'MAC', 'BEATS', 'AIRPODS', 'HOMEPOD');
    public static $default_tech_id = 'G1DFE7494B';
    protected static $gsx_v2 = null;

    public static function useGsxV2()
    {
        return 1;
    }

    public static function getGsxV2()
    {
        if (is_null(self::$gsx_v2)) {
            self::$gsx_v2 = new GSX_v2();
        }

        return self::$gsx_v2;
    }

    public static function getProductsCode()
    {
        $gsx_v2 = self::getGsxV2();

        if (!$gsx_v2->logged) {
            $errors[] = 'Non connecté à GSX';
            return array();
        }
        $type = 'PRODUCT_FAMILY_CLASS_PRODUCT_GROUP_MAP';
        $return = array();
        $result = $gsx_v2->attributeLookup($type);
        if (isset($result[$type])) {
            foreach ($result[$type] as $data) {
                $return[$data['key']] = $data['text'];
            }
        }
        return $return;
    }

    public static function fetchReservationsSummay($soldTo, $shipTo, $productCode, $from, $to, &$errors = array(), &$debug = '')
    {
        if (!(int) $soldTo || !(int) $shipTo) {
            return array();
        }

        $gsx_v2 = self::getGsxV2();

        if (!$gsx_v2->logged) {
            $errors[] = 'Non connecté à GSX';
            return array();
        }

        $gsx_v2->setSoldTo($soldTo);
        $gsx_v2->setShipTo($shipTo);

        $gsx_v2->resetErrors();

        $reservations = $gsx_v2->fetchReservationsSummary($shipTo, $from, $to, $productCode);

        $curl_errors = $gsx_v2->errors['curl'];

        foreach ($curl_errors as $error) {
            if (isset($error['code']) && $error['code'] == 'RESERVATIONS_NOT_FOUND') {
                return array();
            }
        }

        $errors = BimpTools::merge_array($errors, $gsx_v2->getErrors());

        return $reservations;
    }

    public static function fetchReservation($soldTo, $shipTo, $reservation_id, &$errors = array(), &$debug = '')
    {
        $gsx_v2 = self::getGsxV2();

        if (!$gsx_v2->logged) {
            $errors[] = 'Non connecté à GSX';
            return array();
        }

        $gsx_v2->setSoldTo($soldTo);
        $gsx_v2->setShipTo($shipTo);
        $gsx_v2->resetErrors();

        $reservation = $gsx_v2->fetchReservation($shipTo, $reservation_id);
        $errors = BimpTools::merge_array($errors, $gsx_v2->getErrors());

        return $reservation;
    }

    public static function fetchAvailableSlots($soldTo, $shipTo, $product_code, &$errors = array(), &$debug = '')
    {
        $gsx_v2 = self::getGsxV2();

        if (!$gsx_v2->logged) {
            $errors[] = 'Non connecté à GSX';
            return array();
        }

        $gsx_v2->setSoldTo($soldTo);
        $gsx_v2->setShipTo($shipTo);
        $gsx_v2->resetErrors();

        $slots = $gsx_v2->fetchAvailableSlots($shipTo, $product_code);

        $curl_errors = $gsx_v2->errors['curl'];

        foreach ($curl_errors as $error) {
            if (isset($error['code']) && $error['code'] == 'NO_SLOTS_AVAILABLE') {
                return array();
            }
        }

        $errors = BimpTools::merge_array($errors, $gsx_v2->getErrors());

        return $slots;
    }

    public static function createReservation($soldTo, $shipTo, $params, &$errors = array(), &$debug = '')
    {
        $gsx_v2 = self::getGsxV2();

        if (!$gsx_v2->logged) {
            $errors[] = 'Non connecté à GSX';
            return array();
        }

        $gsx_v2->setSoldTo($soldTo);
        $gsx_v2->setShipTo($shipTo);
        $gsx_v2->resetErrors();

        $reservations = $gsx_v2->createReservation($shipTo, $params);
        $errors = BimpTools::merge_array($errors, $gsx_v2->getErrors());

        return $reservations;
    }

    public static function cancelReservation($soldTo, $shipTo, $reservationId, &$errors = array(), &$debug = '', $params = array())
    {
        $gsx_v2 = self::getGsxV2();

        if (!$gsx_v2->logged) {
            $errors[] = 'Non connecté à GSX';
            return array();
        }

        $gsx_v2->setSoldTo($soldTo);
        $gsx_v2->setShipTo($shipTo);
        $gsx_v2->resetErrors();

        $result = $gsx_v2->cancelReservation($shipTo, $reservationId);
        $errors = BimpTools::merge_array($errors, $gsx_v2->getErrors());

        return $result;
    }
}
