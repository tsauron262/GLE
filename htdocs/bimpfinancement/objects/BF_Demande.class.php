<?php

class BF_Demande extends BimpObject
{

    public static $durations = array(
        24 => '24 mois',
        36 => '36 mois',
        48 => '48 mois',
        60 => '60 mois',
        72 => '72 mois',
        84 => '84 mois'
    );
    public static $periodicities = array(
        1  => 'Mensuelle',
        3  => 'Trimestrielle',
        6  => 'Semestrielle',
        12 => 'Annuelle'
    );
    public static $annexes = array(
        0 => '-',
        1 => 'OFC',
        2 => 'OA'
    );
    public static $termes_paiement = array(
        0 => '-',
        1 => 'A terme échu',
        2 => 'A terme à échoir'
    );
    public static $status_list = array(
        0 => '-',
        1 => 'Cédé',
        2 => 'Signé - en attente de cession',
        3 => 'En attente de retour',
        4 => 'Sans suite',
        5 => 'Terminé',
        6 => 'Reconduit',
        7 => 'Remplacé'
    );

    public function getClient_contactsArray()
    {
        $contacts = array();

        $id_client = $this->getData('id_client');
        if (!is_null($id_client) && $id_client) {
            $where = '`fk_soc` = ' . (int) $id_client;
            $rows = $this->db->getRows('socpeople', $where, null, 'array', array('rowid', 'firstname', 'lastname'));
            if (!is_null($rows)) {
                foreach ($rows as $r) {
                    $contacts[(int) $r['rowid']] = BimpTools::ucfirst($r['firstname']) . ' ' . strtoupper($r['lastname']);
                }
            }
        }

        return $contacts;
    }

    public function getSupplier_contactsArray()
    {
        $contacts = array();

        $id_supplier = $this->getData('id_supplier');
        if (!is_null($id_supplier) && $id_supplier) {
            $where = '`fk_soc` = ' . (int) $id_supplier;
            $rows = $this->db->getRows('socpeople', $where, null, 'array', array('rowid', 'firstname', 'lastname'));
            if (!is_null($rows)) {
                foreach ($rows as $r) {
                    $contacts[(int) $r['rowid']] = BimpTools::ucfirst($r['firstname']) . ' ' . strtoupper($r['lastname']);
                }
            }
        }

        return $contacts;
    }

    public function renderViewLoadedScript()
    {
        if (isset($this->id) && $this->id) {
            return '<script type="text/javascript">onBFDemandeViewLoaded(' . $this->id . ');</script>';
        }
        return '';
    }
}
