<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.2
  * Created on : 18 aout 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : minicard.php
  * GLE-1.2
  */

  $cookieName = "GleWSCookie";
    if ($_REQUEST['authByPass']."x" != "x")
    {
        require_once('../master.inc.php');
        $login = $_REQUEST['authByPass'];
        $requete = "SELECT * FROM ".MAIN_DB_PREFIX."user WHERE login ='".$login."'";
        $sql = $db->query($requete);
        $res = $db->fetch_object($sql);
        if ($res->pass."x" != "x")
        {
            $requete = "DELETE FROM `Babel_WS_auth` WHERE user_id = ".$res->rowid;
            $sql=$db->query($requete);

            $requete = "INSERT INTO `Babel_WS_auth`
                                    (`tms`,`cookie_value`,`user_id`)
                             VALUES (now(), '".md5($res->pass)."', ".$res->rowid.")";
            $sql=$db->query($requete);
        }
    }

    if($_COOKIE[$cookieName] ."x" != "x")
    {
        require_once('../master.inc.php');
        $requete = "SELECT * FROM Babel_WS_auth WHERE cookie_value = '".$_COOKIE[$cookieName]."'";
        $sql = $db->query($requete);
        $res = $db->fetch_object($sql);
        $requete1 = "SELECT * FROM ".MAIN_DB_PREFIX."user WHERE rowid = ".$res->user_id;
        $sql1 = $db->query($requete1);
        $res1 = $db->fetch_object($sql1);
        $_POST["username"]=$res1->login;
        $_POST["password"]=$res1->pass;
    }

        require_once('../master.inc.php');

  //require_once('../main.inc.php');
  $contid=$_REQUEST['contratid'];
  if (strlen($contid) > 0)
  {
    //Recupere la societe du contrat
    require_once(DOL_DOCUMENT_ROOT."/contrat/class/contrat.class.php");
    $cont = new Contrat($db);
    $cont->fetch($contid);
    $socid = $cont->socid;
    $soc = new Societe($db);
    $soc->fetch($socid);

    //Recupere la liste des contacts
    $requete = "SELECT * FROM ".MAIN_DB_PREFIX."socpeople WHERE fk_soc = ".$soc->id;
    $sql = $db->query($requete);
    while ($res=$db->fetch_object($sql))
    {
        $socpeopleid[$res->fk_socpeople] = -1;
    }

    $requete = "SELECT fk_socpeople, ".MAIN_DB_PREFIX."c_type_contact.rowid as ctid
                  FROM ".MAIN_DB_PREFIX."element_contact, ".MAIN_DB_PREFIX."c_type_contact
                 WHERE element_id = ".$contid . "
                   AND ".MAIN_DB_PREFIX."c_type_contact.rowid = ".MAIN_DB_PREFIX."element_contact.fk_c_type_contact
                   AND ".MAIN_DB_PREFIX."element_contact.fk_c_type_contact
                        in (SELECT rowid FROM ".MAIN_DB_PREFIX."c_type_contact WHERE source='external' AND element='contrat') ";
    $sql = $db->query($requete);
    $socpeopleid=array();
    while ($res=$db->fetch_object($sql))
    {
        $socpeopleid[$res->fk_socpeople] =$res->ctid;
    }
    print "<table width='450' >";
    require_once(DOL_DOCUMENT_ROOT."/contact/class/contact.class.php");
    foreach($socpeopleid as $key=>$val)
    {
        if ($val>0)
        {
            $requete= "SELECT * FROM ".MAIN_DB_PREFIX."c_type_contact WHERE rowid = ".$val;
            $sql = $db->query($requete);
            $res=$db->fetch_object($sql);
            $role = $res->libelle;
            $socpeop = new Contact($db);
            $socpeop->fetch($key);
            $rowspan=2;
            ($socpeop->poste."x"!="x"?$rowspan++:"");
            ($socpeop->phone_pro."x"!="x"?$rowspan++:"");
            ($socpeop->phone_mobile."x"!="x"?$rowspan++:"");
            ($socpeop->fax."x"!="x"?$rowspan++:"");
            ($socpeop->email."x"!="x"?$rowspan++:"");
            ($socpeop->socid!=$soc->id?$rowspan++:"");

            print "<tr><td colspan=2>&nbsp;";
            print "<tr><th rowspan=".$rowspan." width=20%>Contacts Contrat";
            print "    <td>Nom: ".utf8_encode($socpeop->civility_id. " ".$socpeop->nom. " ".$socpeop->prenom);
            if ($socpeop->poste."x" != "x")
            {
                print "<tr><td>Poste: ".utf8_encode($socpeop->poste);
            }
            print "<tr><td>Role: ".utf8_encode($role);
            if ($socpeop->socid!=$soc->id)
            {
                $tmpSoc = new Societe($db);
                $tmpSoc->fetch($socpeop->socid);

                print "<tr><td>Soci&eacute;t&eacute;: ".utf8_encode($tmpSoc->getNomUrl(1));
            }

            if ($socpeop->phone_pro."x" != "x")
            {
                print "<tr><td>Fixe: ".utf8_encode($socpeop->phone_pro);
            }
            if ($socpeop->phone_mobile."x" != "x")
            {
                print "<tr><td>GSM: ".utf8_encode($socpeop->phone_mobile);
            }
            if ($socpeop->fax."x" != "x")
            {
                print "<tr><td>Fax: ".utf8_encode($socpeop->fax);
            }
            if ($socpeop->email."x" != "x")
            {
                print "<tr><td>Email: <a href='".$socpeop->email."'>".utf8_encode($socpeop->email)."</a>";
            }
            print "<tr><td colspan=2>&nbsp;";
        }
    }
    foreach($socpeopleid as $key=>$val)
    {
        if ($val<0)
        {
            $socpeop = new Contact($db);
            $socpeop->fetch($key);
            $rowspan=1;
            ($socpeop->poste."x"!="x"?$rowspan++:"");
            ($socpeop->phone_pro."x"!="x"?$rowspan++:"");
            ($socpeop->phone_mobile."x"!="x"?$rowspan++:"");
            ($socpeop->fax."x"!="x"?$rowspan++:"");
            ($socpeop->email."x"!="x"?$rowspan++:"");

            print "<tr><td colspan=2>&nbsp;";
            print "<tr><th rowspan=".$rowspan." width=20%>Contacts";
            print "    <td>Nom: ".utf8_encode($socpeop->civility_id. " ".$socpeop->nom. " ".$socpeop->prenom);
            if ($socpeop->poste."x" != "x")
            {
                print "<tr><td>Poste: ".utf8_encode($socpeop->poste);
            }
            if ($socpeop->phone_pro."x" != "x")
            {
                print "<tr><td>Fixe: ".utf8_encode($socpeop->phone_pro);
            }
            if ($socpeop->phone_mobile."x" != "x")
            {
                print "<tr><td>GSM: ".utf8_encode($socpeop->phone_mobile);
            }
            if ($socpeop->fax."x" != "x")
            {
                print "<tr><td>Fax: ".utf8_encode($socpeop->fax);
            }
            if ($socpeop->email."x" != "x")
            {
                print "<tr><td>Email: <a href='".$socpeop->email."'>".utf8_encode($socpeop->email)."</a>";
            }
            print "<tr><td colspan=2>&nbsp;";
        }
    }
    print "</table><br>";
    //Recupere les données du contrat

    print "<table width='450' cellpadding=5>";
    print "<tr><th width=20%>Contrat<td>".$cont->getNomUrl(1);
    $tmp = $cont->getTypeContrat();
    print "<tr><th>Type<td>".$tmp['Nom'];
    print "<tr><th>Statut<td>".$cont->getLibStatut(4);
        $requete = "SELECT ".MAIN_DB_PREFIX."Synopsis_contrat_GMAO.qte * ".MAIN_DB_PREFIX."product.qte as qtetot
                      FROM ".MAIN_DB_PREFIX."contrat,
                           ".MAIN_DB_PREFIX."Synopsis_contrat_GMAO,
                           ".MAIN_DB_PREFIX."product
                     WHERE ".MAIN_DB_PREFIX."Synopsis_contrat_GMAO.contrat_refid = ".MAIN_DB_PREFIX."contrat.rowid
                       AND ".MAIN_DB_PREFIX."Synopsis_contrat_GMAO.fk_prod = ".MAIN_DB_PREFIX."product.rowid
                       AND ".MAIN_DB_PREFIX."contrat.rowid = ".$contid;
        $sql = $db->query($requete);
        $res = $db->fetch_object($sql);
        if ($res->qtetot > 0)
        {
            $requete = "SELECT max(tms) as mtms, nbTicketRestant
                          FROM Babel_GMAO_Contrat_Tkt
                         WHERE contrat_refid = ".$contid. " GROUP BY contrat_refid ";
            $sql = $db->query($requete);
            $res1 = $db->fetch_object($sql);
            print "<tr><th>Tickets restants</th><td>".intval( $res->qtetot - $res1->nbTicketRestant )."/".$res->qtetot;
        }
    print "</table>";
  } else {
    print "Pas d'information contrat";
  }
?>
