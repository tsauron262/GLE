<?php

class BC_CaisseUser extends BimpObject
{

    public function create(&$warnings = array())
    {
        $errors = array();

        if (!(int) $this->getData('id_caisse')) {
            $errors[] = 'ID de la caisse absent';
        } elseif (!(int) $this->getData('id_user')) {
            $errors[] = 'Utilisateur non spécifié';
        } else {
            $caisse = $this->getChildObject('caisse');
            if (!BimpObject::objectLoaded($caisse)) {
                $errors[] = 'La caisse d\'ID ' . $this->getData('id_caisse') . ' n\'existe pas';
            } else {
                $id_caisse = (int) BC_Caisse::getUserCaisse((int) $this->getData('id_user'));

                if ($id_caisse) {
                    if ($id_caisse === (int) $caisse->id) {
                        $errors[] = 'Vous êtes déjà connecté à la caisse "' . $caisse->getData('name') . '"';
                    } else {
                        $caisse2 = BimpObject::getInstance('bimpcaisse', 'BC_Caisse', $id_caisse);
                        $caisse_label = '';
                        if (BimpObject::objectLoaded($caisse2)) {
                            $caisse_label = "'" . $caisse2->getData('name') . '"';
                        } else {
                            $caisse_label = ' d\'ID ' . $id_caisse;
                        }
                        $errors[] = 'Vous êtes déjà connecté à la caisse' . $caisse_label;
                    }
                }

                $id_session = (int) $caisse->getData('id_current_session');
                if (!$id_session) {
                    $errors[] = 'Cette caisse est fermée.';
                } else {
                    $this->set('id_caisse_session', $id_session);
                }
            }
        }

        if (count($errors)) {
            return $errors;
        }

        return parent::create($warnings);
    }
}
