<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcommercial/objects/ObjectLine.class.php';

class Bimp_PropalLine extends ObjectLine
{

    public static $parent_comm_type = 'propal';
    public static $dol_line_table = 'propaldet';
    public static $dol_line_parent_field = 'fk_propal';
    public static $periodicities = array(
        0  => 'Aucune',
        1  => 'Mensuelle',
        2  => 'Bimensuelle',
        3  => 'Trimestrielle',
        4  => 'Quadrimestrielle',
        6  => 'Semestrielle',
        12 => 'Annuelle'
    );
    public static $periodicities_masc = array(
        1  => 'Mensuel',
        2  => 'Bimensuel',
        3  => 'Trimestriel',
        4  => 'Quadrimestriel',
        6  => 'Semestriel',
        12 => 'Annuel'
    );

    // Getters booléens

    public function isDeletable($force_delete = false, &$errors = array()): int
    {
        if ($this->getData('linked_object_name') == 'discount')
            return 1;
        return parent::isDeletable($force_delete, $errors);
    }

    public function isAbonnement()
    {
        $prod = $this->getProduct();
        if (BimpObject::objectLoaded($prod)) {
            return $prod->isAbonnement();
        } else {
            $parentLine = $this->getParentLine();
            if (BimpObject::objectLoaded($parentLine)) {
                return $parentLine->isAbonnement();
            }
        }

        return 0;
    }

    public function isFieldEditable($field, $force_edit = false)
    {
        if (in_array($field, array('abo_fac_periodicity', 'abo_duration', 'abo_fac_term', 'abo_nb_renouv'))) {
            if ((int) BimpTools::getPostFieldValue('id_linked_contrat_line', $this->getData('id_linked_contrat_line'))) {
                return 0;
            }

            return 1;
        }

        return parent::isFieldEditable($field, $force_edit);
    }

    public function showMarginsInForms()
    {
        return 1;
    }

    // Getters arrays: 

    public function getNbRenouvellementsArray($max = 10)
    {
        $n = array(
            0  => 'Aucun',
            -1 => 'Illimité',
        );

        for ($i = 1; $i <= $max; $i++) {
            $n[$i] = $i;
        }
        return $n;
    }

    public function getValueByProduct($field)
    {
        if (in_array($field, array('is_abonnement', 'abo_fac_periodicity', 'abo_fac_term'))) {
            $prod = $this->getProduct();

            if (BimpObject::objectLoaded($prod)) {
                switch ($field) {
                    case 'is_abonnement':
                        return $prod->isAbonnement();
                    case 'abo_fac_periodicity':
                        return $prod->getData('fac_def_periodicity');
                    case 'abo_fac_term':
                        return $prod->getData('fac_def_terme');
                }
            }
        }

        return parent::getValueByProduct($field);
    }

    public function getInputValue($field_name)
    {
        if (in_array($field_name, array('abo_duration', 'abo_fac_periodicity', 'abo_fac_term'))) {
            $id_linked_contrat_line = (int) BimpTools::getPostFieldValue('id_linked_contrat_line', $this->getData('id_linked_contrat_line'));

            if ($id_linked_contrat_line) {
                $contrat_line = BimpCache::getBimpObjectInstance('bimpcontrat', 'BCT_ContratLine', $id_linked_contrat_line);
                if (BimpObject::objectLoaded($contrat_line)) {
                    return $contrat_line->getDataAtDate(str_replace('abo_', '', $field_name));
                }
            }
        }

        if (in_array($field_name, array('abo_fac_periodicity', 'abo_fac_term'))) {
            if (!$this->isLoaded() || $this->id_product != (int) BimpTools::getPostFieldValue('id_product', $this->id_product)) {
                return $this->getValueByProduct($field_name);
            }
        }

        return $this->getData($field_name);
    }

    public function getAboQties()
    {
        $prod = $this->getProduct();
        if (!BimpObject::objectLoaded($prod)) {
            $parentLine = $this->getParentLine();
            if (BimpObject::objectLoaded($parentLine)) {
                $prod = $parentLine->getProduct();
            }
        }
        $qties = array(
            'total'           => $this->getFullQty(),
            'fac_periodicity' => (int) $this->getData('abo_fac_periodicity'),
            'duration'        => (int) $this->getData('abo_duration'),
            'prod_duration'   => (BimpObject::objectLoaded($prod) ? $prod->getData('duree') : 0),
            'per_month'       => 0,
            'per_fac_period'  => 0,
            'per_prod_period' => 1
        );

        if ($qties['total'] && $qties['duration']) {
            $qties['per_month'] = $qties['total'] / $qties['duration'];
            $qties['per_fac_period'] = $qties['per_month'] * $qties['fac_periodicity'];
            $qties['per_prod_period'] = $qties['per_month'] * $qties['prod_duration'];
        }

        return $qties;
    }

    // Affichages : 

    public function displayLineData($field, $edit = 0, $display_name = 'default', $no_html = false)
    {
        if ($field == 'qty' && $this->isAbonnement()) {
            return $this->displayAboQty();
        }

        return parent::displayLineData($field, $edit, $display_name, $no_html);
    }

    public function displayAboQty()
    {
        $html = '';

        if ($this->isAbonnement()) {
            $qties = $this->getAboQties();

            $id_contrat_line = (int) $this->getData('id_linked_contrat_line');
            if ($id_contrat_line) {
                $contrat_line = BimpCache::getBimpObjectInstance('bimpcontrat', 'BCT_ContratLine', $id_contrat_line);
                if (BimpObject::objectLoaded($contrat_line)) {
                    $contrat = $contrat_line->getParentInstance();
                    if (BimpObject::objectLoaded($contrat)) {
                        $html .= 'Ajout à un abonnement en cours du contrat ' . $contrat->getLink() . '<br/><br/>';
                    }
                }
            }

            if ($qties['fac_periodicity'] && $qties['duration']) {
                $nb_prod_periodes = 0;
                if ((int) $qties['prod_duration'] > 0) {
                    $nb_prod_periodes = $qties['duration'] / $qties['prod_duration'];
                }
                $html .= '<b>' . BimpTools::displayFloatValue((float) $qties['per_prod_period'], 8, ',', 0, 0, 0, 0, 1, 1) . '</b> unité(s) de <b>' . $qties['prod_duration'] . ' mois' . ($nb_prod_periodes > 0 && $nb_prod_periodes != 1 ? ' x ' . ($nb_prod_periodes) : '') . '</b><br/>';
            } else {
                if (!$qties['fac_periodicity']) {
                    $html .= ($html ? '<br/>' : '') . '<span class="danger">Périodicité de facturation non définie</span>';
                }
                if (!$qties['duration']) {
                    $html .= ($html ? '<br/>' : '') . '<span class="danger">Durée totale de l\'abonnement non définie</span>';
                }
            }

            if ($qties['fac_periodicity'] && $qties['duration']) {
                $html .= '<br/><b>- Durée abonnement : </b>' . $qties['duration'] . ' mois';
                $html .= '<br/><b>- Qté par facturation ' . lcfirst($this->displayDataDefault('abo_fac_periodicity')) . ': </b>';
                $html .= BimpTools::displayFloatValue((float) $qties['per_fac_period'], 8, ',', 0, 0, 0, 0, 1, 1);
            }
            $html .= '<br/><b>- Qté totale : </b>' . parent::displayLineData('qty');
            $html .= '<br/>- Facturation à terme ' . ((int) $this->getData('abo_fac_term') ? 'à échoir' : 'échu');
//            $html .= '<br/>- Renouvellement(s) tacite(s) : ' . $this->displayDataDefault('abo_nb_renouv');
        }

        return $html;
    }

    public function displayLinkedContratLineInfos()
    {
        $html = '';

        $id_linked_contrat_line = (int) BimpTools::getPostFieldValue('id_linked_contrat_line', $this->getData('id_linked_contrat_line'));

        if ($id_linked_contrat_line) {
            $contrat_line = BimpCache::getBimpObjectInstance('bimpcontrat', 'BCT_ContratLine', $id_linked_contrat_line);
            if (BimpObject::objectLoaded($contrat_line)) {
                $html .= 'Dates : ';
                if ($contrat_line->getData('statut') <= 0) {
                    $html .= '<span class="warning">Abonnement inactif</span><br/>';
                } else {
                    $html .= '<b>' . $contrat_line->displayPeriods() . '</b>';
                }
                $html .= '<br/>';
                $date_next_fac = $contrat_line->getData('date_next_facture');
                if ($date_next_fac) {
                    $html .= 'Prochaine facture : <b>Le ' . date('d / m / Y', strtotime($date_next_fac)) . '</b><br/>';
                }

                $html .= 'Durée : <b>' . $contrat_line->displayDataDefault('duration') . ' mois</b><br/>';
                $html .= 'Facturation : <b>' . $contrat_line->displayDataDefault('fac_periodicity') . ' / ' . $contrat_line->displayDataDefault('fac_term') . '</b><br/>';
            }
        }

        return $html;
    }

    // Rendus HTML: 

    public function renderAboInfos()
    {
        $html = '<span class="warning">' . BimpRender::renderIcon('fas_exclamation-triangle', 'iconLeft');
        $html .= 'Ce produit doit être inclus dans un contrat d\'abonnement. Veuillez renseigner les champs "Périodicité de facturation", ';
        $html .= '"Durée de l\'abonnement", "Terme de facturation" et "Nombre de renouvellements tacites"';
        $html .= '</span>';

        return $html;
    }

    public function renderAboDurationInput()
    {
        $html = '';

        $duree_unitaire = 0;
        $id_product = (int) BimpTools::getPostFieldValue('id_product', $this->id_product);

        if ($id_product) {
            $prod = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product', $id_product);
            if (BimpObject::objectLoaded($prod)) {
                $duree_unitaire = (int) $prod->getData('duree');
            }
        }

        $value = (int) $this->getData('abo_duration');
        $options = array();

        if ($duree_unitaire) {
            $options['data']['min'] = $duree_unitaire;
            $options['min_label'] = 1;
            $options['step'] = $duree_unitaire;

            if ($value < $duree_unitaire) {
                $value = $duree_unitaire;
            }
        }

        $html .= BimpInput::renderInput('qty', 'abo_duration', $value, $options);

        if ($duree_unitaire) {
            $html .= '<div style="margin-top: 10px">';
            $html .= '<b>La durée totale doit être un multiple de la durée unitaire du produit (' . $prod->getData('duree') . ' mois)</b><br/>';
            $html .= '</div>';
        }

        return $html;
    }

    public function renderAboQtyInput()
    {
        $html = '';

        if ($this->isFieldEditable('qty') && $this->canEditField('qty')) {
            $qties = $this->getAboQties();
            $options = array(
                'data' => array(
                    'data_type' => 'number',
                    'decimals'  => 8,
                    'unsigned'  => 1
                )
            );

            $html .= '<span class="bold">Nombre d\'unités :</span><br/>';
            $html .= BimpInput::renderInput('qty', 'abo_qty_per_product_period', $qties['per_prod_period'], $options);

            $content = 'Qté totale :<br/>';
            $content .= BimpInput::renderInput('qty', 'abo_total_qty', $qties['total'], $options);
            if ($qties['prod_duration']) {
                $content .= '<br/><span class="small">1 quantité correpond à 1 unité sur ' . $qties['prod_duration'] . ' mois</span>';
            }

            $content .= '<br/><br/>Qté par facturation :<br/>';
            $content .= BimpInput::renderInput('qty', 'abo_qty_per_fac_period', $qties['per_fac_period'], $options);

            $html .= '<br/><div style="margin-top: 15px; display: inline-block">';
            $html .= BimpRender::renderFoldableContainer(BimpRender::renderIcon('fas_plus-circle', 'iconLeft') . 'Avancé', $content, array(
                        'offset_left' => 1,
                        'grey_bk'     => 1,
                        'open'        => 0
            ));
            $html .= '</div>';

            $html .= '<input type="hidden" name="prod_duration" value="' . $qties['prod_duration'] . '"/>';
        } else {
            $html .= $this->displayAboQty();
        }

        return $html;
    }

    public function renderLinkedContratLineInput()
    {
        $html = '';

        $product = $this->getProduct();
        if (BimpObject::objectLoaded($product) && $product->isAbonnement()) {
            if (!(int) $product->getData('achats_partiels_allowed')) {
                $html .= BimpRender::renderAlerts('Ce produit ne peut pas être acheté partiellement, l\'ajout à un abonnement existant n\'est pas possible', 'warning');
            } else {
                BimpObject::loadClass('bimpcontrat', 'BCT_Contrat');
                $id_client = (int) $this->db->getValue('propal', 'fk_soc', 'rowid = ' . (int) $this->getData('id_obj'));

                if ($id_client) {
                    $lines = BCT_Contrat::getClientAbosLinesArray((int) $id_client, $product->id, true, 'NON (Ajouter en tant que nouvel abonnement)');

                    if (count($lines) > 1) {
                        $html .= BimpInput::renderInput('select', 'id_linked_contrat_line', (int) $this->getData('id_linked_contrat_line'), array(
                                    'options' => $lines
                        ));

                        $msg = 'Si un abonnement en cours est sélectionné, les unités ajoutées seront facturées au prorata de la durée restante de cet abonnement.<br/>';
                        $msg .= 'Toutes les unités pourront ainsi être renouvellées simultanément';
                        $html .= BimpRender::renderAlerts($msg, 'info');
                        return $html;
                    } else {
                        $html .= BimpRender::renderAlerts('Le client ne dispose pas d\'abonnement en cours pour ce produit', 'warning');
                    }
                } else {
                    $html .= BimpRender::renderAlerts('Client non renseigné');
                }
            }
        } else {
            return 'KO - ' . $product->id;
        }

        $html .= '<input type="hidden" value="0" name="id_linked_contrat_line"/>';

        return $html;
    }

    // Traitements :

    public function checkLinkedContratLine(&$errors = array())
    {
        $check = true;
        $id_contrat_line = (int) $this->getData('id_linked_contrat_line');
        if ($id_contrat_line) {
            $contrat_line = BimpCache::getBimpObjectInstance('bimpcontrat', 'BCT_ContratLine', $id_contrat_line);

            if (BimpObject::objectLoaded($contrat_line)) {
                $propal = $this->getParentInstance();
                $contrat = $contrat_line->getParentInstance();

                if (!BimpObject::objectLoaded($propal)) {
                    $errors[] = 'Devis absent';
                    $check = false;
                }
                if (!BimpObject::objectLoaded($contrat)) {
                    $errors[] = 'Le contrat pour l\'abonnement en cours #' . $id_contrat_line . ' n\'existe plus';
                    $check = false;
                }

                if (!count($errors)) {
                    if ((int) $contrat->getData('fk_soc') !== (int) $propal->getData('fk_soc')) {
                        $errors[] = 'Le client du contrat sélectionné pour l\'ajout à un abonnement en cours n\'est pas le même que celui du devis';
                        $check = false;
                    }

                    if ((int) $contrat_line->getData('fk_product') !== (int) $this->id_product) {
                        $errors[] = 'Ajout à un abonnement en cours : le produit ne correspond pas';
                        $check = false;
                    }
                }
            } else {
                $errors[] = 'La ligne de contrat d\'abonnement liée #' . $id_contrat_line . ' n\'existe plus';
                $check = false;
            }
        }

        return $check;
    }

    // Overrides : 

    public function validate()
    {
        $errors = parent::validate();

        if ($this->isAbonnement()) {
            $id_contrat_line = (int) $this->getData('id_linked_contrat_line');
            if ($id_contrat_line) {
                if ($this->checkLinkedContratLine($errors)) {
                    $contrat_line = BimpCache::getBimpObjectInstance('bimpcontrat', 'BCT_ContratLine', $id_contrat_line);
                    $this->set('abo_fac_periodicity', $contrat_line->getDataAtDate('fac_periodicity'));
                    $this->set('abo_duration', $contrat_line->getDataAtDate('duration'));
                    $this->set('abo_fac_term', $contrat_line->getDataAtDate('fac_term'));
                    $this->set('abo_nb_renouv', $contrat_line->getDataAtDate('nb_renouv'));
                }
            }
        } else {
            $this->set('abo_fac_periodicity', 0);
            $this->set('abo_duration', 0);
            $this->set('abo_fac_term', 0);
            $this->set('abo_nb_renouv', 0);
            $this->set('id_linked_contrat_line', 0);
        }

        return $errors;
    }

    public function delete(&$warnings = array(), $force_delete = false)
    {
        if ($this->getData('linked_object_name') == 'discount') {
            $parent = $this->getParentInstance();
            $parent->dol_object->statut = 0;
            $return = parent::delete($warnings, $force_delete);
            $parent->dol_object->statut = $parent->getInitData('statut');
        } else
            $return = parent::delete($warnings, $force_delete);


        return $return;
    }
}
