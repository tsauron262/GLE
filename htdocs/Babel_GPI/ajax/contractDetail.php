<?php
    /*
    ** GLE by Synopsis et DRSI
    *
    * Author: Tommy SAURON <tommy@drsi.fr>
    * Licence : Artistic Licence v2.0
    *
    * Version 1.1
    * Created on : 13 juil. 09
    *
    * Infos on http://www.finapro.fr
    *
    */
    /**
    *
    * Name : contractDetail.php
    * magentoGLE
    */

    require_once('pre.inc.php');
    require_once(DOL_DOCUMENT_ROOT."/core/lib/files.lib.php");

    if ($_REQUEST['type']=='contrat' || $_REQUEST['type']=='contratGA')
    {

        require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");
        $modulepart='contract';

        $contratId=$_REQUEST['contratId'];
        require_once(DOL_DOCUMENT_ROOT."/contrat/class/contrat.class.php");
        require_once(DOL_DOCUMENT_ROOT."/projet/class/project.class.php");
        require_once(DOL_DOCUMENT_ROOT."/contact/class/contact.class.php");
        $contrat = new Contrat($db);
        $contactstatic=new Contact($db);
        $contrat->fetch($contratId);
        if ($contrat->isFinancement == 1)
        {
            require_once(DOL_DOCUMENT_ROOT.'/Babel_GA/ContratGA.class.php');
            $contrat = new ContratGA($db);
            $contactstatic=new ContactGA($db);
            $contrat->fetch($contratId);
        }
        $contrat->info($contratId);
        $contrat->fetch_lignes();
        $requete = "SELECT *
        FROM ".MAIN_DB_PREFIX."contratdet
        WHERE fk_contrat=".$contratId;
        //print $requete ;
        $sql = $db->query($requete);
        $servNum = 1;
        $xml = "<ajax-response>";

        //Cartouche
        $ref = $contrat->ref;
        $societe = $contrat->societe->getNomUrl(1);
        $date_contrat = $contrat->date_contrat;
        $proj = new project($db);
        $projet = "";
        if( "x".$contrat->fk_projet != "x")
        {
            $projet = $proj->fetch($contrat->fk_projet)->name;
        }

        $status = "" ;
        if ($contrat->statut==0) $status = $contrat->getLibStatut(2);
        else $status = $contrat->getLibStatut(4);

        $xml .= "<main>";
        $xml .= "<societe><![CDATA[".$societe."]]></societe>";
        $xml .= "<date><![CDATA[".date("j/m/Y",$date_contrat)."]]></date>";
        $xml .= "<projet><![CDATA[".$projet."]]></projet>";
        $xml .= "<ref><![CDATA[".$ref."]]></ref>";
        $xml .= "<status><![CDATA[".$status."]]></status>";
        $xml .= "<remiseAbs><![CDATA[".$contrat->societe->getAvailableDiscounts()."]]></remiseAbs>";
        $xml .= "<remisePercent>".$contrat->remise_percent."</remisePercent>";
        $xml .= "</main>";


        $xml .= "<services>";
        $xml1="";
        $xml2="";
        while ($res = $db->fetch_object($sql))
        {
            $warnStop = "";
            if ($res->statut == 4 && strtotime($res->date_fin_validite) < time() - $conf->contrat->services->inactifs->warning_delay) { $warnStop = " ".img_warning($langs->trans("Late")); }

            $warnRealStop = "";
            if ($res->statut == 4 && strtotime($res->date_cloture) < time() - $conf->contrat->services->inactifs->warning_delay) { $warnRealStop = " ".img_warning($langs->trans("Late")); }

            $warnStart = "";
            if ($res->statut == 0 && strtotime($res->date_ouverture_prevue) < time() - $conf->contrat->warning_delay) { $warnStart = " ".img_warning($langs->trans("Late")); }
            $warnRealStart = "";
            if ($res->statut == 0 && strtotime($res->date_ouverture) < time() - $conf->contrat->warning_delay) { $warnRealStart = " ".img_warning($langs->trans("Late")); }

            $dateSrvStart = ($res->date_ouverture_prevue ? date('d/m/Y',strtotime($res->date_ouverture_prevue)).$warnStart : "-");
            $dateSrvStop = ($res->date_fin_validite ? date('d/m/Y',strtotime($res->date_fin_validite)).$warnStop : "-");

            $dateSrvRealStart = ($res->date_ouverture ? date('d/m/Y',strtotime($res->date_ouverture)).$warnRealStart : "-");
            $dateSrvRealStop = ($res->date_cloture ? date('d/m/Y',strtotime($res->date_cloture)).$warnRealStop : "-");

            //send description, tca, pu ht , Qte , reduc , dates mise en service / dates fin de service, status
            $xml .= "<service>";
            $xml .= " <num><![CDATA[".$servNum."]]></num>";
            $xml .= " <desc><![CDATA[".utf8_encode($res->description)."]]></desc>";
            $xml .= " <tva><![CDATA[".utf8_encode(round($res->tva_tx,1))."%]]></tva>";
            $xml .= " <puht><![CDATA[".utf8_encode($res->price_ht)."&euro;]]></puht>";
            $xml .= " <qte><![CDATA[".utf8_encode($res->qty)."]]></qte>";
            $xml .= " <reduc><![CDATA[".utf8_encode($res->remise)."&euro;]]></reduc>";
            $xml .= " <reducPercent><![CDATA[".utf8_encode($res->remise_percent)."%]]></reducPercent>";
            $xml .= " <DateServiceStart><![CDATA[".utf8_encode($dateSrvStart)."]]></DateServiceStart>";
            $xml .= " <RealDateServiceStart><![CDATA[".utf8_encode($dateSrvRealStart)."]]></RealDateServiceStart>";
            $xml .= " <DateServiceStop><![CDATA[".utf8_encode($dateSrvStop)."]]></DateServiceStop>";
            $xml .= " <RealDateServiceStop><![CDATA[".utf8_encode($dateSrvRealStop)."]]></RealDateServiceStop>";
            if ($contrat->lignes[$servNum-1])
            {
                $xml .= " <status><![CDATA[".utf8_encode($contrat->lignes[$servNum-1]->getLibStatut(4))."]]></status>";
            } else {
                $xml .= " <status></status>";
            }
            $xml .= "</service>";


            $xml2 .= "<produit>";
            if ("x".$res->fk_product == "x")
            {
                $xml2 .= "<desc><![CDATA[";
                $xml2 .= $res->description;
                $xml2 .= "]]></desc>";
                $xml2 .= "<qte><![CDATA[";
                $xml2 .= $res->qty;
                $xml2 .= "]]></qte>";
                $xml2 .= "<puht><![CDATA[";
                $xml2 .= round($res->price_ht,2);
                $xml2 .= "]]></puht>";
                $xml2 .= "<statut><![CDATA[";
                $xml2 .= $res->statut;
                $xml2 .= "]]></statut>";
                $xml2 .= "<extra><![CDATA[";
                $xml2 .= "]]></extra>";

            } else {
                $prod = new Product($db);
                $prod->fetch($res->fk_product);
                $xml2 .= "<desc><![CDATA[";
                $xml2 .= utf8_encode($prod->description);
                $xml2 .= "]]></desc>";
                $xml2 .= "<qte><![CDATA[";
                $xml2 .= utf8_encode($res->qty);
                $xml2 .= "]]></qte>";
                $xml2 .= "<puht><![CDATA[";
                $xml2 .= utf8_encode(round($prod->price,2));
                $xml2 .= "]]></puht>";
                $xml2 .= "<statut><![CDATA[";
                $xml2 .= utf8_encode($prod->status);
                $xml2 .= "]]></statut>";
                $xml2 .= "<extra>";
                $xml2 .= "<duration><![CDATA[".utf8_encode($prod->duration) ."]]></duration>";
                $xml2 .= "<weight><![CDATA[".utf8_encode($prod->weight . $res->weight_units)."]]></weight>";
                $xml2 .= "<volume><![CDATA[".utf8_encode($prod->volume . $res->volume_units)."]]></volume>";
                $xml2 .= "<note><![CDATA[".utf8_encode($prod->note)."]]></note>";
                $arr = $prod->show_photos_returnArr($conf->produit->dir_output,1,1,0);
                $xml2 .= "<photo><![CDATA[".$arr['html']."]]></photo>";
                $xml2 .= "</extra>";
            }
            $xml2 .= "</produit>";

            $requete = "SELECT *
            FROM Babel_financement
            WHERE fk_contratdet = ".$res->rowid;
            $sqlfin = $db->query($requete);
            $resfin = $db->fetch_object($sqlfin);
//            print $requete ;
            $taux = round($resfin->taux,2)."%";
            $tauxAchat = round($resfin->tauxAchat,2)."%";
            $duree = $resfin->duree;

            $monthlyCost = calculateMonthlyAmortizingCost($res->total_ht, $resfin->duree, $resfin->taux);
            $total = calculateTotalAmortizingCost($res->total_ht, $resfin->duree, $resfin->taux);
            if ($resfin->taux ."x" != "x")
            {

                $xml1 .= "<amortissement>";
                //Pour chaque ligne,  mens restant + somme tot verse et restant


                $openDate = $res->date_ouverture;
                if ('x'.$openDate != "x")
                {
                    $openDate=$res->date_ouverture_prevue;
                    $dureePaye = datediff($openDate,date("Y-m-d"));
                    $dureeRest = $duree - $dureePaye;

                    $payedAmount = $dureePaye * $monthlyCost;
                    $due = ($total +$res->total_ht) - $payedAmount;
                    $percentPayed = round(($payedAmount/($total +$res->total_ht) )* 100,2);
                } else {
                    $dureePaye = "-";
                    $dureeRest = "-";
                    $payedAmount = "-";
                    $due = "-";
                    $percentPayed = 0;
                }

                $xml1 .= " <desc><![CDATA[".utf8_encode($res->description)."]]></desc>";
                $xml1 .= " <qte><![CDATA[".utf8_encode($res->qty)."]]></qte>";
                $xml1 .= "<taux><![CDATA[".utf8_encode($taux)."%]]></taux>";
                $xml1 .= "<monthAmount><![CDATA[".utf8_encode($monthlyCost)." mois]]></monthAmount>";
                $xml1 .= "<totalCost><![CDATA[".utf8_encode($total)." &euro;]]></totalCost>";
                $xml1 .= "<nbMonth><![CDATA[".utf8_encode($duree)." mois]]></nbMonth>";
                $xml1 .= "<nbMonthRest><![CDATA[".utf8_encode($dureeRest)."]]></nbMonthRest>";
                $xml1 .= "<Payedamount><![CDATA[".utf8_encode($payedAmount)." &euro;]]></Payedamount>";
                $xml1 .= "<percentPayed><![CDATA[".utf8_encode($percentPayed)."%]]></percentPayed>";
                $xml1 .= "<due><![CDATA[".utf8_encode($due)." &euro;]]></due>";

                $xml1 .= "</amortissement>";
            }


            $servNum ++;

        }
        $xml .= "</services>";
        $xml .= "<amortissements>";
        $xml .= $xml1;
        $xml .= "</amortissements>";
        $xml .= "<produits>";
        $xml .= $xml2;
        $xml .= "</produits>";
        $xml .= "<contacts>";
        $upload_dir = $conf->contrat->dir_output.'/'.sanitize_string($contrat->ref);


        foreach(array('internal','external') as $source)
        {
            $tab = $contrat->liste_contact(-1,$source);

            foreach($tab as $key=>$val)
            {
                $xml .= "<contact>";
                if ($val['source']=='internal') $xml .= "<source><![CDATA[".$langs->trans("Internal")."]]></source>";
                if ($val['source']=='external') $xml .= "<source><![CDATA[".$langs->trans("External")."]]></source>";
                $xml .= "<societe>";
                if ($val['socid'] > 0)
                {
                    $xml .= '<![CDATA[<a href="'.DOL_URL_ROOT.'/soc.php?socid='.$val['socid'].'">';
                    $xml .=  img_object($langs->trans("ShowCompany"),"company").' '.$contrat->societe->get_nom($val['socid']);
                    $xml .=  '</a>]]>';
                } else if ($val['socid'] < 0)
                {
                    $xml .=  "<![CDATA[".$conf->global->MAIN_INFO_SOCIETE_NOM."]]>";
                }
                $xml .= "</societe>";
                $xml .= "<nom><![CDATA[";

                if ($val['source']=='internal')
                {
                    $xml .=  '<a href="'.DOL_URL_ROOT.'/user/fiche.php?id='.$val['id'].'">';
                    $xml .=  img_object($langs->trans("ShowUser"),"user").' '.$val['nom'].'</a>';
                }
                if ($val['source']=='external')
                {
                    $xml .=  '<a href="'.DOL_URL_ROOT.'/contact/fiche.php?id='.$val['id'].'">';
                    $xml .=  img_object($langs->trans("ShowContact"),"contact").' '.$val['nom'].'</a>';
                }
                $xml .= "]]></nom>";
                $xml .= "<type>";
                $xml .=  ''.$val['libelle'].'';
                $xml .= "</type>";
                $xml .= "<statut><![CDATA[";
                if ($contrat->statut >= 0) $xml .= '<a href="'.DOL_URL_ROOT.'/contrat/contact.php?id='.$contrat->id.'&amp;action=swapstatut&amp;ligne='.$val['rowid'].'">';
                $xml .=  $contactstatic->LibStatut($tab[$i]['status'],3);
                if ($contrat->statut >= 0) $xmk .= '</a>';
                $xml .= "]]></statut>";

                $xml .= "</contact>";

            }
        }
        $xml .= "</contacts>";
        $xml .= "<notes>";
        $xml .= "<public><![CDATA[";
        $xml .=  ($contrat->note_public?nl2br($contrat->note_public):"&nbsp;");
        $xml .= "]]></public>";
        if (! $user->societe_id)
        {
            $xml .= "<private><![CDATA[";
            $xml .= ($contrat->note?nl2br($contrat->note):"&nbsp;");
            $xml .= "]]></private>";
        }
        $xml .= "</notes>";
        $xml .= "<suivi>";

        global $langs;
        $langs->load("other");

        if (isset($contrat->user_creation) && $contrat->user_creation->fullname)
            $xml .=  '<CreatedBy><![CDATA['.$langs->trans("CreatedBy")." : " . utf8_encode($contrat->user_creation->fullname) . '<br>]]></CreatedBy>';

        if (isset($contrat->date_creation))
            $xml .=  '<DateCreation><![CDATA['.$langs->trans("DateCreation")." : " . utf8_encode(dol_print_date($contrat->date_creation,"dayhourtext")) . '<br>]]></DateCreation>';

        if (isset($contrat->user_modification) && $contrat->user_modification->fullname)
            $xml .=  '<ModifiedBy><![CDATA['.$langs->trans("ModifiedBy")." : " . utf8_encode($contrat->user_modification->fullname) . '<br>]]></ModifiedBy>';

        if (isset($contrat->date_modification))
            $xml .=  '<DateLastModification><![CDATA['.$langs->trans("DateLastModification")." : " . utf8_encode(dol_print_date($contrat->date_modification,"dayhourtext")) . '<br>]]></DateLastModification>';

        if (isset($contrat->user_validation) && $contrat->user_validation->fullname)
            $xml .=  '<ValidatedBy><![CDATA['.$langs->trans("ValidatedBy")." : " . utf8_encode($contrat->user_validation->fullname) . '<br>]]></ValidatedBy>';

        if (isset($contrat->date_validation))
            $xml .=  '<date_validation><![CDATA['.$langs->trans("DateValidation")." : " . utf8_encode(dol_print_date($contrat->date_validation,"dayhourtext")) . '<br>]]></date_validation>';

        if (isset($contrat->user_cloture) && $contrat->user_cloture->fullname )
            $xml .=  '<user_cloture><![CDATA['.$langs->trans("ClosedBy")." : " . utf8_encode($contrat->user_cloture->fullname) . '<br>]]></user_cloture>';

        if (isset($contrat->date_cloture))
            $xml .=  '<date_cloture><![CDATA['.$langs->trans("DateClosing")." : " . utf8_encode(dol_print_date($contrat->date_cloture,"dayhourtext")) . '<br>]]></date_cloture>';

        if (isset($contrat->user_rappro) && $contrat->user_rappro->fullname )
            $xml .=  '<ConciliatedBy><![CDATA['.$langs->trans("ConciliatedBy")." : " . utf8_encode($contrat->user_rappro->fullname) . '<br>]]></ConciliatedBy>';

        if (isset($contrat->date_rappro))
            $xml .=  '<date_rappro><![CDATA['.$langs->trans("DateConciliating")." : " . utf8_encode(dol_print_date($contrat->date_rappro,"dayhourtext")) . '<br>]]></date_rappro>';
        $xml .= "</suivi>";

        $xml .= "<documents>";
        $filearray=dol_dir_list($upload_dir,"files",0,'','\.meta$',$sortfield,(strtolower($sortorder)=='desc'?SORT_ASC:SORT_DESC),1);
        //var_dump($filearray);
        $totalsize=0;
        foreach($filearray as $key => $file)
        {
            $totalsize+=$file['size'];
        }

        $xml .= "<totDoc>".sizeof($filearray)."</totDoc>";
        $xml .= "<TotalSizeOfAttachedFiles>".$totalsize."</TotalSizeOfAttachedFiles>";
        foreach($filearray as $key => $file)
        {
            if (!is_dir($dir.$file['name']) && $file['name'] != '.' && $file['name'] != '..' && $file['name'] != 'CVS' && ! preg_match('/\.meta$/i',$file['name']))
            {
                // Define relative path used to store the file
                if (! $relativepath)
                {
                    $relativepath=$contrat->ref.'/';
                }
                $xml .= "<document>";
                $xml .= "<url><![CDATA[";
                $xml .= '<a href="'.DOL_URL_ROOT.'/document.php?modulepart='.$modulepart.'&type=application/binary&file='.urlencode($relativepath.$file['name']).'">';
                $xml .= img_mime($file['name']).' ';
                $xml .= $file['name'];
                $xml .= '</a>]]></url>';
                $xml .= '<size><![CDATA['.dol_print_size($file['size']).']]></size>';
                $xml .= '<date><![CDATA['.dol_print_date($file['date'],"dayhour").']]></date>';
                $xml .= "</document>";
            }
        }

        $xml .= "</documents>";


        $xml .= "</ajax-response>";
        if ( stristr($_SERVER["HTTP_ACCEPT"],"application/xhtml+xml") ) {
            header("Content-type: application/xhtml+xml;charset=utf-8");
        } else {
            header("Content-type: text/xml;charset=utf-8");
        } $et = ">";
        echo "<?xml version='1.0' encoding='utf-8'?$et\n";
        echo $xml;
    } else if ($_REQUEST['type'] == 'propal')
    {
        $langs->load('propal');

        require_once(DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php');
        $propal = new Propal($db);
        $propal->fetch($_REQUEST['contratId']);
        if ($propal->isFinancement == 1)
        {
            require_once(DOL_DOCUMENT_ROOT.'/Babel_GA/PropalGA.class.php');
            $propal = new PropalGA($db);
            $propal->fetch($_REQUEST['contratId']);
        }
        $xml = "<ajax-response>";

        require_once(DOL_DOCUMENT_ROOT."/projet/class/project.class.php");
        require_once(DOL_DOCUMENT_ROOT."/contact/class/contact.class.php");

        $contactstatic=new Contact($db);

        $requete = "SELECT *
                    FROM ".MAIN_DB_PREFIX."propaldet
                    WHERE fk_propal=".$propal->id;
        //print $requete ;
        $sql = $db->query($requete);
        $servNum = 1;
        $xml = "<ajax-response>";

        //Cartouche
        $ref = $propal->ref;
        $societe = $propal->societe->getNomUrl(1);
        $date_contrat = $propal->datep;
        $proj = new project($db);
        $projet = "";
        if( "x".$propal->fk_projet != "x")
        {
            $projet = $proj->fetch($propal->fk_projet)->name;
        }

        $status = "" ;
        if ($contrat->statut==0) $status = $propal->getLibStatut(2);
        else $status = $propal->getLibStatut(4);

        $xml .= "<main>";
        $xml .= "<societe><![CDATA[".$societe."]]></societe>";
        $xml .= "<date><![CDATA[".date("j/m/Y",$date_contrat)."]]></date>";
        $xml .= "<projet><![CDATA[".$projet."]]></projet>";
        $xml .= "<ref><![CDATA[".$ref."]]></ref>";
        $xml .= "<status><![CDATA[".$status."]]></status>";
        $xml .= "<remiseAbs><![CDATA[".$propal->societe->getAvailableDiscounts()."]]></remiseAbs>";
        $xml .= "<remisePercent>".$propal->remise_percent."</remisePercent>";
        $xml .= "</main>";


        $xml .= "<services>";
        $xml1="";
        $xml2="";
        while ($res = $db->fetch_object($sql))
        {

            //send description, tca, pu ht , Qte , reduc , dates mise en service / dates fin de service, status
            $xml .= "<service>";
            $xml .= " <num><![CDATA[".$servNum."]]></num>";
            $xml .= " <desc><![CDATA[".utf8_encode($res->description)."]]></desc>";
            $xml .= " <tva><![CDATA[".utf8_encode(round($res->tva_tx,1))."%]]></tva>";
            $xml .= " <puht><![CDATA[".utf8_encode(price(round($res->subprice*100)/100))."&euro;]]></puht>";
            $xml .= " <qte><![CDATA[".utf8_encode($res->qty)."]]></qte>";
            $xml .= " <reduc><![CDATA[".utf8_encode($res->remise)."&euro;]]></reduc>";
            $xml .= " <reducPercent><![CDATA[".utf8_encode($res->remise_percent)."%]]></reducPercent>";
            $xml .= "</service>";


            $xml2 .= "<produit>";
            if ("x".$res->fk_product == "x")
            {
                $xml2 .= "<desc><![CDATA[";
                $xml2 .= $res->description;
                $xml2 .= "]]></desc>";
                $xml2 .= "<qte><![CDATA[";
                $xml2 .= $res->qty;
                $xml2 .= "]]></qte>";
                $xml2 .= "<puht><![CDATA[";
                $xml2 .= round($res->subprice,2);
                $xml2 .= "]]></puht>";
                $xml2 .= "<statut><![CDATA[";
                $xml2 .= $res->statut;
                $xml2 .= "]]></statut>";
                $xml2 .= "<extra><![CDATA[";
                $xml2 .= "]]></extra>";

            } else {
                $prod = new Product($db);
                $prod->fetch($res->fk_product);
                $xml2 .= "<desc><![CDATA[";
                $xml2 .= utf8_encode($prod->description);
                $xml2 .= "]]></desc>";
                $xml2 .= "<qte><![CDATA[";
                $xml2 .= utf8_encode($res->qty);
                $xml2 .= "]]></qte>";
                $xml2 .= "<puht><![CDATA[";
                $xml2 .= utf8_encode(round($prod->price,2));
                $xml2 .= "]]></puht>";
                $xml2 .= "<statut><![CDATA[";
                $xml2 .= utf8_encode($prod->status);
                $xml2 .= "]]></statut>";
                $xml2 .= "<extra>";
                $xml2 .= "<duration><![CDATA[".utf8_encode($prod->duration) ."]]></duration>";
                $xml2 .= "<weight><![CDATA[".utf8_encode($prod->weight . $res->weight_units)."]]></weight>";
                $xml2 .= "<volume><![CDATA[".utf8_encode($prod->volume . $res->volume_units)."]]></volume>";
                $xml2 .= "<note><![CDATA[".utf8_encode($prod->note)."]]></note>";
                $arr = $prod->show_photos_returnArr($conf->produit->dir_output,1,1,0);
                $xml2 .= "<photo><![CDATA[".$arr['html']."]]></photo>";
                $xml2 .= "</extra>";
            }
            $xml2 .= "</produit>";
        }
            $xml .= "</services>";
            $xml .= "<produits>";
            $xml .= $xml2;
            $xml .= "</produits>";
        $xml .= "<contacts>";

        $upload_dir = $conf->propal->dir_output.'/'.sanitize_string($propal->ref);

        foreach(array('internal','external') as $source)
        {
            $tab = $propal->liste_contact(-1,$source);
            foreach($tab as $key=>$val)
            {
                $xml .= "<contact>";
                if ($val['source']=='internal') $xml .= "<source><![CDATA[".$langs->trans("Internal")."]]></source>";
                if ($val['source']=='external') $xml .= "<source><![CDATA[".$langs->trans("External")."]]></source>";
                $xml .= "<societe>";
                if ($val['socid'] > 0)
                {
                    $xml .= '<![CDATA[<a href="'.DOL_URL_ROOT.'/soc.php?socid='.$val['socid'].'">';
                    $xml .=  img_object($langs->trans("ShowCompany"),"company").' '.$propal->societe->get_nom($val['socid']);
                    $xml .=  '</a>]]>';
                } else if ($val['socid'] < 0)
                {
                    $xml .=  "<![CDATA[".$conf->global->MAIN_INFO_SOCIETE_NOM."]]>";
                }
                $xml .= "</societe>";
                $xml .= "<nom><![CDATA[";

                if ($val['source']=='internal')
                {
                    $xml .=  '<a href="'.DOL_URL_ROOT.'/user/fiche.php?id='.$val['id'].'">';
                    $xml .=  img_object($langs->trans("ShowUser"),"user").' '.$val['nom'].'</a>';
                }
                if ($val['source']=='external')
                {
                    $xml .=  '<a href="'.DOL_URL_ROOT.'/contact/fiche.php?id='.$val['id'].'">';
                    $xml .=  img_object($langs->trans("ShowContact"),"contact").' '.$val['nom'].'</a>';
                }
                $xml .= "]]></nom>";
                $xml .= "<type>";
                $xml .=  ''.$val['libelle'].'';
                $xml .= "</type>";
                $xml .= "<statut><![CDATA[";
                if ($propal->statut >= 0) $xml .= '<a href="'.DOL_URL_ROOT.'/comm/propal.php?id='.$propal->id.'&amp;action=swapstatut&amp;ligne='.$val['rowid'].'">';
                $xml .=  $propal->LibStatut($tab[$i]['status'],3);
                if ($propal->statut >= 0) $xml .= '</a>';
                $xml .= "]]></statut>";

                $xml .= "</contact>";

            }
        }
        $xml .= "</contacts>";
        $xml .= "<notes>";
        $xml .= "<public><![CDATA[";
        $xml .=  ($propal->note_public?nl2br($propal->note_public):"&nbsp;");
        $xml .= "]]></public>";
        $xml .= "</notes>";
        $xml .= "<suivi>";

        global $langs;
        $langs->load("other");
        $propal->info($propal->id);

        if (isset($propal->user_creation) && $propal->user_creation->fullname)
            $xml .=  '<CreatedBy><![CDATA['.$langs->trans("CreatedBy")." : " . $propal->user_creation->fullname . '<br>]]></CreatedBy>';

        if (isset($propal->date_creation))
            $xml .=  '<DateCreation><![CDATA['.$langs->trans("DateCreation")." : " . dol_print_date($propal->date_creation,"dayhourtext") . '<br>]]></DateCreation>';

        if (isset($propal->user_modification) && $propal->user_modification->fullname)
            $xml .=  '<ModifiedBy><![CDATA['.$langs->trans("ModifiedBy")." : " . $propal->user_modification->fullname . '<br>]]></ModifiedBy>';

        if (isset($propal->date_modification))
            $xml .=  '<DateLastModification><![CDATA['.$langs->trans("DateLastModification")." : " . utf8_encode(dol_print_date($propal->date_modification,"dayhourtext")) . '<br>]]></DateLastModification>';

        if (isset($propal->user_validation) && $propal->user_validation->fullname)
            $xml .=  '<ValidatedBy><![CDATA['.$langs->trans("ValidatedBy")." : " . utf8_encode($propal->user_validation->fullname) . '<br>]]></ValidatedBy>';

        if (isset($propal->date_validation))
            $xml .=  '<date_validation><![CDATA['.$langs->trans("DateValidation")." : " . utf8_encode(dol_print_date($propal->date_validation,"dayhourtext")) . '<br>]]></date_validation>';

        if (isset($propal->user_cloture) && $propal->user_cloture->fullname )
            $xml .=  '<user_cloture><![CDATA['.$langs->trans("ClosedBy")." : " . utf8_encode($propal->user_cloture->fullname) . '<br>]]></user_cloture>';

        if (isset($propal->date_cloture))
            $xml .=  '<date_cloture><![CDATA['.$langs->trans("DateClosing")." : " . utf8_encode(dol_print_date($propal->date_cloture,"dayhourtext")) . '<br>]]></date_cloture>';

        if (isset($propal->user_rappro) && $propal->user_rappro->fullname )
            $xml .=  '<ConciliatedBy><![CDATA['.$langs->trans("ConciliatedBy")." : " . utf8_encode($propal->user_rappro->fullname) . '<br>]]></ConciliatedBy>';

        if (isset($propal->date_rappro))
            $xml .=  '<date_rappro><![CDATA['.$langs->trans("DateConciliating")." : " . utf8_encode(dol_print_date($propal->date_rappro,"dayhourtext")) . '<br>]]></date_rappro>';
        $xml .= "</suivi>";

        $xml .= "<documents>";
        $filearray=dol_dir_list($upload_dir,"files",0,'','\.meta$',$sortfield,(strtolower($sortorder)=='desc'?SORT_ASC:SORT_DESC),1);
        //var_dump($filearray);
        $totalsize=0;
        foreach($filearray as $key => $file)
        {
            $totalsize+=$file['size'];
        }
        $sf = sizeof($filearray);
        $modulepart='propal';
        foreach($filearray as $key => $file)
        {
            if (!is_dir($dir.$file['name']) && $file['name'] != '.' && $file['name'] != '..' && $file['name'] != 'CVS' && ! preg_match('/\.meta$/i',$file['name']))
            {
                // Define relative path used to store the file
                if (! $relativepath)
                {
                    $relativepath=$propal->ref.'/';
                }
                $xml .= "<document>";
                $xml .= "<url><![CDATA[";
                $xml .= '<a href="'.DOL_URL_ROOT.'/document.php?modulepart='.$modulepart.'&type=application/binary&file='.urlencode(preg_replace('/_$/','',preg_replace('/\//','_',$relativepath)))."/".urlencode(preg_replace('/\//','_',$file['name'])).'">';
                $xml .= img_mime($file['name']).' ';
                $xml .= $file['name'];
                $xml .= '</a>]]></url>';
                $xml .= '<size><![CDATA['.dol_print_size($file['size']).']]></size>';
                $xml .= '<date><![CDATA['.dol_print_date($file['date'],"dayhour").']]></date>';
                $xml .= "</document>";
            }
        }

        $upload_dir = $conf->PROPALEGA->dir_output.'/'.sanitize_string($propal->ref);
        $modulepart='propalGA';
//        var_dump($conf);
        $filearray=dol_dir_list($upload_dir,"files",0,'','\.meta$',$sortfield,(strtolower($sortorder)=='desc'?SORT_ASC:SORT_DESC),1);
        foreach($filearray as $key => $file)
        {
            $totalsize+=$file['size'];
        }
        $sf += sizeof($filearray);
        $totalsize = round(100 * $totalsize / 1024 )/100;
        $xml .= "<totDoc>".  $sf ."</totDoc>";
        $xml .= "<TotalSizeOfAttachedFiles>". $totalsize ."</TotalSizeOfAttachedFiles>";
        foreach($filearray as $key => $file)
        {
            if (!is_dir($dir.$file['name']) && $file['name'] != '.' && $file['name'] != '..' && $file['name'] != 'CVS' && ! preg_match('/\.meta$/i',$file['name']))
            {
                // Define relative path used to store the file
                if (! $relativepath)
                {
                    $relativepath=$propal->ref.'/';
                }
                $xml .= "<document>";
                $xml .= "<url><![CDATA[";
                $xml .= '<a href="'.DOL_URL_ROOT.'/document.php?modulepart='.$modulepart.'&type=application/binary&file='.urlencode(preg_replace('/_$/','',preg_replace('/\//','_',$relativepath)))."/".urlencode(preg_replace('/\//','_',$file['name'])).'">';
                $xml .= img_mime($file['name']).' ';
                $xml .= $file['name'];
                $xml .= '</a>]]></url>';
                $xml .= '<size><![CDATA['.dol_print_size($file['size']).']]></size>';
                $xml .= '<date><![CDATA['.dol_print_date($file['date'],"dayhour").']]></date>';
                $xml .= "</document>";
            }
        }
        $xml .= "</documents>";



        $xml .= "</ajax-response>";
        if ( stristr($_SERVER["HTTP_ACCEPT"],"application/xhtml+xml") ) {
            header("Content-type: application/xhtml+xml;charset=utf-8");
        } else {
            header("Content-type: text/xml;charset=utf-8");
        } $et = ">";
        echo "<?xml version='1.0' encoding='utf-8'?$et\n";
        echo $xml;

    }

    function datediff($a,$b)
    {
        $date1 = intval(substr($a,0,4))*12+intval(substr($a,5,2));
        $date2 = intval(substr($b,0,4))*12+intval(substr($b,5,2));
        return abs($date1-$date2); //abs pour eviter les resultas negatifs suivant l'ordre des arguments de la fonction
    }



    function calculateMonthlyAmortizingCost($totalLoan, $month, $interest )
    {
        $years = $month / 12;
        $tmp = pow((1 + ($interest / 1200)), ($years * 12));
        return round(($totalLoan * $tmp) * ($interest / 1200) / ($tmp - 1),2);
    }
    function calculateTotalAmortizingCost($totalLoan, $month, $interest )
    {
        $years = $month / 12;
        $tmp = pow((1 + ($interest / 1200)), ($years * 12));
        return round(($years*12*(($totalLoan * $tmp) * ($interest / 1200) / ($tmp - 1))-$totalLoan),2);
    }
    ?>