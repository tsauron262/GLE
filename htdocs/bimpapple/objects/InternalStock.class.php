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
        $success = '';
        $nOk = 0;

        $file_name = BimpTools::getArrayValueFromPath($data, 'file/0', '');

        if (!$file_name) {
            $errors[] = 'Fichier absent';
        } else {
            $file = DOL_DATA_ROOT . '/' . BimpTools::getTmpFilesDir() . '/' . $file_name;

            if (!file_exists($file)) {
                $errors[] = 'Le fichier semble ne pas avoir été téléchargé correctement';
            }
        }

        if (!count($errors)) {
            $centres = BimpCache::getCentres();

            $keys = array(
                'code_centre' => 0,
                'part_number' => 1,
                'qty'         => 2,
                'pa_ht'       => 3
            );

            $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

            // Checks: 
            $i = 0;
            foreach ($lines as $line) {
                $i++;
                $line_errors = array();
                $line_data = str_getcsv($line, ';');

                $code_centre = BimpTools::getArrayValueFromPath($line_data, $keys['code_centre'], '', $line_errors, true, 'Code centre absent');
                $part_number = BimpTools::getArrayValueFromPath($line_data, $keys['part_number'], '', $line_errors, true, 'Réf. Composant absent');
                $qty = (int) BimpTools::getArrayValueFromPath($line_data, $keys['qty'], 0, $line_errors, true, 'Qtés à ajouter absentes');
                
                if (!isset($centres[$code_centre])) {
                    $line_errors[] = 'Code centre invalide';
                }
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
                    $line_errors = array();
                    $line_data = str_getcsv($line, ';');

                    $code_centre = BimpTools::getArrayValueFromPath($line_data, $keys['code_centre'], '');
                    $part_number = BimpTools::getArrayValueFromPath($line_data, $keys['part_number'], '');
                    $qty = BimpTools::getArrayValueFromPath($line_data, $keys['qty'], 0);
                    $pa_ht = (float) BimpTools::getArrayValueFromPath($line_data, $keys['pa_ht'], null);
                    
                    $stock = static::getStockInstance($code_centre, $part_number);
                    
                    if (BimpObject::objectLoaded($stock)) {
                        $stock->correctStock($qty, '', 'IMPORT_CSV', 'Import CSV');
                        
                        if ($pa_ht && $pa_ht != (float) $stock->getData('last_pa')) {
                            
                        }
                    } else {
                        
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
}
