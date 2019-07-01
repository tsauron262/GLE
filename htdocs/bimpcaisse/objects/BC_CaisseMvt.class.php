<?php

class BC_CaisseMvt extends BimpObject
{

    public static $types = array(
        1 => array('label' => 'Encaissement', 'icon' => 'plus-circle', 'classes' => array('success')),
        2 => array('label' => 'Décaissement', 'icon' => 'minus-circle', 'classes' => array('danger'))
    );

    public function getCaissesArray()
    {
        $caisses = array(0 => '');

        $id_entrepot = (int) $this->getData('id_entrepot');
        if ($id_entrepot) {
            $instance = BimpObject::getInstance($this->module, 'BC_Caisse');
            $list = $instance->getList(array(
                'id_entrepot' => $id_entrepot
                    ), null, null, 'id', 'asc', 'array', array(
                'id', 'name'
            ));

            foreach ($list as $item) {
                $caisses[(int) $item['id']] = $item['name'];
            }
        }
        return $caisses;
    }

    // Overrides: 

    public function create(&$warnings = array(), $force_create = false)
    {
        $msg = 'Echec de la mise à jour du fond de caisse';
        $caisse = $this->getChildObject('caisse');

        if (!BimpObject::objectLoaded($caisse)) {
            $id_caisse = (int) $this->getData('id_caisse');
            if ($id_caisse) {
                $errors[] = $msg . ' - La caisse d\'ID ' . $id_caisse . ' n\'existe pas';
            } else {
                $errors[] = $msg .= ' - ID de la caisse absent';
            }
        } else {
            $montant = (float) $this->getData('montant');
            if (!$montant) {
                $errors[] = $msg . ' - Aucun montant spécifié';
            } else {
                $this->set('id_caisse_session', (int) $caisse->getData('id_current_session'));
            }
        }

        if (count($errors)) {
            return $errors;
        }

        $errors = parent::create($warnings, $force_create);

        if ($this->isLoaded()) {
            $type = (int) $this->getData('type');
            if ($type === 2) {
                $montant *= -1;
            }
            $current_montant = (float) $caisse->getData('fonds');
            $caisse->set('fonds', $current_montant + $montant);
            $caisse_errors = $caisse->update();
            if (count($caisse_errors)) {
                $errors[] = BimpTools::getMsgFromArray($caisse_errors, 'Echec de la mise à jour du montant du fonds de caisse');
            }
        } elseif (!count($errors)) {
            $errors[] = 'Le montant du fonds de caisse n\'a pas été mis à jour';
        }

        return $errors;
    }
}
