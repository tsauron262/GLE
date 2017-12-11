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

    public function getEquipmentsArray()
    {
        $equipments = array();
        $id_contrat = (int) $this->getData('id_contrat');
        if (!is_null($id_contrat) && $id_contrat) {
            $equipment = BimpObject::getInstance('bimphotline', 'Equipment');
            $bimpAsso = new BimpAssociation($equipment, 'contrats');
            $equipments = $bimpAsso->getObjectsList($id_contrat);
        }

        return $equipments;
    }

    public function defaultDisplayEquipmentsItem($id_equipment)
    {
        $equipment = BimpObject::getInstance('bimphotline', 'Equipment');
        if ($equipment->fetch($id_equipment)) {
            $label = '';
            $product = $equipment->config->getObject('', 'product');
            if (!is_null($product) && isset($product->id) && $product->id) {
                $label = $product->label;
            } else {
                return BimpRender::renderAlerts('Equipement ' . $id_equipment . ': Produit associé non trouvé');
            }

            $label .= ' - N° série: ' . $equipment->getData('serial');

            return $label;
        }
        return BimpRender::renderAlerts('Equipement non trouvé (ID ' . $id_equipment . ')', 'warning');
    }
}
