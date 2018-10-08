<?php

class BC_Paiement extends BimpObject
{

    // Gettesr: 

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

        if (BimpObject::objectLoaded($p)) {
            $rows = $this->db->getRows('paiement_facture', '`fk_paiement` = ' . (int) $p->id, null, 'array', array('fk_facture', 'amount'));
            $facture = BimpObject::getInstance('bimpcommercial', 'Bimp_Facture');
            if (!is_null($rows)) {
                $html = '';
                $fl = true;
                foreach ($rows as $r) {
                    if (!$fl) {
                        $html .= '<br/>';
                    } else {
                        $fl = false;
                    }

                    if ($facture->fetch((int) $r['fk_facture'])) {
                        $html .= BimpObject::getInstanceNomUrl($facture);
                    } else {
                        $html .= BimpRender::renderAlerts('Erreur: la facture d\'ID ' . $r['fk_facture'] . ' n\'existe pas');
                    }

                    $html .= ' (' . BimpTools::displayMoneyValue((float) $r['amount'], 'EUR') . ')';
                }

                return $html;
            }
        }

        return '';
    }
}
