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

        if (!BimpObject::objectLoaded($this->object)) {
            $html = 'Erreur: expédition ou commande client absente ou invalide';
        } else {
            $shipment = null;
            $commande = null;

            if (is_a($this->object, 'BL_CommandeShipment')) {
                $shipment = $this->object;
                $commande = $this->object->getParentInstance();
            } elseif (is_a($this->object, 'Bimp_Commande')) {
                $commande = $this->object;
            }

            if (!BimpObject::objectLoaded($commande)) {
                $html .= 'Erreur: Commande client associée absente';
            } else {
                $client = $commande->getChildObject('client');

                if (!BimpObject::objectLoaded($client)) {
                    $html .= 'Erreur: aucun client enregistré pour la commande ' . $commande->getRef();
                } else {

//                   A Décommenter pour affichage du commercial (+ positionner le HTML)
//                    $commercial = null;
//                    $id_commercial = $commande->dol_object->getIdContact('internal', 'SALESREPSIGN');
//
//                    if (is_array($id_commercial) && count($id_commercial)) {
//                        $id_commercial = (int) $id_commercial[0];
//                    }
//
//                    if (!$id_commercial) {
//                        $id_commercial = $commande->dol_object->getIdContact('internal', 'SALESREPFOLL');
//
//                        if (is_array($id_commercial) && count($id_commercial)) {
//                            $id_commercial = (int) $id_commercial[0];
//                        }
//                    }
//
//                    if (!$id_commercial) {
//                        $id_commercial = (int) $commande->getData('id_user_resp');
//                    }
//
//                    if ($id_commercial) {
//                        $commercial = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', (int) $id_commercial);
//                    }
//                    if (BimpObject::objectLoaded($commercial)) {
//                        $html .= '<tr><td  colspan="2" style="font-size: 9px;">Commercial: ' . $commercial->getName() . '</td></tr>';
//                    }

                    $html .= '<table>';
                    $html .= '<tr>';
                    $name = $client->getName();

                    if (strlen($name) > 50) {
                        $name = substr($name, 0, 50) . '...';
                    }

                    $font_size = 18;
                    $max_chars = 14;

                    while (BimpTools::getStringNbLines($name, $max_chars) > 1) {
                        $max_chars++;
                        $font_size--;

                        if ($font_size < 10) {
                            break;
                        }
                    }

                    $html .= '<td style="text-align: center; font-size: ' . $font_size . 'px; font-weight: bold">' . $name . '</td>';
                    $html .= '</tr>';

                    $html .= '<tr>';
                    $html .= '<td style="text-align: center;font-size: 18px;font-weight: bold;color: #000000">' . $commande->getRef() . '</td>';
                    $html .= '</tr>';


                    $html .= '<tr><td style="text-align: center;font-size: 18px;font-weight: bold">';
                    if (BimpObject::objectLoaded($shipment)) {
                        $html .= 'Livraison n°' . $shipment->getData('num_livraison');
                    } else {
                        $html .= ' ';
                    }
                    $html .= '</td></tr>';


                    if ($this->qty_etiquettes > 1) {
                        $html .= '<tr>';
                        $html .= '<td style="text-align: right; color: #000000; font-weight: bold;font-size: 18px;">Colis etiquette_number/' . $this->qty_etiquettes . '</td>';
                        $html .= '</tr>';
                    }

                    $html .= '</table>';
                }
            }
        }

        return $html;
    }
}
