<?php

class BC_Caisse extends BimpObject
{

    public static $states = array(
        0 => array('label' => 'Fermée', 'icon' => 'times', 'classes' => array('danger')),
        1 => array('label' => 'Ouverte', 'icon' => 'check', 'classes' => array('success'))
    );
    public static $printer_dpis = array(
        72  => '72 dpi',
        100 => '100 dpi',
        200 => '200 dpi',
        300 => '300dpi'
    );
    public static $windowWidthByDpi = array(
        72  => 596,
        100 => 827,
        200 => 1654,
        300 => 2480
    );

    // Getters:
    
    public function canDelete(){
        global $user;
        if ($user->admin)
            return 1;
        return 0;
    }

    public function isValid(&$errors = array())
    {
        global $user;

        if (!$this->isLoaded()) {
            $errors[] = 'ID de la caisse absent ou invalide';
        } else {
            if (!(int) $this->getData('status') || !(int) $this->getData('id_current_session')) {
                $errors[] = 'La caisse "' . $this->getData('name') . '" est fermée';
            }
            if (!(int) $this->getData('id_account')) {
                $errors[] = 'La caisse à laquelle vous etes connecté n\'est associé à aucun compte bancaire';
            } else {
                $account = $this->getChildObject('account');
                if (!BimpObject::objectLoaded($account)) {
                    $errors[] = 'Le compte bancaire auquel est associé cette caisse est invalide';
                }
            }

            if (!$this->isUserConnected((int) $user->id)) {
                $errors[] = 'Vous n\'êtes pas connecté à cette caisse';
            }
        }

        return (count($errors) ? 0 : 1);
    }

    public function isUserConnected($id_user)
    {
        if (!$this->isLoaded()) {
            return 0;
        }

        $id_session = (int) $this->getData('id_current_session');
        if (!$id_session) {
            return 0;
        }

        $caisseUser = BimpObject::getInstance('bimpcaisse', 'BC_CaisseUser');

        $list = $caisseUser->getList(array(
            'id_caisse' => (int) $this->id,
            'id_user'   => (int) $id_user
                ), null, null, 'id', 'asc', 'array', array('id', 'id_caisse_session'));

        $connected = 0;
        foreach ($list as $item) {
            if ((int) $item['id_caisse_session'] !== $id_session) {
                if ($caisseUser->fetch((int) $item['item'])) {
                    $warnings = array();
                    $caisseUser->delete($warnings, true);
                }
            } else {
                $connected = 1;
            }
        }

        return $connected;
    }

    public static function getUserCaisse($id_user)
    {
        if ((int) $id_user) {
            global $db;

            $bdb = new BimpDb($db);

            $sql = 'SELECT DISTINCT cu.id_caisse FROM ' . MAIN_DB_PREFIX . 'bc_caisse_user cu';
            $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'bc_caisse c ON c.id = cu.id_caisse';
            $sql .= ' WHERE c.status > 0 AND c.id_current_session = cu.id_caisse_session';
            $sql .= ' AND cu.id_user = ' . (int) $id_user;

            $rows = $bdb->executeS($sql, 'array');

            echo $bdb->db->lasterror();

            if (isset($rows[0]['id_caisse'])) {
                return (int) $rows[0]['id_caisse'];
            }
        }

        return 0;
    }

    public function getOpenCaissesArray()
    {
        $caisses = array();

        $id_entrepot = (int) $this->getData('id_entrepot');

        if (!$id_entrepot) {
            if (BimpTools::isSubmit('id_entrepot')) {
                $id_entrepot = (int) BimpTools::getValue('id_entrepot');
            } elseif (BimpTools::isSubmit('param_values/fields/id_entrepot')) {
                $id_entrepot = (int) BimpTools::getValue('param_values/fields/id_entrepot');
            }
        }

        if ($id_entrepot) {
            $instance = BimpObject::getInstance('bimpcaisse', 'BC_Caisse');
            $list = $instance->getList(array(
                'id_entrepot'        => (int) $id_entrepot,
                'status'             => array(
                    'operator' => '>',
                    'value'    => 0
                ),
                'id_current_session' => array(
                    'operator' => '>',
                    'value'    => 0
                )
            ));

            foreach ($list as $item) {
                $caisses[(int) $item['id']] = $item['name'];
            }
        }

        return $caisses;
    }

    // Traitements: 

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

    public function connectUser($id_user)
    {
        $errors = array();

        if (!$this->isLoaded()) {
            $errors[] = 'ID de la caisse absent';
        } elseif (!(int) $this->getData('status') || !(int) $this->getData('id_current_session')) {
            $errors[] = 'Cette caisse est fermée';
        } else {
            $caisseUser = BimpObject::getInstance('bimpcaisse', 'BC_CaisseUser');
            $errors = $caisseUser->validateArray(array(
                'id_caisse' => (int) $this->id,
                'id_user'   => (int) $id_user
            ));
            if (!count($errors)) {
                $errors = $caisseUser->create();
            }
        }

        return $errors;
    }

    public function disconnectUser($id_user)
    {
        $errors = array();

        if (!$this->isLoaded()) {
            $errors[] = 'ID de la caisse absent';
        } elseif (!$id_user) {
            $errors[] = 'Aucun utilisateur à déconnecter de la caisse spécifié';
        } else {
            $caisseUser = BimpObject::getInstance('bimpcaisse', 'BC_CaisseUser');
            $caisseUser->deleteBy(array(
                'id_caisse' => (int) $this->id,
                'id_user'   => (int) $id_user
                    ), $errors, true);
        }

        return $errors;
    }

    public function disconnectAllUsers()
    {
        $errors = array();

        if (!$this->isLoaded()) {
            $errors[] = 'ID de la caisse absent';
        } else {
            $caisseUser = BimpObject::getInstance('bimpcaisse', 'BC_CaisseUser');
            $caisseUser->deleteBy(array(
                'id_caisse' => (int) $this->id
                    ), $errors, true);
        }

        return $errors;
    }

    public function addPaiement(Paiement $paiement, $id_facture, $id_vente = 0)
    {
        $errors = array();

        if (!$this->isValid($errors)) {
            return $errors;
        }

        if (isset($paiement->id)) {
            $paiement->fetch((int) $paiement->id);
        }

        if (!BimpObject::objectLoaded($paiement)) {
            $errors[] = 'Paiement invalide';
        } else {
            // Ajout du paiement en caisse: 
            $bc_paiement = BimpObject::getInstance('bimpcaisse', 'BC_Paiement');
            $paiement_errors = $bc_paiement->validateArray(array(
                'id_caisse'         => (int) $this->id,
                'id_caisse_session' => (int) $this->getData('id_current_session'),
                'id_facture'        => (int) $id_facture,
                'id_vente'          => (int) $id_vente,
                'id_paiement'       => (int) $paiement->id
            ));
            if (!count($paiement_errors)) {
                $paiement_errors = $bc_paiement->create();
            }

            if (count($paiement_errors)) {
                $errors[] = BimpTools::getMsgFromArray($paiement_errors, 'Echec de l\'enregistrement du paiement en caisse de ' . BimpTools::displayMoneyValue((float) $paiement->amount, 'EUR'));
            }

            // Correction du fonds de caisse: 
            if ($paiement->type_code === 'LIQ') {
                $fonds = (float) $this->getData('fonds');
                $fonds += (float) $paiement->amount;
                $this->set('fonds', $fonds);
                $update_errors = $this->update();
                if (count($update_errors)) {
                    $errors[] = BimpTools::getMsgFromArray($update_errors, 'Echec de la mise à jour du fonds de caisse (Nouveau montant: ' . $fonds . ')');
                }
            }
        }

        return $errors;
    }

    // Acions: 

    public function actionConnectUser($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success_callback = 'window.location.reload();';

        global $user;

        if (!$this->isLoaded() && isset($data['id_caisse']) && (int) $data['id_caisse']) {
            $this->fetch((int) $data['id_caisse']);
        }

        if (!$this->isLoaded()) {
            $errors[] = 'ID de la caisse absent ou invalide';
        } else {
            $success = 'Connexion à la caisse "' . $this->getData('name') . '" effectuée avec succès';
            $errors = $this->connectUser($user->id);
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $success_callback
        );
    }

    public function actionDisconnectUser($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success_callback = 'window.location.reload();';

        global $user;

        if (!$this->isLoaded() && isset($data['id_caisse']) && (int) $data['id_caisse']) {
            $this->fetch((int) $data['id_caisse']);
        }

        if (!$this->isLoaded()) {
            $errors[] = 'ID de la caisse absent ou invalide';
        } else {
            $success = 'Déconnexion de la caisse "' . $this->getData('name') . '" effectuée avec succès';
            $errors = $this->disconnectUser($user->id);
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $success_callback
        );
    }
}
