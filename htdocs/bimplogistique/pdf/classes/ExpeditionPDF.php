<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/pdf/classes/BimpEtiquettePDF.php';

class ExpeditionPDF extends BimpEtiquettePDF
{
    public function __construct($db)
    {
        parent::__construct($db);

        $this->prefName = "Etiquette_Expedition_";
    }

    protected function getContentHtml()
    {
        $html = '';

        if (!BimpObject::objectLoaded($this->object) ||
                !is_a($this->object, 'BL_CommandeShipment')) {
            $html .= 'Erreur: expédition absente ou invalide';
        } else {
            $commande = $this->object->getParentInstance();

            if (!BimpObject::objectLoaded($commande)) {
                $html .= 'Erreur: Commande client associée absente';
            } else {
                $client = $commande->getChildObject('client');

                if (!BimpObject::objectLoaded($client)) {
                    $html .= 'Erreur: aucun client enregistré pour la commande ' . $commande->getRef();
                } else {
                    $commercial = null;
                    $id_commercial = $commande->dol_object->getIdContact('internal', 'SALESREPSIGN');

                    if (is_array($id_commercial) && count($id_commercial)) {
                        $id_commercial = (int) $id_commercial[0];
                    }

                    if (!$id_commercial) {
                        $id_commercial = $commande->dol_object->getIdContact('internal', 'SALESREPFOLL');

                        if (is_array($id_commercial) && count($id_commercial)) {
                            $id_commercial = (int) $id_commercial[0];
                        }
                    }

                    if (!$id_commercial) {
                        $id_commercial = (int) $commande->getData('id_user_resp');
                    }

                    if ($id_commercial) {
                        $commercial = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', (int) $id_commercial);
                    }

                    $html .= '<table>';
                    $html .= '<tr>';
                    $html .= '<td width="175px" style="font-size: 12px; font-weight: bold; color: #' . BimpCore::getParam('pdf/primary', '000000') . '">Commande ' . $commande->getRef().'</td>';
                    $html .= '<td width="55px" style="text-align: right; color: #000000; font-weight: bold"> etiquette_number / '.$this->qty_etiquettes.'</td>';
                    $html .= '</tr>';
                    $html .= '<tr><td colspan="2" style="font-size: 10px;font-weight: bold">Livraison n°' . $this->object->getData('num_livraison') . '</td></tr>';
                    if (BimpObject::objectLoaded($commercial)) {
                        $html .= '<tr><td  colspan="2" style="font-size: 9px;">Commercial: ' . $commercial->getName() . '</td></tr>';
                    }
                    $html .= '<tr>';
                    $html .= '<td colspan="2">';
                    $html .= '<div style="text-align: center;font-size: 8px">';
                    $html .= '<br/><span style="font-size: 9px">Client:</span><br/>';
                    $html .= '<span style="font-size: 10px; font-weight: bold">' . $client->getName() . '</span>';
                    $html .= '</div>';
                    $html .= '</td>';
                    $html .= '</tr>';
                    $html .= '</table>';
                }
            }
        }
        
        return $html;
    }
}
