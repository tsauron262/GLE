<?php

require_once DOL_DOCUMENT_ROOT . "/contrat/class/contrat.class.php";

class Synopsis_Contrat extends Contrat {

    public function getTypeContrat_noLoad($id) {
        $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "contrat WHERE rowid = " . $id;
        $sql = $this->db->query($requete);
        $res = $this->db->fetch_object($sql);
        return($res->extraparams);
    }

    public function fetch($id, $ref = '') {
        parent::fetch($id, $ref);
        $this->type = $this->extraparams;
    }

    public function displayExtraInfoCartouche() {
        return "";
    }

    public function contratCheck_link() {
        $this->linkedArray['co'] = array();
        $this->linkedArray['pr'] = array();
        $this->linkedArray['fa'] = array();
        $db = $this->db;
        //check si commande ou propale ou facture
        if (preg_match('/^([c|p|f]{1})([0-9]*)/', $this->linkedTo, $arr)) {
            //si commande check si propal lie a la commande / facture etc ...
            switch ($arr[1]) {
                case "p":
                    //test si commande facture
                    $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "co_pr WHERE fk_propale = " . $arr[2];
                    if ($resql = $db->query($requete)) {
                        while ($res = $db->fetch_object($resql)) {
                            array_push($this->linkedArray['co'], $res->fk_commande);
                        }
                    }
                    $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "fa_pr WHERE fk_propale = " . $arr[2];
                    if ($resql = $db->query($requete)) {
                        while ($res = $db->fetch_object($resql)) {
                            array_push($this->linkedArray['fa'], $res->fk_facture);
                        }
                    }
                    break;
                case "c":
                    //test si commande propal ...
                    $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "co_pr WHERE fk_commande = " . $arr[2];
                    if ($resql = $db->query($requete)) {
                        while ($res = $db->fetch_object($resql)) {
                            array_push($this->linkedArray['pr'], $res->fk_propale);
                        }
                    }
                    $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "co_fa WHERE fk_commande = " . $arr[2];
                    if ($resql = $db->query($requete)) {
                        while ($res = $db->fetch_object($resql)) {
                            array_push($this->linkedArray['fa'], $res->fk_facture);
                        }
                    }
                    break;
                case "f":
                    //test si propal facture ...
                    $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "co_fa WHERE fk_facture = " . $arr[2];
                    if ($resql = $db->query($requete)) {
                        while ($res = $db->fetch_object($resql)) {
                            array_push($this->linkedArray['co'], $res->fk_commande);
                        }
                    }
                    $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "fa_pr WHERE fk_facture = " . $arr[2];
                    if ($resql = $db->query($requete)) {
                        while ($res = $db->fetch_object($resql)) {
                            array_push($this->linkedArray['pr'], $res->fk_propal);
                        }
                    }
                    break;
            }
        }
        //ajoute donnees dans les tables
//        var_dump($this->linkedArray);
    }

    public function getTypeContrat() {
        $array[0]['type'] = "Simple";
        $array[0]['Nom'] = "Simple";
        $array[1]['type'] = "Service";
        $array[1]['Nom'] = "Service";
        $array[2]['type'] = "Ticket";
        $array[2]['Nom'] = "Au ticket";
        $array[3]['type'] = "Maintenance";
        $array[3]['Nom'] = "Maintenance";
        $array[4]['type'] = "SAV";
        $array[4]['Nom'] = "SAV";
        $array[5]['type'] = "Location";
        $array[5]['Nom'] = "Location de produits";
        $array[6]['type'] = "LocationFinanciere";
        $array[6]['Nom'] = "Location Financi&egrave;re";
        $array[7]['type'] = "Mixte";
        $array[7]['Nom'] = "Mixte";
        return ($array[$this->typeContrat]);
    }

    public function getExtraHeadTab($head) {
        return $head;
    }

    public function list_all_valid_contacts() {
        return array();
    }

    function display1Line($object, $objL) {
        global $langs;
        $langs->load("contracts");
        $idLigne = $objL->id;
        $db = $this->db;
        $productstatic = new Product($db);
        print '<tr height="16" ' . $bc[false] . '>';
        print '<td class="liste_titre" width="90" style="border-left: 1px solid #' . $colorb . '; border-top: 1px solid #' . $colorb . '; border-bottom: 1px solid #' . $colorb . ';">';
//            print $langs->trans("ServiceNb",$cursorline).'</td>';

        print '<td class="tab" style="border-right: 1px solid #' . $colorb . '; border-top: 1px solid #' . $colorb . '; border-bottom: 1px solid #' . $colorb . ';" rowspan="2">';

        // Area with common detail of line
        print '<table class="notopnoleft" width="100%">';

        $sql = "SELECT cd.rowid, cd.statut, cd.label as label_det, cd.fk_product, cd.description, cd.price_ht, cd.qty,";
        $sql.= " cd.tva_tx, cd.remise_percent, cd.info_bits, cd.subprice,";
        $sql.= " cd.date_ouverture_prevue as date_debut, cd.date_ouverture as date_debut_reelle,";
        $sql.= " cd.date_fin_validite as date_fin, cd.date_cloture as date_fin_reelle,";
        $sql.= " cd.commentaire as comment,";
        $sql.= " p.rowid as pid, p.ref as pref, p.label as label, p.fk_product_type as ptype";
        $sql.= " FROM " . MAIN_DB_PREFIX . "contratdet as cd";
        $sql.= " LEFT JOIN " . MAIN_DB_PREFIX . "product as p ON cd.fk_product = p.rowid";
        $sql.= " WHERE cd.rowid = " . $idLigne;

        $result = $db->query($sql);
        if ($result) {
            $total = 0;

            print '<tr class="liste_titre">';
            print '<td>' . $langs->trans("Service") . '</td>';
            print '<td width="50" align="center">' . $langs->trans("VAT") . '</td>';
            print '<td width="50" align="right">' . $langs->trans("PriceUHT") . '</td>';
            print '<td width="30" align="center">' . $langs->trans("Qty") . '</td>';
            print '<td width="50" align="right">' . $langs->trans("ReductionShort") . '</td>';
            print '<td width="30">&nbsp;</td>';
            print "</tr>\n";

            $var = true;

            $objp = $db->fetch_object($result);

            $var = !$var;

            if ($action != 'editline' || GETPOST('rowid') != $objp->rowid) {
                print '<tr ' . $bc[$var] . ' valign="top">';
                // Libelle
                if ($objp->fk_product > 0) {
                    print '<td>';
                    $productstatic->id = $objp->fk_product;
                    $productstatic->type = $objp->ptype;
                    $productstatic->ref = $objp->pref;
                    print $productstatic->getNomUrl(1, '', 20);
                    print $objp->label ? ' - ' . dol_trunc($objp->label, 56) : '';
                    if ($objp->description)
                        print '<br>' . dol_nl2br($objp->description);
                    print '</td>';
                }
                else {
                    print "<td>" . nl2br($objp->description) . "</td>\n";
                }
                // TVA
                print '<td align="center">' . vatrate($objp->tva_tx, '%', $objp->info_bits) . '</td>';
                // Prix
                print '<td align="right">' . price($objp->subprice) . "</td>\n";
                // Quantite
                print '<td align="center">' . $objp->qty . '</td>';
                // Remise
                if ($objp->remise_percent > 0) {
                    print '<td align="right">' . $objp->remise_percent . "%</td>\n";
                } else {
                    print '<td>&nbsp;</td>';
                }
                // Icon move, update et delete (statut contrat 0=brouillon,1=valide,2=ferme)
                print '<td align="right" nowrap="nowrap">';
                if ($user->rights->contrat->creer && count($arrayothercontracts) && ($object->statut >= 0)) {
                    print '<a href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&amp;action=move&amp;rowid=' . $objp->rowid . '">';
                    print img_picto($langs->trans("MoveToAnotherContract"), 'uparrow');
                    print '</a>';
                } else {
                    print '&nbsp;';
                }
                if ($user->rights->contrat->creer && ($object->statut >= 0)) {
                    print '<a href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&amp;action=editline&amp;rowid=' . $objp->rowid . '">';
                    print img_edit();
                    print '</a>';
                } else {
                    print '&nbsp;';
                }
                if ($user->rights->contrat->creer && ($object->statut >= 0)) {
                    print '&nbsp;';
                    print '<a href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&amp;action=deleteline&amp;rowid=' . $objp->rowid . '">';
                    print img_delete();
                    print '</a>';
                }
                print '</td>';

                print "</tr>\n";

                // Dates de en service prevues et effectives
                if ($objp->subprice >= 0) {
                    print '<tr ' . $bc[$var] . '>';
                    print '<td colspan="6">';

                    // Date planned
                    print $langs->trans("DateStartPlanned") . ': ';
                    if ($objp->date_debut) {
                        print dol_print_date($db->jdate($objp->date_debut));
                        // Warning si date prevu passee et pas en service
                        if ($objp->statut == 0 && $db->jdate($objp->date_debut) < ($now - $conf->contrat->services->inactifs->warning_delay)) {
                            print " " . img_warning($langs->trans("Late"));
                        }
                    }
                    else
                        print $langs->trans("Unknown");
                    print ' &nbsp;-&nbsp; ';
                    print $langs->trans("DateEndPlanned") . ': ';
                    if ($objp->date_fin) {
                        print dol_print_date($db->jdate($objp->date_fin));
                        if ($objp->statut == 4 && $db->jdate($objp->date_fin) < ($now - $conf->contrat->services->expires->warning_delay)) {
                            print " " . img_warning($langs->trans("Late"));
                        }
                    }
                    else
                        print $langs->trans("Unknown");

                    print '</td>';
                    print '</tr>';
                }
            }
            // Ligne en mode update
            else {
                print '<form name="update" action="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '" method="post">';
                print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
                print '<input type="hidden" name="action" value="updateligne">';
                print '<input type="hidden" name="elrowid" value="' . GETPOST('rowid') . '">';
                // Ligne carac
                print "<tr $bc[$var]>";
                print '<td>';
                if ($objp->fk_product) {
                    $productstatic->id = $objp->fk_product;
                    $productstatic->type = $objp->ptype;
                    $productstatic->ref = $objp->pref;
                    print $productstatic->getNomUrl(1, '', 20);
                    print $objp->label ? ' - ' . dol_trunc($objp->label, 56) : '';
                    print '<br>';
                } else {
                    print $objp->label ? $objp->label . '<br>' : '';
                }
                print '<textarea name="eldesc" cols="70" rows="1">' . $objp->description . '</textarea></td>';
                print '<td align="right">';
                print $form->load_tva("eltva_tx", $objp->tva_tx, $mysoc, $object->thirdparty);
                print '</td>';
                print '<td align="right"><input size="5" type="text" name="elprice" value="' . price($objp->subprice) . '"></td>';
                print '<td align="center"><input size="2" type="text" name="elqty" value="' . $objp->qty . '"></td>';
                print '<td align="right" nowrap="nowrap"><input size="1" type="text" name="elremise_percent" value="' . $objp->remise_percent . '">%</td>';
                print '<td align="center" rowspan="2" valign="middle"><input type="submit" class="button" name="save" value="' . $langs->trans("Modify") . '">';
                print '<br><input type="submit" class="button" name="cancel" value="' . $langs->trans("Cancel") . '">';
                print '</td>';
                // Ligne dates prevues
                print "<tr $bc[$var]>";
                print '<td colspan="5">';
                print $langs->trans("DateStartPlanned") . ' ';
                $form->select_date($db->jdate($objp->date_debut), "date_start_update", $usehm, $usehm, ($db->jdate($objp->date_debut) > 0 ? 0 : 1), "update");
                print '<br>' . $langs->trans("DateEndPlanned") . ' ';
                $form->select_date($db->jdate($objp->date_fin), "date_end_update", $usehm, $usehm, ($db->jdate($objp->date_fin) > 0 ? 0 : 1), "update");
                print '</td>';
                print '</tr>';

                print "</form>\n";
            }

            $db->free($result);
        } else {
            dol_print_error($db);
        }

        if ($object->statut > 0) {
            print '<tr ' . $bc[false] . '>';
            print '<td colspan="6"><hr></td>';
            print "</tr>\n";
        }

        print "</table>";


        /*
         * Confirmation to delete service line of contract
         */
        if ($action == 'deleteline' && !$_REQUEST["cancel"] && $user->rights->contrat->creer && $idLigne == GETPOST('rowid')) {
            $ret = $form->form_confirm($_SERVER["PHP_SELF"] . "?id=" . $object->id . "&lineid=" . GETPOST('rowid'), $langs->trans("DeleteContractLine"), $langs->trans("ConfirmDeleteContractLine"), "confirm_deleteline", '', 0, 1);
            if ($ret == 'html')
                print '<table class="notopnoleftnoright" width="100%"><tr ' . $bc[false] . ' height="6"><td></td></tr></table>';
        }

        /*
         * Confirmation to move service toward another contract
         */
        if ($action == 'move' && !$_REQUEST["cancel"] && $user->rights->contrat->creer && $idLigne == GETPOST('rowid')) {
            $arraycontractid = array();
            foreach ($arrayothercontracts as $contractcursor) {
                $arraycontractid[$contractcursor->id] = $contractcursor->ref;
            }
            //var_dump($arraycontractid);
            // Cree un tableau formulaire
            $formquestion = array(
                'text' => $langs->trans("ConfirmMoveToAnotherContractQuestion"),
                array('type' => 'select', 'name' => 'newcid', 'values' => $arraycontractid));

            $form->form_confirm($_SERVER["PHP_SELF"] . "?id=" . $object->id . "&lineid=" . GETPOST('rowid'), $langs->trans("MoveToAnotherContract"), $langs->trans("ConfirmMoveToAnotherContract"), "confirm_move", $formquestion);
            print '<table class="notopnoleftnoright" width="100%"><tr ' . $bc[false] . ' height="6"><td></td></tr></table>';
        }

        /*
         * Confirmation de la validation activation
         */
        if ($action == 'active' && !$_REQUEST["cancel"] && $user->rights->contrat->activer && $idLigne == GETPOST('ligne')) {
            $dateactstart = dol_mktime(12, 0, 0, GETPOST('remonth'), GETPOST('reday'), GETPOST('reyear'));
            $dateactend = dol_mktime(12, 0, 0, GETPOST('endmonth'), GETPOST('endday'), GETPOST('endyear'));
            $comment = GETPOST('comment');
            $form->form_confirm($_SERVER["PHP_SELF"] . "?id=" . $object->id . "&ligne=" . GETPOST('ligne') . "&date=" . $dateactstart . "&dateend=" . $dateactend . "&comment=" . urlencode($comment), $langs->trans("ActivateService"), $langs->trans("ConfirmActivateService", dol_print_date($dateactstart, "%A %d %B %Y")), "confirm_active", '', 0, 1);
            print '<table class="notopnoleftnoright" width="100%"><tr ' . $bc[false] . ' height="6"><td></td></tr></table>';
        }

        /*
         * Confirmation de la validation fermeture
         */
        if ($action == 'closeline' && !$_REQUEST["cancel"] && $user->rights->contrat->activer && $idLigne == GETPOST('ligne')) {
            $dateactstart = dol_mktime(12, 0, 0, GETPOST('remonth'), GETPOST('reday'), GETPOST('reyear'));
            $dateactend = dol_mktime(12, 0, 0, GETPOST('endmonth'), GETPOST('endday'), GETPOST('endyear'));
            $comment = GETPOST('comment');
            $form->form_confirm($_SERVER["PHP_SELF"] . "?id=" . $object->id . "&ligne=" . GETPOST('ligne') . "&date=" . $dateactstart . "&dateend=" . $dateactend . "&comment=" . urlencode($comment), $langs->trans("CloseService"), $langs->trans("ConfirmCloseService", dol_print_date($dateactend, "%A %d %B %Y")), "confirm_closeline", '', 0, 1);
            print '<table class="notopnoleftnoright" width="100%"><tr ' . $bc[false] . ' height="6"><td></td></tr></table>';
        }


        // Area with status and activation info of line
        if ($object->statut > 0) {
            print '<table class="notopnoleft" width="100%">';

            print '<tr ' . $bc[false] . '>';
            print '<td>' . $langs->trans("ServiceStatus") . ': ' . $objL->getLibStatut(4) . '</td>';
            print '<td width="30" align="right">';
            if ($user->societe_id == 0) {
                if ($object->statut > 0 && $action != 'activateline' && $action != 'unactivateline') {
                    $tmpaction = 'activateline';
                    if ($objp->statut == 4)
                        $tmpaction = 'unactivateline';
                    print '<a href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&amp;ligne=' . $idLigne . '&amp;action=' . $tmpaction . '">';
                    print img_edit();
                    print '</a>';
                }
            }
            print '</td>';
            print "</tr>\n";

            print '<tr ' . $bc[false] . '>';

            print '<td>';
            // Si pas encore active
            if (!$objp->date_debut_reelle) {
                print $langs->trans("DateStartReal") . ': ';
                if ($objp->date_debut_reelle)
                    print dol_print_date($objp->date_debut_reelle);
                else
                    print $langs->trans("ContractStatusNotRunning");
            }
            // Si active et en cours
            if ($objp->date_debut_reelle && !$objp->date_fin_reelle) {
                print $langs->trans("DateStartReal") . ': ';
                print dol_print_date($objp->date_debut_reelle);
            }
            // Si desactive
            if ($objp->date_debut_reelle && $objp->date_fin_reelle) {
                print $langs->trans("DateStartReal") . ': ';
                print dol_print_date($objp->date_debut_reelle);
                print ' &nbsp;-&nbsp; ';
                print $langs->trans("DateEndReal") . ': ';
                print dol_print_date($objp->date_fin_reelle);
            }
            if (!empty($objp->comment))
                print "<br>" . $objp->comment;
            print '</td>';

            print '<td align="center">&nbsp;</td>';

            print '</tr>';
            print '</table>';
        }

        if ($user->rights->contrat->activer && $action == 'activateline' && $idLigne == GETPOST('ligne')) {
            /**
             * Activer la ligne de contrat
             */
            print '<form name="active" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&amp;ligne=' . GETPOST('ligne') . '&amp;action=active" method="post">';
            print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';

            print '<table class="noborder" width="100%">';
            //print '<tr class="liste_titre"><td colspan="5">'.$langs->trans("Status").'</td></tr>';
            // Definie date debut et fin par defaut
            $dateactstart = $objp->date_debut;
            if (GETPOST('remonth'))
                $dateactstart = dol_mktime(12, 0, 0, GETPOST('remonth'), GETPOST('reday'), GETPOST('reyear'));
            elseif (!$dateactstart)
                $dateactstart = time();

            $dateactend = $objp->date_fin;
            if (GETPOST('endmonth'))
                $dateactend = dol_mktime(12, 0, 0, GETPOST('endmonth'), GETPOST('endday'), GETPOST('endyear'));
            elseif (!$dateactend) {
                if ($objp->fk_product > 0) {
                    $product = new Product($db);
                    $product->fetch($objp->fk_product);
                    $dateactend = dol_time_plus_duree(time(), $product->duration_value, $product->duration_unit);
                }
            }

            print '<tr ' . $bc[$var] . '><td>' . $langs->trans("DateServiceActivate") . '</td><td>';
            print $form->select_date($dateactstart, '', $usehm, $usehm, '', "active");
            print '</td>';

            print '<td>' . $langs->trans("DateEndPlanned") . '</td><td>';
            print $form->select_date($dateactend, "end", $usehm, $usehm, '', "active");
            print '</td>';

            print '<td align="center" rowspan="2" valign="middle">';
            print '<input type="submit" class="button" name="activate" value="' . $langs->trans("Activate") . '"><br>';
            print '<input type="submit" class="button" name="cancel" value="' . $langs->trans("Cancel") . '">';
            print '</td>';

            print '</tr>';

            print '<tr ' . $bc[$var] . '><td>' . $langs->trans("Comment") . '</td><td colspan="3"><input size="80" type="text" name="comment" value="' . GETPOST('comment') . '"></td></tr>';

            print '</table>';

            print '</form>';
        }

        if ($user->rights->contrat->activer && $action == 'unactivateline' && $idLigne == GETPOST('ligne')) {
            /**
             * Desactiver la ligne de contrat
             */
            print '<form name="closeline" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&amp;ligne=' . $idLigne . '&amp;action=closeline" method="post">';
            print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';

            print '<table class="noborder" width="100%">';

            // Definie date debut et fin par defaut
            $dateactstart = $objp->date_debut_reelle;
            if (GETPOST('remonth'))
                $dateactstart = dol_mktime(12, 0, 0, GETPOST('remonth'), GETPOST('reday'), GETPOST('reyear'));
            elseif (!$dateactstart)
                $dateactstart = time();

            $dateactend = $objp->date_fin_reelle;
            if (GETPOST('endmonth'))
                $dateactend = dol_mktime(12, 0, 0, GETPOST('endmonth'), GETPOST('endday'), GETPOST('endyear'));
            elseif (!$dateactend) {
                if ($objp->fk_product > 0) {
                    $product = new Product($db);
                    $product->fetch($objp->fk_product);
                    $dateactend = dol_time_plus_duree(time(), $product->duration_value, $product->duration_unit);
                }
            }
            $now = dol_now();
            if ($dateactend > $now)
                $dateactend = $now;

            print '<tr ' . $bc[$var] . '><td colspan="2">';
            if ($objp->statut >= 4) {
                if ($objp->statut == 4) {
                    print $langs->trans("DateEndReal") . ' ';
                    $form->select_date($dateactend, "end", $usehm, $usehm, ($objp->date_fin_reelle > 0 ? 0 : 1), "closeline");
                }
            }
            print '</td>';

            print '<td align="right" rowspan="2"><input type="submit" class="button" name="close" value="' . $langs->trans("Close") . '"><br>';
            print '<input type="submit" class="button" name="cancel" value="' . $langs->trans("Cancel") . '">';
            print '</td></tr>';

            print '<tr ' . $bc[$var] . '><td>' . $langs->trans("Comment") . '</td><td><input size="70" type="text" class="flat" name="comment" value="' . GETPOST('comment') . '"></td></tr>';
            print '</table>';

            print '</form>';
        }

        print '</td>'; // End td if line is 1

        print '</tr>';
        print '<tr><td style="border-right: 1px solid #' . $colorb . '">&nbsp;</td></tr>';
    }

}

?>
