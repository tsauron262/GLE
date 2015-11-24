<?php
require_once(DOL_DOCUMENT_ROOT."/synopsisapple/centre.inc.php");
class shipToList {
	static function init(){
		global $tabCentre;
foreach($tabCentre as $centre)
	if(isset($centre['7']))
self::$list[$centre['4']] =  array(
            'Name' => 'BIMP',
            'AttentionName' => 'SAV',
            'ShipperNumber' => 'R8X411',
            'Address' => array(
                'AddressLine' => $centre['7'],
                'City' => $centre['6'],
                'StateProvinceCode' => substr($centre['5'],0,2),
                'PostalCode' => $centre['5'],
                'CountryCode' => 'FR',
            ));/*echo "<pre>";
print_r(self::$list);*/
	}
  public static $list = array();
    /*public static $list = array(
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
    );*/

}


if(count(shipToList::$list)==0)
	shipToList::init();
