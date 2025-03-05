<?php

if (!defined('BIMP_LIB')) {
    require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
}
//BimpCore::requireFileForEntity('bimpsupport', 'centre.inc.php');
$tabCentre = BimpCache::getCentres();

class shipToList
{

    public static $list = array();

    static function init()
    {
//        global $tabCentre;
		$tabCentre = BimpCache::getCentres();
        foreach ($tabCentre as $centre)
            if (isset($centre['address']))
                self::$list[$centre['4']] = array(
                    'Name'          => 'BIMP',
                    'AttentionName' => 'SAV',
                    'ShipperNumber' => 'R8X411',
//            'ShipperNumber' => '4W63V6VCVF8',
                    'Address'       => array(
                        'AddressLine'       => $centre['address'],
                        'City'              => $centre['town'],
                        'StateProvinceCode' => substr($centre['zip'], 0, 2),
                        'PostalCode'        => $centre['zip'],
                        'CountryCode'       => 'FR',
                    ),
                    'Phone'         => array(
                        'Number' => $centre['tel']
                )); /* echo "<pre>";
                  print_r(self::$list); */
    }

    /* public static $list = array(
      '0000462140' => array(
      'Name' => 'BIMP',
      'AttentionName' => 'SAV',
      'ShipperNumber' => 'R8X411',
      'Address' => array(
      'AddressLine' => '3 Rue du Vieux Moulin',
      'City' => 'Meythet',
      'StateProvinceCode' => '74',
      'PostalCode' => '74960',
      'CountryCode' => 'FR',
      ),
      'Phone' => array(
      'Number' => '0450227597'
      )
      ),
      '0000494685' => array(
      'Name' => 'BIMP',
      'AttentionName' => 'SAV',
      'ShipperNumber' => 'R8X411',
      'Address' => array(
      'AddressLine' => '5 rue des arts et métiers',
      'City' => 'Grenoble',
      'StateProvinceCode' => '38',
      'PostalCode' => '38000',
      'CountryCode' => 'FR',
      ),
      'Phone' => array(
      'Number' => '0476230518'
      )
      )
      ); */
}

if (count(shipToList::$list) == 0)
    shipToList::init();
