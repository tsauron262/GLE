<?php

class BC_Caisse extends BimpObject
{

    public static $states = array(
        0 => array('label' => 'Fermée', 'icon' => 'times', 'classes' => array('danger')),
        1 => array('label' => 'Ouverte', 'icon' => 'check', 'classes' => array('success'))
    );

    public function validate()
    {
        $errors = parent::validate();

        if (!count($errors)) {
            $id_user = (int) $this->getData('id_current_user');
            $id_caisse = (int) $this->getUserCaisse($id_user);
            if ($id_caisse && ($id_caisse !== (int) $this->id)) {
                $errors[] = 'Cet utilisateur est déjà connecté à une caisse';
            }
        }

        return $errors;
    }

    public function getUserCaisse($id_user)
    {
        if ((int) $id_user) {
            $rows = $this->db->getValues($this->getTable(), 'id', '`id_current_user` = ' . (int) $id_user);
            if (!is_null($rows) && count($rows)) {
                return $rows[0];
            }
        }

        return false;
    }

    public function correctFonds($new_fonds, $msg = '')
    {
        $errors = array();

        if (!$this->isLoaded()) {
            $errors[] = 'ID de la caisse absent';
        } else {
            $current_fonds = (float) $this->getSavedData('fonds');

            if ((float) $new_fonds === $current_fonds) {
                return array();
            }

            if ($new_fonds < $current_fonds) {
                $type = 2;
                $montant = $current_fonds - (float) $new_fonds;
            } else {
                $type = 1;
                $montant = (float) $new_fonds - $current_fonds;
            }

            $mvt = BimpObject::getInstance($this->module, 'BC_CaisseMvt');
            $errors = $mvt->validateArray(array(
                'id_entrepot' => (int) $this->getData('id_entrepot'),
                'id_caisse'   => (int) $this->id,
                'type'        => (int) $type,
                'montant'     => (float) $montant,
                'note'        => $msg
            ));

            if (!count($errors)) {
                $errors = $mvt->create();
            }
        }

        return $errors;
    }
}
