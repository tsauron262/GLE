<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/classes/BimpInput.php';
require_once DOL_DOCUMENT_ROOT . '/bimpcontract/objects/BContract_contrat.class.php';

class BContract_echeancier extends BimpObject {

    public function canViewObject($object) {
        if (is_object($object))
            return true;
        return false;
    }

    public function display($display_error = '') {
        global $db;
        $bimp = new BimpDb($db);
        if ($display_error) {
            return BimpRender::renderAlerts($display_error);
        }

        global $db;
        $facture = BimpTools::loadDolClass('compta/facture', 'facture');
        $facture = new Facture($db);

        //$line = $this->db->getRow('contrat_next_facture', 'id_contrat = ' . $this->id); // TODO à voir pour le 
        $parent = $this->getParentInstance();
        $nb_period = $parent->getData('duree_mois') / $parent->getData('periodicity');
        $this->calc_period_echeancier();
        $this->getNbFacture();
        $nb_period_restante = $nb_period - $this->nb_facture;
        $stop_echeancier = ($nb_period_restante == 0) ? true : false;
        $verif_statut = ($parent->getData('statut') > 1) ? true : false;

        $html = '';
        $html .= ($stop_echeancier) ? BimpRender::renderAlerts('Toutes les factures de l\'échéancier ont été générées', 'info', false) : '';
        $html .= '<table class="noborder objectlistTable" style="border: none; min-width: 480px">'
                . '<thead>'
                . '<tr class="headerRow">'
                . '<th class="th_checkboxes" width="40px" style="text-align: center">Période de facturation<br />Début - Fin</th>'
                . '<th class="th_checkboxes" width="40px" style="text-align: center">Montant HT</th>'
                . '<th class="th_checkboxes" width="40px" style="text-align: center">Montant TTC</th>'
                . '<th class="th_checkboxes" width="40px" style="text-align: center">Facture</th>'
                . '<th class="th_checkboxes" width="40px" style="text-align: center">&Eacute;tat de paiement</th>'
                . '<th class="th_checkboxes" width="40px" style="text-align: center">Action facture</th>'
                . '</tr></thead>'
                . '<tbody class="listRows">';
            
        foreach ($this->tab_echeancier as $lignes) {
            $html .= '<tr class="objectListItemRow" >'
                    . '<td style="text-align:center" >'
                    . 'Du ' . dol_print_date($lignes['date_debut']) . ' au ' . dol_print_date($lignes['date_fin']);
            $total_facture_ht = 0;
            $total_facture_ttc = 0;
            $new_price_ht = $this->get_total_contrat('ht');
            $new_price_ttc = $this->get_total_contrat('ttc');
            
            $have_facture = false;
            if ($lignes['facture'] > 0) {
                $have_facture = true;
                $facture->fetch($lignes['facture']);
                $new_price_ht =  $facture->total_ht;
                $new_price_ttc =  $facture->total_ttc;
                $total_facture_ht += $facture->total_ht;
                $total_facture_ttc += $facture->total_ttc;
            } else {
                $facture = null;
                                
            }            
            if($have_facture){
                $affichage_ht = $new_price_ht;
                $affichage_ttc = $new_price_ttc;
            } else {
                $tab_total_fact = $this->get_total_facture();
                $affichage_ht = ($tab_total_fact['info']['reste_a_payer_ht']) / $nb_period_restante;
                $affichage_ttc = ($tab_total_fact['info']['reste_a_payer_ttc']) / $nb_period_restante;
            }
            
            
            if ($facture->paye) {
                $paye = '<i class="fa fa-check" style="color:green"><b> Payée</b></i>';
            } elseif (is_object($facture) && $facture->paye == 0) {
                $paye = '<i class="fa fa-close" style="color:red"> <b>Impayée</b></i>';
            } else {
                $paye = '<i class="fa fa-refresh" style="color:orange" > <b>En attente de facturation</b></i>';
            }

            // Construction actionButton
            $callback = 'function(result) {if (typeof (result.file_url) !== \'undefined\' && result.file_url) {window.open(result.file_url)}}';
            $actionsButtons = '';
            $actionsButtons .= ($this->canViewObject($facture)) ? '<span class="rowButton bs-popover" data-trigger="hover" data-placement="top"  data-content="Vue rapide de la facture" onclick="loadModalView(\'bimpcommercial\', \'Bimp_facture\', ' . $facture->id . ', \'default\', $(this), \'Facture ' . $facture->ref . '\')"><i class="far fa5-eye"  ></i></span>' : '';
            $actionsButtons .= (is_object($facture) && $facture->statut == 0 && $this->getData('validate') == 0) ? '<span class="rowButton bs-popover" data-trigger="hover" data-placement="top"  data-content="Supprimer la facture" onclick="' . $this->getJsActionOnclick("delete_facture", array('id_facture' => $facture->id), array("success_callback" => $callback)) . '")"><i class="fa fa-times" ></i></span>' : '';
            $actionsButtons .= (is_object($facture) && $facture->statut == 0 && $this->getData('validate') == 0 ) ? '<span class="rowButton bs-popover" data-trigger="hover" data-placement="top"  data-content="Valider la facture" onclick="' . $this->getJsActionOnclick("validate_facture", array('id_facture' => $facture->id), array("success_callback" => $callback)) . '")"><i class="fa fa-check" ></i></span>' : '';
            $actionsButtons .= (!$this->canViewObject($facture)) ? '<span style="cursor: not-allowed" class="rowButton bs-popover" data-trigger="hover" data-placement="top"  data-content="Pas de facture à voir" onclick="")"><i class="far fa-eye-slash"></i></span>' : '';

            $html .= '</td>'
                    . '<td style="text-align:center">' . price($affichage_ht) . ' € </td>'
                    . '<td style="text-align:center">' . price($affichage_ttc) . ' € </td>'
                    . '<td style="text-align:center">' . (is_object($facture) ? $facture->getNomUrl(1) : "<b style='color:grey'>Pas encore de facture</b>") . '</td>'
                    . '<td style="text-align:center">' . $paye . '</td>'
                    . '<td style="text-align:center; margin-right:10%">'
                    . $actionsButtons
                    . '</td>'
                    . '</tr>';
        }
        $html .= '</tbody>' . '</table>';
        $html .= $this->display_info();
        if (!$stop_echeancier && !$verif_statut) {
            if ($this->getData('next_facture_date') == '0000-00-00 00:00:00') {
                $last_facture = $this->get_last_facture();
                $udpadeArray = Array('next_facture_date' => $last_facture['date_debut'], 'next_facture_amount' => $last_facture['montant_ht']);
                $bimp->update('bcontract_prelevement', $udpadeArray, 'id_contrat = ' . $parent->id);
            }
            $html .= "<table style='width:30%;' class='border' border='1'>"
                    . "<tr><th style='background-color:#ed7c1c;color:white;text-align:center'>Date prochaine facture</th>"
                    . "<td style='text-align:center'><b>" . dol_print_date($this->getData('next_facture_date')) . "</b></td></tr>"
                    . "<tr><th style='background-color:#ed7c1c;color:white;text-align:center'>Montant prochaine facture</th>"
                    . "<td style='text-align:center'><b>" . price($this->calc_next_facture_amount_ht()) . " € HT / " . price($this->calc_next_facture_amount_ttc()) . " € TTC </b></td></tr>"
                    . "</table><br />";


            $callback = 'function(result) {if (typeof (result.file_url) !== \'undefined\' && result.file_url) {window.open(result.file_url)}}'; // TODO 
            if ($this->getData('validate') == 0) {
                $showCreate = true;
                foreach ($this->tab_echeancier as $facture => $attr) { // TODO a voir lundi pour mettre dans tab echeancier statut facture
                    if ($attr['facture'] > 0) {
                        if ($attr['statut'] == 0) {
                            $showCreate = false;
                            break;
                        }
                    }
                }
                if ($showCreate) {
                    $html .= '<br /><input class="btn btn-primary saveButton" value="Créer une facture" onclick="' .
                            $this->getJsActionOnclick("create_facture", array(), array(
                                "success_callback" => $callback
                            ))
                            . '"><br /><br />';
                }
            }
        } else {
            $updateData = Array('next_facture_date' => null, 'next_facture_amount' => null);
            $bimp->update('bcontract_prelevement', $updateData, 'id_contrat = ' . $parent->id);
        }


        $tmp_id_facture = 0;
        foreach ($this->tab_echeancier as $tab => $attr) {
            if ($attr['facture'] > 0) {
                $tmp_id_facture = $attr['facture'];
            }
        }
        if ($parent->getData('statut') > 0 && $parent->getInitData('statut') > 0 && $tmp_id_facture > 0) {
            $facture = new Facture($db);
            $facture->fetch($tmp_id_facture);
            $date_database = new DateTime($this->getData('next_facture_date'));
            $date_database->getTimestamp();
            $date_database->sub(new DateInterval("P" . $parent->getData('periodicity') . "M"));
            $date_facture = new DateTime(date('Y-m-d', $facture->date));
            $date_facture->getTimestamp();

            if ($date_database > $date_facture) {
                $this->updateLine($this->getData('id_contrat'), $date_database->format('Y-m-d'));
            }
        }

        // Gestion de la première facture
        if ($tmp_id_facture == 0) {
            $this->updateLine($this->getData('id_contrat'), $parent->getData('date_start'));
        }

        // facture personnalisé
        //$html .= '' . $this->display_select();
        $html .= ' ' . $this->display_facture_perso();

//        echo '<pre>';
//        print_r($this->tab_echeancier);
        return $html;
    }

    public function display_facture_perso() {
        $callback = 'function(result) {if (typeof (result.file_url) !== \'undefined\' && result.file_url) {window.open(result.file_url)}}';

        $html .= '<br /><form action="#" method="post">'
                . 'Du <input class="datePicker" name="date_debut_select" placeholder="Date de début" /input> au '
                . '<input class="datePicker" name="date_fin_select" placeholder="Date de fin" /input><br />'
//                . '<input type="submit" name="submit" class="btn btn-primary saveButton" value="Créer facture perso" onclick="' .
//                $this->getJsActionOnclick("create_facture_perso", array(), array(
//                    "success_callback" => $callback
//                )) . '">';
                . '<input class="btn btn-primary saveButton" type="submit" name="submit" value="Créee facture personnalisé" /></form>';

        if (isset($_POST['submit'])) {
            $select_debut = $_POST['date_debut_select'];
            $select_fin = $_POST['date_fin_select'];
            //echo "test : Du " . $select_debut . ' au ' . $select_fin;

            $converted_date_debut = $this->formatDate($select_debut);
            $converted_date_fin = $this->formatDate($select_fin);
        }
        return $html;
    }
    
    public function formatDate($date) { {
            if (strpos($date, '/') !== false) :
                $date = str_replace('/', '-', $date);
                $date = date('Y-m-d', strtotime($date));
            else :
                $date = date('d-m-Y', strtotime($date));
                $date = str_replace('-', '/', $date);
            endif;
            return $date;
        }
    }

    public function display_info() {
        $tab_total_fact = $this->get_total_facture();
        foreach ($tab_total_fact['info'] as $line) {
            //echo 'info = ' . $line;
        }
        $html .= "<br/>"
                . "<table style='width:50%;float:right' class='border' border='1'>"
                . "<tr> <th style='border: 1px solid Transparent!important;'></th>  <th style='background-color:#ed7c1c;color:white;text-align:center'>Montant HT</th> <th style='background-color:#ed7c1c;color:white;text-align:center'>Montant TTC</th> </tr>"
                . "<tr> <th style='background-color:#ed7c1c;color:white;text-align:center'>Total contrat</th> <td style='text-align:center'><b>" . price($this->get_total_contrat('ht')) . " €</b></td> <td style='text-align:center'><b> " . price($this->get_total_contrat('ttc')) . " €</b></td> </tr>"
                . "<tr > <th  style='background-color:#ed7c1c;color:white;text-align:center'>Déjà payer</th> <td style='text-align:center'><b>" . price($tab_total_fact['info']['deja_payer_ht']) . " € </b></td> <td style='text-align:center'><b> " . price($tab_total_fact['info']['deja_payer_ttc']) . " €</b></td> </tr>"
                . "<tr> <th style='background-color:#ed7c1c;color:white;text-align:center'>Reste à payer</th> <td style='text-align:center'><b> " . price($tab_total_fact['info']['reste_a_payer_ht']) . " € </b></td> <td style='text-align:center'><b> " . price($tab_total_fact['info']['reste_a_payer_ttc']) . " €</b></td> </tr>"
                . "</table>";

        return $html;
    }

    public function calc_period_echeancier() {
        $parent = $this->getParentInstance();
        $date_contrat = $parent->getData('date_start');
        $total_ht = $this->get_total_contrat('ht');
        $total_ttc = $this->get_total_contrat('ttc');
        $date_debut = new DateTime("$date_contrat");
        $date_debut->getTimestamp();
        $date_fin = new DateTime("$date_contrat");
        $date_fin->getTimestamp();
        $nb_period = $parent->getData('duree_mois') / $parent->getData('periodicity');
        $montant_facturer_ttc = $total_ttc / $nb_period;
        $montant_facturer_ht = $total_ht / $nb_period;
        $date_fin->add(new DateInterval("P" . $parent->getData('periodicity') . "M"));
        $date_fin->sub(new DateInterval("P1D"));
        $list_fact = $this->get_total_facture();
        for ($i = 0; $i < $nb_period; $i++) {
            $facture = $this->getInstance('bimpcommercial', 'Bimp_Facture', $list_fact[$i]['id_facture']);
            $format = 'Y-m-d';
            if ($date_debut->format('d') == '01') {
                $format = 'Y-m-t';
                $date_fin->sub(new DateInterval("P1D"));
            }
            $this->tab_echeancier[$i] = array('date_debut' => $date_debut->format('Y-m-d'), 'date_fin' => $date_fin->format("$format"), 'montant_ht' => $montant_facturer_ht, 'montant_ttc' => $montant_facturer_ttc);
            $date_debut->add(new DateInterval("P" . $parent->getData('periodicity') . "M"));
            $date_fin->add(new DateInterval("P" . $parent->getData('periodicity') . "M"));
            if (array_key_exists($i, $list_fact)) {
                $this->tab_echeancier[$i]['statut'] = $facture->getData('fk_statut');
                $this->tab_echeancier[$i]['facture'] = $list_fact[$i]['id_facture'];
            } else {
                $this->tab_echeancier[$i]['facture'] = 0;
            }
        }
    }

    public function calc_next_facture_amount_ht() {
        $parent = $this->getParentInstance();
        $instance = $this->getInstance('bimpcontract', 'BContract_contrat', $parent->id);
        $nb_period = $instance->getData('duree_mois') / $instance->getData('periodicity');
        $montantFacture = number_format($this->get_total_contrat('ht') / $nb_period, 2, '.', '');

        return $montantFacture;
    }

    public function calc_next_facture_amount_ttc() {
        $parent = $this->getParentInstance();
        $instance = $this->getInstance('bimpcontract', 'BContract_contrat', $parent->id);
        $nb_period = $instance->getData('duree_mois') / $instance->getData('periodicity');
        $montantFacture = number_format($this->get_total_contrat('ttc') / $nb_period, 2, '.', '');

        return $montantFacture;
    }

    public function calc_next_date($sub = false) {
        $parent = $this->getParentInstance();
        $date = $this->getData('next_facture_date');
        $periodicity = $parent->getData('periodicity');
        $nextdate = new DateTime("$date");
        $nextdate->getTimestamp();
        $nextdate->add(new DateInterval("P" . $periodicity . "M"));

        if ($sub) {
            $nextdate->sub(new DateInterval("P1D"));
        }

        $newdate = $nextdate->format('Y-m-d');
        return $newdate;
    }

    public function updateLine($id_contrat = null, $new_date = null) {
        global $db;
        $parent = $this->getParentInstance();
        $bimp = new BimpDb($db);
        $lines = $bimp->getRows('bcontract_prelevement', 'id_contrat = ' . $id_contrat);
        $linesContrat = $bimp->getRows('contratdet', 'fk_contrat = ' . $id_contrat);

        if ($parent->getData('statut') > 0 && $parent->getInitData('statut') > 0) {
            $updateDate = $this->calc_next_date();
        }


        if (!is_null($new_date)) {
            $updateDate = $new_date;
        }

        foreach ($linesContrat as $line) {
            $ttc += $line->total_ttc;
        }
        if ($lines) {
            $parent = $this->getParentInstance();
            $updateData = Array(
                'next_facture_date' => $updateDate,
                'next_facture_amount' => $this->calc_next_facture_amount_ht()
            );
            $bimp->update('bcontract_prelevement', $updateData, 'id_contrat = ' . $parent->id);
        } else {
            if (!is_null($id_contrat)) {
                $instance = $this->getInstance('bimpcontract', 'BContract_contrat', $id_contrat);
                $insertData = Array(
                    'id_contrat' => $instance->id,
                    'next_facture_date' => $instance->getData('date_start'),
                    'next_facture_amount' => $this->calc_next_facture_amount_ht(),
                    'validate' => 0
                );
                $bimp->insert('bcontract_prelevement', $insertData);
                return true;
            }
        }
    }

    public function getNbFacture() {
        $return = 0;
        if (!$this->tab_echeancier) {
            $this->calc_period_echeancier();
        }
        foreach ($this->tab_echeancier as $periode) {
            if ($periode['facture'] > 0) {
                $return++;
            }
        }
        return $return + 1;
    }

    public function actionDelete_facture($data, &$success) {
        global $user, $db;
        $bimp = new BimpDb($db);

        BimpTools::loadDolClass('compta/facture', 'facture');
        $facture = new Facture($db);
        $facture->fetch($data['id_facture']);
        $success = '';

        $facture->delete($user, 0);

        $success = 'Facture ' . $data['id_facture'] . ' supprimer avec succès';
    }

    public function actionValidate_facture($data, &$success) {
        global $user, $db;
        $bimp = new BimpDb($db);

        BimpTools::loadDolClass('compta/facture', 'facture');
        $facture = new Facture($db);
        $facture->fetch($data['id_facture']);
        $success = '';

        $facture->validate($user, 0);

        $success = 'Facture ' . $data['id_facture'] . ' valider avec succès';
    }

    public function actionCreate_facture($data, &$success) {
        global $user, $db;
        $bimp = new BimpDb($db);
        $success = '';

        BimpTools::loadDolClass('compta/facture', 'facture');
        $facture = new Facture($db);

        $parent = $this->getParentInstance();
        $date_debut = $parent->getData('date_start');
        $period = $this->getData('periodicity');

        $facture->date = $this->getData('next_facture_date');


        $facture->cond_reglement_id = 2;
        $facture->cond_reglement_code = 'RECEP';
        $now = dol_now();
        $arraynow = dol_getdate($now);
        $nownotime = dol_mktime(0, 0, 0, $arraynow['mon'], $arraynow['mday'], $arraynow['year']);
        $facture->date_lim_reglement = $nownotime + 3600 * 24 * 30;
        $facture->date_lim_reglement = $facture->calculate_date_lim_reglement();
        $facture->mode_reglement_id = 0;  // Not forced to show payment mode CHQ + VIR
        $facture->mode_reglement_code = ''; // Not forced to show payment mode CHQ + VIR
        $facture->socid = $parent->getData('fk_soc');
        $facture->array_options['options_type'] = "C";
        $facture->array_options['options_entrepot'] = 50;
        $facture->array_options['options_libelle'] = "Facture N°" . $this->getNbFacture() . " du contrat " . $parent->getData('ref');
        if ($facture->create($user) > 0) {
            $nb_period = $parent->getData('duree_mois') / $parent->getData('periodicity');
            $nb_period_restante = $nb_period - $this->nb_facture;
            $rest = $this->get_total_facture();
            $facture->addline("Période de facturation : Du <b>" . dol_print_date($facture->date) . "</b> au <b>" . dol_print_date($this->calc_next_date(true)) . "</b>", price($rest['info']['reste_a_payer_ht'] / $nb_period_restante), 1, 20);
            addElementElement('contrat', 'facture', $parent->id, $facture->id);
        } else {
            return Array('errors' => 'error facture');
        }
        $this->updateLine($parent->id);

        $success = 'Facture ' . $facture->id . ' créer avec succès d\'un montant de ' . price($this->getData('next_facture_amount')) . ' €';
    }

    public function actionCreate_facture_perso($select_debut, $select_fin) {

        global $user, $db;
        $bimp = new BimpDb($db);
        $success = '';

        BimpTools::loadDolClass('compta/facture', 'facture');
        $facture = new Facture($db);
        $parent = $this->getParentInstance();
        $facture->date = $select_debut;
        $facture->socid = $parent->getData('fk_soc');
        $facture->array_options['options_type'] = "C";
        $facture->array_options['options_entrepot'] = 50;
        $facture->array_options['options_libelle'] = "Facture N°" . $this->getNbFacture() . " du contrat " . $parent->getData('ref');
        if ($facture->create($user) > 0) {
            $nb_period = $parent->getData('duree_mois') / $parent->getData('periodicity');
            $facture->addline("Période de facturation : Du <b>" . dol_print_date($select_debut) . "</b> au <b>" . dol_print_date($select_fin) . "</b>", number_format($this->get_total_contrat() / $nb_period, 2, '.', ''), 1, 20);
            addElementElement('contrat', 'facture', $parent->id, $facture->id);
        } else {
            return Array('errors' => 'error facture');
        }
        $this->updateLine($parent->id);

        $success = 'Facture personnalisé ' . $facture->id . ' créer avec succès d\'un montant de ' . price($this->getData('next_facture_amount')) . ' €';
    }

    public function cron_create_facture() {
        // recuperer tous les echeancier passer

        $echeanciers = $this->getList();
        foreach ($echeanciers as $echeancier) {
            $echeancier->create_facture(true);
        }
    }

    public function get_total_contrat($ttc_or_ht = 'ht') {
        $parent = $this->getParentInstance();
        //print('<pre>');
        //print_r($parent->dol_object->lines); //TODO a changer
        //$lines = $parent->getChildrenListArray('lines');
        $lines = $parent->dol_object->lines;

        foreach ($lines as $line) {
            if ($ttc_or_ht == 'ht') {
                $return += $line->total_ht;
            } else {
                $return += $line->total_ttc;
            }
        }
        return $return;
    }

    public function get_total_facture() {
        global $db;

        $this->deja_payer_ttc = $this->deja_payer_ht = $this->nb_facture = 0;
        $parent = $this->getParentInstance();
        $bimp = new BimpDb($db);
        $lines = $bimp->getRows('element_element', 'sourcetype = "contrat" AND targettype="facture" AND fk_source=' . $parent->id . ' ORDER BY rowid ASC');
        $tab_facture = Array();
        foreach ($lines as $line) {
            $facture = new Facture($db);
            $facture->fetch($line->fk_target);
            $this->deja_payer_ht += $facture->total_ht;
            $this->deja_payer_ttc += $facture->total_ttc;
            $this->nb_facture++;
            $tab_facture[]['id_facture'] = $line->fk_target;
            $tab_facture['info']['deja_payer_ht'] = $this->deja_payer_ht;
            $tab_facture['info']['deja_payer_ttc'] = $this->deja_payer_ttc;
            $tab_facture['info']['reste_a_payer_ht'] = $this->get_total_contrat('ht') - $tab_facture['info']['deja_payer_ht'];
            $tab_facture['info']['reste_a_payer_ttc'] = $this->get_total_contrat('ttc') - $tab_facture['info']['deja_payer_ttc'];
        }
        return $tab_facture;
    }

    //get last facture id/object to get date last fact
    public function get_last_facture() {
        return(end($this->tab_echeancier));
    }

}
