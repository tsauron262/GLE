<?php

require_once("../../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/../Bimp_Lib.php';

top_htmlhead('', 'CREA REVALS', 0, 0, array(), array());

echo '<body>';

BimpCore::displayHeaderFiles();

global $db;
$bdb = new BimpDb($db);

$tabInfo = array();
$tabInfo[] = array(
                        "entrepotSource" => 166,
                        "type" => 6,
                        "entrepot" => 50,
                        "date" => "2019-06-30 00:00:01",
                  );


$tabInfo[] = array(
                        "entrepotSource" => 136,
                        "type" => 9,
                        "entrepot" => 70,
                        "date" => '2014-01-01 00:00:01',
                  );

$tabInfo[] = array(
                        "entrepotSource" => 170,
                        "type" => 9,
                        "entrepot" => 70,
                        "date" => '2015-01-01 00:00:01',
                  );

$tabInfo[] = array(
                        "entrepotSource" => 222,
                        "type" => 9,
                        "entrepot" => 70,
                        "date" => '2016-01-01 00:00:01',
                  );

$tabInfo[] = array(
                        "entrepotSource" => 256,
                        "type" => 9,
                        "entrepot" => 70,
                        "date" => '2017-01-01 00:00:01',
                  );

$tabInfo[] = array(
                        "entrepotSource" => 366,
                        "type" => 9,
                        "entrepot" => 70,
                        "date" => '2018-01-01 00:00:01',
                  );

$tabInfo[] = array(
                        "entrepotSource" => 428,
                        "type" => 9,
                        "entrepot" => 70,
                        "date" => '2019-01-01 00:00:01',
                  );


$tabInfo[] = array(
                        "entrepotSource" => 134,
                        "type" => 9,
                        "entrepot" => 50,
                        "date" => '2014-01-01 00:00:01',
                  );

$tabInfo[] = array(
                        "entrepotSource" => 172,
                        "type" => 9,
                        "entrepot" => 50,
                        "date" => '2015-01-01 00:00:01',
                  );

$tabInfo[] = array(
                        "entrepotSource" => 224,
                        "type" => 9,
                        "entrepot" => 50,
                        "date" => '2016-01-01 00:00:01',
                  );

$tabInfo[] = array(
                        "entrepotSource" => 258,
                        "type" => 9,
                        "entrepot" => 50,
                        "date" => '2017-01-01 00:00:01',
                  );

$tabInfo[] = array(
                        "entrepotSource" => 370,
                        "type" => 9,
                        "entrepot" => 50,
                        "date" => '2018-01-01 00:00:01',
                  );

$tabInfo[] = array(
                        "entrepotSource" => 431,
                        "type" => 9,
                        "entrepot" => 50,
                        "date" => '2019-01-01 00:00:01',
                  );

$tabInfo[] = array(
                        "entrepotSource" => 262,
                        "type" => 9,
                        "entrepot" => 248,
                        "date" => '2017-01-01 00:00:01',
                  );
$tabInfo[] = array(
                        "entrepotSource" => 368,
                        "type" => 9,
                        "entrepot" => 248,
                        "date" => '2018-01-01 00:00:01',
                  );
$tabInfo[] = array(
                        "entrepotSource" => 422,
                        "type" => 9,
                        "entrepot" => 248,
                        "date" => '2019-01-01 00:00:01',
                  );


$tabInfo[] = array(
                        "entrepotSource" => 358,
                        "type" => 9,
                        "entrepot" => 180,
                        "date" => '2017-01-01 00:00:01',
                  );
$tabInfo[] = array(
                        "entrepotSource" => 372,
                        "type" => 9,
                        "entrepot" => 180,
                        "date" => '2018-01-01 00:00:01',
                  );
$tabInfo[] = array(
                        "entrepotSource" => 434,
                        "type" => 9,
                        "entrepot" => 180,
                        "date" => '2019-01-01 00:00:01',
                  );


$tabInfo[] = array(
                        "entrepotSource" => 374,
                        "type" => 9,
                        "entrepot" => 72,
                        "date" => '2018-01-01 00:00:01',
                  );
$tabInfo[] = array(
                        "entrepotSource" => 425,
                        "type" => 9,
                        "entrepot" => 72,
                        "date" => '2019-01-01 00:00:01',
                  );

/*demonstration*/

$tabInfo[] = array(
                        "entrepotSource" => 116,
                        "type" => 5,
                        "entrepot" => 66,
                        "date" => '2019-01-01 00:00:01',
                  );
$tabInfo[] = array(
                        "entrepotSource" => 118,
                        "type" => 5,
                        "entrepot" => 64,
                        "date" => '2019-01-01 00:00:01',
                  );
$tabInfo[] = array(
                        "entrepotSource" => 204,
                        "type" => 5,
                        "entrepot" => 196,
                        "date" => '2019-01-01 00:00:01',
                  );
$tabInfo[] = array(
                        "entrepotSource" => 144,
                        "type" => 5,
                        "entrepot" => 72,
                        "date" => '2019-01-01 00:00:01',
                  );
$tabInfo[] = array(
                        "entrepotSource" => 120,
                        "type" => 5,
                        "entrepot" => 68,
                        "date" => '2019-01-01 00:00:01',
                  );
$tabInfo[] = array(
                        "entrepotSource" => 250,
                        "type" => 5,
                        "entrepot" => 248,
                        "date" => '2019-01-01 00:00:01',
                  );
$tabInfo[] = array(
                        "entrepotSource" => 122,
                        "type" => 5,
                        "entrepot" => 62,
                        "date" => '2019-01-01 00:00:01',
                  );
$tabInfo[] = array(
                        "entrepotSource" => 142,
                        "type" => 5,
                        "entrepot" => 76,
                        "date" => '2019-01-01 00:00:01',
                  );
$tabInfo[] = array(
                        "entrepotSource" => 148,
                        "type" => 5,
                        "entrepot" => 54,
                        "date" => '2019-01-01 00:00:01',
                  );
$tabInfo[] = array(
                        "entrepotSource" => 124,
                        "type" => 5,
                        "entrepot" => 56,
                        "date" => '2019-01-01 00:00:01',
                  );
$tabInfo[] = array(
                        "entrepotSource" => 200,
                        "type" => 5,
                        "entrepot" => 180,
                        "date" => '2019-01-01 00:00:01',
                  );
$tabInfo[] = array(
                        "entrepotSource" => 288,
                        "type" => 5,
                        "entrepot" => 278,
                        "date" => '2019-01-01 00:00:01',
                  );
$tabInfo[] = array(
                        "entrepotSource" => 126,
                        "type" => 5,
                        "entrepot" => 70,
                        "date" => '2019-01-01 00:00:01',
                  );
$tabInfo[] = array(
                        "entrepotSource" => 146,
                        "type" => 5,
                        "entrepot" => 74,
                        "date" => '2019-01-01 00:00:01',
                  );
$tabInfo[] = array(
                        "entrepotSource" => 314,
                        "type" => 5,
                        "entrepot" => 296,
                        "date" => '2019-01-01 00:00:01',
                  );
$tabInfo[] = array(
                        "entrepotSource" => 326,
                        "type" => 5,
                        "entrepot" => 298,
                        "date" => '2019-01-01 00:00:01',
                  );
$tabInfo[] = array(
                        "entrepotSource" => 150,
                        "type" => 5,
                        "entrepot" => 52,
                        "date" => '2019-01-01 00:00:01',
                  );
$tabInfo[] = array(
                        "entrepotSource" => 126,
                        "type" => 5,
                        "entrepot" => 70,
                        "date" => '2019-01-01 00:00:01',
                  );
$tabInfo[] = array(
                        "entrepotSource" => 128,
                        "type" => 5,
                        "entrepot" => 58,
                        "date" => '2019-01-01 00:00:01',
                  );
$tabInfo[] = array(
                        "entrepotSource" => 302,
                        "type" => 5,
                        "entrepot" => 294,
                        "date" => '2019-01-01 00:00:01',
                  );






    $i = 0;
foreach($tabInfo as $infos){
    
    $req = "SELECT DISTINCT (a.id) FROM llx_be_equipment a LEFT JOIN llx_be_equipment_place places ON a.id = places.id_equipment WHERE places.position = 1 AND places.id_entrepot = ".$infos['entrepotSource']." AND places.type = 2 ORDER BY a.date_create";

    $sql = $db->query($req);
    $errors = array();
    while($ln = $db->fetch_object($sql)){
        $i++;
    //    $obj = BimpCache::getBimpObjectInstance("bimpequipment", 'Equipment', $ln->id);
    //    
    //    
    //    
    //    echo "<br/><br/>".$obj->ref;

        $id_equipment = $ln->id;

            if ($id_equipment > 0 && count($errors) == 0) {
                $emplacement = BimpObject::getInstance('bimpequipment', 'BE_Place');
                    $errors = array_merge($errors, $emplacement->validateArray(array(
                                'id_equipment' => $id_equipment,
                                'type'         => $infos['type'],
                                'id_entrepot'  => $infos['entrepot'],
                                'infos'        => 'Auto converssion des import 8Sens',
                                'code_mvt'     => $codemove,
                                'date'         => $infos['date']//dol_print_date(dol_now(), '%Y-%m-%d %H:%M:%S')
                    )));
                $errors = array_merge($errors, $emplacement->create());
                // Product
            }
    }
}

print_r($errors);
echo $i;