<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.1
  * Created on : 7-22-2009
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : socinfo_html-response.php
  * GLE-1.0
  */
require_once('../../main.inc.php');
require_once('../Campagne.class.php');
require_once(DOL_DOCUMENT_ROOT."/core/class/html.form.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/company.lib.php");
require_once(DOL_DOCUMENT_ROOT."/contact/class/contact.class.php");
require_once(DOL_DOCUMENT_ROOT."/commande/class/commande.class.php");
require_once(DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php");
require_once(DOL_DOCUMENT_ROOT."/comm/propal/class/propal.class.php");


$userId = $_REQUEST['userid'];
$campagne_id = $_REQUEST['campagneId'];
$socid = $_REQUEST['socid'];

$langs->load("synopsisGene@Synopsis_Tools");
$langs->load('companies');
$langs->load('compta');
$langs->load('orders');
$langs->load('propal');
$langs->load('other');
$langs->load('main');

$campagne = new Campagne($db);
$campagne->fetch($campagne_id);
$user=new User($db,$userId);
$user->id=$userId;
$user->fetch();
$user->getrights();

//duree par default
$durMonth = 12;

    if ($socid < 0)
    {
        //print Err

    }  else {


        // 1=> affiche tabs
        //     a) recap client
        //     b) calendrier
        //     c) document soc
        //     c) document
        //     d) ctrl de gestion
        //     e) stats
        //     f) action com (recap et new)
        print <<<EOF
        <div id="tabs">
            <ul>
                <li><a href="#fragment-Recap"><span>R&eacute;capitulatif</span></a></li>
                <li><a href="#fragment-Contact"><span>Contact</span></a></li>
                <li><a href="#fragment-Calendar"><span>Calendrier</span></a></li>
                <li><a href="#fragment-DocClients"><span>Documents clients</span></a></li>
                <li><a href="#fragment-DocGLE"><span>Documents GLE</span></a></li>
                <li><a href="#fragment-Stats"><span>Stats</span></a></li>
                <li><a href="#fragment-ActCom"><span>Action Com</span></a></li>
                <li><a href="#fragment-SuiviCom"><span>Suivi commercial</span></a></li>
            </ul>
EOF;
            print '<div id="fragment-Recap">';

            $societe = new Societe($db);
            $societe->id = $socid;
            $societe->fetch($socid);
            print "<table width=\"100%\">\n";
            print '<tr><td valign="top" width="50%">';
            print '<table class="border" width="100%">';
            // Nom
            print '<tr><td width="20%">'.$langs->trans("Name").'</td><td width="80%" colspan="3">'.htmlentities($societe->nom).'</td></tr>';

            // Prefix
            if ($noheader)
            {
                    print '<tr><td>'.$langs->trans("Prefix").'</td><td colspan="3">';
                print ($societe->prefix_comm?htmlentities($societe->prefix_comm):'&nbsp;');
                print '</td></tr>';

            } else {
                //info de base societe

                    print '<tr><td>'.$langs->trans("Adresse").'</td><td colspan="1">';
                    print ($societe->adresse_full?htmlentities($societe->adresse_full):'&nbsp;');
                    print '</td>';
                    print '<td>'.$langs->trans("D&eacute;partement").'</td><td colspan="1">';
                    print ($societe->departement?htmlentities($societe->departement):'&nbsp;');
                    print '</td></tr>';

                    print '<tr><td>'.$langs->trans("Secteur").'</td><td colspan="1">';
                    print ($societe->sect_libelle?htmlentities($societe->sect_libelle):'&nbsp;');
                    print '</td>';
                    print '<td>'.$langs->trans("Type").'</td><td colspan="1">';
                    print ($societe->typent_libelle?htmlentities($societe->typent_libelle):'&nbsp;');
                    print '</td></tr>';

                    print '<tr><td>'.$langs->trans("Effectif").'</td><td colspan="1">';
                    print ($societe->effectif?htmlentities($societe->effectif):'&nbsp;');
                    print '</td>';
                    print '<td>'.$langs->trans("Capital").'</td><td colspan="1">';
                    print ($societe->capital?price($societe->capital)."&euro;":'&nbsp;');
                    print '</td></tr>';

                    print '<tr><td>'.$langs->trans("URL").'</td><td colspan="1">';
                    print ($societe->url?'<a href="http://'.$societe->url.'">'.htmlentities($societe->nom).'</a>':'&nbsp;');
                    print '</td>';
                    print '<td>'.$langs->trans("Email soci&eacute;t&eacute;").'</td><td colspan="1">';
                    print ($societe->email?'<a href="mailto:'.$societe->email."\">".htmlentities($societe->email)."</a>":'&nbsp;');
                    print '</td></tr>';



            }

            // Duree
            print '<tr><td>'.$langs->trans("Dur&eacute;e").'</td><td colspan="1" align="center" style="width: 20px;">';
            print "<div style='float:left; vertical-align: middle; width:100%;' ><span id='duree' name='duree' >".$durMonth."</span> mois </div> ";
            print '<td colspan="3" style="line-height: 12px;">';

            $requeteCnt = "SELECT  max(TIMESTAMPDIFF(MONTH,  date_valid, now() )) AS timeDif
                             FROM ".MAIN_DB_PREFIX."propal
                            WHERE fk_soc = ".$socid;
            $resql = $societe->db->query($requeteCnt);
            $cntMax = 24;
            if ($resql)
            {
                $res = $societe->db->fetch_object($resql);
                $cntTmp = $res->timeDif;
                if ($cntMax < intval( $cntTmp ) )
                {
                    $cntMax = intval( $cntTmp );
                }
            }

            print ' <div style="float:left; font-weight: 900;  font-size: 12pt; ">&nbsp;-&nbsp;</div><div class="demo" style="float: left; padding-left: 10pt; width: 90%;">';
            print ' <div id="zoom_slider" class="slider" style="foat: left;">';
//            print '    <div style="position: absolute; color: #EEFEFF; font-weight: 800; top: -2pt; left: 0.5em;">-</div>';
//            print '    <div style="position: absolute; color: #EEFEFF; font-weight: 800; top: -2pt; right: 0.5em;">+</div>';
            print '  </div>';
            print '</div><div style="float:left; font-weight: 900; font-size: 12pt; ">&nbsp;&nbsp;+&nbsp;</div>';


            print '</td></tr>';


            print "</table>";

            print "<div>";

            print '<table  id="recapMainTable" class="border" width="100%"><thead>';

            $requete = "SELECT concat(day(".MAIN_DB_PREFIX."propal.date_valid),'/',month(".MAIN_DB_PREFIX."propal.date_valid), '/',year(".MAIN_DB_PREFIX."propal.date_valid)) as date_valid," .
                    "          ".MAIN_DB_PREFIX."propal.remise_percent," .
                    "          ".MAIN_DB_PREFIX."propal.remise_absolue," .
                    "          ".MAIN_DB_PREFIX."propal.remise," .
                    "          ifnull(year(".MAIN_DB_PREFIX."propal.date_valid),year(now()) + 1) as yearValid," .
                    "          ".MAIN_DB_PREFIX."propal.fk_user_author," .
                    "          ".MAIN_DB_PREFIX."propal.fk_user_valid," .
                    "          ".MAIN_DB_PREFIX."propal.fk_user_cloture," .
                    "          ".MAIN_DB_PREFIX."propal.ref," .
                    "          ".MAIN_DB_PREFIX."propal.rowid as pid," .
                    "          ".MAIN_DB_PREFIX."propal.total_ht," .
                    "          ".MAIN_DB_PREFIX."projet.title," .
                    "          ".MAIN_DB_PREFIX."propal.fk_statut " .
                    "     FROM ".MAIN_DB_PREFIX."propal
                               ".MAIN_DB_PREFIX."propal LEFT JOIN ".MAIN_DB_PREFIX."projet ON  ".MAIN_DB_PREFIX."projet.rowid = ".MAIN_DB_PREFIX."propal.fk_projet
                         WHERE ".MAIN_DB_PREFIX."propal.fk_soc=".$societe->id."
                           AND ".MAIN_DB_PREFIX."propal.datec > DATE_SUB(NOW(),INTERVAL $durMonth MONTH)
                       ORDER BY  yearValid desc, year(".MAIN_DB_PREFIX."propal.date_valid) desc , month(".MAIN_DB_PREFIX."propal.date_valid) desc, day(".MAIN_DB_PREFIX."propal.date_valid) desc";
        //    print $requete;
            echo "<tr><th colspan=14>Proposition des <span name='duree'>" . $durMonth ." </span> derniers mois";
            dol_syslog("Propal::Fetch last $durMonth months propales socid=".$societe->id);
            print "<tr>";
            print "    <th>Info";
            print "    <th>Date Validation";
            print "    <th>Projet";
            print "    <th>Ref Propale";
            print "    <th>Statut";
            print "    <th>Remise %";
            print "    <th>Remise Abs";
            print "    <th>Remise";
            print "    <th>Prix HT";
            print "    <th>Ref Commande";
            print "    <th>Statut Commande";
            print "    <th>Ref facture";
            print "    <th>Statut facture";
            print "    <th>Statut Paiement";
            print "</tr></thead><tbody id='recapMain'>";
            $contactArr=array();
            $resql=$societe->db->query($requete);
            $remPropal = array();
                if ($resql)
                {
                    if ($societe->db->num_rows($resql))
                    {
                        $im='';
                        while ($res=$societe->db->fetch_object($resql))
                        {
                            array_push($remPropal,$res->pid);
                            //commande et facture associée
                            $commandeHTMLArr = array();
                            $commandeStatutHTMLArr=array();
                            $factureHTMLArr = array();
                            $factureStatutHTMLArr = array();
                            $statutPayeArr = array();
                            $requeteCom = "SELECT *
                                             FROM ".MAIN_DB_PREFIX."co_pr
                                            WHERE fk_propale = ".$res->pid;
                            $resqlCom=$societe->db->query($requeteCom);
                            if ($resqlCom)
                            {
                                $com = new Commande($societe->db);
                                while ($resCom = $com->db->fetch_object($resqlCom))
                                {
                                    $com->fetch($resCom->fk_commande);
                                    if ($noheader)
                                    {
                                        array_push($commandeHTMLArr , "<a target=_top href='".DOL_URL_ROOT."/commande/fiche.php?id=".$com->id."'>".img_object('commande','order').$com->ref ."</a>");
                                    } else {
                                        array_push($commandeHTMLArr , "<a href='".DOL_URL_ROOT."/commande/fiche.php?id=".$com->id."'>".img_object('commande','order').$com->ref ."</a>");
                                    }

                                    array_push($commandeStatutHTMLArr , $com->getLibStatut(6));
                                    /*contact commande */
                                    $tmpArr =  $com->liste_contact(-1,'internal');
                                    foreach($tmpArr as $key=>$val)
                                    {
                                        array_push($contactArr,$val);
                                    }
                                    $codeArrCOM['user_validation']='COMMANDEAUTHOR';
                                    $codeArrCOM['user_validation']='COMMANDEVALID';
                                    $codeArrCOM['user_cloture']='COMMANDECLOTURE';

                                    $codeArrTCOM['user_validation']=$langs->trans('COMMANDEAUTHOR');
                                    $codeArrTCOM['user_validation']=$langs->trans('COMMANDEVALID');
                                    $codeArrTCOM['user_cloture']=$langs->trans('COMMANDECLOTURE');
                                    $com->info($com->id);
                                    foreach(array('user_creation' , 'user_validation', 'user_cloture') as $key=>$val)
                                    {
                                            if (is_object( $com->{$val}))
                                            {
                                                $tmpUserCreate = $com->{$val};
                                                array_push($contactArr,
                                                            array('source' => 'internal',
                                                                  'socid' => '-1',
                                                                  'id' => $com->{$val}->id,
                                                                  'nom' => $com->{$val}->fullname,
                                                                  "rowid" => -1,
                                                                  'code' => $codeArrCOM[$val],
                                                                  'libelle' => $codeArrTCOM[$val],
                                                                  'status' => $com->{$val}->statut + 4 ));
                                            }
                                    }


                                    $requeteFact1 = "SELECT *
                                                       FROM ".MAIN_DB_PREFIX."co_fa
                                                      WHERE fk_facture NOT IN (SELECT fk_facture
                                                                                 FROM ".MAIN_DB_PREFIX."fa_pr )
                                                        AND ".MAIN_DB_PREFIX."co_fa.fk_commande = ".$com->id;
                                    $resqlFac=$societe->db->query($requeteFact1);
                                    $statutPayeArr = array();
                                    if ($resqlFac)
                                    {
                                        $fac = new Facture($societe->db);
                                        while ($resFac = $fac->db->fetch_object($resqlFac))
                                        {
                                            $fac->fetch($resFac->fk_facture);
                                            if ($noheader)
                                            {
                                                array_push($factureHTMLArr , "<a target=_top href='".DOL_URL_ROOT."/compta/facture.php?facid=".$fac->id."'>".img_object('facture','bill') . $fac->ref ."</a>");
                                            } else {
                                                array_push($factureHTMLArr , "<a href='".DOL_URL_ROOT."/compta/facture.php?facid=".$fac->id."'>".img_object('facture','bill') . $fac->ref ."</a>");
                                            }

                                            array_push($factureStatutHTMLArr , $fac->getLibStatut(2));
                                            $PayStr = "";
                                            if ($fac->paye == 1)
                                            {
                                                $PayStr = img_picto("Pay&eacute;","tick");
                                            } else {
                                                $PayStr = img_picto("Non Pay&eacute;","stcomm-1");
                                            }
                                            array_push($statutPayeArr, $PayStr);

                                            /*contact facture */
                                            $tmpArr =  $fac->liste_contact(-1,'internal');
                                            foreach($tmpArr as $key=>$val)
                                            {
                                                array_push($contactArr,$val);
                                            }
                                            $codeArrFA['user_creation']='FACTUREAUTHOR';
                                            $codeArrFA['user_validation']='FACTUREVALID';

                                            $codeArrTFA['user_creation']=$langs->trans('FACTUREAUTHOR');
                                            $codeArrTFA['user_validation']=$langs->trans('FACTUREVALID');
                                            $fac->info($fac->id);
                                            foreach(array('user_creation' , 'user_validation') as $key=>$val)
                                            {
                                                if (is_object( $fac->{$val}))
                                                {
                                                    $tmpUserCreate = $fac->{$val};
                                                    array_push($contactArr,array('source' => 'internal',
                                                                          'socid' => '-1',
                                                                          'id' => $fac->{$val}->id,
                                                                          'nom' => $fac->{$val}->fullname,
                                                                          "rowid" => -1,
                                                                          'code' => $codeArrFA[$val],
                                                                          'libelle' => $codeArrTFA[$val],
                                                                          'status' => $fac->{$val}->statut + 4 ));
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                            $commandeHTML = join($commandeHTMLArr,"<HR/>");
                            if (strlen($commandeHTML) < 1) { $commandeHTML = "&nbsp;";}
                            $commandeStatutHTML = join($commandeStatutHTMLArr,"<HR/>");
                            if (strlen($commandeHTML) < 1) { $commandeHTML = "&nbsp;";}

                            $requeteFact = "SELECT *
                                              FROM ".MAIN_DB_PREFIX."fa_pr
                                             WHERE fk_propal = ".$res->pid;
                            $resqlFac=$societe->db->query($requeteFact);
                            if ($resqlFac)
                            {
                                $fac = new Facture($societe->db);
                                while ($resFac = $fac->db->fetch_object($resqlFac))
                                {
                                    $fac->fetch($resFac->fk_facture);
                                    if ($noheader)
                                    {
                                        array_push($factureHTMLArr , "<a target=_top href='".DOL_URL_ROOT."/compta/facture.php?facid=".$fac->id."'>".img_object('facture','bill').$fac->ref ."</a>");
                                    } else {
                                        array_push($factureHTMLArr , "<a href='".DOL_URL_ROOT."/compta/facture.php?facid=".$fac->id."'>".img_object('facture','bill').$fac->ref ."</a>");
                                    }
                                    array_push($factureStatutHTMLArr , $fac->getLibStatut(2));
                                    $PayStr = "";
                                    if ($fac->paye == 1)
                                    {
                                        $PayStr = img_picto("Pay&eacute;","tick");
                                    } else {
                                        $PayStr = img_picto("Non Pay&eacute;","stcomm-1");
                                    }
                                    array_push($statutPayeArr, $PayStr);
                                    /* contact facture */
                                    $tmpArr =  $fac->liste_contact(-1,'internal');
                                    foreach($tmpArr as $key=>$val)
                                    {
                                        array_push($contactArr,$val);
                                    }
                                    $codeArrFA['user_validation']='FACTUREAUTHOR';
                                    $codeArrFA['user_validation']='FACTUREVALID';

                                    $codeArrTFA['user_validation']=$langs->trans('FACTUREAUTHOR');
                                    $codeArrTFA['user_validation']=$langs->trans('FACTUREVALID');

                                    $fac->info($fac->id);
                                    foreach(array('user_creation' , 'user_validation') as $key=>$val)
                                    {
                                        if (is_object( $fac->{$val}))
                                        {
                                            $tmpUserCreate = $fac->{$val};
                                            array_push($contactArr,array('source' => 'internal',
                                                                  'socid' => '-1',
                                                                  'id' => $fac->{$val}->id,
                                                                  'nom' => $fac->{$val}->fullname,
                                                                  "rowid" => -1,
                                                                  'code' => $codeArrFA[$val],
                                                                  'libelle' => $codeArrTFA[$val],
                                                                  'status' => $fac->{$val}->statut + 4 ));
                                        }
                                    }
                                }
                            }


                            $factureHTML = join($factureHTMLArr,"<HR/>");
                            if (strlen($factureHTML) < 1) { $factureHTML = "&nbsp;";}
                            $factureStatutHTML = join($factureStatutHTMLArr,"<HR/>");
                            if (strlen($factureStatutHTML) < 1) { $factureStatutHTML = "&nbsp;";}
                            $statutPaye = join($statutPayeArr,"<HR/>");
                            if (strlen($statutPaye) < 1) { $statutPaye = "&nbsp;";}

                            $prop=new Propal($db);
                            $prop->statut = $res->fk_statut;
                            $propStatut = $prop->getLibStatut(2);


                            print "<tr>";
                            if ($noheader)
                            {
                                print   "<td  class='".$im."pair' onMouseOver='parent.SOCID = SOCID ;parent.showContact($res->pid,this);' align='center' width='5px'>".img_picto('Infos','info')."</td>";

                            } else {
                                print   "<td  class='".$im."pair' onMouseOver='showContact($res->pid,this);' align='center' width='5px'>".img_picto('Infos','info')."</td>";
                            }
                            print   "<td id='date_valid_".$res->pid."' width='30px'  align='center' class='".$im."pair'>$res->date_valid</td>" .
                                    "<td id='title_".$res->pid."' width='auto' class='".$im."pair'>$res->title</td>";
                            if ($noheader)
                            {
                                print "  <td id='ref_".$res->pid."' width='100px' class='".$im."pair'><a target=_top href='".DOL_URL_ROOT."/comm/propal.php?propalid=".$res->pid."'>".img_object('propale','propal')."$res->ref</a></td>";
                            } else {
                                print "  <td id='ref_".$res->pid."' width='100px' class='".$im."pair'><a href='".DOL_URL_ROOT."/comm/propal.php?propalid=".$res->pid."'>".img_object('propale','propal')."$res->ref</a></td>";
                            }
                            print "  <td id='propalStatut_".$res->pid."' width='80px'  align='left'  class='".$im."pair'>$propStatut</td>" .
                                    "<td id='remise_percent_".$res->pid."' width='50px' align='center' class='".$im."pair'>$res->remise_percent</td>" .
                                    "<td id='remise_absolue_".$res->pid."' width='50px' align='center' class='".$im."pair'>$res->remise_absolue</td>" .
                                    "<td id='remise_".$res->pid."' width='50px' align='center' class='".$im."pair'>".price($res->remise,1,"","","0",0)." &euro;</td>" .
                                    "<td id='total_ht_".$res->pid."' width='80px'  align='center' class='".$im."pair'>".price($res->total_ht,1,"","","0",0)." &euro;</td>";
                        print "     <td id='commande_".$res->pid."'  width='100px' class='".$im."pair'>".$commandeHTML."</td>";
                        print "     <td id='commandeStatut_".$res->pid."'  width='80px' class='".$im."pair'>$commandeStatutHTML</td>";
                        print "     <td id='facture_".$res->pid."'  width='100px' class='".$im."pair'>$factureHTML</td>";
                        print "     <td id='factureStatut_".$res->pid."'  width='80px' class='".$im."pair'>$factureStatutHTML</td>";
                        print "     <td id='paye_".$res->pid."'  width='40px' align='center' class='".$im."pair'>$statutPaye</td>";

                        print "</tr>";
                        if ($im ."x"== "x"){ $im="im"; } else { $im="";}

                        /* contact */
                        /*
                         *
                         *      createur
                         *           propal*
                         *           commande
                         *           facture,
                         *      cloturateur
                         *           propal*
                         *           commande
                         *           facture,
                         *       validateur
                         *           propal*
                         *           commande
                         *           facture,
                         *       statut contact*
                         *           actuel*
                         *           moment du debut de la propal*
                         *       interlocuteur
                         *           interne*
                         *           externe*
                         *        commercial associe a la societe
                         */
                         // interlocuteurs :
                         //propal
                        $prop->id = $res->pid;
                        $arr=array();
                        $arr =  $prop->liste_contact(-1,'internal');
                        //var_dump($arr);
                        $codeArr['fk_user_author']='PROPALAUTHOR';
                        $codeArr['fk_user_valid']='PROPALVALID';
                        $codeArr['fk_user_cloture']='PROPALCLOTURE';

                        $codeArrT['fk_user_author']=$langs->trans('PROPALAUTHOR');
                        $codeArrT['fk_user_valid']=$langs->trans('PROPALVALID');
                        $codeArrT['fk_user_cloture']=$langs->trans('PROPALCLOTURE');

                        $tmpArr =  $prop->liste_contact(-1,'internal');
                        foreach($arr as $key=>$val)
                        {
                            array_push($contactArr,$val);
                        }

                        foreach(array('fk_user_author' , 'fk_user_valid' , 'fk_user_cloture') as $key=>$val)
                        {
                            if ($res->{$val})
                            {
                                $tmpUserCreate = new User($db);
                                $tmpUserCreate->id =$res->{$val};
                                $tmpUserCreate->fetch();
        //                        var_dump($tmpUserCreate);
                                array_push($contactArr,array('source' => 'internal',
                                                      'socid' => '-1',
                                                      'id' => $res->{$val},
                                                      'nom' => $tmpUserCreate->fullname,
                                                      "rowid" => -1,
                                                      'code' => $codeArr[$val],
                                                      'libelle' => $codeArrT[$val],
                                                      'status' => $tmpUserCreate->statut + 4 ));
                            }
                        }
                    }
                }
            }
            print "</td></tr></tbody></table>\n";
            print "</div>";

        //
        //
        //    require_once('Var_Dump.php');
        //    Var_Dump::displayInit(array('display_mode' => 'HTML4_Text'), array('mode' => 'normal','offset' => 4));
        //    Var_Dump::Display($contactArr);


            print "<BR>";
            print "<div  style='float: left; width: 49%;'>";
            print '<table  id="recapProdTable" class="border" width="100%"><thead>';
            echo "<tr><th colspan=2>Les produits les plus propos&eacute;s ces <span name='duree'>" . $durMonth ." </span> derniers mois</th></tr>";
            print " <tr><TH>Qt&eacute;</TH>";
            print " <TH>D&eacute;signation</TH></thead><tbody id='recapProd'>";

            $sqlJoin = join($remPropal,",");
            $requete = "SELECT count(llx_product.rowid) as cnt," .
                    "          llx_product.description" .
                    "     FROM ".MAIN_DB_PREFIX."propaldet," .
                    "          llx_product" .
                    "    WHERE llx_product.rowid = ".MAIN_DB_PREFIX."propaldet.fk_product " .
                    "      AND fk_propal in ($sqlJoin)" .
                    "      AND ".MAIN_DB_PREFIX."propaldet.fk_product <> 0  AND llx_product.fk_product_type = 0 " .
                    " GROUP BY llx_product.rowid" .
                    " ORDER BY count(llx_product.rowid) DESC LIMIT 25";
            $resql=$societe->db->query($requete);
            if ($resql)
            {
                if ($societe->db->num_rows($resql) > 0)
                {
                    $im='';
                    while ($res=$societe->db->fetch_object($resql))
                    {
                        print "<TR><td align='center' id='ProdQty' style='width: 100px;' class='".$im."pair'>".$res->cnt."</td><td id='ProdDesc' class='".$im."pair'>".$res->description."</td></TR>";
                        if ($im ."x"== "x"){ $im="im"; } else { $im="";}
                    }
                }
            }

            print "</tbody></table>";
            print "</div>";
            print "<div style='padding-left: 10pt; float: left; width: 49%;'>";

            print '<table  id="recapServTable" class="border" width="100%"><thead>';
            echo "<tr><th colspan=2>Les services les plus propos&eacute;s ces <span name='duree'>" . $durMonth ." </span> derniers mois</th></tr>";
            print " <tr><TH>Qt&eacute;</TH>";
            print " <TH>D&eacute;signation</TH>";
            print "</thead><tbody id='recapServ'>";

            $sqlJoin = join($remPropal,",");
            $requete = "SELECT count(llx_product.rowid) as cnt," .
                    "          llx_product.description" .
                    "     FROM ".MAIN_DB_PREFIX."propaldet," .
                    "          llx_product" .
                    "    WHERE llx_product.rowid = ".MAIN_DB_PREFIX."propaldet.fk_product " .
                    "      AND fk_propal in ($sqlJoin)" .
                    "      AND ".MAIN_DB_PREFIX."propaldet.fk_product <> 0
                           AND llx_product.fk_product_type = 1 " .
                    " GROUP BY llx_product.rowid" .
                    " ORDER BY count(llx_product.rowid) DESC LIMIT 25";
            $resql=$societe->db->query($requete);
            if ($resql)
            {
                if ($societe->db->num_rows($resql) > 0)
                {
                    $im='';
                    while ($res=$societe->db->fetch_object($resql))
                    {
                        print "<TR><td align='center' class='".$im."pair'>".$res->cnt."</td><td class='".$im."pair'>".$res->description."</td></TR>";
                        if ($im ."x"== "x"){ $im="im"; } else { $im="";}
                    }
                }
            }
            print "</tbody>";
            print "</table>";
            print '</div>';
            print '</div><tr><td/><tr><td>';



            print "<br><br>";
            print "<div style=' float: left; width: 100%;'>";
            print '<table  id="recapContratTable" class="border" width="100%">';
            echo "<tr><th colspan=5>Les contrats du client</th></tr>";
            print " <tr>";
            print " <TH>D&eacute;signation</TH>";
            print " <TH>Date</TH>";
            print " <TH>Projet</TH>";
            print " <TH>Li&eacute; &agrave;</TH>";
            print " <TH>Statut</TH>";

            $requete = "SELECT ".MAIN_DB_PREFIX."contrat.ref,
                               date_format(".MAIN_DB_PREFIX."contrat.date_contrat,'%d/%m/%Y') as date_contrat,
                               ".MAIN_DB_PREFIX."contrat.rowid as cid,
                               ".MAIN_DB_PREFIX."projet.title,
                               ".MAIN_DB_PREFIX."contrat.linkedTo,
                               ".MAIN_DB_PREFIX."contrat.statut
                          FROM ".MAIN_DB_PREFIX."contrat LEFT JOIN ".MAIN_DB_PREFIX."projet on ".MAIN_DB_PREFIX."contrat.fk_projet = ".MAIN_DB_PREFIX."projet.rowid
                         WHERE  ".MAIN_DB_PREFIX."contrat.fk_soc = ".$societe->id;
        //                 print $requete;
            if ($resql = $db->query($requete))
            {
                while ($res=$db->fetch_object($resql))
                {
                    print "<tr>";
                    print "<td><a href='".DOL_URL_ROOT."/contrat/fiche.php?id=".$res->cid."'>".img_object('contract','contract')." ".$res->ref."</a>";
                    print "<td align='center'>".$res->date_contrat;
                    print "<td>".$res->title;
                    print "<td width= 100px><table width=100%  class='nobordernopadding' width='100%'>";
                    require_once(DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php');
                    $contrat = new Contrat($db);
                    $contrat->fetch($res->cid);
                    if ($contrat->linkedTo)
                    {
                        if (preg_match('/^([c|p|f]{1})([0-9]*)/',$contrat->linkedTo,$arr))
                        {
                            print '<tr><td><table class="nobordernopadding" style="width:100%;">';
                            print '<tr> ';
                            $val1 = $arr[2];
                            switch ($arr[1])
                            {
                                case 'c':
                                    print '';
                                    print '<td align="right" style="width:20px"><a href="'.$_SERVER["PHP_SELF"].'?action=chSrc&amp;id='.$id.'">'.img_edit($langs->trans("Change la source")).'</a>';
                                    require_once(DOL_DOCUMENT_ROOT."/commande/class/commande.class.php");
                                    $comm = new Commande($db);
                                    $comm->fetch($val1);
                                    print "</table><td><a href='".DOL_URL_ROOT."/commande/fiche.php?id=".$comm->id."'>".$comm->ref."</a>";
                                break;
                                case 'f':
                                    print '';
                                    print '<td align="right" style="width:20px"><a href="'.$_SERVER["PHP_SELF"].'?action=chSrc&amp;id='.$id.'">'.img_edit($langs->trans("Change la source")).'</a>';
                                    require_once(DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php");
                                    $fact = new Facture($db);
                                    $fact->fetch($val1);
                                    print "</table><td><a href='".DOL_URL_ROOT."/compta/facture.php?facid=".$fact->id."'>".$fact->ref."</a>";
                                break;
                                case 'p':
                                    print '';
                                    print '<td align="right" style="width:20px"><a href="'.$_SERVER["PHP_SELF"].'?action=chSrc&amp;id='.$id.'">'.img_edit($langs->trans("Change la source")).'</a>';
                                    require_once(DOL_DOCUMENT_ROOT."/comm/propal/class/propal.class.php");
                                    $prop = new Propal($db);
                                    $prop->fetch($val1);
                                    print "</table><td><a href='".DOL_URL_ROOT."/comm/propal.php?propalid=".$prop->id."'>".$prop->ref."</a>";
                                break;
                            }
                        }
                    }

                //ajoute le lien vers les propal / commande / facture
                foreach($contrat->linkedArray as $key=>$val)
                {
        //            print $key;
                    if ($key=='co')
                    {
                        foreach($val as $key1=>$val1)
                        {
                                print '<tr><td>';
                                print 'Commandes associ&eacute;es<td>';
                                require_once(DOL_DOCUMENT_ROOT."/commande/class/commande.class.php");
                                $comm = new Commande($db);
                                $comm->fetch($val1);
                                print "<a href='".DOL_URL_ROOT."/commande/fiche.php?id=".$comm->id."'>".$comm->ref."</a>";
                        }
                    } else if ($key=='fa') {
                        foreach($val as $key1=>$val1)
                        {
                                print '<tr><td>';
                                print 'Factures associ&eacute;es<td>';
                                require_once(DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php");
                                $fac = new Facture($db);
                                $fac->fetch($val1);
                                print "<a href='".DOL_URL_ROOT."/compta/facture.php?facid=".$fac->id."'>".$fac->ref."</a>";
                        }
                    }else if ($key=='pr') {
                        foreach($val as $key1=>$val1)
                        {
                                print '<tr><td>';
                                print 'Propositions associ&eacute;es<td>';
                                require_once(DOL_DOCUMENT_ROOT."/comm/propal/class/propal.class.php");
                                $prop = new Propal($db);
        //                        print 'tutu';
                                $prop->fetch($val1);
                                print "<a href='".DOL_URL_ROOT."/comm/propal.php?propalid=".$prop->id."'>".$prop->ref."</a>";
                         }
                    }
                }
                print "</table>";
                print "<td>".$contrat->getLibStatut(5) ."<BR>". $contrat->getLibStatut(4);
                }

            }
                print "</table>";
                print "</table>";
            print '</div>';
            print '<div id="fragment-Calendar">';

//var_dump($user);
        if (!$user->rights->SynopsisZimbra->AfficheCal)
        {
            //var_dump($user->rights);
            print "Ce module ne vous est pas accessible";
        } else {
                print '<table width="100%"><tr><td>';
                //dol_print_object_info($societe);

                $PREAUTH_KEY = ZIMBRA_PREAUTH;
                $ZIMBRA_HOST = ZIMBRA_HOST;
                $ZIMBRA_DOMAIN = ZIMBRA_DOMAIN;
                $ZIMBRA_PROTO = ZIMBRA_PROTO;
                $userZim = $_SESSION["dol_login"];
                $domain = $ZIMBRA_DOMAIN;
                $email = "{$userZim}@{$domain}";
                $getUrl = "";
                foreach($_REQUEST as $key=>$val)
                {
                    if (preg_match("/^DOLSESSID/",$key) || preg_match("/^webcalendar/",$key) ||preg_match("/^action/",$key) )
                    {
                        continue;
                    }
                    $getUrl .= "&".$key."=".$val;
                }
                $arrAlpha=array(0=>'abc',1=>'def',2=>'ghi',3=>'jkl',4=>'mno', 5=>'pqrs', 6=>'tuv', 7=>'wxyz', 8=>'autres');
                $firstLetter = $societe->nom;
                $firstLetterA = $firstLetter[0];
                $firstLetterIn=$arrAlpha[8];
                for($i=0;$i<8;$i++)
                {
                    if (preg_match("/".$firstLetterA."/i",$arrAlpha[$i]))
                    {
                        $firstLetterIn = $arrAlpha[$i];
                    }
                }
                $urlRoot = $conf->global->ZIMBRA_PROTO.'://'.$conf->global->ZIMBRA_HOST."/home/gle";
                $url = $conf->global->ZIMBRA_PROTO.'://'.$conf->global->ZIMBRA_HOST."/home/gle/Calendriers%20-%20GLE"."/"."Soci%C3%A9t%C3%A9s"."/"."abc"."/".rawurlencode($societe->nom."-".$socid.".html")."?view=month";

                $url1 = "/home/gle/Calendriers%20-%20GLE"."/"."Soci%C3%A9t%C3%A9s"."/".$firstLetterIn."/".rawurlencode($societe->nom."-".$socid.".html")."?view=month";
                $url2 = "/home/gle/Calendriers%20-%20GLE"."/"."Soci%C3%A9t%C3%A9s"."/".$firstLetterIn."/".rawurlencode($societe->nom."-".$socid)."/Propales.html"."?view=month";
                $url3 = "/home/gle/Calendriers%20-%20GLE"."/"."Soci%C3%A9t%C3%A9s"."/".$firstLetterIn."/".rawurlencode($societe->nom."-".$socid)."/Commandes.html"."?view=month";
                $url4 = "/home/gle/Calendriers%20-%20GLE"."/"."Soci%C3%A9t%C3%A9s"."/".$firstLetterIn."/".rawurlencode($societe->nom."-".$socid)."/Factures.html"."?view=month";
                $url5 = "/home/gle/Calendriers%20-%20GLE"."/"."Soci%C3%A9t%C3%A9s"."/".$firstLetterIn."/".rawurlencode($societe->nom."-".$socid)."/Expeditions.html"."?view=month";
                $url6 = "/home/gle/Calendriers%20-%20GLE"."/"."Soci%C3%A9t%C3%A9s"."/".$firstLetterIn."/".rawurlencode($societe->nom."-".$socid)."/Contrats.html"."?view=month";
                $url7 = "/home/gle/Calendriers%20-%20GLE"."/"."Soci%C3%A9t%C3%A9s"."/".$firstLetterIn."/".rawurlencode($societe->nom."-".$socid)."/Actions.html"."?view=month";
                $url8 = "/home/gle/Calendriers%20-%20GLE"."/"."Soci%C3%A9t%C3%A9s"."/".$firstLetterIn."/".rawurlencode($societe->nom."-".$socid)."/Interventions.html"."?view=month";

                $url9 = "/home/".$user->login."/"."Calendriers%20-%20GLE"."/"."Soci%C3%A9t%C3%A9s"."/".$firstLetterIn."/".rawurlencode($societe->nom."-".$socid)."/Commandes%20fournisseur.html"."?view=month";
                $url10 = "/home/".$user->login."/"."Calendriers%20-%20GLE"."/"."Soci%C3%A9t%C3%A9s"."/".$firstLetterIn."/".rawurlencode($societe->nom."-".$socid)."/Factures%20fournisseur.html"."?view=month";


                $arrAlpha=array(0=>'abc',1=>'def',2=>'ghi',3=>'jkl',4=>'mno', 5=>'pqrs', 6=>'tuv', 7=>'wxyz', 8=>'autres');
                $urlM = $url1;
                //if ($_REQUEST['showPart'] == "Propale")
                //{
                //    $urlM = $url2;
                //} elseif ($_REQUEST['showPart'] == "Commande")
                //{
                //    $urlM = $url3;
                //}


                print "<a onclick='showPart(\"Tout\")' href='#' class='butAction ui-corner-top ' style='margin-right: 5pt; font-size: 120%; height: 120%;' >Tout</a> ";
                if ($societe->client >0)
                {
                    print "<a onclick='showPart(\"Propales\")' href='#' class='butAction ui-corner-top ' style='margin-right: 2pt;margin-left: 5pt;'>Propales</a> ";
                    print "<a onclick='showPart(\"Commandes\")' href='#' class='butAction ui-corner-top ' style='margin-right: 2pt;margin-left: 2pt;'> Commandes</a> ";
                    print "<a onclick='showPart(\"Factures\")' href='#' class='butAction ui-corner-top ' style='margin-right: 2pt;margin-left: 2pt;'>Factures</a> ";
                    print "<a onclick='showPart(\"Expeditions\")' href='#' class='butAction ui-corner-top ' style='margin-right: 2pt;margin-left: 2pt;'>Exp&eacute;ditions / Livraisons</a> ";
                    print "<a onclick='showPart(\"Contrat\")' href='#' class='butAction ui-corner-top ' style='margin-right: 2pt;margin-left: 2pt;'>Contrats</a> ";
                    print "<a onclick='showPart(\"ActionCom\")' href='#' class='butAction ui-corner-top ' style='margin-right: 2pt;margin-left: 2pt;'>Actions commerciales</a> ";
                    print "<a onclick='showPart(\"Intervention\")' href='#' class='butAction ui-corner-top ' style='margin-right: 2pt;margin-left: 2pt;'>Interventions</a> ";
                }
                if ($societe->fournisseur > 0)
                {
                    print "<a onclick='showPart(\"CommandeFourn\")' href='#;' class='butAction ui-corner-top ' style='margin-right: 2pt;margin-left: 2pt;'>Commandes fournisseur</a> ";
                    print "<a onclick='showPart(\"FactureFourn\")' href='#' class='butAction ui-corner-top ' style='margin-right: 2pt;margin-left: 2pt;'>Factures fournisseur</a> ";
                }

                print "<script type='text/javascript'>";
                require_once(DOL_DOCUMENT_ROOT."/Synopsis_Zimbra/ZimbraSoap.class.php");
                //$zim=new Zimbra("eos");
                $zimuser="";
                if ($conf->global->ZIMBRA_ZIMBRA_USE_LDAP=="true")
                {
                    $zimuser=$user->login;
                } else {
                    $user->getZimbraCred($user->id);
                    $zimuser=$user->ZimbraLogin;
                }
                $zim = new Zimbra("gle");
                $zim->connect();
                $authTok = $zim->auth_token;

                $preauthURL1 = $zim->_protocol."".$zim->_server."/service/preauth?isredirect=1&authtoken=".$authTok."&redirectURL=".urlencode($url1);
                $preauthURL2 = $zim->_protocol."".$zim->_server."/service/preauth?isredirect=1&authtoken=".$authTok."&redirectURL=".urlencode($url2);
                $preauthURL3 = $zim->_protocol."".$zim->_server."/service/preauth?isredirect=1&authtoken=".$authTok."&redirectURL=".urlencode($url3);
                $preauthURL4 = $zim->_protocol."".$zim->_server."/service/preauth?isredirect=1&authtoken=".$authTok."&redirectURL=".urlencode($url4);
                $preauthURL5 = $zim->_protocol."".$zim->_server."/service/preauth?isredirect=1&authtoken=".$authTok."&redirectURL=".urlencode($url5);
                $preauthURL6 = $zim->_protocol."".$zim->_server."/service/preauth?isredirect=1&authtoken=".$authTok."&redirectURL=".urlencode($url6);
                $preauthURL7 = $zim->_protocol."".$zim->_server."/service/preauth?isredirect=1&authtoken=".$authTok."&redirectURL=".urlencode($url7);
                $preauthURL8 = $zim->_protocol."".$zim->_server."/service/preauth?isredirect=1&authtoken=".$authTok."&redirectURL=".urlencode($url8);
                $preauthURL9 = $zim->_protocol."".$zim->_server."/service/preauth?isredirect=1&authtoken=".$authTok."&redirectURL=".urlencode($url9);
                $preauthURL10 = $zim->_protocol."".$zim->_server."/service/preauth?isredirect=1&authtoken=".$authTok."&redirectURL=".urlencode($url10);

                print <<<EOF
                function showPart(whichPart)
                {
                    var url1 = "$preauthURL1";
                    var url2 = "$preauthURL2";
                    var url3 = "$preauthURL3";
                    var url4 = "$preauthURL4";
                    var url5 = "$preauthURL5";
                    var url6 = "$preauthURL6";
                    var url7 = "$preauthURL7";
                    var url8 = "$preauthURL8";
                    var url9 = "$preauthURL9";
                    var url10 = "$preauthURL10";
                    var url = url1;
                    if (whichPart == 'Propales')
                    {
                        url = url2;
                    } else if  (whichPart == 'Commandes')
                    {
                        url=url3;
                    } else if (whichPart == 'Factures')
                    {
                        url=url4;
                    } else if (whichPart == 'Expeditions')
                    {
                        url=url5;
                    } else if (whichPart == 'Contrat')
                    {
                        url=url6;
                    } else if (whichPart == 'ActionCom')
                    {
                        url=url7;
                    } else if (whichPart == 'Intervention')
                    {
                        url=url8;
                    }else if (whichPart == 'CommandeFourn')
                    {
                        url=url9;
                    }else if (whichPart == 'FactureFourn')
                    {
                        url=url10;
                    }
                //    alert(url);
                    document.getElementById('if').src=url;
                    document.getElementById('if').setAttribute('src')=url;
                }
EOF;
                print "</script>";

                print "<iframe id='if' scrolling='no'
                               style='overflow: hidden;
                               width:1300px;
                               height: 820px;'
                               src='".$preauthURL1."'>";
                print "</iframe>";

                //src='".DOL_URL_ROOT."/Babel_Calendar/cal_per_soc.php?societe_id=".$socid.$getUrl."'>";
                print '</td></tr></table>';

                print '</div>';

                print '<div class="tabsAction">';
                print '</div>';
        }
        print '<div id="fragment-DocClients">';

        // Construit liste des fichiers
        require_once(DOL_DOCUMENT_ROOT."/core/lib/company.lib.php");
        require_once(DOL_DOCUMENT_ROOT."/core/lib/files.lib.php");
        require_once(DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php");

        if (! $sortorder) $sortorder="ASC";
        if (! $sortfield) $sortfield="name";
        if ($page == -1) { $page = 0 ; }
        $offset = $conf->liste_limit * $page ;
        $pageprev = $page - 1;
        $pagenext = $page + 1;

        $sortorder=$_GET["sortorder"];
        $sortfield=$_GET["sortfield"];
        if (! $sortorder) $sortorder="ASC";
        if (! $sortfield) $sortfield="name";

        $upload_dir = $conf->societe->dir_output . "/" . $socid ;
        $courrier_dir = $conf->societe->dir_output . "/courrier/" . get_exdir($socid) ;

        $filearray=dol_dir_list($upload_dir,"files",0,'','\.meta$',$sortfield,(strtolower($sortorder)=='desc'?SORT_ASC:SORT_DESC),1);
        $totalsize=0;
        foreach($filearray as $key => $file)
        {
            $totalsize+=$file['size'];
        }

        print '<table class="border"width="100%">';
        // Ref
        print '     <tr><td width="30%">'.$langs->trans("Name").'</td><td colspan="3">'.$societe->nom.'</td></tr>';
        // Prefix
        print '     <tr><td>'.$langs->trans('Prefix').'</td><td colspan="3">'.$societe->prefix_comm.'</td></tr>';
        // Nbre fichiers
        print '     <tr><td>'.$langs->trans("NbOfAttachedFiles").'</td><td colspan="3">'.sizeof($filearray).'</td></tr>';
        //Total taille
        print '     <tr><td>'.$langs->trans("TotalSizeOfAttachedFiles").'</td><td colspan="3">'.$totalsize.' '.$langs->trans("octets").'</td></tr>';
        print '</table>';

        if ($mesg) { print "$mesg<br>"; }

        // Affiche formulaire upload
        $formfile=new FormFile($db);
        $formfile->form_attach_new_file(DOL_URL_ROOT.'/societe/document.php?socid='.$socid);
        // List of document
        $param='&socid='.$societe->id;
        $formfile->list_of_documents($filearray,$societe,'societe',$param);
        //Download all docs via zip
        print "<br>";
        print '<form action="'.DOL_URL_ROOT.'/societe/document?socid='.$socid.'" method="POST">';
        print '<input type="hidden" name="societe_id" value="'.$socid.'"/>';
        print '<input type="hidden" name="SynAction" value="dlZip"/>';
        print '<input class="button" type="submit" value="'.$langs->trans('DownloadZip').'"/>';
        print '</form>';
        print "<br><br>";

print '</div>';
print '            <div id="fragment-Stats">';


//Affiche les stats societes
                require_once(DOL_DOCUMENT_ROOT."/comm/propal/stats/propalestats.class.php");
                require_once(DOL_DOCUMENT_ROOT."/commande/stats/commandestats.class.php");
                require_once(DOL_DOCUMENT_ROOT."/core/dolgraph.class.php");

                //require_once(DOL_DOCUMENT_ROOT."/core/modules/propale/modules_propale.php");
                require_once(DOL_DOCUMENT_ROOT."/core/lib/company.lib.php");


                $WIDTH=500;
                $HEIGHT=200;

            print_fiche_titre($langs->trans("ProposalsStatistics")." ".$societe->nom, $mesg);

            $stats = new PropaleStats($db);
            $year = strftime("%Y", time());
            $startyear=$year-2;
            $endyear=$year;
            $data = $stats->getNbByMonthWithPrevYearPerSoc($endyear,$startyear,$societe->id);

            dol_mkdir($conf->propal->dir_temp);

            if (!$user->rights->societe->client->voir || $user->societe_id)
            {
                $filename = $conf->propal->dir_temp.'/nbpropale2year-'.$user->id.'-'.$year.'.png';
                $fileurl = DOL_URL_ROOT.'/viewimage.php?modulepart=propalstats&file=nbpropale2year-'.$user->id.'-'.$year.'.png';
            }
            else
            {
                $filename = $conf->propal->dir_temp.'/nbpropale2year-'.$year.'.png';
                $fileurl = DOL_URL_ROOT.'/viewimage.php?modulepart=propalstats&file=nbpropale2year-'.$year.'.png';
            }

            $px = new DolGraph();
            $mesg = $px->isGraphKo();
            if (! $mesg)
            {
                $px->SetData($data);
                $px->SetPrecisionY(0);
                $i=$startyear;
                while ($i <= $endyear)
                {
                    $legend[]=$i;
                    $i++;
                }
                $px->SetLegend($legend);
                $px->SetMaxValue($px->GetCeilMaxValue());
                $px->SetWidth($WIDTH);
                $px->SetHeight($HEIGHT);
                $px->SetYLabel($langs->trans("NbOfProposals"));
                $px->SetShading(3);
                $px->SetHorizTickIncrement(1);
                $px->SetPrecisionY(0);
                $px->mode='depth';
                $px->draw($filename);
            }

            $sql = "SELECT count(*) as nb, date_format(p.datep,'%Y') as dm, sum(p.total) as total_ttc";
            if (!$user->rights->societe->client->voir && !$user->societe_id) $sql .= ", sc.fk_soc, sc.fk_user";
            $sql.= " FROM ".MAIN_DB_PREFIX."propal as p";
            if (!$user->rights->societe->client->voir && !$user->societe_id) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
            $sql.= " WHERE fk_statut > 0";
            if (!$user->rights->societe->client->voir && !$user->societe_id) $sql .= " AND p.fk_soc = sc.fk_soc AND sc.fk_user = " .$user->id;
            if($user->societe_id)
            {
               $sql .= " AND p.fk_soc = ".$user->societe_id;
            }
               $sql .= " AND p.fk_soc = ".$societe->id;
            $sql.= " GROUP BY dm DESC ";
            $result=$db->query($sql);
            if ($result)
            {
              $num = $db->num_rows($result);
              print '<table class="border" width="100%" cellspacing="0" cellpadding="2">';
              print '<tr><td align="center">'.$langs->trans("Year").'</td><td width="10%" align="center">'.$langs->trans("NbOfProposals").'</td><td align="center">'.$langs->trans("AmountTotal").'</td>';
              print '<td align="center" valign="top" rowspan="'.($num + 1).'">';

              if ($mesg)
              {
                print "$mesg";
              }
              else
              {
                print '<img src="'.$fileurl.'" alt="Nombre de proposition par mois">';
              }

              print '</td></tr>';
              $i = 0;
              while ($i < $num)
                {
                  $obj = $db->fetch_object($result);
                  $nbproduct = $obj->nb;
                  $year = $obj->dm;
                  print "<tr>";
                  print '<td align="center"><a href="month.php?year='.$year.'">'.$year.'</a></td>';
                  print '<td align="center">'.$nbproduct.'</td>';
                  print '<td align="center">'.price(round($obj->total_ttc,2)).'&euro;</td></tr>';
                  $i++;
                }

              print '</table>';
              $db->free($result);
            }
            else
            {
              dol_print_error($db);
            }


            //Commande
            print_fiche_titre($langs->trans("OrdersStatistics")." ".$societe->nom, $mesg);

            $stats = new CommandeStats($db,$societe->id);
            $year = strftime("%Y", time());
            $startyear=$year-2;
            $endyear=$year;
            $data = $stats->getNbByMonthWithPrevYearPerSoc($endyear,$startyear,$societe->id);

            $dir=$conf->commande->dir_temp;
            if (! file_exists($dir))
            {
                if (dol_mkdir($dir) < 0)
                {
                    $mesg = $langs->trans("ErrorCanNotCreateDir",$dir);
                }
            }
            if (!$user->rights->societe->client->voir || $user->societe_id)
            {
                $filename = $conf->commande->dir_temp.'/nbcommande2year-'.$user->id.'-'.$year.'.png';
                $fileurl = DOL_URL_ROOT.'/viewimage.php?modulepart=orderstats&file=nbcommande2year-'.$user->id.'-'.$year.'.png';
            }
            else
            {
                $filename = $conf->commande->dir_temp.'/nbcommande2year-'.$year.'.png';
                $fileurl = DOL_URL_ROOT.'/viewimage.php?modulepart=orderstats&file=nbcommande2year-'.$year.'.png';
            }

            $px = new DolGraph();
            $mesg = $px->isGraphKo();
            if (! $mesg)
            {
                $px->SetData($data);
                $px->SetPrecisionY(0);
                $i=$startyear;
                while ($i <= $endyear)
                {
                    $legend[]=$i;
                    $i++;
                }
                $px->SetLegend($legend);
                $px->SetMaxValue($px->GetCeilMaxValue());
                $px->SetWidth($WIDTH);
                $px->SetHeight($HEIGHT);
                $px->SetYLabel($langs->trans("NbOfOrder"));
                $px->SetShading(3);
                $px->SetHorizTickIncrement(1);
                $px->SetPrecisionY(0);
                $px->mode='depth';
                $px->draw($filename);
            }

            $rows = $stats->getNbByYearPerSoc($societe->id);
            $num = sizeof($rows);

            print '<table class="border" width="100%">';
            print '<tr><td align="center">'.$langs->trans("Year").'</td><td width="10%" align="center">'.$langs->trans("NbOfOrders").'</td><td align="center">'.$langs->trans("AmountTotal").'</td>';
            print '<td align="center" valign="top" rowspan="'.($num + 1).'">';
            if ($px->isGraphKo()) { print '<font class="error">'.$px->isGraphKo().'</div>'; }
            else { print '<img src="'.$fileurl.'" alt="Nombre de commande par mois">'; }
            print '</td></tr>';
            $i = 0;
            while (list($key, $value) = each ($rows))
            {
              $year = $value[0];
              $nbproduct = $value[1];
              $price = $value[2];
              print "<tr>";
              print '<td align="center"><a href="month.php?year='.$year.'">'.$year.'</a></td><td align="center">'.$nbproduct.'</td><td align="center">'.price($price).'</td></tr>';
              $i++;
            }


              print '</table>';
              $db->free($result);
            print '</div>';
            print '<div id="fragment-ActCom">';

            //Affiche un bouton pour creer une action comm (jquery dialog)
            //Affiche les dernieres action com de la societe
                /*
                 *      Listes des actions a faire
                 */
                show_actions_todo($conf,$langs,$db,$societe);

                /*
                 *      Listes des actions effectuees
                 */
                show_actions_done($conf,$langs,$db,$societe);
            print "<div style='margin-left: 90%;' id='nextSoc' class='ui-state-default ui-corner-all butaction'><center>";
            print "<a href='#toptop' name='newActCom'  class='butaction' id='newActCom'>Cr&eacute;er une action</a>";
            print '</center></div>';


            print '</div>';
            print '<div id="fragment-Contact">';
                    /*
                     *      Liste des contacts
                     */
                    show_contacts($conf,$langs,$db,$societe);

            print '</div>';
            print '<div id="fragment-SuiviCom">';

            print "<iframe style='width: 100%; height: 600px; border: 0px;' src='".DOL_URL_ROOT."/BabelProspect/ajax/iframe-suiviProspection.php?socid=".$socid."'></iframe>";

            print '</div>';


            print '            <div id="fragment-DocGLE">';

            //Affiche un tree avec la liste des documents de GLE
            print "<iframe style='width: 100%; height: 600px; border: 0px;' src='".DOL_URL_ROOT."/BabelProspect/ajax/iframe-doc.php'></iframe>";

            print '            </div>';

            print '        </div>';

    }

?>
