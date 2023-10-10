<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/pdf/classes/BimpDocumentPDF.php';

ini_set('display_errors', 1);

class PretPDF extends BimpDocumentPDF
{

    public $pret = null;
    public $signature_bloc = true;
    public $signature_bloc_label = '';
    public $signature_title = '';
    public $signature_pro_title = '';
    public $signature_mentions = 'Signature précédée de la mention "Lu et approuvé"<br/>';

    public function __construct($pret, $db)
    {
        $this->pret = $pret;
        $this->bimpObject = $pret;

        parent::__construct($db);

        $this->target_label = 'Client';
    }

    public function initData()
    {
        parent::initData();

        if (!BimpObject::objectLoaded($this->pret)) {
            $this->errors[] = 'Pret invalide';
        } else {
            $client = $this->pret->getChildObject('client');

            if (!BimpObject::objectLoaded($client)) {
                $this->errors[] = 'Client absent';
            } else {
                $this->thirdparty = $client->dol_object;
            }

            $entrepot = $this->pret->getChildObject('entrepot');
            if (BimpObject::objectLoaded($entrepot)) {
                if ($entrepot->address != "" && $entrepot->town != "") {
                    $this->fromCompany->zip = $entrepot->zip;
                    $this->fromCompany->address = $entrepot->address;
                    $this->fromCompany->town = $entrepot->town;

                    if (BimpCore::isEntity('bimp')) {
                        if ($this->fromCompany->name == "Bimp Groupe Olys")
                            $this->fromCompany->name = "Bimp Olys SAS";

                        if ($entrepot->ref == "PR") {
                            $this->fromCompany->address = "2 rue des Erables CS 21055  ";
                            $this->fromCompany->town = "LIMONEST";
                            $this->fromCompany->zip = "69760";
                        }
                    }
                }
            }
        }
    }

    protected function initHeader()
    {
        parent::initHeader();

        if (!count($this->errors)) {
            $this->header_vars['doc_name'] = 'BON DE PRÊT';
            $this->header_vars['doc_ref'] = $this->pret->getRef();
            $this->header_vars['ref_extra'] = '';
        }
    }

    public function getFromUsers()
    {
        $users = array();

        if (BimpObject::objectLoaded($this->pret)) {
            $id_user = $this->pret->getData('user_create');
            if ($id_user) {
                $users[$id_user] = 'Interlocuteur';
            }
        }

        return $users;
    }

    public function getDocInfosHtml()
    {
        $html = '';

        if (!count($this->errors)) {
            $dt_begin = new DateTime($this->pret->getData('date_begin'));
            $dt_end = new DateTime($this->pret->getData('date_end'));
            $interval = $dt_begin->diff($dt_end);

            if ((int) $this->pret->getData('id_sav')) {
                $sav = $this->pret->getChildObject('sav');

                if (BimpObject::objectLoaded($sav)) {
                    $html .= '<b>Dossier SAV : </b>' . $sav->getRef() . '<br/>';
                }
            }

            $html .= '<b>Durée du prêt : </b>' . $interval->format('%a') . ' jours<br/>';
            $html .= '<b>Début : </b>' . $dt_begin->format('d / m / Y') . '<br/>';
            $html .= '<b>Fin : </b>' . $dt_end->format('d / m / Y') . '<br/>';

            $html .= parent::getDocInfosHtml();
        }

        return $html;
    }

    public function renderTop()
    {
        $html = '';

        if (!count($this->errors)) {
            $user = $this->pret->getChildObject('user_create');
            $html .= '<p style="font-size: 9px;">';
            $html .= 'Par la présente, la societe ' . $this->fromCompany->nom;

            if (BimpObject::objectLoaded($user)) {
                $html .= ' représentée par ' . $user->getName();
            }

            $html .= ' déclare mettre à disposition de son client, pour la durée mentionnée les matériels listés ci-dessous : ';
            $html .= '</p>';

            $this->writeContent($html);
        }
    }

    public function renderLines()
    {
        if (count($this->errors)) {
            return'';
        }

        $table = new BimpPDF_AmountsTable($this->pdf);
        $table->setCols(array('desc', 'pu_ht', 'qte', 'total_ht', 'total_ttc'));
        $table->cols_def['qte']['style'] = 'text-align: center;';

        $asso = new BimpAssociation($this->pret, 'equipments');
        $equipments = $asso->getAssociatesList();
        $products_lines = $this->pret->getChildrenObjects('products');
        if (!count($equipments) && !count($products_lines)) {
            $this->errors[] = 'Erreur: aucun équipement ni produit enregistré pour le prêt "' . $this->pret->getRef() . '"';
            return 0;
        }

        // Ajout des équipements: 

        if (!empty($equipments)) {
            foreach ($equipments as $id_equipment) {
                $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', (int) $id_equipment);
                if (!$equipment->isLoaded()) {
                    $this->errors[] = 'Equipement d\'ID ' . $id_equipment . ' non trouvé';
                    return 0;
                } else {
                    $desc = '<b>' . $equipment->getProductLabel(true) . '</b>';
                    $desc .= ($desc ? '<br/>' : '') . 'N° de série : ' . $equipment->getData('serial');

                    $pu_ht = 0;
                    $total_ttc = 0;
                    if ((int) $equipment->getData('id_product')) {
                        $product = $equipment->getChildObject('product');
                        if (!BimpObject::objectLoaded($product)) {
                            $this->errors[] = 'Erreur: aucun produit associé pour l\'équipement d\'ID ' . $equipment->id;
                            return 0;
                        }
                        $pu_ht = $product->price;
                        $total_ttc = $product->price_ttc;
                    } else {
                        $pu_ht = (float) $equipment->getData('prix_vente');

                        if (!$pu_ht) {
                            $pu_ht = (float) $equipment->getData('prix_vente_except');
                        }

                        $tva_tx = (float) $equipment->getData('vente_tva_tx');
                        if (!$tva_tx) {
                            $tva_tx = BimpCache::cacheServeurFunction('getDefaultTva');
                        }

                        $total_ttc = BimpTools::calculatePriceTaxIn($pu_ht, $tva_tx);
                    }
                    $row = array(
                        'desc'      => $desc,
                        'qte'       => 1,
                        'pu_ht'     => BimpTools::displayMoneyValue($pu_ht),
                        'total_ht'  => BimpTools::displayMoneyValue($pu_ht),
                        'total_ttc' => BimpTools::displayMoneyValue($total_ttc),
                    );

                    $table->rows[] = $row;
                }
            }
        }


        // Ajout des produits non sérialisés: 
        if (!empty($products_lines)) {
            foreach ($products_lines as $product_line) {
                $product = $product_line->getChildObject('product');
                if (!BimpObject::objectLoaded($product)) {
                    $this->errors[] = 'Erreur: aucun produit associé pour la ligne d\'ID ' . $product_line->id;
                    return 0;
                } else {
                    $qty = (float) $product_line->getData('qty');
                    $price = (float) $product->dol_object->price;
                    $table->rows[] = array(
                        'desc'     => '<b>' . $product->getRef() . '</b><br/>' . $product->getName(),
                        'qte'      => $qty,
                        'pu_ht'    => BimpTools::displayMoneyValue($price),
                        'total_ht' => BimpTools::displayMoneyValue($price * $qty),
                        'total_ttc'    => BimpTools::displayMoneyValue($product->dol_object->price_ttc * $qty)
                    );
                }
            }
        }

        $this->writeContent('<div style="text-align: right; font-size: 6px;">Montants exprimés en Euros</div>');
        $this->pdf->addVMargin(1);
        $table->write();
        unset($table);
    }

    public function getTotauxRowsHtml()
    {
        return '';
    }

    public function renderAfterLines()
    {
        $html = '<br/>';

        if (!count($this->errors)) {
            $note = $this->pret->getData('note');

            if ($note) {
                $html .= '<div style="margin-top: 15px; font-size: 9px">';
                $html .= '<b>Information complémentaire : </b>' . str_replace("\n", '<br/>', $note);
                $html .= '</div>';
            }

            $html .= '<div style="font-size: 8px; font-style: italic">';
            $html .= 'Nous ne sommes pas responsable des données contenues dans les produits qui nous sont confiés. Nous vous conseillons de toujours effectuer';
            $html .= ' une sauvegarde de vos données. A votre demande, nous pouvons effectuer une sauvegarde avant intervention.<br/>';
            $html .= '<b>Réserves de propriété : applicables selon la loi n°80.335 du 12 mai 1980. Seul le tribunal de Lyon est compétent</b>';
            $html .= '</div>';

            $html .= '<div style="font-size: 9px">';
            $html .= '<b>Le client s\'engage :</b>';
            $html .= '<ul>';
            $html .= '<li>à assurer les matériels prêtés</li>';
            $html .= '<li>à prendre soin des matériels prêtés</li>';
            $html .= '<li>à respecter les délais du prêt</li>';
            $html .= '<li>à accepter la facturation automatique du matériel si les produits faisaient l\'objet d\'une quelconque déterioration ou perte</li>';

            if ((int) $this->pret->getData('caution')) {
                $html .= '<li>à fournir un chèque de caution d\'un montant de <b>120 €</b></li>';
            }
            $html .= '</ul>';
            $html .= '</div>';
        }

        $this->writeContent($html);
    }
}
