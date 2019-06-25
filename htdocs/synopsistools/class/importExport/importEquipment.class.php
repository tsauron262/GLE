<?php

require_once DOL_DOCUMENT_ROOT . "/synopsistools/class/importExport/import8sens.class.php";

class importEquiment extends import8sens {

    public function __construct($db) {
        parent::__construct($db);
        $this->path = $this->path . "equipment/";
    }

    public function go() {
        $this->netoyage = false;
        $this->nSuccess = 0;
        $this->nExists = 0;
        $this->nNoRef = 0;
        $this->nSuppr = 0;
        $this->nRows = 0;
        $this->errors = array();


        $this->idx = array(
            'date'   => 5,
            'ref'    => 'OpeGArtCode',
            'serial' => 'OpeNumSerie',
            'dep'    => 'OpeGDepCode',
            'type'   => 22
        );

        $this->entrepots = array();

        global $db;
        $bdb = new BimpDb($db);
        $rows = $bdb->getRows('entrepot', '1', null, 'array', array('rowid', 'ref'));

        if (is_null($rows) || !count($rows)) {
            die('Aucun entrepot trouvé ou échec récupération des entrepots');
        }

        foreach ($rows as $r) {
            if (array_key_exists($r['ref'], $this->entrepots)) {
                echo 'Code Entrepot en double: ' . $r['ref'] . '<br/>';
            } else {
                $this->entrepots[$r['ref']] = (int) $r['rowid'];
            }
        }

        $this->equipment = BimpObject::getInstance('bimpequipment', 'Equipment');
        $this->product = BimpObject::getInstance('bimpcore', 'Bimp_Product');
        $this->place = BimpObject::getInstance('bimpequipment', 'BE_Place');
        
        
        
        
        
        
        $this->maxLn = 0;
        
        parent::go();
        
        
        
        
        echo $this->nSuppr . ' lignes supprimé<br/>';
        echo $this->nRows . ' lignes traitées <br/>';
        echo $this->nSuccess . ' Equipements créés avec succès <br/>';
        echo $this->nNoRef . ' Références absentes<br/>';
        echo $this->nExists . ' n° de série déjà enregistrés<br/><br/>';
        echo count($this->errors) . ' erreur(s): <br/>';

        foreach ($this->errors as $e) {
            echo ' - ' . $e . '<br/>';
        }
    }

    function traiteLn($ln) {
        if(!$this->netoyage){
            $list = $this->equipment->getList(array('epl.type'=> (int) BE_Place::BE_PLACE_ENTREPOT,
    //            'epl.id_entrepot' => (int) 66,
                'epl.position' => 1

                ), null, null, 'id', 'DESC', 'array', null, array(array('table'=>'be_equipment_place', 'alias' => 'epl', 'on' => 'a.id = epl.id_equipment')));

            foreach($list as $elem){
                    $obj = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', $elem['id_equipment']);
                    $obj->delete();
                    $this->nSuppr++;
            }
            $this->netoyage = true;
        }
        
        
        
        
         $this->nRows++;
        $n = $this->nRows;
        $fields = $ln;
        if (count($fields)){
            if(!in_array($fields[$this->idx['serial']], array("HDTB310EK3AA***001", "3697307", "24915466", "25002482"))) {
                if($fields[$this->idx['serial']] == "2766041934\n2766041934\n")
                    $fields[$this->idx['serial']] = "2766041934";
                
                
                $this->product->reset();
                if (!$fields[$this->idx['ref']]) {
                    $this->errors[] = 'LIGNE ' . $n . ' - Réf. absente';
                    return 0;
                }

                if (!$fields[$this->idx['serial']]) {
                    $this->errors[] = 'LIGNE ' . $n . ' - Serial. absent';
                    return 0;
                }

                if (!$fields[$this->idx['dep']]) {
                    $this->errors[] = 'LIGNE ' . $n . ' - Dépôt. absent';
                    return 0;
                }

                if (!array_key_exists($fields[$this->idx['dep']], $this->entrepots)) {
                    $this->errors[] = 'LIGNE ' . $n . ' - le dépôt "' . $fields[$this->idx['dep']] . '" n\'existe pas';
                    return 0;
                }

                if (!$this->product->find(array(
                            'ref' => $fields[$this->idx['ref']]
                        ))) {
                    echo 'LIGNE ' . $n . ' - REF: ' . $fields[$this->idx['ref']] . ' - SERIAL: ' . $fields[$this->idx['serial']] . '<br/>';
                    $this->nNoRef++;
                    return 0;
                }
                if ($this->equipment->equipmentExists($fields[$this->idx['serial']], (int) $this->product->id)) {
                    $this->nExists++;
                    $this->error("Serial deja existant ".$fields[$this->idx['serial']]);
                    return 0;
                }

                switch ($fields[$this->idx['type']]) {
                    case 'MATERIEL':
                    case 'MATÉRIEL':
                        $type = 1;
                        break;

                    case 'LOGICIELS':
                        $type = 3;
                        break;

                    default:
                        $type = 1;
                        break;
                }

                $date = '2019-06-30 00:00:01';
    //            if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $fields[$this->idx['date']], $matches)) {
    //                $date = $matches[3] . '-' . $matches[2] . '-' . $matches[1] . ' 00:00:00';
    //            }

                $this->equipment->reset();

                $data_errors = $this->equipment->validateArray(array(
                    'id_product'  => (int) $this->product->id,
                    'type'        => $type,
                    'serial'      => $fields[$this->idx['serial']],
                    'date_create' => $date
                ));

                if (count($data_errors)) {
                    $this->errors[] = 'LIGNE ' . $n . ' - données invalides: ' . print_r($data_errors, true) . '<br/><br/>';
                    return 0;
                }

                $create_errors = $this->equipment->create();

                if (count($create_errors)) {
                    $this->errors[] = 'LIGNE ' . $n . ' - Echec Création équipement: ' . print_r($create_errors, true) . '<br/><br/>';
                } else {
                    $this->nSuccess++;

                    $this->place->reset();
                    $data_errors = $this->place->validateArray(array(
                        'id_equipment' => (int) $this->equipment->id,
                        'type'         => 2,
                        'id_entrepot'  => (int) $this->entrepots[$fields[$this->idx['dep']]],
                        'date'         => $date,
                        'infos'        => "Import 8sens"
                    ));

                    if (count($data_errors)) {
                        $this->errors[] = 'LIGNE ' . $n . ' - données invalides pour l\'emplacement: ' . print_r($data_errors, true) . '<br/><br/>';
                    } else {
                        $create_errors = $this->place->create();

                        if (count($create_errors)) {
                            $this->errors[] = 'LIGNE ' . $n . ' - Echec Création emplacement: ' . print_r($create_errors, true) . '<br/><br/>';
                        }
                    }
                }
            }
        } else {
            $this->errors[] = 'Aucun champ trouvé';
        }
        
        
        
    }


}
