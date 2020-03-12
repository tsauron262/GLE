<?php

class BC_Paiement extends BimpObject
{

    // Getters booléens: 

    public function isEditable($force_edit = false, &$errors = array())
    {
        global $user;
        if ($this->isLoaded() && !$user->rights->bimpcommercial->adminPaiement){
            $caisse_session = $this->getChildObject('caisse_session');
            $caisse = $this->getChildObject('caisse');
            if (BimpObject::objectLoaded($caisse_session)) {
                $caisse = $this->getChildObject('caisse');
                if ((int) $caisse_session->getData('id_user_closed') || (BimpObject::objectLoaded($caisse) && (int) $caisse->getData('id_current_session') !== (int) $caisse_session->id)) {
                    $errors[] = 'Paiement enregistré dans une session de caisse fermée';
                    return 0;
                }
            } else {
                $errors[] = 'ID de la session de caisse absent';
                return 0;
            }

            if ((int) $this->getData('id_paiement')) {
                $paiement = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Paiement', (int) $this->getData('id_paiement'));

                if (BimpObject::objectLoaded($paiement)) {
                    // Surtout pas d'appel à $paiement->isEditable() sinon boucle infinie. 
                    if ($paiement->getData('exported') == 1) {
                        $errors[] = 'Paiement exporté en compta';
                        return 0;
                    }
                }
            }
        }

        return 1;
    }

    public function isDeletable($force_delete = false, &$errors = array())
    {
        return $this->isEditable($force_delete, $errors);
    }

    // Getters: 

    public function getAmount()
    {
        $paiement = $this->getChildObject('paiement');

        if (BimpObject::objectLoaded($paiement)) {
            return (float) $paiement->dol_object->amount;
        }

        return 0;
    }

    // Affichages: 

    public function displayType()
    {
        $paiement = $this->getChildObject('paiement');

        if (BimpObject::objectLoaded($paiement)) {
            return $paiement->dol_object->type_libelle;
        }

        return '';
    }

    public function displayAmount()
    {
        return BimpTools::displayMoneyValue($this->getAmount(), 'EUR');
    }

    public function displayFacture($display_name = 'nom_url')
    {
        if ((int) $this->getData('id_facture')) {
            return $this->displayData('id_facture', $display_name);
        }

        $p = $this->getChildObject('paiement');

        $init_id_facture = (int) $this->getData('id_facture');

        if (BimpObject::objectLoaded($p)) {
            $rows = $this->db->getRows('paiement_facture', '`fk_paiement` = ' . (int) $p->id, null, 'array', array('fk_facture', 'amount'));
            if (!is_null($rows)) {
                $html = '';
                $fl = true;
                foreach ($rows as $r) {
                    if (!$fl) {
                        $html .= '<br/>';
                    } else {
                        $fl = false;
                    }
                    $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $r['fk_facture']);
                    if ($facture->isLoaded()) {
                        $this->set('id_facture', (int) $facture->id);
                        $html .= $this->displayData('id_facture', $display_name);
                    } else {
                        $html .= BimpRender::renderAlerts('Erreur: la facture d\'ID ' . $r['fk_facture'] . ' n\'existe pas');
                    }

                    $html .= ' (' . BimpTools::displayMoneyValue((float) $r['amount'], 'EUR') . ')';
                }

                return $html;
            }
        }

        $this->set('id_facture', $init_id_facture);

        return '';
    }

    public function displayClient($display_name = 'nom_url')
    {
        if (!$this->isLoaded()) {
            return '';
        }

        $facture = null;

        if ((int) $this->getData('id_facture')) {
            $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $this->getData('id_facture'));
        }

        if (!BimpObject::objectLoaded($facture)) {
            $p = $this->getChildObject('paiement');

            if (BimpObject::objectLoaded($p)) {
                $rows = $this->db->getRows('paiement_facture', '`fk_paiement` = ' . (int) $p->id, null, 'array', array('fk_facture', 'amount'));
                if (!is_null($rows)) {
                    foreach ($rows as $r) {
                        $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $r['fk_facture']);
                        if ($facture->isLoaded()) {
                            break;
                        }
                    }
                }
            }
        }

        if (BimpObject::objectLoaded($facture)) {
            $client = $facture->getChildObject('client');
            if (BimpObject::objectLoaded($client)) {
                if ($display_name === 'nom_url') {
                    return $client->getLink();
                } else {
                    return BimpTools::ucfirst($client->getRef() . ' - ' . $client->getName());
                }
            }
        }

        return '';
    }
}
