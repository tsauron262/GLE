<?php

abstract class PartStock extends BimpObject
{

    public static $stock_type = '';

    // Droits user:

    public function canCreate()
    {
        return 1;
    }

    public function canEdit()
    {
        return $this->canCreate();
    }

    public function canDelete()
    {
        global $user;

        return (int) $user->admin;
    }

    public function canEditField($field_name)
    {
        if (!$this->isLoaded()) {
            return 1;
        }

        global $user;

        if ($user->admin) {
            return 1;
        }

        if (in_array($field_name, array('serials', 'qty'))) {
            return 1;
        }

        return 0;
    }

    public function canSetAction($action)
    {
        switch ($action) {
            case 'correct':
                return $this->isUserAdmin();
        }

        return parent::canSetAction($action);
    }

    // Getters booléens: 

    public function isActionAllowed($action, &$errors = array())
    {
        switch ($action) {
            case 'correct':
                if (!$this->isLoaded($errors)) {
                    return 0;
                }
                return 1;
        }

        return parent::isActionAllowed($action, $errors);
    }

    // Getters données: 

    public function getCentreData($code_centre, &$errors = array())
    {
        $centres = BimpCache::getCentres();

        if (!isset($centres[$code_centre])) {
            $errors[] = 'Aucun centre pour le code "' . $code_centre . '"';
            return array();
        }

        return $centres[$code_centre];
    }

    // Getters statics: 

    abstract public static function getStockInstance($code_centre, $part_number);

    // Getters params: 

    public function getListExtraButtons()
    {
        $buttons = array();

        if ($this->isActionAllowed('correct') && $this->canSetAction('correct')) {
            $buttons[] = array(
                'label'   => 'Corriger le stock',
                'icon'    => 'fas_pen',
                'onclick' => $this->getJsActionOnclick('correct', array(), array(
                    'form_name' => 'correct' . ((int) $this->getData('serialized') ? '_serialized' : '')
                ))
            );
        }

        return $buttons;
    }

    // Traitements:

    public function correctStock($qty_modif, $serial = '', $code_mvt = '', $desc = '', &$warnings = array(), $log_error = false, $no_db_transactions = false)
    {
        $errors = array();
        if (!static::$stock_type) {
            $errors[] = 'Correction du stock impossible depuis une instance de base';
            return $errors;
        }

        if ($this->isLoaded($errors)) {
            $serials = $this->getData('serials');
            $qty = (int) $this->getData('qty');

            if ((int) $this->getData('serialized')) {
                if (!$serial) {
                    $errors[] = 'Composant sérialisé - n° de série obligatoire';
                } else {
                    if ($qty_modif < 0) {
                        if (!in_array($serial, $serials)) {
                            $errors[] = 'Le n° de série "' . $serial . '" n\'est pas en stock';
                        } else {
                            foreach ($serials as $idx => $s) {
                                if ($s == $serial) {
                                    unset($serials[$idx]);
                                    break;
                                }
                            }
                            $qty_modif = -1;
                        }
                    } else if ($qty_modif > 0) {
                        if (in_array($serial, $serials)) {
                            $errors[] = 'Le n° de série "' . $serial . '" est déjà en stock';
                        } else {
                            $serials[] = $serial;
                            $qty_modif = 1;
                        }
                    }

                    $qty = count($serials);
                }
            } else {
                $serials = array();
                $qty += $qty_modif;
            }

            if (!count($errors)) {
                $prev_db = $this->db;
                if ($no_db_transactions) {
                    $this->useNoTransactionsDb();
                }

                $this->set('serials', $serials);
                $this->set('qty', $qty);
                $errors = $this->update($warnings, true);

                if (!count($errors)) {
                    global $user;

                    $mvt_errors = array();
                    $mvt_warnings = array();
                    BimpObject::createBimpObject('bimpapple', 'PartStockMvt', array(
                        'stock_type'  => static::$stock_type,
                        'id_stock'    => (int) $this->id,
                        'id_user'     => (int) $user->id,
                        'code_centre' => $this->getData('code_centre'),
                        'part_number' => $this->getData('part_number'),
                        'date'        => date('Y-m-d H:i:s'),
                        'qty'         => $qty_modif,
                        'serial'      => $serial,
                        'code_mvt'    => $code_mvt,
                        'description' => $desc
                            ), true, $mvt_errors, $mvt_warnings, $no_db_transactions);

                    if (count($mvt_errors)) {
                        $warnings[] = BimpTools::getMsgFromArray($mvt_errors, $this->getData('part_number'));
                    }
                } elseif ($log_error) {
                    BimpCore::addlog('Echec mise à jour stock consigné Apple - A corriger', Bimp_Log::BIMP_LOG_ERREUR, 'stocks', $this, array(
                        'Modificateur qté' => $qty_modif,
                        'Serial'           => $serial,
                        'Code mvt'         => $code_mvt,
                        'Description mvt'  => $desc
                            ), true);
                }

                $this->db = $prev_db;
            }
        }

        return $errors;
    }

    // Actions: 

    public function actionCorrect($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';
        $sc = 'triggerObjectChange(\'bimpapple\', \'PartStockMvt\');';

        $code_mvt = 'MANUAL';
        $desc = BimpTools::getArrayValueFromPath($data, 'infos', '');

        if ((int) $this->getData('serialized')) {
            $nAdded = 0;
            $nRemoved = 0;

            $serials = $this->getData('serials');
            $new_serials = BimpTools::getArrayValueFromPath($data, 'serials', array());

            foreach ($serials as $serial) {
                if (!in_array($serial, $new_serials)) {
                    $stock_errors = $this->correctStock(-1, $serial, $code_mvt, $desc, $warnings);

                    if (count($stock_errors)) {
                        $warnings[] = BimpTools::getMsgFromArray($stock_errors, 'Echec retrait du n° de série "' . $serial . '"');
                    } else {
                        $nRemoved++;
                    }
                }
            }

            foreach ($new_serials as $serial) {
                if (!in_array($serial, $serials)) {
                    $stock_errors = $this->correctStock(1, $serial, $code_mvt, $desc, $warnings);

                    if (count($stock_errors)) {
                        $warnings[] = BimpTools::getMsgFromArray($stock_errors, 'Echec ajout du n° de série "' . $serial . '"');
                    } else {
                        $nAdded++;
                    }
                }
            }

            if (!$nAdded && !$nRemoved) {
                $warnings[] = 'Aucun changement n\'a été éffectué';
            } else {
                if ($nAdded) {
                    $success .= ($success ? '<br/>' : '') . $nAdded . ' n° de série ajouté(s) avec succès';
                }
                if ($nRemoved) {
                    $success .= ($success ? '<br/>' : '') . $nRemoved . ' n° de série retiré(s) avec succès';
                }
            }
        } else {
            $mode = BimpTools::getArrayValueFromPath($data, 'mode', '');

            if (!$mode) {
                $errors[] = 'Mode de saisie des quantités absent';
            } else {
                $qty_modif = 0;

                switch ($mode) {
                    case 'total':
                        $new_qty = (int) BimpTools::getArrayValueFromPath($data, 'qty', $this->getData('qty'));
                        $qty = (int) $this->getData('qty');
                        $qty_modif = $new_qty - $qty;
                        break;

                    case 'add':
                        $qty_modif = (int) BimpTools::getArrayValueFromPath($data, 'qty_to_add', 0);
                        break;

                    case 'remove':
                        $qty_modif = (int) BimpTools::getArrayValueFromPath($data, 'qty_to_remove', 0) * -1;
                        break;
                }

                if (!$qty_modif) {
                    $errors[] = 'Nouvelle quantité identique à la quantité actuelle';
                } else {
                    $errors = $this->correctStock($qty_modif, '', $code_mvt, $desc, $warnings);

                    if (!count($errors)) {
                        $success = 'Correction du stock effectuée avec succès.<br/>Nouvelle quantité <b>' . $this->getData('qty') . '</b>';
                    }
                }
            }
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $sc
        );
    }

    // Overrides: 

    public function validate()
    {
        $errors = array();

        if ((int) $this->getData('serialized')) {
            $this->set('qty', count($this->getData('serials')));
        } else {
            $this->set('serials', array());
        }

        if (!count($errors)) {
            $errors = parent::validate();
        }

        return $errors;
    }

    public function create(&$warnings = array(), $force_create = false)
    {
        $errors = array();

        $stock = static::getStockInstance($this->getData('code_centre'), $this->getData('part_number'));

        if (BimpObject::objectLoaded($stock)) {
            $errors[] = 'Cette référence existe déja pour ce centre';
        }

        $errors = parent::create($warnings, $force_create);

        if (!count($errors) && !$this->getData('serialized')) {
            $qty = BimpTools::getValue('qty_tot', 0);
            if ($qty > 0) {
                $errors = $this->correctStock($qty, '', 'CREATION');
            }
        }

        return $errors;
    }
}
