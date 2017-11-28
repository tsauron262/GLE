<?php

class BH_Ticket extends BimpObject
{

    public function create()
    {
        $this->data['ticket_number'] = 'BH' . date('ymdhis');

        return parent::create();
    }

    public function getClientNomUrl()
    {
        $contrat = $this->getChildObject('contrat');
        if (!is_null($contrat)) {
            if (isset($contrat->societe) && is_a($contrat->societe, 'Societe')) {
                if (isset($contrat->societe->id) && $contrat->societe->id) {
                    return $contrat->getNomUrl(1);
                }
            }
            if (isset($contrat->socid) && $contrat->socid) {
                global $db;
                $soc = new Societe($db);
                if ($soc->fetch($contrat->socid) > 0) {
                    return $soc->getNomUrl(1);
                }
            }
        }

        return '';
    }
}
