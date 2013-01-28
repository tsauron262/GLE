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
        $langs->load("bills");
        $idLigne = $objL->id;
        $db = $this->db;
        $productstatic = new Product($db);
        $return = '';
//        $return .= '<tr height="16" ' . $bc[false] . '>';
//        $return .= '<td class="liste_titre" width="90" style="border-left: 1px solid #' . $colorb . '; border-top: 1px solid #' . $colorb . '; border-bottom: 1px solid #' . $colorb . ';">';
//            $return .= $langs->trans("ServiceNb",$cursorline).'</td>';

//        $return .= '<td class="tab" style="border-right: 1px solid #' . $colorb . '; border-top: 1px solid #' . $colorb . '; border-bottom: 1px solid #' . $colorb . ';" rowspan="2">';

        // Area with common detail of line
        $return .= '<table class="notopnoleft" width="100%">';

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

            $return .= '<tr class="liste_titre">';
            $return .= '<td>' . $langs->trans("Service") . '</td>';
            $return .= '<td width="50" align="center">' . $langs->trans("VAT") . '</td>';
            $return .= '<td width="50" align="right">' . $langs->trans("PriceUHT") . '</td>';
            $return .= '<td width="30" align="center">' . $langs->trans("Qty") . '</td>';
            $return .= '<td width="50" align="right">' . $langs->trans("ReductionShort") . '</td>';
            $return .= '<td width="30">&nbsp;</td>';
            $return .= "</tr>\n";

            $var = true;

            $objp = $db->fetch_object($result);

            $var = !$var;

            if ($action != 'editline' || GETPOST('rowid') != $objp->rowid) {
                $return .= '<tr ' . $bc[$var] . ' valign="top">';
                // Libelle
                if ($objp->fk_product > 0) {
                    $return .= '<td>';
                    $productstatic->id = $objp->fk_product;
                    $productstatic->type = $objp->ptype;
                    $productstatic->ref = $objp->pref;
                    $return .= $productstatic->getNomUrl(1, '', 20);
                    $return .= $objp->label ? ' - ' . dol_trunc($objp->label, 56) : '';
                    if ($objp->description)
                        $return .= '<br>' . dol_nl2br($objp->description);
                    $return .= '</td>';
                }
                else {
                    $return .= "<td>" . nl2br($objp->description) . "</td>\n";
                }
                // TVA
                $return .= '<td align="center">' . vatrate($objp->tva_tx, '%', $objp->info_bits) . '</td>';
                // Prix
                $return .= '<td align="right">' . price($objp->subprice) . "</td>\n";
                // Quantite
                $return .= '<td align="center">' . $objp->qty . '</td>';
                // Remise
                if ($objp->remise_percent > 0) {
                    $return .= '<td align="right">' . $objp->remise_percent . "%</td>\n";
                } else {
                    $return .= '<td>&nbsp;</td>';
                }
                // Icon move, update et delete (statut contrat 0=brouillon,1=valide,2=ferme)
                $return .= '<td align="right" nowrap="nowrap">';
                if ($user->rights->contrat->creer && count($arrayothercontracts) && ($object->statut >= 0)) {
                    $return .= '<a href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&amp;action=move&amp;rowid=' . $objp->rowid . '">';
                    $return .= img_picto($langs->trans("MoveToAnotherContract"), 'uparrow');
                    $return .= '</a>';
                } else {
                    $return .= '&nbsp;';
                }
                if ($user->rights->contrat->creer && ($object->statut >= 0)) {
                    $return .= '<a href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&amp;action=editline&amp;rowid=' . $objp->rowid . '">';
                    $return .= img_edit();
                    $return .= '</a>';
                } else {
                    $return .= '&nbsp;';
                }
                if ($user->rights->contrat->creer && ($object->statut >= 0)) {
                    $return .= '&nbsp;';
                    $return .= '<a href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&amp;action=deleteline&amp;rowid=' . $objp->rowid . '">';
                    $return .= img_delete();
                    $return .= '</a>';
                }
                $return .= '</td>';

                $return .= "</tr>\n";

                // Dates de en service prevues et effectives
                if ($objp->subprice >= 0) {
                    $return .= '<tr ' . $bc[$var] . '>';
                    $return .= '<td colspan="6">';

                    // Date planned
                    $return .= $langs->trans("DateStartPlanned") . ': ';
                    if ($objp->date_debut) {
                        $return .= dol_print_date($db->jdate($objp->date_debut));
                        // Warning si date prevu passee et pas en service
                        if ($objp->statut == 0 && $db->jdate($objp->date_debut) < ($now - $conf->contrat->services->inactifs->warning_delay)) {
                            $return .= " " . img_warning($langs->trans("Late"));
                        }
                    }
                    else
                        $return .= $langs->trans("Unknown");
                    $return .= ' &nbsp;-&nbsp; ';
                    $return .= $langs->trans("DateEndPlanned") . ': ';
                    if ($objp->date_fin) {
                        $return .= dol_print_date($db->jdate($objp->date_fin));
                        if ($objp->statut == 4 && $db->jdate($objp->date_fin) < ($now - $conf->contrat->services->expires->warning_delay)) {
                            $return .= " " . img_warning($langs->trans("Late"));
                        }
                    }
                    else
                        $return .= $langs->trans("Unknown");

                    $return .= '</td>';
                    $return .= '</tr>';
                }
            }
            // Ligne en mode update
            else {
                $return .= '<form name="update" action="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '" method="post">';
                $return .= '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
                $return .= '<input type="hidden" name="action" value="updateligne">';
                $return .= '<input type="hidden" name="elrowid" value="' . GETPOST('rowid') . '">';
                // Ligne carac
                $return .= "<tr $bc[$var]>";
                $return .= '<td>';
                if ($objp->fk_product) {
                    $productstatic->id = $objp->fk_product;
                    $productstatic->type = $objp->ptype;
                    $productstatic->ref = $objp->pref;
                    $return .= $productstatic->getNomUrl(1, '', 20);
                    $return .= $objp->label ? ' - ' . dol_trunc($objp->label, 56) : '';
                    $return .= '<br>';
                } else {
                    $return .= $objp->label ? $objp->label . '<br>' : '';
                }
                $return .= '<textarea name="eldesc" cols="70" rows="1">' . $objp->description . '</textarea></td>';
                $return .= '<td align="right">';
                $return .= $form->load_tva("eltva_tx", $objp->tva_tx, $mysoc, $object->thirdparty);
                $return .= '</td>';
                $return .= '<td align="right"><input size="5" type="text" name="elprice" value="' . price($objp->subprice) . '"></td>';
                $return .= '<td align="center"><input size="2" type="text" name="elqty" value="' . $objp->qty . '"></td>';
                $return .= '<td align="right" nowrap="nowrap"><input size="1" type="text" name="elremise_percent" value="' . $objp->remise_percent . '">%</td>';
                $return .= '<td align="center" rowspan="2" valign="middle"><input type="submit" class="button" name="save" value="' . $langs->trans("Modify") . '">';
                $return .= '<br><input type="submit" class="button" name="cancel" value="' . $langs->trans("Cancel") . '">';
                $return .= '</td>';
                // Ligne dates prevues
                $return .= "<tr $bc[$var]>";
                $return .= '<td colspan="5">';
                $return .= $langs->trans("DateStartPlanned") . ' ';
                $form->select_date($db->jdate($objp->date_debut), "date_start_update", $usehm, $usehm, ($db->jdate($objp->date_debut) > 0 ? 0 : 1), "update");
                $return .= '<br>' . $langs->trans("DateEndPlanned") . ' ';
                $form->select_date($db->jdate($objp->date_fin), "date_end_update", $usehm, $usehm, ($db->jdate($objp->date_fin) > 0 ? 0 : 1), "update");
                $return .= '</td>';
                $return .= '</tr>';

                $return .= "</form>\n";
            }

            $db->free($result);
        } else {
            dol_print_error($db);
        }

        if ($object->statut > 0) {
            $return .= '<tr ' . $bc[false] . '>';
            $return .= '<td colspan="6"><hr></td>';
            $return .= "</tr>\n";
        }

        $return .= "</table>";


        /*
         * Confirmation to delete service line of contract
         */
        if ($action == 'deleteline' && !$_REQUEST["cancel"] && $user->rights->contrat->creer && $idLigne == GETPOST('rowid')) {
            $ret = $form->form_confirm($_SERVER["PHP_SELF"] . "?id=" . $object->id . "&lineid=" . GETPOST('rowid'), $langs->trans("DeleteContractLine"), $langs->trans("ConfirmDeleteContractLine"), "confirm_deleteline", '', 0, 1);
            if ($ret == 'html')
                $return .= '<table class="notopnoleftnoright" width="100%"><tr ' . $bc[false] . ' height="6"><td></td></tr></table>';
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
            $return .= '<table class="notopnoleftnoright" width="100%"><tr ' . $bc[false] . ' height="6"><td></td></tr></table>';
        }

        /*
         * Confirmation de la validation activation
         */
        if ($action == 'active' && !$_REQUEST["cancel"] && $user->rights->contrat->activer && $idLigne == GETPOST('ligne')) {
            $dateactstart = dol_mktime(12, 0, 0, GETPOST('remonth'), GETPOST('reday'), GETPOST('reyear'));
            $dateactend = dol_mktime(12, 0, 0, GETPOST('endmonth'), GETPOST('endday'), GETPOST('endyear'));
            $comment = GETPOST('comment');
            $form->form_confirm($_SERVER["PHP_SELF"] . "?id=" . $object->id . "&ligne=" . GETPOST('ligne') . "&date=" . $dateactstart . "&dateend=" . $dateactend . "&comment=" . urlencode($comment), $langs->trans("ActivateService"), $langs->trans("ConfirmActivateService", dol_print_date($dateactstart, "%A %d %B %Y")), "confirm_active", '', 0, 1);
            $return .= '<table class="notopnoleftnoright" width="100%"><tr ' . $bc[false] . ' height="6"><td></td></tr></table>';
        }

        /*
         * Confirmation de la validation fermeture
         */
        if ($action == 'closeline' && !$_REQUEST["cancel"] && $user->rights->contrat->activer && $idLigne == GETPOST('ligne')) {
            $dateactstart = dol_mktime(12, 0, 0, GETPOST('remonth'), GETPOST('reday'), GETPOST('reyear'));
            $dateactend = dol_mktime(12, 0, 0, GETPOST('endmonth'), GETPOST('endday'), GETPOST('endyear'));
            $comment = GETPOST('comment');
            $form->form_confirm($_SERVER["PHP_SELF"] . "?id=" . $object->id . "&ligne=" . GETPOST('ligne') . "&date=" . $dateactstart . "&dateend=" . $dateactend . "&comment=" . urlencode($comment), $langs->trans("CloseService"), $langs->trans("ConfirmCloseService", dol_print_date($dateactend, "%A %d %B %Y")), "confirm_closeline", '', 0, 1);
            $return .= '<table class="notopnoleftnoright" width="100%"><tr ' . $bc[false] . ' height="6"><td></td></tr></table>';
        }


        // Area with status and activation info of line
        if ($object->statut > 0) {
            $return .= '<table class="notopnoleft" width="100%">';

            $return .= '<tr ' . $bc[false] . '>';
            $return .= '<td>' . $langs->trans("ServiceStatus") . ': ' . $objL->getLibStatut(4) . '</td>';
            $return .= '<td width="30" align="right">';
            if ($user->societe_id == 0) {
                if ($object->statut > 0 && $action != 'activateline' && $action != 'unactivateline') {
                    $tmpaction = 'activateline';
                    if ($objp->statut == 4)
                        $tmpaction = 'unactivateline';
                    $return .= '<a href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&amp;ligne=' . $idLigne . '&amp;action=' . $tmpaction . '">';
                    $return .= img_edit();
                    $return .= '</a>';
                }
            }
            $return .= '</td>';
            $return .= "</tr>\n";

            $return .= '<tr ' . $bc[false] . '>';

            $return .= '<td>';
            // Si pas encore active
            if (!$objp->date_debut_reelle) {
                $return .= $langs->trans("DateStartReal") . ': ';
                if ($objp->date_debut_reelle)
                    $return .= dol_print_date($objp->date_debut_reelle);
                else
                    $return .= $langs->trans("ContractStatusNotRunning");
            }
            // Si active et en cours
            if ($objp->date_debut_reelle && !$objp->date_fin_reelle) {
                $return .= $langs->trans("DateStartReal") . ': ';
                $return .= dol_print_date($objp->date_debut_reelle);
            }
            // Si desactive
            if ($objp->date_debut_reelle && $objp->date_fin_reelle) {
                $return .= $langs->trans("DateStartReal") . ': ';
                $return .= dol_print_date($objp->date_debut_reelle);
                $return .= ' &nbsp;-&nbsp; ';
                $return .= $langs->trans("DateEndReal") . ': ';
                $return .= dol_print_date($objp->date_fin_reelle);
            }
            if (!empty($objp->comment))
                $return .= "<br>" . $objp->comment;
            $return .= '</td>';

            $return .= '<td align="center">&nbsp;</td>';

            $return .= '</tr>';
            $return .= '</table>';
        }

        if ($user->rights->contrat->activer && $action == 'activateline' && $idLigne == GETPOST('ligne')) {
            /**
             * Activer la ligne de contrat
             */
            $return .= '<form name="active" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&amp;ligne=' . GETPOST('ligne') . '&amp;action=active" method="post">';
            $return .= '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';

            $return .= '<table class="noborder" width="100%">';
            //$return .= '<tr class="liste_titre"><td colspan="5">'.$langs->trans("Status").'</td></tr>';
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

            $return .= '<tr ' . $bc[$var] . '><td>' . $langs->trans("DateServiceActivate") . '</td><td>';
            $return .= $form->select_date($dateactstart, '', $usehm, $usehm, '', "active");
            $return .= '</td>';

            $return .= '<td>' . $langs->trans("DateEndPlanned") . '</td><td>';
            $return .= $form->select_date($dateactend, "end", $usehm, $usehm, '', "active");
            $return .= '</td>';

            $return .= '<td align="center" rowspan="2" valign="middle">';
            $return .= '<input type="submit" class="button" name="activate" value="' . $langs->trans("Activate") . '"><br>';
            $return .= '<input type="submit" class="button" name="cancel" value="' . $langs->trans("Cancel") . '">';
            $return .= '</td>';

            $return .= '</tr>';

            $return .= '<tr ' . $bc[$var] . '><td>' . $langs->trans("Comment") . '</td><td colspan="3"><input size="80" type="text" name="comment" value="' . GETPOST('comment') . '"></td></tr>';

            $return .= '</table>';

            $return .= '</form>';
        }

        if ($user->rights->contrat->activer && $action == 'unactivateline' && $idLigne == GETPOST('ligne')) {
            /**
             * Desactiver la ligne de contrat
             */
            $return .= '<form name="closeline" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&amp;ligne=' . $idLigne . '&amp;action=closeline" method="post">';
            $return .= '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';

            $return .= '<table class="noborder" width="100%">';

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

            $return .= '<tr ' . $bc[$var] . '><td colspan="2">';
            if ($objp->statut >= 4) {
                if ($objp->statut == 4) {
                    $return .= $langs->trans("DateEndReal") . ' ';
                    $form->select_date($dateactend, "end", $usehm, $usehm, ($objp->date_fin_reelle > 0 ? 0 : 1), "closeline");
                }
            }
            $return .= '</td>';

            $return .= '<td align="right" rowspan="2"><input type="submit" class="button" name="close" value="' . $langs->trans("Close") . '"><br>';
            $return .= '<input type="submit" class="button" name="cancel" value="' . $langs->trans("Cancel") . '">';
            $return .= '</td></tr>';

            $return .= '<tr ' . $bc[$var] . '><td>' . $langs->trans("Comment") . '</td><td><input size="70" type="text" class="flat" name="comment" value="' . GETPOST('comment') . '"></td></tr>';
            $return .= '</table>';

            $return .= '</form>';
        }

//        $return .= '</td>'; // End td if line is 1

//        $return .= '</tr>';
//        $return .= '<tr><td style="border-right: 1px solid #' . $colorb . '">&nbsp;';
        return $return;
    }

}

?>
