<?php
/*
 * GLE by Babel-Services
 *
 * Author: Jean-Marc LE FEVRE <jm.lefevre@babel-services.com>
 * Licence : Artistic Licence v2.0
 *
 * Version 1.1
 * Create on : 4-1-2009
 *
 * Infos on http://www.babel-services.com
 *
 */
global $langs;
require_once('../../master.inc.php');

require_once(DOL_DOCUMENT_ROOT."/lib/company.lib.php");
require_once(DOL_DOCUMENT_ROOT.'/propal.class.php');
require_once(DOL_DOCUMENT_ROOT."/lib/propal.lib.php");

require_once(DOL_DOCUMENT_ROOT."/facture.class.php");
require_once(DOL_DOCUMENT_ROOT .'/commande/class/commande.class.php');


$langs->load("companies");
$langs->load('synopsisGene@Synopsis_Tools');
$langs->load("propal");
$langs->load("order");
$langs->load("main");
$langs->load("compta");
$langs->load("users");
$langs->load("bills");

$cdataStart = "<![CDATA[";
$cdataStop = "]]>";
//var_dump($_REQUEST);
if ($_REQUEST['level'] == 1)
{
    //require_once('../../master.inc.php');
    $durMonth = ($_REQUEST['duree']."x" != "x"?$_REQUEST['duree']:false);
    $xml = "";
    $requete = "SELECT concat(day(llx_propal.date_valid),'/',month(llx_propal.date_valid), '/',year(llx_propal.date_valid)) as date_valid," .
            "          llx_propal.remise_percent," .
            "          llx_propal.remise_absolue," .
            "          llx_propal.remise," .
            "          ifnull(year(llx_propal.date_valid),year(now()) + 1) as yearValid," .
            "          llx_propal.ref," .
            "          llx_propal.rowid as pid," .
            "          llx_propal.total_ht," .
            "          llx_projet.title," .
            "          llx_propal.fk_statut" .
            "     FROM llx_propal
                       llx_propal LEFT JOIN llx_projet on  llx_projet.rowid = llx_propal.fk_projet
                 WHERE llx_propal.fk_soc=".$_REQUEST['socid'];
    if ($durMonth."x" != "x")
    {
        $requete  .=  " AND llx_propal.datec > DATE_SUB(NOW(),INTERVAL ".$durMonth." MONTH)";
    }
    $requete .= "   ORDER BY  yearValid desc, year(llx_propal.date_valid) desc , month(llx_propal.date_valid) desc, day(llx_propal.date_valid) desc";
    $resql=$db->query($requete);

    $remPropal = array();
    if ($resql)
    {
        if ($db->num_rows($resql) > 0)
        {
            $xml .= "\t<recapMain>\n";
            while ($res=$db->fetch_object($resql))
            {

                    array_push($remPropal,$res->pid);
                    //commande et facture associée
                    $commandeHTMLArr = array();
                    $commandeStatutHTMLArr=array();
                    $factureHTMLArr = array();
                    $factureStatutHTMLArr = array();
                    $statutPayeArr = array();
                    $requeteCom = "SELECT *
                                     FROM llx_co_pr
                                    WHERE fk_propale = ".$res->pid;
                    $resqlCom=$db->query($requeteCom);
                    if ($resqlCom)
                    {
                        $com = new Commande($db);
                        while ($resCom = $com->db->fetch_object($resqlCom))
                        {
                            $com->fetch($resCom->fk_commande);
                            array_push($commandeHTMLArr , "<a href='".DOL_URL_ROOT."/commande/fiche.php?id=".$com->id."'>".img_object('commande','order').$com->ref ."</a>");
                            array_push($commandeStatutHTMLArr , $com->getLibStatut(6));


                            $requeteFact1 = "SELECT *
                                               FROM llx_co_fa
                                              WHERE fk_facture NOT IN (SELECT fk_facture
                                                                         FROM llx_fa_pr )
                                                AND llx_co_fa.fk_commande = ".$com->id;
                            $resqlFac=$db->query($requeteFact1);
                            $statutPayeArr = array();
                            if ($resqlFac)
                            {
                                $fac = new Facture($db);
                                while ($resFac = $fac->db->fetch_object($resqlFac))
                                {
                                    $fac->fetch($resFac->fk_facture);
                                    array_push($factureHTMLArr , "<a href='".DOL_URL_ROOT."/facture/fiche.php?id=".$fac->id."'>".img_object('facture','bill') . $fac->ref ."</a>");
                                    array_push($factureStatutHTMLArr , $fac->getLibStatut(2));
                                    $PayStr = "";
                                    if ($fac->paye == 1)
                                    {
                                        $PayStr = img_picto("Pay&eacute;","tick");
                                    } else {
                                        $PayStr = img_picto("Non Pay&eacute;","stcomm-1");
                                    }
                                    array_push($statutPayeArr, $PayStr);
                                }
                            }
                        }
                    }
                    $commandeHTML = join($commandeHTMLArr,"<HR/>");
                    if (strlen($commandeHTML) < 1) { $commandeHTML = "&nbsp;";}
                    $commandeStatutHTML = join($commandeStatutHTMLArr,"<HR/>");
                    if (strlen($commandeHTML) < 1) { $commandeHTML = "&nbsp;";}

                    $requeteFact = "SELECT *
                                      FROM llx_fa_pr
                                     WHERE fk_propal = ".$res->pid;
                    $resqlFac=$db->query($requeteFact);
                    if ($resqlFac)
                    {
                        $fac = new Facture($db);
                        while ($resFac = $fac->db->fetch_object($resqlFac))
                        {
                            $fac->fetch($resFac->fk_facture);
                            array_push($factureHTMLArr , "<a href='".DOL_URL_ROOT."/facture/fiche.php?id=".$fac->id."'>".img_object('facture','bill').$fac->ref ."</a>");
                            array_push($factureStatutHTMLArr , $fac->getLibStatut(2));
                            $PayStr = "";
                            if ($fac->paye == 1)
                            {
                                $PayStr = img_picto("Pay&eacute;","tick");
                            } else {
                                $PayStr = img_picto("Non Pay&eacute;","stcomm-1");
                            }
                            array_push($statutPayeArr, $PayStr);
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


                $xml .= "\t\t<row id='".$res->pid."'>\n";
                $xml .= "\t\t\t<info>".$cdataStart."<span onMouseover='showContact(".$res->pid.",this);'  >".img_picto('infos','info')."</span>".$cdataStop."</info> \n";
                $xml .= "\t\t\t<date_valid>".$cdataStart.$res->date_valid.$cdataStop."</date_valid> \n";
                $xml .= "\t\t\t<remise_percent>".$cdataStart.$res->remise_percent.$cdataStop."</remise_percent> \n";
                $xml .= "\t\t\t<remise_absolue>".$cdataStart.$res->remise_absolue.$cdataStop."</remise_absolue> \n";
                $xml .= "\t\t\t<remise>".$cdataStart.price($res->remise,1,"","","0",0)." &euro;".$cdataStop."</remise> \n";
                $xml .= "\t\t\t<ref>".$cdataStart."<A HREF='".DOL_URL_ROOT."/comm/propal.php?propalid=".$res->pid."'>".img_object('propale','propal').$res->ref."</A>".$cdataStop."</ref> \n";
                $xml .= "\t\t\t<pid>".$cdataStart.$res->pid.$cdataStop."</pid> \n";
                $xml .= "\t\t\t<total_ht>".$cdataStart.price($res->total_ht,1,"","","0",0)." &euro;".$cdataStop."</total_ht> \n";
                $xml .= "\t\t\t<title>".$cdataStart.$res->title.$cdataStop."</title> \n";
                $xml .= "\t\t\t<propalStatut>".$cdataStart.$propStatut.$cdataStop."</propalStatut> \n";
                $xml .= "\t\t\t<commande>".$cdataStart.$commandeHTML.$cdataStop."</commande> \n";
                $xml .= "\t\t\t<commandeStatut>".$cdataStart.$commandeStatutHTML.$cdataStop."</commandeStatut> \n";
                $xml .= "\t\t\t<facture>".$cdataStart.$factureHTML.$cdataStop."</facture> \n";
                $xml .= "\t\t\t<factureStatut>".$cdataStart.$factureStatutHTML.$cdataStop."</factureStatut> \n";
                $xml .= "\t\t\t<paye>".$cdataStart.$statutPaye.$cdataStop."</paye> \n";
                $xml .= "\t\t</row>\n";
            }
            $xml .= "\t</recapMain>\n";
        }
    }

    $sqlJoin = join($remPropal,",");
    $requete = "SELECT count(babel_product.rowid) as cnt," .
            "          babel_product.description" .
            "     FROM llx_propaldet," .
            "          babel_product" .
            "    WHERE babel_product.rowid = llx_propaldet.fk_product " .
            "      AND fk_propal in ($sqlJoin)" .
            "      AND llx_propaldet.fk_product <> 0  AND babel_product.fk_product_type = 0 " .
            " GROUP BY babel_product.rowid" .
            " ORDER BY count(babel_product.rowid) DESC LIMIT 25";
    $resql=$db->query($requete);
    if ($resql)
    {
        if ($db->num_rows($resql) > 0)
        {
            $xml .= "\t<recapProdTable>\n";
            while ($res=$db->fetch_object($resql))
            {
                $xml .= "\t\t<row>\n";
                $xml .= "\t\t\t<ProdQty>".$cdataStart.$res->cnt.$cdataStop."</ProdQty> \n";
                $xml .= "\t\t\t<ProdDesc>".$cdataStart.$res->description.$cdataStop."</ProdDesc> \n";
                $xml .= "\t\t</row>\n";
            }
            $xml .= "\t</recapProdTable>\n";
        }
    }

    $sqlJoin = join($remPropal,",");
    $requete = "SELECT count(babel_product.rowid) as cnt," .
            "          babel_product.description" .
            "     FROM llx_propaldet," .
            "          babel_product" .
            "    WHERE babel_product.rowid = llx_propaldet.fk_product " .
            "      AND fk_propal in ($sqlJoin)" .
            "      AND llx_propaldet.fk_product <> 0  AND babel_product.fk_product_type = 1 " .
            " GROUP BY llx_product.rowid" .
            " ORDER BY count(babel_product.rowid) DESC LIMIT 25";

    $resql=$db->query($requete);
    if ($resql)
    {
        if ($db->num_rows($resql) > 0)
        {

            $xml .= "\t<recapServTable>\n";
            while ($res=$db->fetch_object($resql))
            {
                $xml .= "\t\t<row>\n";
                $xml .= "\t\t\t<ServQty>".$cdataStart.$res->cnt.$cdataStop."</ServQty> \n";
                $xml .= "\t\t\t<ServDesc>".$cdataStart.$res->description.$cdataStop."</ServDesc> \n";
                $xml .= "\t\t</row>\n";
            }
            $xml .= "\t</recapServTable>\n";
        }
    }


//            $requete = "SELECT llx_contrat.ref,
//                               date_format(llx_contrat.date_contrat,'%d/%m/%Y') as date_contrat,
//                               llx_contrat.rowid as cid,
//                               llx_projet.title,
//                               llx_contrat.linkedTo,
//                               llx_contrat.statut
//                          FROM llx_contrat LEFT JOIN llx_projet on llx_contrat.fk_projet = llx_projet.rowid
//                         WHERE  llx_contrat.fk_soc = ".$societe->id;
//        //                 print $requete;
//            if ($resql = $db->query($requete))
//            {
//                while ($res=$db->fetch_object($resql))
//                {
//                    print "<td><a href='".DOL_URL_ROOT."/contrat/fiche.php?id=".$res->cid."'>".img_object('contract','contract')." ".$res->ref."</a>";
//                    print "<td align='center'>".$res->date_contrat;
//                    print "<td>".$res->title;
//                    print "<td width= 100px><table width=100%  class='nobordernopadding' width='100%'>";
//                    require_once(DOL_DOCUMENT_ROOT.'/contrat/contrat.class.php');
//                    $contrat = new Contrat($db);
//                    $contrat->fetch($res->cid);
//                    if ($contrat->linkedTo)
//                    {
//                        if (preg_match('/^([c|p|f]{1})([0-9]*)/',$contrat->linkedTo,$arr))
//                        {
//                            print '<tr><td><table class="nobordernopadding" style="width:100%;">';
//                            print '<tr> ';
//                            $val1 = $arr[2];
//                            switch ($arr[1])
//                            {
//                                case 'c':
//                                    print '';
//                                    print '<td align="right" style="width:20px"><a href="'.$_SERVER["PHP_SELF"].'?action=chSrc&amp;id='.$id.'">'.img_edit($langs->trans("Change la source")).'</a>';
//                                    require_once(DOL_DOCUMENT_ROOT."/commande/class/commande.class.php");
//                                    $comm = new Commande($db);
//                                    $comm->fetch($val1);
//                                    print "</table><td><a href='".DOL_URL_ROOT."/commande/fiche.php?id=".$comm->id."'>".$comm->ref."</a>";
//                                break;
//                                case 'f':
//                                    print '';
//                                    print '<td align="right" style="width:20px"><a href="'.$_SERVER["PHP_SELF"].'?action=chSrc&amp;id='.$id.'">'.img_edit($langs->trans("Change la source")).'</a>';
//                                    require_once(DOL_DOCUMENT_ROOT."/facture.class.php");
//                                    $fact = new Facture($db);
//                                    $fact->fetch($val1);
//                                    print "</table><td><a href='".DOL_URL_ROOT."/compta/facture.php?facid=".$fact->id."'>".$fact->ref."</a>";
//                                break;
//                                case 'p':
//                                    print '';
//                                    print '<td align="right" style="width:20px"><a href="'.$_SERVER["PHP_SELF"].'?action=chSrc&amp;id='.$id.'">'.img_edit($langs->trans("Change la source")).'</a>';
//                                    require_once(DOL_DOCUMENT_ROOT."/propal.class.php");
//                                    $prop = new Propal($db);
//                                    $prop->fetch($val1);
//                                    print "</table><td><a href='".DOL_URL_ROOT."/comm/propal.php?propalid=".$prop->id."'>".$prop->ref."</a>";
//                                break;
//                            }
//                        }
//                    }
//
//                //ajoute le lien vers les propal / commande / facture
//                foreach($contrat->linkedArray as $key=>$val)
//                {
//        //            print $key;
//                    if ($key=='co')
//                    {
//                        foreach($val as $key1=>$val1)
//                        {
//                                print '<tr><td>';
//                                print 'Commandes associ&eacute;es<td>';
//                                require_once(DOL_DOCUMENT_ROOT."/commande/class/commande.class.php");
//                                $comm = new Commande($db);
//                                $comm->fetch($val1);
//                                print "<a href='".DOL_URL_ROOT."/commande/fiche.php?id=".$comm->id."'>".$comm->ref."</a>";
//                        }
//                    } else if ($key=='fa') {
//                        foreach($val as $key1=>$val1)
//                        {
//                                print '<tr><td>';
//                                print 'Factures associ&eacute;es<td>';
//                                require_once(DOL_DOCUMENT_ROOT."/facture.class.php");
//                                $fac = new Facture($db);
//                                $fac->fetch($val1);
//                                print "<a href='".DOL_URL_ROOT."/compta/facture.php?facid=".$fac->id."'>".$fac->ref."</a>";
//                        }
//                    }else if ($key=='pr') {
//                        foreach($val as $key1=>$val1)
//                        {
//                                print '<tr><td>';
//                                print 'Propositions associ&eacute;es<td>';
//                                require_once(DOL_DOCUMENT_ROOT."/propal.class.php");
//                                $prop = new Propal($db);
//        //                        print 'tutu';
//                                $prop->fetch($val1);
//                                print "<a href='".DOL_URL_ROOT."/comm/propal.php?propalid=".$prop->id."'>".$prop->ref."</a>";
//                         }
//                    }
//                }
//                print "</table>";
//                print "<td>".$contrat->getLibStatut(5) ."<BR>". $contrat->getLibStatut(4);
//                }


    header("Content-Type: text/xml");
    $xmlStr = '<'.'?xml version="1.0" encoding="ISO-8859-1"?'.'>';
    $xmlStr .= '<ajax-response><response>'."\n";
    $xmlStr .= $xml;
    $xmlStr .= '</response></ajax-response>'."\n";
    print $xmlStr;
} elseif ($_REQUEST['level'] == 2 && $_REQUEST["propalid"] > 0)
{
    $contactArr=array();
    $socid  = $_REQUEST['socid'];
    if ($socid > 0)
    {
        $societe = new Societe($db);
        $societe->fetch($socid, $to);  // si $to='next' ajouter " AND s.rowid > $socid ORDER BY idp ASC LIMIT 1";

        /*contact societe*/
        $requete = "SELECT * FROM llx_societe_commerciaux WHERE fk_soc = " .$socid;
        $resqlPre = $societe->db->query($requete);
        if ($resqlPre){
            while ($res = $societe->db->fetch_object($resqlPre))
            {
                $tmpUser = new User($db,$res->fk_user);
                $tmpUser->fetch();
                array_push($contactArr,array('source' => 'internal',
                                             'socid' => '-1',
                                             'id' => $tmpUser->id,
                                             'nom' => $tmpUser->fullname,
                                             "rowid" => -1,
                                             'code' => "SOCCOMMERCIAL",
                                             'libelle' => $langs->trans("SOCCOMMERCIAL"),
                                             'status' => $tmpUser->statut + 4 ));
            }
        }

        $propal = new Propal($db);
        $propal->fetch($_REQUEST["propalid"], $socid);


        $requete = "SELECT concat(day(llx_propal.date_valid),'/',month(llx_propal.date_valid), '/',year(llx_propal.date_valid)) as date_valid," .
                "          llx_propal.remise_percent," .
                "          llx_propal.remise_absolue," .
                "          llx_propal.remise," .
                "          ifnull(year(llx_propal.date_valid),year(now()) + 1) as yearValid," .
                "          llx_propal.fk_user_author," .
                "          llx_propal.fk_user_valid," .
                "          llx_propal.fk_user_cloture," .
                "          llx_propal.ref," .
                "          llx_propal.rowid as pid," .
                "          llx_propal.total_ht," .
                "          llx_projet.title," .
                "          llx_propal.fk_statut " .
                "     FROM llx_propal
                           llx_propal LEFT JOIN llx_projet on  llx_projet.rowid = llx_propal.fk_projet
                     WHERE llx_propal.fk_soc=".$societe->id."
                       AND llx_propal.rowid = ".$_REQUEST['propalid']."
                   ORDER BY  yearValid desc, year(llx_propal.date_valid) desc , month(llx_propal.date_valid) desc, day(llx_propal.date_valid) desc";

        $resql=$societe->db->query($requete);
//        print $requete."<BR>";
        $remPropal = array();
        if ($resql)
        {
            if ($societe->db->num_rows($resql))
            {
                $im='';
                while ($res=$societe->db->fetch_object($resql))
                {
                    array_push($remPropal,$res->pid);
                    //commande et facture associee
                    $requeteCom = "SELECT *
                                     FROM llx_co_pr
                                    WHERE fk_propale = ".$res->pid;
                    $resqlCom=$societe->db->query($requeteCom);
                    if ($resqlCom)
                    {
                        $com = new Commande($societe->db);
                        while ($resCom = $com->db->fetch_object($resqlCom))
                        {
                            $com->fetch($resCom->fk_commande);
                            /*contact commande */
                            $tmpArr =  $com->liste_contact(-1,'internal');
                            foreach($tmpArr as $key=>$val)
                            {
                                array_push($contactArr,$val);
                            }
                            $codeArrCOM['user_creation']='COMMANDEAUTHOR';
                            $codeArrCOM['user_validation']='COMMANDEVALID';
                            $codeArrCOM['user_cloture']='COMMANDECLOTURE';

                            $codeArrTCOM['user_creation']=$langs->trans('COMMANDEAUTHOR');
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
                                               FROM llx_co_fa
                                              WHERE fk_facture NOT IN (SELECT fk_facture
                                                                         FROM llx_fa_pr )
                                                AND llx_co_fa.fk_commande = ".$com->id;
                            $resqlFac=$societe->db->query($requeteFact1);
                            if ($resqlFac)
                            {
                                $fac = new Facture($societe->db);
                                while ($resFac = $fac->db->fetch_object($resqlFac))
                                {
                                    $fac->fetch($resFac->fk_facture);
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
                                                                  'status' => $fac->{$val}->statut +4));
                                        }
                                    }
                                }
                            }
                        }
                    }
                    $requeteFact = "SELECT *
                                      FROM llx_fa_pr
                                     WHERE fk_propal = ".$res->pid;
                    $resqlFac=$societe->db->query($requeteFact);
                    if ($resqlFac)
                    {
                        $fac = new Facture($societe->db);
                        while ($resFac = $fac->db->fetch_object($resqlFac))
                        {
                            $fac->fetch($resFac->fk_facture);
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
                                                          'status' => $fac->{$val}->statut +4 ));
                                }
                            }
                        }
                    }


                    $prop=new Propal($db);

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
                 *        commercial associe e� la societe
                 */
                 // interlocuteurs :
                 //propal
                $propal->id = $res->pid;
                $arr=array();
                $arr =  $propal->liste_contact(-1,'internal');
                //var_dump($arr);
                $codeArr['fk_user_author']='PROPALAUTHOR';
                $codeArr['fk_user_valid']='PROPALVALID';
                $codeArr['fk_user_cloture']='PROPALCLOTURE';

                $codeArrT['fk_user_author']=$langs->trans('PROPALAUTHOR');
                $codeArrT['fk_user_valid']=$langs->trans('PROPALVALID');
                $codeArrT['fk_user_cloture']=$langs->trans('PROPALCLOTURE');

                $tmpArr =  $propal->liste_contact(-1,'internal');
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
                        array_push($contactArr,
                                        array('source' => 'internal',
                                              'socid' => '-1',
                                              'id' => $tmpUserCreate->id,
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
//            var_dump($contactArr);
    $xml="";
//    array_multisort($contactArr[]['code']);
    foreach($contactArr as $key=>$val)
    {
        if ($val['code']."x"!="x")
        {
           $xml .= "\t<row cat='".$val['code']."'>\n";
           if (is_array($val) && count($val) > 0)
           {
               foreach($val as $key1=>$val1)
               {
                    switch($key1)
                    {
                        case 'nom':
                            $url="";
                            if ($val['source'] != "external")
                            {
                                $url = "<A href='".DOL_URL_ROOT . "/user/fiche.php?id=".($val['id']) .'\'>'.$val['nom'].'</A>';
                            } else {
                                $url = "<A href='".DOL_URL_ROOT . "/contact/fiche.php?id=".($val['id']).'\'>'.$val['nom'].'</A>';
                            }
//                            print $url;
                            $xml .= "\t\t<nomCont element='".$val1."'  >". $cdataStart. $url .$cdataStop ."</nomCont>\n";
                        break;
                        case 'socid':
                            if ($val1 > 0)
                            {
                                $soc = new Societe($db);
                                $soc->fetch($val1);
                                $url = "<A href='".DOL_URL_ROOT."/societe/fiche.php?socid=".$soc->id."'\' >".$soc->name." </A> ";
                            } else {
                                $url = $conf->global->MAIN_INFO_SOCIETE_NOM;
                            }
//                            print $url."<BR>";
//                            var_dump($conf);
                            $xml .= "\t\t<societe element='".$val1."'>". $cdataStart. $url .$cdataStop ."</societe>\n";
                        break;
//                        case 'code':
//                            $xml .= "\t\t<cat>". $cdataStart. $val1 .$cdataStop ."</cat>\n";
//                        break;
                        case 'libelle':
                            $xml .= "\t\t<libelle>". $cdataStart. $val1 .$cdataStop ."</libelle>\n";
                        break;
                        case 'status':
                            $curVal = $val1;
                            if ($val['key1']['source'] == "internal")
                            {
                                $curVal += 4;
                            }
                            $obj = new Contact($db);


                            $url = $obj->LibStatut($curVal,3);
                            $xml .= "\t\t<status>". $cdataStart. $url .$cdataStop ."</status>\n";
                        break;
                    }
//                    $xml .= "\t\t<$key1>".$cdataStart.$val1.$cdataStop."</$key1>\n";
               }
           }
            $xml .= "\t</row>\n";
        }

    }


    header("Content-Type: text/xml");
    $xmlStr = '<'.'?xml version="1.0" encoding="ISO-8859-1"?'.'>';
    $xmlStr .= '<ajax-response><response>'."\n";
    $xmlStr .= $xml;
    $xmlStr .= '</response></ajax-response>'."\n";
    print $xmlStr;
    }

}
?>