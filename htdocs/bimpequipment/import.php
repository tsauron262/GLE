<?php

require_once __DIR__ . '/../main.inc.php';

ini_set('display_errors', 1);
set_time_limit(0);

require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';

$file = __DIR__ . '/prodSerialutff84.csv';
if (file_exists($file)) {
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    if (!$lines || !count($lines)) {
        die('Fichier vide ou invalide');
    }

    $nSuccess = 0;
    $nExists = 0;
    $nNoRef = 0;
    $nRows = 0;
    $errors = array();

    $idx = array(
        'date'   => 5,
        'ref'    => 6,
        'serial' => 8,
        'dep'    => 9,
        'type'   => 22
    );

    unset($lines[0]);
    unset($lines[1]);

    $entrepots = array();

    global $db;
    $bdb = new BimpDb($db);
    $rows = $bdb->getRows('entrepot', '1', null, 'array', array('rowid', 'ref'));

    if (is_null($rows) || !count($rows)) {
        die('Aucun entrepot trouvé ou échec récupération des entrepots');
    }

    foreach ($rows as $r) {
        if (array_key_exists($r['ref'], $entrepots)) {
            echo 'Code Entrepot en double: ' . $r['ref'] . '<br/>';
        } else {
            $entrepots[$r['ref']] = (int) $r['rowid'];
        }
    }

    $equipment = BimpObject::getInstance('bimpequipment', 'Equipment');
    $product = BimpObject::getInstance('bimpcore', 'Bimp_Product');
    $place = BimpObject::getInstance('bimpequipment', 'BE_Place');

    foreach ($lines as $key => $line) {
        $nRows++;
        $n = $key + 1;
        $fields = explode(';', $line);
        if (count($fields)) {
            $product->reset();
            if (!$fields[$idx['ref']]) {
                $errors[] = 'LIGNE ' . $n . ' - Réf. absente';
                continue;
            }

            if (!$fields[$idx['serial']]) {
                $errors[] = 'LIGNE ' . $n . ' - Serial. absent';
                continue;
            }

            if (!$fields[$idx['dep']]) {
                $errors[] = 'LIGNE ' . $n . ' - Dépôt. absent';
                continue;
            }

            if (!array_key_exists($fields[$idx['dep']], $entrepots)) {
                $errors[] = 'LIGNE ' . $n . ' - le dépôt "' . $fields[$idx['dep']] . '" n\'existe pas';
                continue;
            }

            if (!$product->find(array(
                        'ref' => $fields[$idx['ref']]
                    ))) {
                echo 'LIGNE ' . $n . ' - REF: ' . $fields[$idx['ref']] . ' - SERIAL: ' . $fields[$idx['serial']] . '<br/>';
                $nNoRef++;
                continue;
            }

            if ($equipment->equipmentExists($fields[$idx['serial']], (int) $product->id)) {
                $nExists++;
                continue;
            }

            switch ($fields[$idx['type']]) {
                case 'MATERIEL':
                case 'MATÉRIEL':
                    $type = 1;
                    break;

                case 'LOGICIELS':
                    $type = 3;
                    break;

                default:
                    $type = 6;
                    break;
            }

            $date = '';
            if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $fields[$idx['date']], $matches)) {
                $date = $matches[3] . '-' . $matches[2] . '-' . $matches[1] . ' 00:00:00';
            }

            $equipment->reset();

            $data_errors = $equipment->validateArray(array(
                'id_product'  => (int) $product->id,
                'type'        => $type,
                'serial'      => $fields[$idx['serial']],
                'date_create' => $date
            ));

            if (count($data_errors)) {
                $errors[] = 'LIGNE ' . $n . ' - données invalides: ' . print_r($data_errors, true) . '<br/><br/>';
                continue;
            }

            $create_errors = $equipment->create();

            if (count($create_errors)) {
                $errors[] = 'LIGNE ' . $n . ' - Echec Création équipement: ' . print_r($create_errors, true) . '<br/><br/>';
            } else {
                $nSuccess++;

                $place->reset();
                $data_errors = $place->validateArray(array(
                    'id_equipment' => (int) $equipment->id,
                    'type'         => 2,
                    'id_entrepot'  => (int) $entrepots[$fields[$idx['dep']]],
                    'date'         => $date
                ));

                if (count($data_errors)) {
                    $errors[] = 'LIGNE ' . $n . ' - données invalides pour l\'emplacement: ' . print_r($data_errors, true) . '<br/><br/>';
                } else {
                    $create_errors = $place->create();

                    if (count($create_errors)) {
                        $errors[] = 'LIGNE ' . $n . ' - Echec Création emplacement: ' . print_r($create_errors, true) . '<br/><br/>';
                    }
                }
            }
        } else {
            $errors[] = 'Aucun champ trouvé';
        }
    }

//    echo $nRows . ' lignes traitées <br/>';
//    echo $nSuccess . ' Equipements créés avec succès <br/>';
//    echo $nNoRef . ' Références absentes<br/>';
//    echo $nExists . ' n° de série déjà enregistrés<br/><br/>';
//    echo count($errors) . ' erreur(s): <br/>';
//
//    foreach ($errors as $e) {
//        echo ' - ' . $e . '<br/>';
//    }
} else {
    echo 'Fichier absent';
}

