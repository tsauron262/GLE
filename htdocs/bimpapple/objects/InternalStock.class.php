<?php

require_once DOL_DOCUMENT_ROOT . '/bimpapple/objects/PartStock.class.php';

class InternalStock extends PartStock
{

    public static $stock_type = 'internal';

    // Droits users: 

    public function canSetAction($action)
    {
        global $user;
        switch ($action) {
            case 'csvImport':
                if ($user->admin) {
                    return 1;
                }

                return 0;
                break;
            case 'correct':
                return 1;
                break;
        }

        return parent::canSetAction($action);
    }

    // Getters params: 

    public function getListHeaderButtons()
    {
        $buttons = array();

        if ($this->isActionAllowed('csvImport') && $this->canSetAction('csvImport')) {
            $buttons[] = array(
                'label'   => 'Import CSV',
                'icon'    => 'fas_file-import',
                'onclick' => $this->getJsActionOnclick('csvImport', array(), array(
                    'form_name' => 'csv_import'
                ))
            );
        }

        return $buttons;
    }

    // Actions: 

    public function actionCsvImport($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Toutes les lignes du fichier traitées avec succès';

        $file_name = BimpTools::getArrayValueFromPath($data, 'file/0', '');

        if (!$file_name) {
            $errors[] = 'Fichier absent';
        } else {
            $file = DOL_DATA_ROOT . '/' . BimpTools::getTmpFilesDir() . '/' . $file_name;

            if (!file_exists($file)) {
                $errors[] = 'Le fichier semble ne pas avoir été téléchargé correctement';
            }
        }

        $code_centre = BimpTools::getArrayValueFromPath($data, 'code_centre', '', $errors, true, 'Code centre absent');
        if ($code_centre) {
            $centres = BimpCache::getCentres();
            if (!isset($centres[$code_centre])) {
                $errors[] = 'Code centre invalide';
            }
        }

        if (!count($errors)) {
            $keys = array(
                'part_number' => 0,
                'desc'        => 1,
                'prod'        => 2,
                'pa_ht'       => 3,
                'eee'         => 4,
                'qty'         => 5
            );

            $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

            // Checks: 
            $i = 0;
            foreach ($lines as $line) {
                $i++;
                $line_errors = array();
                $line_data = str_getcsv($line, ';');

                $part_number = BimpTools::getArrayValueFromPath($line_data, $keys['part_number'], '', $line_errors, true, 'Réf. Composant absent');
                $qty = (int) BimpTools::getArrayValueFromPath($line_data, $keys['qty'], 0, $line_errors, true, 'Qtés à ajouter absentes');

                if ($qty <= 0) {
                    $line_errors[] = 'Qté à ajouter invalides';
                }

                if (count($line_errors)) {
                    $errors[] = BimpTools::getMsgFromArray($line_errors, 'Ligne n°' . $i);
                }
            }

            if (!count($errors)) {
                $i = 0;
                foreach ($lines as $line) {
                    $i++;
                    $line_errors = $line_warnings = array();
                    $line_data = str_getcsv($line, ';');

                    $part_number = trim(BimpTools::getArrayValueFromPath($line_data, $keys['part_number'], ''));
                    $desc = trim(BimpTools::getArrayValueFromPath($line_data, $keys['desc'], ''));
                    $prod_label = trim(BimpTools::getArrayValueFromPath($line_data, $keys['prod'], ''));
                    $eee = trim(BimpTools::getArrayValueFromPath($line_data, $keys['eee'], ''));
                    $qty = (int) trim(BimpTools::getArrayValueFromPath($line_data, $keys['qty'], 0));
                    $pa_ht = (float) str_replace(',', '.', trim(BimpTools::getArrayValueFromPath($line_data, $keys['pa_ht'], null)));

                    if ($eee == '0') {
                        $eee = '';
                    }

                    $stock = static::getStockInstance($code_centre, $part_number);

                    if (BimpObject::objectLoaded($stock)) {
                        $line_errors = $stock->correctStock($qty, '', 'IMPORT_CSV', 'Import CSV');

                        if (!count($line_errors)) {
                            $up = false;

                            if ($pa_ht > 0) {
                                $stock->set('last_pa', $pa_ht);
                                $up = true;
                            }
                            if ($desc) {
                                $stock->set('description', $desc);
                                $up = true;
                            }
                            if ($prod_label) {
                                $stock->set('product_label', $prod_label);
                                $up = true;
                            }
                            if ($eee) {
                                $stock->set('code_eee', $eee);
                                $up = true;
                            }

                            if ($up) {
                                $line_errors = $stock->update($line_warnings, true);
                            }
                        }
                    } else {
                        $stock = BimpObject::createBimpObject('bimpapple', 'InternalStock', array(
                                    'code_centre'   => $code_centre,
                                    'part_number'   => $part_number,
                                    'qty'           => 0,
                                    'description'   => $desc,
                                    'product_label' => $prod_label,
                                    'code_eee'      => $eee,
                                    'last_pa'       => $pa_ht
                                        ), true, $line_errors, $line_wanings);
                        if(!count($line_errors))
                            $line_errors = $stock->correctStock($qty, '', 'IMPORT_CSV', 'Import CSV');
                    }

                    if (count($line_errors)) {
                        $errors[] = BimpTools::getMsgFromArray($line_errors, 'Ligne n° ' . $i);
                    }

                    if (count($line_warnings)) {
                        $warnings[] = BimpTools::getMsgFromArray($line_warnings, 'Ligne n° ' . $i);
                    }
                }
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    // Getters statics: 

    public static function getStockInstance($code_centre, $part_number)
    {
        return BimpCache::findBimpObjectInstance('bimpapple', 'InternalStock', array(
                    'code_centre' => $code_centre,
                    'part_number' => $part_number
                        ), true);
    }

    public static function getInternalStockQtyUsed($id_stock)
    {
        if ((int) $id_stock) {
            $sql = 'SELECT SUM(pdet.qty) as qty_used';
            $sql .= BimpTools::getSqlFrom('propaldet', array(
                        'bl' => array(
                            'table' => 'bs_sav_propal_line',
                            'on'    => 'bl.id_line = pdet.rowid'
                        ),
                        's'  => array(
                            'table' => 'bs_sav',
                            'on'    => 's.id_propal = pdet.fk_propal'
                        )
                            ), 'pdet');
            $sql .= BimpTools::getSqlWhere(array(
                        'bl.linked_object_name' => 'internal_stock',
                        'bl.linked_id_object'   => $id_stock,
                        's.status'              => array(
                            'operator' => '<',
                            'value'    => 999
                        )
                            ), 'pdet');
            
            $result = self::getBdb()->executeS($sql, 'array');
            
            if (isset($result[0]['qty_used'])) {
                return (int) $result[0]['qty_used'];
            }
        }
        return 0;
    }
}
