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
        12 => 'Annuelle',
        24 => 'Biannuelle',
        36 => 'Triannuelle'
    );
    public static $periodicities_masc = array(
        1  => 'Mensuel',
        2  => 'Bimensuel',
        3  => 'Trimestriel',
        4  => 'Quadrimestriel',
        6  => 'Semestriel',
        12 => 'Annuel',
        24 => 'Biannuel',
        36 => 'Triannuel'
    );

    // Getters booléens :

    public function isDeletable($force_delete = false, &$errors = array()): int
    {
        if ($this->getData('linked_object_name') == 'discount')
            return 1;
        return parent::isDeletable($force_delete, $errors);
    }

    public function isFieldEditable($field, $force_edit = false)
    {
        if ($this->isAbonnement()) {
            if (in_array($field, array('abo_fac_periodicity', 'abo_duration', 'abo_fac_term', 'abo_nb_renouv'))) {
                if ((int) BimpTools::getPostFieldValue('id_linked_contrat_line', (int) $this->getData('id_linked_contrat_line'))) {
                    return 0;
                }
            }

            if (in_array($field, array('abo_fac_periodicity', 'abo_duration', 'abo_nb_units', 'abo_date_from', 'date_from', 'abo_linked_line_other_ref', 'id_linked_contrat_line', 'abo_fac_term', 'abo_nb_renouv'))) {
                if ((int) $this->getData('id_parent_line')) {
                    return 0;
                }

                if (!in_array($field, array('abo_fac_term', 'abo_nb_renouv'))) {
                    $parent = $this->getParentInstance();
                    if (!$parent->areLinesEditable()) {
                        return 0;
                    }
                }

                return 1;
            }
        }

        return parent::isFieldEditable($field, $force_edit);
    }

    public function showMarginsInForms()
    {
        return 1;
    }

    public function isAboLinkedToOtherRef()
    {
        if ((int) $this->getData('id_linked_contrat_line')) {
            $linked_line = $this->getChildObject('linked_contrat_line');

            if (BimpObject::objectLoaded($linked_line)) {
                $id_prod = (int) BimpTools::getPostFieldValue('id_product', $this->id_product);
                if ((int) $linked_line->getData('fk_product') !== $id_prod) {
                    return 1;
                }
            }
        }
        return 0;
    }

    // Getters params : 

    public function getListExtraBtn()
    {
        $buttons = parent::getListExtraBtn();

        if ($this->isLoaded()) {
            if ($this->isAbonnement() && !(int) $this->getData('id_parent_line')) {
                $buttons[] = array(
                    'label'   => 'Paramètre abonnement',
                    'icon'    => 'fas_calendar-alt',
                    'onclick' => $this->getJsLoadModalForm('abonnement', 'Paramètres abonnement'),
                );
            }
        }


        return $buttons;
    }

    public function getCreateJsCallback()
    {
        if ($this->isLoaded()) {
            if ($this->isAbonnement() && (!(int) $this->getData('abo_fac_periodicity') || !(int) $this->getData('abo_duration'))) {
                $onclick = 'setTimeout(function() {';
                $onclick .= html_entity_decode($this->getJsLoadModalForm('abonnement', 'Paramètres abonnement'));
                $onclick .= '}, 500);';
            }
        }

        return $onclick;
    }

    // Getters arrays : 

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

    // Getters données :

    public function getValueByProduct($field)
    {
        if ($field == 'is_abonnement') {
            return $this->isAbonnement();
        }

        if (in_array($field, array('abo_fac_periodicity', 'abo_fac_term'))) {
            $prod = $this->getProduct();

            if (BimpObject::objectLoaded($prod)) {
                switch ($field) {
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
                    return $contrat_line->getData(str_replace('abo_', '', $field_name));
                }
            }
        }

        if (in_array($field_name, array('abo_fac_periodicity', 'abo_fac_term'))) {
            if (!$this->isLoaded() || ($field_name == 'abo_fac_periodicity' && !(int) $this->getData('abo_fac_periodicity')) || $this->id_product != (int) BimpTools::getPostFieldValue('id_product', $this->id_product)) {
                return $this->getValueByProduct($field_name);
            }
        }

        return $this->getData($field_name);
    }

    public function getAboQties()
    {
        $prod = $this->getProduct();

        if (!BimpObject::objectLoaded($prod) && (int) $this->getData('id_parent_line')) {
            $parentLine = $this->getParentLine();
            if (BimpObject::objectLoaded($parentLine)) {
                $prod = $parentLine->getProduct();
            }
        }

        $qties = array(
            'nb_units'        => $this->getData('abo_nb_units'),
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
//            $qties['nb_units'] = $qties['per_month'] * $qties['prod_duration'];
        }

        return $qties;
    }

    // Getters données :

    public function getAboFacData(&$errors = array())
    {
        $data = array(
            'date_debut'              => '',
            'date_fin'                => '',
            'date_first_period_start' => '',
            'date_first_period_end'   => '',
            'first_period_prorata'    => 1,
            'nb_periods_fac'          => 0,
            'qty_per_period'          => 0,
            'total_qty'               => 0,
            'prod_duration'           => 0,
            'debug'                   => array()
        );

        $id_linked_contrat_line = (int) $this->getData('id_linked_contrat_line');
        if ($id_linked_contrat_line) {
            $contrat_line = BimpCache::getBimpObjectInstance('bimpcontrat', 'BCT_ContratLine', $id_linked_contrat_line);

            if (!BimpObject::objectLoaded($contrat_line)) {
                $errors[] = 'L\'abonnement lié #' . $id_linked_contrat_line . ' n\'existe plus';
            } else {
                $periodicity = (int) $contrat_line->getData('fac_periodicity');
                if (!$periodicity) {
                    $errors[] = 'Périodicité de l\'abonnement lié non définie';
                }

                $duration = (int) $contrat_line->getData('duration');
                if (!$duration) {
                    $errors[] = 'Durée totale de l\'abonnement lié non définie';
                }

                $date_debut = $contrat_line->getData('date_ouverture');
                if (!$date_debut) {
                    $errors[] = 'Date de début de validité de l\'abonnement lié non définie';
                } else {
                    $date_debut = date('Y-m-d', strtotime($date_debut));
                }

                $date_fin = $contrat_line->getData('date_fin_validite');
                if (!$date_fin) {
                    $errors[] = 'Date de fin de validité de l\'abonnement lié non définie';
                } else {
                    $date_fin = date('Y-m-d', strtotime($date_fin));
                }

                if (BimpTools::isPostFieldSubmit('abo_date_from')) {
                    $date_start = BimpTools::getPostFieldValue('abo_date_from', $this->date_from);
                } else {
                    $date_start = BimpTools::getPostFieldValue('date_from', $this->date_from);
                }


                if (!$date_start) {
                    $errors[] = 'Date d\'ouverture prévue non renseignée';
                } else {
                    $date_start = date('Y-m-d', strtotime($date_start));
                }

                $nb_units = (float) $this->getData('abo_nb_units');
                if (!$nb_units) {
                    $errors[] = 'Nombre d\'unités non défini';
                }

                $prod = $this->getProduct();

                if (!BimpObject::objectLoaded($prod) && (int) $this->getData('id_parent_line')) {
                    $parentLine = $this->getParentLine();
                    if (BimpObject::objectLoaded($parentLine)) {
                        $prod = $parentLine->getProduct();
                    }
                }

                if (!BimpObject::objectLoaded($prod)) {
                    $errors[] = 'Produit absent';
                } else {
                    $prod_duration = (int) $prod->getData('duree');
                    if (!$prod_duration) {
                        $errors[] = 'Durée unitaire du produit non définie';
                    } else {
                        $data['prod_duration'] = $prod_duration;
                    }
                }

                if ($periodicity && $duration % $periodicity != 0) {
                    $errors[] = 'La durée totale de l\'abonnement doit être un multiple du nombre de mois correspondant à la périodicité de facturation (' . $periodicity . ' mois)';
                }
                if ($prod_duration && $duration % $prod_duration != 0) {
                    $errors[] = 'La durée totale de l\'abonnement doit être un multiple de la durée unitaire de produit (' . $prod_duration . ' mois)';
                }
                if ($duration < $prod_duration) {
                    $errors[] = 'La durée totale de l\'abonnement ne peut pas être inférieure à la durée unitaire de produit (' . $prod_duration . ' mois)';
                }

                if (!count($errors)) {
                    $data['total_qty'] = $nb_units * ($duration / $prod_duration);
                    $data['qty_per_period'] = ($data['total_qty'] / $duration) * $periodicity;
                    $periodic_interval = new DateInterval('P' . $periodicity . 'M');
                    $data['date_debut'] = $date_debut;
                    $data['date_fin'] = $date_fin;
                    $data['nb_total_periods'] = $duration / $periodicity;
                    $data['nb_periods_fac'] = $data['nb_total_periods'];

                    if ($date_debut != $date_start) {
                        // Calcul du début de la première période facturée partiellement : 
                        $interval = BimpTools::getDatesIntervalData($date_debut, $date_start);
                        $nb_periods = floor($interval['nb_monthes_decimal'] / $periodicity); // Nombre de périodes entières avant début de la première période à facturer partiellement
                        $data['debug']['nb_periods_fac'] = array('interval' => $interval, 'nb_periods' => $nb_periods);
                        $dt = new DateTime($date_debut);
                        if ($nb_periods > 0) {
                            $dt->add(new DateInterval('P' . ($nb_periods * $periodicity) . 'M'));
                            $data['nb_periods_fac'] = $data['nb_total_periods'] - $nb_periods;
                        }
//                        elseif ($nb_periods < 0) {
//                            $dt->sub(new DateInterval('P' . (abs($nb_periods) * $periodicity) . 'M'));
//                        }

                        $data['date_first_period_start'] = $dt->format('Y-m-d');

                        // Calcul de la fin de la première période facturée partiellement : 
                        $dt->add($periodic_interval);
                        $dt->sub(new DateInterval('P1D'));
                        $data['date_first_period_end'] = $dt->format('Y-m-d');

                        // Calcul du prorata de facturation :
                        $interval = BimpTools::getDatesIntervalData($data['date_first_period_start'], $data['date_first_period_end']);
                        $nb_full_period_days = $interval['full_days'];

                        $interval = BimpTools::getDatesIntervalData($date_start, $data['date_first_period_end']);
                        $nb_invoiced_days = $interval['full_days'];

                        if ($nb_full_period_days && $nb_invoiced_days) {
                            $data['first_period_prorata'] = ($nb_invoiced_days / $nb_full_period_days);
                            $data['total_qty'] = ($data['qty_per_period'] * $data['first_period_prorata']) + ($data['qty_per_period'] * ($data['nb_periods_fac'] - 1));
                        }
                    } else {
                        $data['date_first_period_start'] = $date_start;
                        $dt = new DateTime($date_start);
                        $dt->add($periodic_interval);
                        $dt->sub(new DateInterval('P1D'));
                        $data['date_first_period_end'] = $dt->format('Y-m-d');
                    }
                }
            }
        }

        return $data;
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
            $contrat_line = null;
            if ($id_contrat_line) {
                $contrat_line = BimpCache::getBimpObjectInstance('bimpcontrat', 'BCT_ContratLine', $id_contrat_line);
                if (BimpObject::objectLoaded($contrat_line)) {
                    $contrat = $contrat_line->getParentInstance();
                    if (BimpObject::objectLoaded($contrat)) {
                        $html .= 'Ajout à un abonnement en cours du contrat ' . $contrat->getLink() . ' (ligne n° ' . $contrat_line->getData('rang') . ')';

//                        if ((int) $contrat_line->getData('fk_product') !== (int) $this->id_product) {
//                            $product = $contrat_line->getChildObject('product');
//                            if (BimpObject::objectLoaded($product)) {
//                                $html .= '<br/>' . $product->getLink();
//                            }
//                        }

                        $html .= '<br/><br/>';
                    } else {
                        $html .= '<span class="danger">Le contrat de l\'abonnement lié n\'existe plus</span><br/><br/>';
                        $contrat_line = null;
                        $id_contrat_line = 0;
                    }
                } else {
                    $html .= 'L\abonnement lié #' . $id_contrat_line . ' n\'existe plus<br/><br/>';
                    $contrat_line = null;
                    $id_contrat_line = 0;
                }
            }

            if ($qties['fac_periodicity'] && $qties['duration']) {
                $nb_prod_periodes = 0;
                if ((int) $qties['prod_duration'] > 0) {
                    $nb_prod_periodes = $qties['duration'] / $qties['prod_duration'];
                }
                $html .= '<b>' . BimpTools::displayFloatValue((float) $qties['nb_units'], 8, ',', 0, 0, 0, 0, 1, 1) . '</b> unité(s) de <b>' . $qties['prod_duration'] . ' mois' . ($nb_prod_periodes > 0 && $nb_prod_periodes != 1 ? ' x ' . ($nb_prod_periodes) : '') . '</b><br/>';
            } else {
                if (!$qties['fac_periodicity']) {
                    $html .= ($html ? '<br/>' : '') . '<span class="danger">Périodicité de facturation non définie</span>';
                }
                if (!$qties['duration']) {
                    $html .= ($html ? '<br/>' : '') . '<span class="danger">Durée totale de l\'abonnement non définie</span>';
                }
            }

            if ($qties['fac_periodicity'] && $qties['duration']) {
                $html .= '<br/><b>Durée abonnement : </b>' . $qties['duration'] . ' mois';
                $html .= '<br/><b>Facturation : </b>' . $this->displayDataDefault('abo_fac_periodicity');

                if ($id_contrat_line) {
                    $html .= '<div style="margin: 8px 0; border: 1px solid #DCDCDC; padding: 5px">';
                    $err = array();
                    $data = $this->getAboFacData($err);

                    if (count($err)) {
                        $html .= '<span class="warning">';
                        $html .= BimpTools::getMsgFromArray($err, 'Le prorata de facturation ne peut pas être calculé');
                        $html .= '</span><br/>';
                    } else {
                        $html .= '<b>Facturé : </b>' . $data['nb_periods_fac'] . ' période' . ($data['nb_periods_fac'] > 1 ? 's' : '') . ' de ' . $qties['fac_periodicity'] . ' mois';
                        if ($data['first_period_prorata'] != 1) {
                            $html .= '<br/><b>Prorata 1ère période : </b>' . BimpTools::displayFloatValue($data['first_period_prorata'], 6, ',', 0, 0, 0, 0, 1, 1);
                        }
                        $html .= '<br/><b>Qté totale : </b>' . BimpTools::displayFloatValue($data['total_qty'], 6, ',', 0, 0, 0, 0, 1, 1);
                        if ($data['first_period_prorata'] != 1) {
                            $html .= '<br/><span class="small">';
                            $html .= '<b>1</b> période de <b>';
                            $html .= BimpTools::displayFloatValue($data['qty_per_period'] * $data['first_period_prorata'], 6, ',', 0, 0, 0, 0, 1, 1);
                            $html .= '</b> unité(s)';
                            if ($data['nb_periods_fac'] > 1) {
                                $html .= ' + <b>' . ($data['nb_periods_fac'] - 1) . '</b> période(s) de <b>';
                                $html .= BimpTools::displayFloatValue($data['qty_per_period'], 6, ',', 0, 0, 0, 0, 1, 1);
                                $html .= '</b> unité(s)';
                            }
                            $html .= '</span>';
                        }
                    }
                    $html .= '</div>';
                } else {
                    $html .= '<br/><b>Qté par facturation : </b>';
                    $html .= BimpTools::displayFloatValue((float) $qties['per_fac_period'], 8, ',', 0, 0, 0, 0, 1, 1);
                }
            }
            $html .= '<br/><b>Qté totale : </b>' . parent::displayLineData('qty');
            $html .= '<br/>Facturation à terme ' . ((int) $this->getData('abo_fac_term') ? 'à échoir' : 'échu');
            if ((int) $this->getData('abo_nb_renouv') >= 0) {
                $s = ((int) $this->getData('abo_nb_renouv') > 1 ? 's' : '');
                $html .= '<br/>' . $this->displayDataDefault('abo_nb_renouv') . ' renouvellement' . $s . ' tacite' . $s;
            } else {
                $html .= '<br/>Renouvellements tacites illimités';
            }
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
                $html .= 'Nb renouvellements tacites : <b>' . $contrat_line->displayDataDefault('nb_renouv') . '</b>';
            }
        }

        return $html;
    }

    public function displayAboFacInfos()
    {
        $html = '';

        $errors = array();
        $data = $this->getAboFacData($errors);

//        if (BimpCore::isUserDev()) {
//            $html .= '<pre>';
//            $html .= print_r($data, 1);
//            $html .= '</pre>';
//        }

        if (count($errors)) {
            $html .= BimpRender::renderAlerts(BimpTools::getMsgFromArray($errors, 'Il n\'est pas possible de déterminer le prorata de facturation'), 'warning');
        } else {
            $html .= '<b>Nb périodes facturées : </b>' . $data['nb_periods_fac'];
            if ($data['first_period_prorata'] != 1) {
                $html .= '<br/><b>Prorata 1ère période (Du ' . date('d / m / Y', strtotime($data['date_first_period_start'])) . ' au ' . date('d / m / Y', strtotime($data['date_first_period_end'])) . ') : </b>' . BimpTools::displayFloatValue($data['first_period_prorata'], 6, ',', 0, 0, 0, 0, 1, 1);
            }
            $html .= '<br/><b>Qté totale finale: </b>' . BimpTools::displayFloatValue($data['total_qty'], 6, ',', 0, 0, 0, 0, 1, 1);
            if ($data['first_period_prorata'] != 1) {
                $html .= '<br/><span class="small">';
                $html .= '<b>1</b> période de <b>';
                $html .= BimpTools::displayFloatValue($data['qty_per_period'] * $data['first_period_prorata'], 6, ',', 0, 0, 0, 0, 1, 1);
                $html .= '</b> unité(s)';
                if ($data['nb_periods_fac'] > 1) {
                    $html .= ' + <b>' . ($data['nb_periods_fac'] - 1) . '</b> période(s) de <b>';
                    $html .= BimpTools::displayFloatValue($data['qty_per_period'], 6, ',', 0, 0, 0, 0, 1, 1);
                    $html .= '</b> unité(s)';
                }
                $html .= '</span>';
            }
        }

        return $html;
    }

    public function displayPdfAboInfos()
    {
        if (!$this->isAbonnement()) {
            return '';
        }

        $html = '';

        if ((int) $this->getData('abo_fac_periodicity')) {
            $html .= '<span style="font-size: 9px">';

            $duration = (int) $this->getData('abo_duration');
            $periodicity = (int) $this->getData('abo_fac_periodicity');
            $nb_units = (float) $this->getData('abo_nb_units');
            $prod_duration = 0;

            $prod = $this->getProduct();
            if (BimpObject::objectLoaded($prod)) {
                $prod_duration = $prod->getData('duree');
            }

            $errors = array();
            $data = $this->getAboFacData($errors);

            if ((int) $this->getData('id_linked_contrat_line')) {
                if (!count($errors)) {
                    $nb_periods = $data['nb_periods_fac'];

                    if ($nb_periods) {


                        if ($data['first_period_prorata'] != 1) {
                            $html .= 'Facturation : ' . $this->displayDataDefault('abo_fac_periodicity') . '<br/>';
                            $html .= '1 facturation de <b>';
                            $html .= BimpTools::displayFloatValue($data['qty_per_period'] * $data['first_period_prorata'], 6, ',', 0, 0, 0, 0, 1, 1);
                            $html .= '</b> unité(s)';

                            $html .= ' <span style="font-style: italic">(';

                            if ($prod_duration && $periodicity != $prod_duration) {
                                $nb_prod_periods = $periodicity / $prod_duration;
                                $html .= 'soit au total <b>' . BimpTools::displayFloatValue($nb_units, 6, ',', 0, 0, 0, 0, 1, 1) . '</b> unité(s) de ' . $prod_duration . ' mois ' . ($nb_prod_periods != 1 ? ' x <b>' . BimpTools::displayFloatValue($nb_prod_periods, 4, ',', 0, 0, 0, 0, 1, 1) . '</b>' : '') . ' ';
                            }

                            $html .= 'au prorata de <b>' . BimpTools::displayFloatValue($data['first_period_prorata'], 6, ',', 0, 0, 0, 0, 1, 1) . '</b>)</span>';

                            if ($nb_periods > 1) {
                                $html .= '<br/>Suivie de ' . ($nb_periods - 1) . ' facturation' . ($nb_periods - 1 > 1 ? 's' : '') . ' de <b>';
                                $html .= BimpTools::displayFloatValue($data['qty_per_period'], 6, ',', 0, 0, 0, 0, 1, 1);
                                $html .= '</b> unité(s)';
                            }

                            if ($prod_duration && $periodicity != $prod_duration) {
                                $nb_prod_periods = ($duration - $periodicity) / $prod_duration;
                                $html .= ' <span style="font-style: italic">(soit au total <b>' . BimpTools::displayFloatValue($nb_units, 6, ',', 0, 0, 0, 0, 1, 1) . '</b> unité(s) de ' . $prod_duration . ' mois ' . ($nb_prod_periods != 1 ? ' x <b>' . BimpTools::displayFloatValue($nb_prod_periods, 4, ',', 0, 0, 0, 0, 1, 1) . '</b>' : '') . ')</span>';
                            }
                        } else {
                            $html .= $nb_periods . ' ';
                            if ($nb_periods > 1) {
                                $html .= 'facturations ' . $this->displayDataDefault('abo_fac_periodicity') . 's ';
                            } else {
                                $html .= 'facturation ' . $this->displayDataDefault('abo_fac_periodicity') . ' ';
                            }

                            $html .= 'de <b>' . BimpTools::displayFloatValue($data['qty_per_period'], 6, ',', 0, 0, 0, 0, 1, 1) . '</b> unité(s)';

                            if ($prod_duration && $periodicity != $prod_duration) {
                                $nb_prod_periods = $duration / $prod_duration;
                                $html .= '<br/><span style="font-style: italic">Soit au total : <b>' . BimpTools::displayFloatValue($nb_units, 6, ',', 0, 0, 0, 0, 1, 1) . '</b> unité(s) de <b>' . $prod_duration . '</b> mois ' . ($nb_prod_periods > 1 ? ' x <b>' . BimpTools::displayFloatValue($nb_prod_periods, 4, ',', 0, 0, 0, 0, 1, 1) . '</b>' : '') . '</span>';
                            }
                        }
                    }
                }
            } elseif ($this->qty) {
                if ($duration && $periodicity) {
                    $nb_periods = $duration / $periodicity;

                    if ($nb_periods) {
                        $html .= $nb_periods . ' ';
                        if ($nb_periods > 1) {
                            $html .= 'facturations ' . $this->displayDataDefault('abo_fac_periodicity') . 's ';
                        } else {
                            $html .= 'facturation ' . $this->displayDataDefault('abo_fac_periodicity') . ' ';
                        }

                        $html .= 'de <b>' . BimpTools::displayFloatValue($this->qty / $nb_periods, 6, ',', 0, 0, 0, 0, 1, 1) . '</b> unité(s)';

                        $prod = $this->getProduct();
                        if (BimpObject::objectLoaded($prod)) {
                            $prod_duration = $prod->getData('duree');

                            if ($prod_duration && $periodicity != $prod_duration) {
                                $nb_prod_periods = $duration / $prod_duration;
                                $html .= '<br/><span style="font-style: italic">Soit au total : <b>' . BimpTools::displayFloatValue($nb_units, 6, ',', 0, 0, 0, 0, 1, 1) . '</b> unité(s) de <b>' . $prod_duration . '</b> mois ' . ($nb_prod_periods > 1 ? ' x <b>' . BimpTools::displayFloatValue($nb_prod_periods, 4, ',', 0, 0, 0, 0, 1, 1) . '</b>' : '') . '</span>';
                            }
                        }
                    }
                }
            }

            $html .= '</span>';
        }

        $nb_renouv = (int) $this->getData('abo_nb_renouv');
        if ($nb_renouv) {
            $html .= ($html ? '<br/>' : '') . '<span style="font-style: italic; font-size: 9px">';
            $html .= 'Cet abonnement sera renouvelé tacitement ';
            if ($nb_renouv > 0) {
                $html .= $nb_renouv . ' fois';
            } else {
                $html .= 'de manière illimité';
            }
            $html .= ' sans dénonciation de votre part.</span>';
        }


        return $html;
    }

    // Rendus HTML : 

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
        $possible_values = array(1, 6, 12, 24, 36, 48, 60);

        if ($duree_unitaire) {
            $options['data']['min'] = $duree_unitaire;
            $options['min_label'] = 1;
            $options['step'] = $duree_unitaire;

            if ($value < $duree_unitaire) {
                $value = $duree_unitaire;
            }

            foreach ($possible_values as $idx => $val) {
                if ($val < $duree_unitaire || ($val % $duree_unitaire != 0)) {
                    unset($possible_values[$idx]);
                }
            }
        }

        if (!empty($possible_values)) {
            $options['possible_values'] = $possible_values;
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
                    'decimals'  => 8
                )
            );

            $nb_units = 1;
            if ($this->isLoaded()) {
                $nb_units = (float) $this->getData('abo_nb_units');
            }

            $html .= '<span class="bold">Nombre d\'unités :</span><br/>';
            $html .= BimpInput::renderInput('qty', 'abo_nb_units', $nb_units, $options);

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
                    $other_ref = BimpTools::getPostFieldValue('abo_linked_line_other_ref', null);
                    $id_linked_contrat_line = (int) $this->getData('id_linked_contrat_line');

                    if (is_null($other_ref) && $id_linked_contrat_line) {
                        $id_linked_product = (int) $this->db->getValue('contratdet', 'fk_product', 'rowid = ' . $id_linked_contrat_line);

                        if ($id_linked_product !== (int) $product->id) {
                            $other_ref = 1;
                        }
                    }

                    $filters = array();

                    if ((int) $other_ref) {
                        $filters['a.fk_product'] = array(
                            'operator' => '!=',
                            'value'    => $product->id
                        );
                    } else {
                        $filters['a.fk_product'] = $product->id;
                    }

                    $lines = BCT_Contrat::getClientAbosLinesArray((int) $id_client, $filters, true, 'NON (Ajouter en tant que nouvel abonnement)', (int) $other_ref);

                    if (count($lines) > 1) {
                        $html .= BimpInput::renderInput('select', 'id_linked_contrat_line', $id_linked_contrat_line, array(
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
            $html .= BimpRender::renderAlerts('Aucun produit sélectionné ou le produit n\'est pas un abonnement');
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

//                    if ((int) $contrat_line->getData('fk_product') !== (int) $this->id_product) {
//                        $errors[] = 'Ajout à un abonnement en cours : le produit ne correspond pas';
//                        $check = false;
//                    }
                }

                $propal = $this->getParentInstance();
                if (BimpObject::objectLoaded($propal) && (int) $propal->getData('fk_statut') === 0) {
                    if (!$this->date_from) {
                        $errors[] = 'Veuillez renseigner la date d\'ouverture prévue pour le calcul du prorata de facturation';
                        $check = false;
                    } else {
                        $from = date('Y-m-d', strtotime($this->date_from));

                        if ($from > $contrat_line->getData('date_fin_validite')) {
                            $errors[] = 'La date d\'ouverture prévue ne peut pas être postérieure à la date de fin de validité de l\'abonnement lié';
                            $check = false;
                        }
                    }
                }
            } else {
                $errors[] = 'La ligne de contrat d\'abonnement liée #' . $id_contrat_line . ' n\'existe plus';
                $check = false;
            }
        }

        return $check;
    }

    public function checkAboData(&$infos = '')
    {
        $errors = array();

        if ($this->isLoaded($errors)) {
            if ($this->isAbonnement()) {
                // Correction nombre d'unité : 
                if ($this->qty && !(float) $this->getData('abo_nb_units')) {
                    $prod_duration = 1;
                    $prod = $this->getProduct();

                    if (!BimpObject::objectLoaded($prod) && (int) $this->getData('id_parent_line')) {
                        $parentLine = $this->getParentLine();
                        if (BimpObject::objectLoaded($parentLine)) {
                            $prod = $parentLine->getProduct();
                        }
                    }

                    if (BimpObject::objectLoaded($prod)) {
                        $prod_duration = (int) $prod->getData('duree');
                        if (!$prod_duration) {
                            $errors[] = 'Durée produit non définie';
                        }
                    }

                    $duration = (int) $this->getData('abo_duration');
                    if (!$duration) {
                        $errors[] = 'Durée abonnement non définie';
                    }
                    if ($duration && $prod_duration) {
                        $nb_units = ($this->qty / $duration) * $prod_duration;
                        $this->updateField('abo_nb_units', $nb_units);

                        $infos .= ($infos ? '<br/>' : '') . 'Correction nombre d\'unités : ' . $nb_units;
                    }
                }

                // Correction dates de fin : 

                if ($this->date_from && (int) $this->getData('id_linked_contrat_line')) {
                    $contrat_line = BimpCache::getBimpObjectInstance('bimpcontrat', 'BCT_ContratLine', (int) $this->getData('id_linked_contrat_line'));
                    if (BimpObject::objectLoaded($contrat_line)) {
                        $date_to = $contrat_line->getData('date_fin_validite');

                        if ($date_to && $date_to != $this->date_to) {
                            $this->date_to = $date_to;

                            $this->db->update('propaldet', array(
                                'date_end' => $this->date_to
                                    ), 'rowid = ' . (int) $this->getData('id_line'));

                            $infos .= ($infos ? '<br/>' : '') . 'Correction date de fin : ' . $this->date_to;
                        }
                    }
                }
            }
        }

        return $errors;
    }

    // Overrides : 

    public function checkObject($context = '', $field = '')
    {
        if ($context === 'fetch') {
            $this->checkAboData();
        }
        parent::checkObject($context, $field);
    }

    public function validatePost()
    {
        $errors = parent::validatePost();

        if ($this->isAbonnement()) {
            if (BimpTools::isSubmit('abo_date_from')) {
                $this->date_from = BimpTools::getValue('abo_date_from', null);
            }
        }

        return $errors;
    }

    public function validate()
    {
        $errors = array();

        if ($this->isAbonnement()) {
            $total_qty = 0;
            $id_contrat_line = (int) $this->getData('id_linked_contrat_line');
            $contrat_line = null;
            if ($id_contrat_line) {
                if ($this->checkLinkedContratLine($errors)) {
                    if (!count($errors)) {
                        $contrat_line = BimpCache::getBimpObjectInstance('bimpcontrat', 'BCT_ContratLine', $id_contrat_line);
                        $this->set('abo_fac_periodicity', $contrat_line->getData('fac_periodicity'));
                        $this->set('abo_duration', $contrat_line->getData('duration'));
                        $this->set('abo_fac_term', $contrat_line->getData('fac_term'));
                        $this->set('abo_nb_renouv', $contrat_line->getData('nb_renouv'));

                        $err = array();
                        $periods_data = $this->getAboFacData($err);

                        if (!count($err)) {
                            $total_qty = $periods_data['total_qty'];
                        }
                    }
                }
            }

            $duration = (int) $this->getData('abo_duration');

            if (!$total_qty) {
                $nb_units = (float) $this->getData('abo_nb_units');

                $prod = $this->getProduct();
                if (BimpObject::objectLoaded($prod)) {
                    $prod_duration = (int) $prod->getData('duree');
                    if (!$prod_duration) {
                        $errors[] = 'Durée unitaire du produit non définie';
                    }
                }

                if ($nb_units && $duration && $prod_duration) {
                    $total_qty = $nb_units * ($duration / $prod_duration);
                } else {
                    $total_qty = $this->qty;
                }
            }

            $this->qty = $total_qty;

            if ($this->date_from && (int) $this->getData('abo_duration')) {
                if (BimpObject::objectLoaded($contrat_line)) {
                    $this->date_to = $contrat_line->getData('date_fin_validite');
                } else {
                    $dt = new DateTime($this->date_from);
                    $dt->add(new DateInterval('P' . (int) $this->getData('abo_duration') . 'M'));
                    $dt->sub(new DateInterval('P1D'));
                    $this->date_to = $dt->format('Y-m-d');
                }
            }
        } else {
            $this->set('abo_nb_units', 0);
            $this->set('abo_fac_periodicity', 0);
            $this->set('abo_duration', 0);
            $this->set('abo_fac_term', 0);
            $this->set('abo_nb_renouv', 0);
            $this->set('id_linked_contrat_line', 0);
//            $this->set('abo_date_start', null);
        }

        $errors = BimpTools::merge_array($errors, parent::validate());

        return $errors;
    }

    public function update(&$warnings = array(), $force_update = false)
    {
        $errors = parent::update($warnings, $force_update);

        if (!count($errors)) {
            $id_contrat_line = (int) $this->db->getValue('contratdet', 'rowid', 'line_origin_type = \'propal_line\' AND id_line_origin = ' . $this->id);
            if ($id_contrat_line) {
                $contrat_line = BimpCache::getBimpObjectInstance('bimpcontrat', 'BCT_ContratLine', $id_contrat_line);

                if (BimpObject::objectLoaded($contrat_line) && (int) $contrat_line->getData('statut') === BCT_ContratLine::STATUS_ATT_PROPAL) {
                    // Maj de la ligne de contrat d'abo liée : 
                    $prod = $this->getProduct();
                    $id_pfp = (int) $this->id_fourn_price;
                    if (!$id_pfp && BimpObject::objectLoaded($prod)) {
                        $id_fourn = (int) $prod->getData('achat_def_id_fourn');
                        if ($id_fourn) {
                            $id_pfp = (int) $prod->getLastFournPriceId($id_fourn);
                        }
                    }

                    $contrat_line->validateArray(array(
                        'fk_product'                   => $this->id_product,
                        'description'                  => $this->desc,
                        'product_type'                 => $this->product_type,
                        'qty'                          => $this->qty,
                        'subprice'                     => $this->pu_ht,
                        'tva_tx'                       => $this->tva_tx,
                        'remise_percent'               => $this->remise,
                        'fk_product_fournisseur_price' => $id_pfp,
                        'buy_price_ht'                 => $this->pa_ht,
                        'fac_periodicity'              => $this->getData('abo_fac_periodicity'),
                        'duration'                     => $this->getData('abo_duration'),
                        'fac_term'                     => $this->getData('abo_fac_term'),
                        'nb_renouv'                    => $this->getData('abo_nb_renouv'),
                        'date_ouverture_prevue'        => date('Y-m-d', strtotime($this->date_from)) . ' 00:00:00'
                    ));

                    $contrat_line->update($w, true);
                }
            }
        }

        return $errors;
    }

    public function delete(&$warnings = array(), $force_delete = false)
    {
        $errors = array();

        if ($this->getData('linked_object_name') == 'discount') {
            $parent = $this->getParentInstance();
            $parent->dol_object->statut = 0;
            $errors = parent::delete($warnings, $force_delete);
            $parent->dol_object->statut = $parent->getInitData('statut');

            return $errors;
        }

        $contrat_line = null;
        $id_contrat_line = (int) $this->db->getValue('contratdet', 'rowid', 'line_origin_type = \'propal_line\' AND id_line_origin = ' . $this->id);
        if ($id_contrat_line) {
            $contrat_line = BimpCache::getBimpObjectInstance('bimpcontrat', 'BCT_ContratLine', $id_contrat_line);
        }

        $errors = parent::delete($warnings, $force_delete);

        if (!count($errors)) {
            if (BimpObject::objectLoaded($contrat_line) && (int) $contrat_line->getData('statut') === BCT_ContratLine::STATUS_ATT_PROPAL) {
                $contrat_line->set('statut', 0);
                $contrat_line->dol_object->statut = 0;
                $w = array();
                $contrat_line->delete($w, true);
            }
        }

        return $errors;
    }
}
