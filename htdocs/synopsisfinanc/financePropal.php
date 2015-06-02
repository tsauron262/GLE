<?php

/*
 * * GLE by Synopsis et DRSI
 *
 * Author: Tommy SAURON <tommy@drsi.fr>
 * Licence : Artistic Licence v2.0
 *
 * Version 1.2
 * Created on : 19 oct. 2010
 *
 * Infos on http://www.finapro.fr
 *
 */
/**
 *
 * Name : intervByContrat.php
 * GLE-1.2
 */
require("../main.inc.php");
require_once (DOL_DOCUMENT_ROOT . "/synopsisfinanc/class/synopsisfinancement.class.php");
require_once(DOL_DOCUMENT_ROOT . '/core/lib/contract.lib.php');
$id_contact_rapport = 780;
$langs->load("propal");

$id = $_REQUEST['id'];
restrictedArea($user, 'propal', $id, '');
require_once(DOL_DOCUMENT_ROOT . "/core/lib/propal.lib.php");
require_once(DOL_DOCUMENT_ROOT . "/comm/propal/class/propal.class.php");
$object = new Propal($db);
$object->fetch($id);

$js = '<link rel="stylesheet" href="css/stylefinance.css">'
        . '<script>$(document).ready(function(){'
        . 'if($("#check")[0].checked==false){'
        . '$("#po_degr").val(0);'
        . '$(".degr").hide();'
        . '$("#dure_degr").val(0);'
        . '}else{'
        . '$(".degr").show();'
        . '}'
        . '$("#socid").change(function(e){'//fonction de changement de rapporteur
        . 'var send=$("#socid").val();'
        . 'if(send>0){'
        . '$.ajax({'
        . 'url:"' . DOL_URL_ROOT . '/synopsischrono/ajax/contactSoc-xml_response.php",'
        . 'method: "POST",'
        . 'data: {"socid":send},'
        . 'dataType: "HTML",'
        . 'success: function(data){'
        . '$("#contactid").html(data);'
        . '},'
        . 'error: function(){'
        . 'alert("Erreur: connexion impossible.");'
        . '}'
        . '});'
        . '}else{'
        . '$("#contactid").html("");'
        . '}'
        . '});'
        . '$("#banque").change(function(e){'//fonction mise à jour donnée en fonction fric dispo du client
        . 'if($("#banque").val()!=""){'
        . '$("#taux").val($("#banque").val());'
        . '}'
        . '$("#Bcache").val($("#banque option:selected").html());'
        . '});'
        . '$("#bouton").click(function(e){'
        . 'e.preventDefault();'
        . 'calc();'
        . '});'
        . '$("#check").change(function(e){'
        . 'if($("#check")[0].checked==false){'
        . '$(".degr input, .degr select").val(0);'
        . '}'
        . '$(".degr").toggle(400);'
        . '});'
        . '$("#pretAP").change(function(e){'
        . 'calc();'
        . '});'
        . '$(".rad").change(function(e){'
        . 'init_location(true);'
        . '});'
        . 'init_location(false);'
        . '});'
        . 'function init_location(valdef){'
        . 'var radio=$(".rad:checked");'
        . 'if($(radio).val()=="financier"){'
        . '$(".pr").fadeOut();'
        . '$("#preter").val(0);'
        . '$(".vr").fadeOut();'
        . '$("#VR").val(0);'
        . 'if(valdef)'
        . '$("#montant").val((parseFloat($("#tot").html().replace(" ","").replace(",","."))));'
        . '}'
        . 'if($(radio).val()=="operationnel"){'
        . '$(".pr").fadeOut();'
        . '$("#preter").val(0);'
        . '$(".vr").fadeIn();'
        . 'if(valdef)'
        . '$("#VR").val(parseFloat($("#matos").html().replace(" ", "").replace(",","."))*0.15);'//calc vr matos
        . 'if(valdef)'
        . '$("#montant").val(parseFloat($("#tot").html().replace(" ","").replace(",","."))-$("#VR").val());'
        . '}'
        . 'if($(radio).val()=="evol+"){'
        . '$(".pr").fadeIn();'
        . '$(".vr").fadeOut();'
        . '$("#VR").val(0);'
        . 'if(valdef){'
        . '$("#preter").val(parseFloat($("#matos").html().replace(" ", "").replace(",",".")));'
        . '$("#montant").val(parseFloat($("#tot").html().replace(" ","").replace(",","."))-$("#preter").val());'
        . '}'
        . '}'
        . '}'
        . 'function calc(){'
        . 'var pret=parseFloat($("#preter").val());'
        . 'var mois = parseFloat($("#mensuel").val());'
        . 'var dure = parseFloat($("#duree").val());'
        . 'var cC=parseFloat($("#commC").val());'
        . 'var cF=parseFloat($("#commF").val());'
        . 'var fric_dispo = parseFloat($("#pretAP").val());'
        . 'var pourc_periode2=parseFloat($("#po_degr").val());'//der
        . 'pourc_periode2=1-(pourc_periode2/100);'//der
        . 'if(pret>0){'
        . 'var loyerpret=pret*pourc_periode2/dure;'
        . '}else{'
        . 'var loyerpret=0;'
        . '}'
        . 'if(fric_dispo>0){'
        . 'var mensualite=fric_dispo/mois-loyerpret;'
        . 'var interet = parseFloat($("#taux").val());'
        . 'interet=interet/100/12;'
        . 'if(interet==0){'
        . 'var emprunt = mensualite * dure'
        . '}else{'
        . 'var emprunt = mensualite / (interet / (1 - Math.pow(1+interet, -dure)));'
        . '}'
        . 'var res=emprunt/((100+cC)/100*(100+cF)/100);'
        . 'res=Math.round(res*100)/100;'
        . 'res=res/pourc_periode2;'//der
        . '$("#montant").val(res);'
        . '}}'
        . '</script>';


$action = GETPOST('action', 'alpha');

// Generation doc (depuis lien ou depuis cartouche doc)
if ($action == 'builddoc' && $user->rights->propal->creer) {
    if (GETPOST('model')) {
        $object->setDocModel($user, GETPOST('model'));
    }
    if (GETPOST('fk_bank')) { // this field may come from an external module
        $object->fk_bank = GETPOST('fk_bank');
    } else {
        $object->fk_bank = $object->fk_account;
    }

    // Define output language
    $outputlangs = $langs;
    if (!empty($conf->global->MAIN_MULTILANGS)) {
        $outputlangs = new Translate("", $conf);
        $newlang = (GETPOST('lang_id') ? GETPOST('lang_id') : $object->thirdparty->default_lang);
        $outputlangs->setDefaultLang($newlang);
    }
    $ret = $object->fetch($id); // Reload to get new records
    $result = $object->generateDocument($object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);

    if ($result <= 0) {
        dol_print_error($db, $result);
        exit();
    } else {
        header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . (empty($conf->global->MAIN_JUMP_TAG) ? '' : '#builddoc'));
        exit();
    }
}

// Remove file in doc form
else if ($action == 'remove_file' && $user->rights->propal->creer) {
    if ($object->id > 0) {
        require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

        $langs->load("other");
        $upload_dir = $conf->propal->dir_output;
        $file = $upload_dir . '/' . GETPOST('file');
        $ret = dol_delete_file($file, 0, 0, 0, $object);
        if ($ret)
            setEventMessage($langs->trans("FileWasRemoved", GETPOST('file')));
        else
            setEventMessage($langs->trans("ErrorFailToDeleteFile", GETPOST('file')), 'errors');
    }
}

llxHeader($js, 'Finanacement');



$head = propal_prepare_head($object);


dol_fiche_head($head, "financ", $langs->trans("Propal"));

if (!$user->rights->synopsisFinanc->read)
    accessforbidden('', false, false);

$totG = $object->total_ht;
//echo '<pre>';
//print_r($object->lines);
$totService = 0;
$totLogiciel = 0;
foreach ($object->lines as $obj) {
    if ($obj->product_type == 1) {
        $totService+=$obj->subprice;
    } elseif ($obj->product_type == 5) {
        $totLogiciel+=$obj->subprice;
    } elseif ($obj->fk_product) {
        $prod = new product($db);
        require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
        $cate = new Categorie($db);
        $ctg = $cate->containing($obj->fk_product, "product");
        $find = false;
        foreach ($ctg as $obj2) {
            if (stripos($obj2->label, "logiciel") !== FALSE) {
                $find = true;
            }
        }
        if ($find == true) {
            $totLogiciel+=$obj->subprice;
        }
//        $prod->fetch($obj->fk_product);
        //print_r($ctg);
    }
}
$totMateriel = $totG - $totService - $totLogiciel;

echo '<table class="noborder monnom" cellspacing=0>'
 . '<tr class="liste_titre">'
 . '<th class="HG">Total propal:</th>'
 . '<th>Total service:</th>'
 . '<th>Total logiciel:</th>'
 . '<th class="HD">Total Materiel:</th>'
 . '</tr>'
 . '<tr>'
 . '<td class="BG" id="tot">' . price($totG) . '</td>'
 . '<td id="serv">' . price($totService) . '</td>'
 . '<td id="log">' . price($totLogiciel) . '</td>'
 . '<td class="BD" id="matos">' . price($totMateriel) . '</td>'
 . '</tr>'
 . '</table>';



$valfinance = new Synopsisfinancement($db);
$valfinance->fetch(null, $object->id);

$montantAF = $totG;
$commC = 2;
$commF = 8;
$duree = 24;
$tauxInteret = 2.4;
$banque = "";
$periode = 1;
$VR = 0;
$pret = 0;
$location = "financier";
$socid = 0;
//$socid = $object->socid;
$idoldcontact = 0;
$idcontact = 0;
$idoldcontact_rowid = 0;
$duree_degr = 0;
$pourcent_degr = 0;


if ($valfinance->id) {
    $montantAF = $valfinance->montantAF;
    $commC = $valfinance->commC;
    $commF = $valfinance->commF;
    $duree = $valfinance->duree;
    $tauxInteret = $valfinance->taux;
    $banque = $valfinance->banque;
    $periode = $valfinance->periode;
    $pret = $valfinance->pret;
    $VR = $valfinance->VR;
    $location = $valfinance->location;
    $duree_degr = $valfinance->duree_degr;
    $pourcent_degr = $valfinance->pourcent_degr;
    
    $valfinance->calcul();
}

$contact = $object->Liste_Contact(-1, "external");
//print_r($contact);
foreach ($contact as $key => $value) {
    if ($value["fk_c_type_contact"] == $id_contact_rapport) {
        $socid = $value["socid"];
        $idcontact = $value["id"];
        $idoldcontact = $value["id"];
        $idoldcontact_rowid = $value["rowid"];
    }
}


if (isset($_POST['form1']) && !$valfinance->contrat_id > 0) {
    $montantAF = $_POST['montantAF'];
    $commC = $_POST['commC'];
    $duree = $_POST['duree'];
    $periode = $_POST['periode'];
    $VR = $_POST["VR"];
    $pret = $_POST["preter"];
    $location = $_POST["rad"];
    $socid = $_POST["socid"];
    $duree_degr = $_POST["duree_degr"];
    $pourcent_degr = $_POST["pour_degr"];

    //droit totale
    if ($user->rights->synopsisFinanc->super_write) {
        $commF = $_POST['commF'];
        $tauxInteret = $_POST['taux'];
        $banque = $_POST['Bcache'];
    }
    $idcontact = $_POST["contactid"];


    $valfinance->taux = $tauxInteret;
    $valfinance->montantAF = $montantAF;
    $valfinance->periode = $periode;
    $valfinance->duree = $duree;
    $valfinance->commC = $commC;
    $valfinance->commF = $commF;
    $valfinance->banque = $banque;
    $valfinance->VR = $VR;
    $valfinance->pret = $pret;
    $valfinance->propal_id = $object->id;
    $valfinance->location = $location;
    $valfinance->duree_degr = $duree_degr;
    $valfinance->pourcent_degr = $pourcent_degr;

    if ($valfinance->id > 0)
        $valfinance->update($user);
    
    else
        $valfinance->insert($user);
    $valfinance->calcul();
    
    if ($idoldcontact != $idcontact) {
        if ($idoldcontact > 0) {
            $object->delete_contact($idoldcontact_rowid);
        }
        if ($idcontact > 0) {
            $object->add_contact($idcontact, $id_contact_rapport);
        }
    }

    require_once DOL_DOCUMENT_ROOT . '/core/modules/propale/modules_propale.php';
    $result = propale_pdf_create($db, $object, (GETPOST('model') ? GETPOST('model') : "azurFinanc"), $outputlangs, $hidedetails, $hidedesc, $hideref);//génération auto
}
//    $valfinance->calcul();

if (isset($_POST["form2"])) {

    $contrat_facture_exist = false;
    if ($valfinance->contrat_id > 0) {
        require_once DOL_DOCUMENT_ROOT . '/contrat/class/contrat.class.php';
        $ctr = new Contrat($db);
        $ctr->fetch($valfinance->contrat_id);
        if ($ctr->id)
            $contrat_facture_exist = true;
    }
    if (!$contrat_facture_exist) {
        require_once DOL_DOCUMENT_ROOT . '/contrat/class/contrat.class.php';
        $contract = new Contrat($db);
        //print_r (convertirDate($_POST["datesign"],false));
        $contract->date_contrat = convertirDate($_POST["datesign"], false);
        $contract->socid = $object->socid;
        $contract->commercial_suivi_id = $user->id;
        $contract->commercial_signature_id = $user->id;
        $contract->create($user);

        $valfinance->contrat_id = $contract->id;

        $date_fin = new DateTime(convertirDate($_POST["datesign"], false));
        $date_fin->add(new DateInterval('P' . $valfinance->duree . 'M'));
        $date_fin->sub(new DateInterval('P1D'));
        $date_fin = $date_fin->format('Y-m-d');

        $contract->addline("Financement Propal " . (($valfinance->duree_degr > 0 && $valfinance->pourcent_degr > 0) ? "(1ère période) " : "") . $object->ref, $valfinance->loyer1, $valfinance->nb_periode, 20, null, null, NULL, NULL, convertirDate($_POST["datesign"], false), $date_fin, "HT", null, NULL, null, $valfinance->calc_no_commF());

        if ($valfinance->duree_degr > 0 && $valfinance->pourcent_degr > 0) {
            $date_debut2 = new DateTime($date_fin);
            $date_debut2->add(new DateInterval('P1D'));
            $date_debut2 = $date_debut2->format('Y-m-d');

            $date_fin2 = new DateTime($date_debut2);
            $date_fin2->add(new DateInterval('P' . $valfinance->duree_degr . 'M'));
            $date_fin2->sub(new DateInterval('P1D'));
            $date_fin2 = $date_fin2->format('Y-m-d');

            $contract->addline("Financement Propal (2nd période)" . $object->ref, $valfinance->loyer2, $valfinance->nb_periode2, 20, null, null, NULL, NULL, $date_debut2, $date_fin2, "HT", null, NULL, null, $valfinance->calc_no_commF());
        }

        addElementElement("propal", "contrat", $object->id, $contract->id);

        $contract->validate($user);

        include_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
        $facture = new Facture($db);

        $facture->date = $db->idate(convertirDate($_POST["datesign"], false));
        $facture->cond_reglement_id=1;

        if ($valfinance->banque != "") {
            $testBanque = $db->query('SELECT * FROM ' . MAIN_DB_PREFIX . 'societe WHERE nom like "%' . $valfinance->banque . '%"');
            if ($db->num_rows($testBanque) > 0) {
                $row = $db->fetch_object($testBanque);
                $facture->socid = $row->rowid;
            }
        }
        if (!$facture->socid) {
            $facture->socid = $object->socid;
        }

        $facture->create($user);

        $valfinance->facture_id = $facture->id;
        $valfinance->update($user);

        $facture->addline("Commission financière du contrat ".$contract->ref.":", $valfinance->commFP1 + $valfinance->commFP2+$valfinance->commFM1+$valfinance->commFM2, 1, 7, NULL, NULL, NULL, null, convertirDate($_POST["datesign"], false), "HT");


        addElementElement("propal", "facture", $object->id, $facture->id);
        
        
        
        require_once(DOL_DOCUMENT_ROOT . "/core/modules/synopsiscontrat/modules_synopsiscontrat.php");
        contrat_pdf_create($db, $contract->id, "contratFinanc");
    }
}
$dif=$valfinance->montantAF + $valfinance->VR + $valfinance->pret - $totG;
if (($dif>=1 || $dif<=-1) && $valfinance->montantAF!=""){
    echo "<div class='redT'><br/>Attention: le total à financer n'est plus égale au total de la propal</div><br/>";
}




if ($user->rights->synopsisFinanc->write) {


    echo "<form method='POST'>";

    require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
    $form = new Form($db);
    echo "Entreprise du rapporteur:".$form->select_thirdparty($socid, "socid")."<br/>Nom du rapporteur:";
    if ($socid > 0)
        echo $form->selectcontacts($socid, $idcontact, "contactid", 1);
    else
        echo "<select id='contactid' class='flat' name='contactid'></select>";

    echo "<br/><hr/><br/>";
    echo "<input type='hidden' name='form1'/>";

    echo '<table class="monnom noborder titreHG" cellspacing=0>';
    echo '<tr class="liste_titre"><th colspan="2" class="HH">';
    foreach ($valfinance::$rad as $name => $value) {
        echo "<input type='radio' class='rad' name='rad' id='rad" . $name . "' value='" . $name . "' " . (($name == $location) ? "checked='checked'" : "") . "/><label for='rad" . $name . "'>" . $value . "</label>";
    }
    echo '</th></tr>';
    echo'<tr>';
    echo "<td><div class='pr'>Somme préter au client: <hr/></div><div class='vr'>VR: <hr/></div>Somme financée au client: <hr/>Argent disponible du client (par mois): </td>";
    echo "<td>"
    . "<div class='pr'><input type='text' id='preter' name='preter' value='" . $pret . "' /><hr/></div>"
    . "<div class='vr'><input type='text' id='VR' name='VR' value='" . $VR . "' /><hr/></div>";
    echo "<input type='text' id='montant' name='montantAF' value='" . $montantAF . "'/><hr/>";
    echo '<input type="text" id="pretAP" name="pretAP" value=""/>€<br/><button class="butAction" id="bouton">calculer le pret</button></td>';
    echo'</tr>';

    echo '<tr>';
    echo "<td>Type de période: </td><td><select id='mensuel' name='periode'>";
    foreach (Synopsisfinancement::$TPeriode as $val => $mensualite) {
        echo "<option value='" . $val . "'" . (($val == $periode) ? 'selected="selected"' : "") . ">" . $mensualite . "</option>";
    }
    echo "</select></td>";
    echo '</tr>';

    echo '<tr>';
    echo "<td>Durée du financement <span class='degr'>(1ère periode)</span>: </td><td><select id='duree' name='duree'>";
    foreach (Synopsisfinancement::$tabD as $dure => $mois) {
        echo "<option value='" . $dure . "'" . (($dure == $duree) ? 'selected="selected"' : "") . ">" . $mois . "</option>";
    }
    echo "</select></td>";
    echo '</tr>';

    echo '<tr>';
    echo '<td>Financement à 2 periodes:</td>';
    echo '<td><INPUT type="checkbox" id="check" name="tarif_degr" value="degr" ' . (($valfinance->duree_degr > 0 || $valfinance->pourcent_degr > 0) ? "checked='checked'" : "") . ' /></td>';
    echo '</tr>';

    echo '<tr class="degr">';
    echo '<td>Durée de la 2nd periode:</td>';
    //echo '<td><INPUT type="text" id="dure_degr" name="duree_degr" value="'.$duree_degr.'"/></td>';
    echo "<td><select id='dure_degr' name='duree_degr'>";
    echo '<option value="0"></option>';
    foreach (Synopsisfinancement::$tabD as $dure => $mois) {
        echo "<option value='" . $dure . "'" . (($dure == $duree_degr) ? 'selected="selected"' : "") . ">" . $mois . "</option>";
    }
    echo "</select></td>";
    echo '</tr>';

    echo '<tr class="degr">';
    echo '<td>Pourcentage du total pour la 2nd priode:</td>';
    echo '<td><INPUT type="text" id="po_degr" name="pour_degr" value="' . $pourcent_degr . '"/></td>';
    echo '</tr>';

    echo '<tr>';
    echo '<td>Commissions: </td>';
    echo "<td>Commerciale:<br/><input type='text' id='commC' name='commC' value='" . $commC . "'/>%";
    if ($user->rights->synopsisFinanc->super_write) {
        echo "<hr/>";
        echo "Financière:<br/><input type='text' name='commF' id='commF' value='" . $commF . "'/>%</td>";
    }
    echo '</tr>';

    if ($user->rights->synopsisFinanc->super_write) {
        echo '<tr>';
        $tabB = array("" => "", "Grenke" => 2, "SEPA" => 4, "BNP" => 3);
        echo '<td>Banque:<hr/>Taux</td><td><select id="banque">';
        foreach ($tabB as $nomB => $tauxT) {
            echo '<option value="' . $tauxT . '"' . (($nomB == $banque) ? 'selected="selected"' : "" ) . '>' . $nomB . '</option>';
        }
        echo '</select><hr/>';

        echo "<input id='taux' type='text' name='taux' value='" . $tauxInteret . "'/>%</td>";
        echo '</tr>';

        echo '<input type="hidden" id="Bcache" name="Bcache" value="'.$banque.'"/>';
        echo '<tr>';
    }
    $contrat_exist = false;
    if ($valfinance->contrat_id > 0) {
        require_once DOL_DOCUMENT_ROOT . '/contrat/class/contrat.class.php';
        $ctr = new Contrat($db);
        $ctr->fetch($valfinance->contrat_id);
        if ($ctr->id)
            $contrat_exist = true;
    }

    echo "<td class='BB' colspan='2'><input type='submit' class='butAction' value='Valider' " . (($contrat_exist) ? "disabled='disabled'" : "") . " /></td>";
    echo '</tr>';
    echo '</table>';
    echo '</form>';
}

if ($valfinance->id > 0) {

    if ($montantAF + $VR + $pret > 0) {
        echo "<br/><hr/><br/>";

        echo "Montant Total a emprunter sur toute la durée : " . price($valfinance->emprunt_total);

        echo"<br/><br/>";



        echo Synopsisfinancement::$TPeriode[$valfinance->periode] . ": " . price(($valfinance->loyer1) + 0.005) . " €   X   " . $valfinance->nb_periode . " periodes";

        if ($valfinance->duree_degr > 0 && $valfinance->pourcent_degr > 0) {
//            echo"<br/><br/>";
            echo ' puis ' . price(($valfinance->loyer2) + 0.005) . " €   X " . $valfinance->nb_periode2 . " periodes";
        }

        echo " soit " . price($valfinance->prix_final) . " € HT";

        if ($valfinance->VR > 0) {

            echo " avec un VR de: " . price($valfinance->VR) . " €";
        }


        if ($user->rights->synopsisFinanc->write) {
            echo '<br/><br/><form method="post">';
            echo '<input type="hidden" name="form2" value="form2"/>';
            echo "signer le: <input type='text' name='datesign' value='' class='datePicker'/>";
            echo '<input type="submit" name="signer" class="butAction" value="transformer en contrat" ' . (($contrat_exist) ? "disabled='disabled'" : "") . ' />';
            echo "</form>";
        }
    }
}
echo '</div>';
echo '<div class="fichehalfleft">';

/*
 * Documents generes
 */
$filename = dol_sanitizeFileName($object->ref);
$filedir = $conf->propal->dir_output . "/" . dol_sanitizeFileName($object->ref);
$urlsource = $_SERVER["PHP_SELF"] . "?id=" . $object->id;
$genallowed = $user->rights->propal->creer;
$delallowed = $user->rights->propal->supprimer;

$var = true;
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';
$formfile = new FormFile($db);
echo "<br/>";
$somethingshown = $formfile->show_documents('propal', $filename, $filedir, $urlsource, $genallowed, $delallowed, $object->modelpdf, 1, 0, 0, 28, 0, '', 0, '', $soc->default_lang);
echo "</div>";

echo "<div class='fichehalfright'>";
$somethingshown = $object->showLinkedObjectBlock();



llxFooter();
