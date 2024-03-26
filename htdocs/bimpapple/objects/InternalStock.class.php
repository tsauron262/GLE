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
                if ($user->admin || $user->rights->bimpapple->part_stock->admin) {
                    return 1;
                }

                return 0;

            case 'correct':
            case 'receive':
                return 1;
        }

        return parent::canSetAction($action);
    }

    // Getters booléents: 

    public function isActionAllowed($action, &$errors = [])
    {
        switch ($action) {
            case 'receive':
                if ($this->isLoaded()) {
                    if ((int) $this->getData('qty_to_receive') <= 0) {
                        $errors[] = 'Aucune unité en attente de réception';
                        return 0;
                    }
                }
                return 1;
        }
        return parent::isActionAllowed($action, $errors);
    }

    // Getters params: 

    public function getListHeaderButtons()
    {
        $buttons = array();

        if ($this->canSetAction('receive')) {
            $buttons[] = array(
                'label'   => 'Réceptionner plusieurs composants',
                'icon'    => 'fas_arrow-circle-down',
                'onclick' => $this->getJsActionOnclick('receive', array(), array(
                    'form_name'      => 'bulk_receive',
                    'on_form_submit' => 'function($form, extra_data) {return  InternalStocks.onBulkReceiveFormSubmit($form, extra_data);}'
                ))
            );
        }

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

    public function getListExtraButtons()
    {
        $buttons = parent::getListExtraButtons();

        if ($this->isActionAllowed('receive') && $this->canSetAction('receive')) {
            $buttons[] = array(
                'label'   => 'Réceptionner',
                'icon'    => 'fas_arrow-alt-circle-down',
                'onclick' => $this->getJsActionOnclick('receive', array(), array(
                    'form_name' => 'receive'
                ))
            );
        }

        return $buttons;
    }

    // Rendus HTML : 

    public function renderPartsReceivedQtiesInputs()
    {
        $html = '';

        $code_centre = BimpTools::getPostFieldValue('code_centre', '');

        if (!$code_centre) {
            $html .= '<span class="danger">Aucun centre sélectionné</span>';
        } else {
            $parts = BimpCache::getBimpObjectObjects('bimpapple', 'InternalStock', array(
                        'code_centre'    => $code_centre,
                        'qty_to_receive' => array(
                            'operator' => '>',
                            'value'    => 0
                        )
            ));

            if (empty($parts)) {
                $html .= BimpRender::renderAlerts('Aucun composant en attente de réception pour ce centre', 'warning');
            } else {
                $html .= '<div class="buttonsContainer align-right">';
                $html .= '<span class="btn btn-default" onclick="InternalStocks.receiveAll($(this))">';
                $html .= BimpRender::renderIcon('fas_check', 'iconLeft') . 'Tout réceptionner';
                $html .= '</span>';
                $html .= '<span class="btn btn-default" onclick="InternalStocks.receiveNone($(this))">';
                $html .= BimpRender::renderIcon('fas_times', 'iconLeft') . 'Tout mettre à 0';
                $html .= '</span>';
                $html .= '</div>';
                $html .= '<table class="bimp_list_table">';
                $html .= '<thead>';
                $html .= '<tr>';
                $html .= '<th>Réf. composant</th>';
                $html .= '<th style="text-align: center">Qté en attente de réception</th>';
                $html .= '<th style="text-align: center">Qté reçue</th>';
                $html .= '</tr>';
                $html .= '</thead>';

                $html .= '<tbody>';

                foreach ($parts as $part) {
                    $qty_to_receive = (int) $part->getData('qty_to_receive');
                    $html .= '<tr>';
                    $html .= '<td>' . $part->getData('part_number') . '</td>';
                    $html .= '<td style="text-align: center">';
                    $html .= '<span class="badge badge-default">' . $qty_to_receive . '</span>';
                    $html .= '<span class="btn btn-default btn-small" style="float: right" onclick="';
                    $html .= htmlentities('$(\'input[name="part_' . $part->id . '_qty_received"]\').val(' . $qty_to_receive . ');');
                    $html .= '">';
                    $html .= 'Tout réceptionner' . BimpRender::renderIcon('fas_arrow-circle-right', 'iconRight');
                    $html .= '</span>';
                    $html .= '</td>';

                    $html .= '<td style="text-align: center">';
                    $html .= BimpInput::renderInput('qty', 'part_' . $part->id . '_qty_received', 0, array(
                                'extra_class' => 'part_qty_received',
                                'data'        => array(
                                    'id_part'  => $part->id,
                                    'decimals' => 0,
                                    'min'      => 0,
                                    'max'      => $qty_to_receive
                                )
                    ));
                    $html .= '</td>';
                    $html .= '</tr>';
                }

                $html .= '</tbody>';
                $html .= '</table>';
            }
        }

        return $html;
    }

    // Traitement : 

    public function modifQtyToReceive($qty_modif)
    {
        $errors = array();
        if ($this->isLoaded($errors)) {
            $qty_to_receive = (int) $this->getData('qty_to_receive') + $qty_modif;
            $errors = $this->updateField('qty_to_receive', $qty_to_receive);
        }

        return $errors;
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

            ini_set("auto_detect_line_endings", true);
            $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

            // Checks: 
            $i = 0;
            foreach ($lines as $line) {
                if (stripos($line, 'Composant') !== false && stripos($line, 'Description') !== false && stripos($line, 'Prix') !== false)//semble être la ligne de titre
                    continue;
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
                    if (stripos($line, 'Composant') !== false && stripos($line, 'Description') !== false && stripos($line, 'Prix') !== false)//semble être la ligne de titre
                        continue;
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
                        if (!count($line_errors))
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

    public function actionReceive($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';

        $reception_number = BimpTools::getArrayValueFromPath($data, 'reception_number', '');

        if ($this->isLoaded()) {
            $qty_received = (int) BimpTools::getArrayValueFromPath($data, 'qty_received', 0);

            if (!$qty_received) {
                $errors = 'Veillez saisir une quantité supérieure à 0';
            } else {
                $errors = $this->correctStock($qty_received, '', 'DELIVERY' . ($reception_number ? '_' . $reception_number : ''), 'Réception' . ($reception_number ? ' n° ' . $reception_number : ''));

                if (!count($errors)) {
                    $errors = $this->modifQtyToReceive(-$qty_received);
                }

                if (!count($errors)) {
                    $s = ($qty_received > 1 ? 's' : '');
                    $success .= $qty_received . ' unité' . $s . ' reçue' . $s . ' avec succès';
                }
            }
        } else {
            $qties = BimpTools::getArrayValueFromPath($data, 'qties', array());

            if (empty($qties)) {
                $errors[] = 'Aucune unité à réceptionner saisie';
            } else {
                $nOk = 0;
                foreach ($qties as $qty_data) {
                    $part = BimpCache::getBimpObjectInstance('bimpapple', 'InternalStock', (int) $qty_data['id_part']);

                    if (!BimpObject::objectLoaded($part)) {
                        $warnings[] = 'Le composant #' . $qty_data['id_part'] . ' n\'existe plus';
                    } else {
                        $qty_received = (int) $qty_data['qty_received'];
                        if (!$qty_received) {
                            continue;
                        }

                        $part_errors = $part->correctStock($qty_received, '', 'DELIVERY' . ($reception_number ? '_' . $reception_number : ''), 'Réception' . ($reception_number ? ' n° ' . $reception_number : ''));

                        if (!count($part_errors)) {
                            $part_errors = $part->modifQtyToReceive(-$qty_received);
                        }

                        if (count($part_errors)) {
                            $errors[] = BimpTools::getMsgFromArray($part_errors, 'Composant "' . $part->getRef() . '"');
                        } else {
                            $nOk++;
                        }
                    }
                }

                if ($nOk) {
                    $success .= 'Réception éffectuée avec succès pour ' . $nOk . ' composant' . ($nOk > 1 ? 's' : '');
                } else {
                    $errors[] = 'Aucune unité réceptionnée';
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
