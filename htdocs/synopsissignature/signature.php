<?php

/*
 */
/**
 *
 * Name : listDetail.php.php
 * BIMP-ERP-1.2
 */
$typeObj = (isset($_REQUEST['obj']) ? $_REQUEST['obj'] : null);
$id = (isset($_REQUEST['id']) ? $_REQUEST['id'] : null);
$selectedFile = isset($_REQUEST['file']) ? $_REQUEST['file'] : null;



$invite = false;
if (isset($_REQUEST['code'])) {
    $code = $_REQUEST['code'];
    define("NOLOGIN", '1');
    $invite = true;
}



require_once('../main.inc.php');
require_once(DOL_DOCUMENT_ROOT . "/synopsischrono/class/chrono.class.php");
require_once(DOL_DOCUMENT_ROOT . "/synopsischrono/chronoDetailList.php");
require_once(DOL_DOCUMENT_ROOT . "/core/class/html.form.class.php");
require_once(DOL_DOCUMENT_ROOT . "/core/lib/files.lib.php");

// Security check
$socid = isset($_GET["socid"]) ? $_GET["socid"] : '';
if ($user->societe_id)
    $socid = $user->societe_id;
if (!$invite)
    $result = restrictedArea($user, 'propal', $socid, '', '', 'Afficher');
else {
    if ($code != "nc" && $code != "" && $code != 0) {
//        $tabRes = getElementElement("demSign", null, $code);
        $sql = "SELECT * FROM " . MAIN_DB_PREFIX . "synopsissignature WHERE code ='" . $code . "' AND `date_fin` > now();";
        $result = $db->query($sql);
        if ($db->num_rows($result) > 0)
            $ligne = $db->fetch_object($result);
        else
            $code = "inc";
    } else
        $code = "nc";
    $conf->dol_hide_topmenu = true;
    $conf->dol_hide_leftmenu = true;




    if ($code == "nc" || $code == "inc") {
        if ($code != "nc")
            echo ("<h3>Code incorrect</h3>");
        echo "Code : <form method='get'>";

        echo "<input type='text' name='code'/><input type='submit'/></form>";
        die;
    }
    else {
        $id = $ligne->id;
        $typeObj = $ligne->type;
        $selectedFile = $ligne->file;
    }
}
//$user, $feature='societe', $objectid=0, $dbtablename='',$feature2='',$feature3=''




$js = $html = $html2 = $titreGege = "";


$champ = array();
$clef = "id";
if ($typeObj) {
    if ($typeObj == "soc") {
        require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
        $object = new Societe($db);
        $object->fetch($id);
        $filtre = "fk_soc=" . urlencode($id);
        $head = societe_prepare_head($object);
        $socid = $id;

        $titreGege = $object->getNomUrl();
    } else if ($typeObj == "ctr") {
        $langs->load("contracts");
        require_once DOL_DOCUMENT_ROOT . '/core/lib/contract.lib.php';
        require_once DOL_DOCUMENT_ROOT . '/contrat/class/contrat.class.php';
        $object = new Contrat($db);
        $object->fetch($id);
//        $filtre = "Contrat=" . urlencode($object->ref);
        $filtre = "fk_contrat=" . $object->id;
        $head = contract_prepare_head($object);
        $socid = $object->socid;
        $objectId = $id;
    } else if ($typeObj == "fi") {
        require_once DOL_DOCUMENT_ROOT . '/core/lib/fichinter.lib.php';
        require_once DOL_DOCUMENT_ROOT . '/fichinter/class/fichinter.class.php';
        $object = new Fichinter($db);
        $object->fetch($id);
        $head = fichinter_prepare_head($object);
        $socid = $object->socid;
        $objectId = $id;
        $module = "ficheinter";
        $clef = "ref";
    } else if ($typeObj == "shipping") {
        require_once DOL_DOCUMENT_ROOT . '/core/lib/sendings.lib.php';
        $object = new Expedition($db);
        $object->fetch($id);
        $head = shipping_prepare_head($object);
        $socid = $object->socid;
        $objectId = $id;
        $module = "expedition";
        $dirFile = $module . "/sending";
        $clef = "ref";
    } else if ($typeObj == "project") {
        $langs->load("projects");
        require_once DOL_DOCUMENT_ROOT . '/core/lib/project.lib.php';
        require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
        $object = new Project($db);
        $object->fetch($id);
        $head = project_prepare_head($object);
        $filtre = "fk_projet=" . $object->id;
        $champ['fk_projet'] = $object->id;
        $socid = $object->socid;
    } else if ($typeObj == "propal") {
        $langs->load("contracts");
        require_once DOL_DOCUMENT_ROOT . '/core/lib/propal.lib.php';
        require_once DOL_DOCUMENT_ROOT . '/comm/propal/class/propal.class.php';
        $object = new Propal($db);
        $object->fetch($id);
        $filtre = "fk_propal=" . $object->id;
        $champ['fk_propal'] = $object->id;
        $head = propal_prepare_head($object);
        $socid = $object->socid;
    } else if ($typeObj == "chrono") {
        $langs->load("contracts");
        require_once DOL_DOCUMENT_ROOT . '/synopsischrono/core/lib/synopsischrono.lib.php';
        require_once DOL_DOCUMENT_ROOT . '/synopsischrono/class/chrono.class.php';
        $object = new Chrono($db);
        $object->fetch($id);
        $filtre = "fk_propal=" . $object->id;
        $champ['fk_propal'] = $object->id;
        $head = chrono_prepare_head($object);
        $socid = $object->socid;
        $module = "synopsischrono";
    }
} else {
    echo "Pas d'object correct détécté";
    die;
}


if (!isset($dirFile))
    $dirFile = $module;

$nomOnglet = "signature";
$afficheSign = false;

$js .= '<style type="text/css">' .
        '.grZoneSignature{'
        . ' position: fixed;'
        . ' top:55px;'
        . 'width: 100%;
            background: white;
            left: 0;
            text-align: center;
            z-index: -1;'
        . '}'
        . 'div#signatureZone{'
        . ' width: 100%; '
        . 'position:fixed; '
        . 'top: 95px; left:0; bottom: 0;'
        . ' margin: auto;'
        . 'border: solid!important;'
        . 'background: rgba(255,255,255,0.8);'
        . '}'
        . '.controleSignature{'
        . ' text-align: center;'
        . 'z-index: 200;'
        . '}'
        . 'div.grZoneSignature input{    '
        . 'margin: 0px 20px 10px '
        . '}'
        . 'img.pdfImg{'
        . '    min-width: 600px;
            max-width: 600px;'
        . 'min-height: 1000px;'
        . '}'
        . '#bodyIndex2{'
        . '  background-color:white;'
        . '}'
        . '.hideWithSign{'
        . ' display: none;'
        . '}'
        . '</style>';


llxHeader($js, $nomOnglet);
if (!$invite) {
    dol_fiche_head($head, 'signature', $langs->trans($nomOnglet));
} else {
    echo "<div id='bodyIndex2'>";
}



print '<table class="border" width="100%">';
print '<tr><td width="25%">' . $langs->trans('Nom élément') . '</td>';
print '<td colspan="3">';
print $object->getNomUrl(1);
//print $form->showrefnav($obj, 'obj=' . $typeObj . '&id', '', ($user->societe_id ? 0 : 1), 'rowid', $champ);
print '</td></tr>';
if ($object != $soc && $socid > 0) {
    $soc = new Societe($db);
    $soc->fetch($socid);
    print '<tr><td width="25%">' . $langs->trans('ThirdPartyName') . '</td>';
    print '<td colspan="3">';
    print $soc->getNomUrl(1);
    print '</td></tr>';
}



$dir = DOL_DATA_ROOT . "/" . $dirFile . "/" . $object->$clef;
$dirTmp = PATH_TMP . "/" . $dirFile . "/" . $object->$clef;
if (!$selectedFile) {
    $filearray = dol_dir_list($dir, "files");
    $filearray2 = array();
    foreach ($filearray as $file)
        if (stripos($file['name'], ".pdf") > -1 && stripos($file['name'], "-signe") < 1)
            $filearray2[] = $file['name'];

    if (count($filearray2) == 1)
        $selectedFile = $filearray2[0];
    else {
        echo "<tr><td>Choix du doc</td><td><form method='post'><select name='file'>";
        foreach ($filearray2 as $file) {
            echo "<option value='" . $file . "'>" . $file . "</option>";
        }
        echo "</select><input type='submit'/></form>";
    }
}
if ($selectedFile) {
    $afficheSign = true;

    $signeFile = str_replace(".pdf", "-signe.pdf", $selectedFile);

    if (isset($_REQUEST['demSign'])) {
//        $code = rand(1, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9);
        $code = rand(1, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9);
//        setElementElement("demSign", $typeObj . "|" . $selectedFile, $code, $id);
        if (strlen($code) > 7)
            $dateFin = strtotime("+ 2 day");
        else
            $dateFin = strtotime("+ 10 minutes");
        $sql = "INSERT INTO " . MAIN_DB_PREFIX . "synopsissignature (code, type, id, file, date_fin) VALUES (" . $code . ",'" . $typeObj . "','" . $id . "','" . $selectedFile . "','" . $db->idate($dateFin) . "');";
//        die($sql);
        $db->query($sql);
        $afficheSign = false;

        echo "<h4>Code de sécurité : " . $code . "</h4>";
        $lien = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . DOL_URL_ROOT . "/synopsissignature/signature.php?code=" . $code;
        echo "<br/><h4>Lien : <a href='" . $lien . "'>" . $lien . "</a></h4>";
    } else if (isset($_REQUEST['img'])) {
        if (!is_dir($dirTmp . "/temp"))
            mkdir($dirTmp . "/temp",0777,true);
        $nomSign = $dirTmp . "/temp/signature.png";
        base64_to_jpeg($_REQUEST['img'], $nomSign);

        $afficheSign = false;

        require_once DOL_DOCUMENT_ROOT . '/core/lib/pdf.lib.php';
        $pdf = pdf_getInstance();
        $pdf->SetMargins(0, 0, 0, 0);   // Left, Top, Right
        $pagecount = $pdf->setSourceFile($dir . "/" . $selectedFile);
        for ($i = 0; $i < $pagecount; $i++) {
            $pdf->AddPage();
            $tplidx = $pdf->importPage($i + 1, '/MediaBox');
            $pdf->useTemplate($tplidx, 0, 0, 0, 0, true);
        }

        $date = date("Y-m-d H:i");
        $fontSize = 10;

        $pdf->setXY(167, 247.5);
        if (stripos($signeFile, 'Destruction') === 0) {
            $pdf->setXY(17, 233);
            $pdf->image($nomSign, 130, 240, 60);
        } elseif (stripos($signeFile, 'PC-SAV') === 0) {
            $pdf->setXY(160, 210);
            $pdf->image($nomSign, 155, 220, 45);
        } elseif (stripos($signeFile, 'PR') === 0) {
            $pdf->setXY(167, 247.5);
            $pdf->image($nomSign, 160, 256, 40);
        } elseif (stripos($signeFile, 'FA') === 0 || stripos($signeFile, 'AC') === 0) {
            $fontSize = 8;
            $pdf->setXY(90, 250);
            $pdf->image($nomSign, 89, 255, 25);
        } elseif (stripos($signeFile, 'FI') === 0) {
            $pdf->setXY(8, 255);
            $pdf->image($nomSign, 10, 260, 28);

            /* $tabFilePc[] = DOL_DATA_ROOT."/ficheinter/".$object->ref."/";
              $tabFilePc2[] = ".pdf";
              $tabFilePc3[] = "PC-" . $chrono->ref . ".pdf"; */
            
            $email = "f.pineri@bimp.fr, v.gilbert@bimp.fr";
            if(is_object($soc) && $soc->id > 0){
                foreach($soc->getSalesRepresentatives($user) as $userT){
                    require_once(DOL_DOCUMENT_ROOT."/user/class/usergroup.class.php");
                    $groups = new UserGroup($db);
                    $grps = $groups->listGroupsForUser($userT['id'], false);
                    if(!isset($grps[210])){
                        $emails[] = $userT['email'];
                    }
                }
                if(count($emails) > 0)
                    $email = implode(",", $emails);
            }
            
            $infoClient = "";
            
            if(isset($object->socid)){                
                require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
                $societe = new Societe($db);
                $societe->fetch($object->socid);
                $infoClient = " du client ".$societe->getNomUrl(1);
            }
            
            mailSyn2("FI Signé", $email, null, "Bonjour la FI " . $object->getNomUrl(1) . " ".$infoClient." est signée", array(), array(), array());
        } elseif (stripos($signeFile, 'SH') === 0) {
            $pdf->setXY(25, 240);
            $pdf->image($nomSign, 25, 245, 40);
        } else
            $pdf->image($nomSign, 148, 220, 65);

        $pdf->SetFont(null, '', $fontSize);
        $pdf->MultiCell(($fontSize < 9) ? 25 : 30, 20, $date);
        $pdf->Close();

        $pdf->Output($dir . "/" . $signeFile, 'F');


        //mail au client
        $socid = (isset($object->socid) ? $object->socid : 0);
        if ($socid > 0) {
            $soc = new Societe($db);
            $soc->fetch($socid);
            $mail = $soc->email;
            $tabFile1 = $tabFile2 = $tabFile3 = array();
            $tabFile1[] = $dir . "/" . $signeFile;
            $tabFile2[] = ".pdf";
            $tabFile3[] = $signeFile;
            mailSyn2("Document Signé", "tommy@bimp.fr", null, "Bonjour veuillez trouvez ci-joint votre document signé.
                    
                    Cordialement
                    BIMP", $tabFile1, $tabFile2, $tabFile3);
        }

        if ($invite) {
//            delElementElement("demSign", null, $code);
            $db->query("DELETE FROM " . MAIN_DB_PREFIX . "synopsissignature WHERE code='" . $code . "';");
            echo "</table><h2>Merci</h2>";
            echo "Code : <form method='get'>";

            echo "<input type='text' name='code'/><input type='submit'/></form>";
            die();
        }
    }





    if ($afficheSign) {
        echo "<div class='grZoneSignature'><div id='signatureZone'></div>";

        echo '<form class="controleSignature" method="post">'
        . '<input type="hidden" class="nav shrinkwidth_parent" name="img" id="someelement"/>'
        . '<input type="hidden" value="' . $selectedFile . '" name="file"/>'
        . '<input type="submit" value="Signer" class="hideWithSign"/>'
        . '<input type="button" value="Effacer" class="hideWithSign" id="clear"/>';

        echo '<script type="text/javascript" src="jSignature.min.js"></script>'
        . '<script type="text/javascript">'
        . '$(window).on("load", function () {'
        . '$("#affSign").click(function(){'
        . '$(this).parent().parent().hide();'
        . '$(".grZoneSignature").css("z-index", 1);'
        . '$(".hideWithSign").show();'
        . '});'
        . 'var $sigdiv = $("#signatureZone"); '
        . '$sigdiv.jSignature(); '
        . '$sigdiv.jSignature("reset");'
        . '$("#signatureZone").change(function(){'
        . 'var datapair = $sigdiv.jSignature("getData", "image");
                        var i = new Image();
                        i.src = "data:" + datapair[0] + "," + datapair[1] ;
                        $("#someelement").val("data:" + datapair[0] + "," + datapair[1]);'
        . '}); '
        . '$("#clear").click(function(){'
        . '$sigdiv.jSignature("reset");'
        . '});'
        . '});'
        . '</script></div>';
    }


    if (1) {
        $fileToShow = (!$invite && is_file($dir . "/" . $signeFile)) ? $signeFile : $selectedFile;

        $save_to = str_replace(".pdf", "", $fileToShow);
//        $dirTemp = $dir . "/temp/";
        $dirTemp = DOL_DOCUMENT_ROOT . "/synopsissignature/temp/";
        if (!is_dir($dirTemp))
            if(!mkdir($dirTemp))
                echo "Impossible de créer le rep '".$dirTemp."' permission refusé.";
//        $commande = 'convert "' . $dir . "/" . $fileToShow . '"  -density 300 "' . $dirTemp . $save_to . '"';
        $format = "ppm";
        $format2 = "png";
        $adressScript = (defined("PATH_PDFTOPPM")) ? PATH_PDFTOPPM . "/" : "";
        $adressScript2 = (defined("PATH_CONVERT")) ? PATH_CONVERT . "/" : "";
        $tabPref = array("", "0000", "00000");

        $commande = $adressScript . 'pdftoppm ' . ' -r 100  "' . $dir . "/" . $fileToShow . '"   "' . $dirTemp . $save_to . '"';
        exec($commande, $output, $return_var);

        $i = 1;
        foreach ($tabPref as $prefixePage) {
            while (is_file($dirTemp . $save_to . "-" . $prefixePage . $i . "." . $format)) {
                $file1 = $save_to . "-" . $prefixePage . $i . "." . $format;
                $file2 = $save_to . "-" . $prefixePage . $i . "." . $format2;
                $commande2 = $adressScript2 . "convert " . $dirTemp . "/" . $file1 . " " . $dirTemp . "/" . $file2;

                exec($commande2, $output2, $return_var2);
                //echo $return_var2;echo $output2;die($commande2);
                $tabFile[] = $file2;
                $i++;
            }
        }
        
        if (count($tabFile) == 0)
            $tabFile[] = $save_to . "." . $format;

        if ($return_var == 0) {

            $debLien = DOL_URL_ROOT . "/document.php?modulepart=" . $module . "&file=" . $object->$clef . "/";
            echo "<tr><td>Fichier PDF</td><td><a target='_blank' href='" . $debLien . $fileToShow . "'>" . $fileToShow . "</a>";

            if ($afficheSign) {
                echo "<tr><td>Signature</td><td><input type='button' id='affSign' value='Signer'/>";
                echo ($invite ? '' : '<br/><input type="submit" name="demSign" value="Demande Signature" id="demande"/></form>');
            }
            foreach ($tabFile as $file)
                echo "<tr><td colspan='2'><img class='pdfImg' src='" . DOL_URL_ROOT . "/synopsissignature/temp/" . $file . "'/>";
        } else
            print "Conversion failed.<br />" . $output . "<br/>" . $commande;
    }
}

function base64_to_jpeg($base64_string, $output_file) {
    // open the output file for writing
    $ifp = fopen($output_file, 'wb');

    // split the string on commas
    // $data[ 0 ] == "data:image/png;base64"
    // $data[ 1 ] == <actual base64 string>
    $data = explode(',', $base64_string);

    // we could add validation here with ensuring count( $data ) > 1
    fwrite($ifp, base64_decode($data[1]));

    // clean up the file resource
    fclose($ifp);

    return $output_file;
}

if (!$invite)
    llxFooter();

$db->close();
?>
