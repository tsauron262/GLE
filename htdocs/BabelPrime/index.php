<?php
/*
 * GLE by Synopsis et DRSI
 *
 * Author: Tommy SAURON <tommy@drsi.fr>
 * Licence : Artistic Licence v2.0
 *
 * Version 1.1
 * Create on : 4-1-2009
 *
 * Infos on http://www.finapro.fr
 *
 */

//TODO lié à la fiche PN

    include_once ("../master.inc.php");
#    include_once ("../main.inc.php");
    include_once ("./pre.inc.php");
    $webPath = DOL_URL_ROOT;
    $hd_path = DOL_DOCUMENT_ROOT;


    require_once(DOL_DOCUMENT_ROOT."/core/lib/company.lib.php");
    require_once(DOL_DOCUMENT_ROOT."/prospect.class.php");
    require_once(DOL_DOCUMENT_ROOT."/contact/class/contact.class.php");
    require_once(DOL_DOCUMENT_ROOT."/BabelPrime/fct_BabelPrime.php");



llxHeader('',$langs->trans('Proposal'),'Proposition');
$langs->load("synopsisGene@Synopsis_Tools");

    $user->getrights('');

    $userid = $_GET['userid'];
    if ("x".$userid== 'x')
    {
        $userid = $_POST['userid'];
        if ("x".$userid == "x")
        {
            $userid = 0;
        }
    }
//date
        $defaultYear = date('Y');
        if ('x'.$_GET["Year"] != "x")
        {
            $defaultYear = $_GET["Year"];
        }
        if ('x'.$_POST["Year"] != "x")
        {
            $defaultYear = $_POST["Year"];
        }


    $requetepre = "SELECT * " .
            "        FROM ".MAIN_DB_PREFIX."user, " .
            "             Babel_Prime_li_Profil_User " .
            "       WHERE Babel_Prime_li_Profil_User.User_refid=".MAIN_DB_PREFIX."user.rowid " .
            "         AND ".MAIN_DB_PREFIX."user.rowid = ".$userid;
    $sqlpre = $db->query($requetepre);
    $objpre = $db->fetch_object($sqlpre);
    $Getprofilid = $objpre->Profil_refid;
    if ("x".$Getprofilid == "x")
    {
        $Getprofilid = 0;
    }





//javascript
print<<<EOF

<script type='text/javascript'>
var remWidth_hidePropal="";
var remStatus_hidePropal="r"; // r :> reduce , m :> magnify
var imgPropSav = false;
function hidePropal()
{
//id='propalBut'
    //saveImg if needed
    if (imgPropSav == false)
    {
        imgPropSav = document.getElementById('propalBut').getElementsByTagName('img')[0].cloneNode(false);
    }
    var Div = document.getElementById("displayPropal");
    if ("x"+remWidth_hidePropal == "x")
    {
        remWidth_hidePropal = Div.offsetWidth;
    }
    if (remStatus_hidePropal == "r")
    {
        Div.style.width = "30px";
        remStatus_hidePropal = "m";
        clearInnerHTML(Div.getElementsByTagName("button")[0]);
        imgPropSav.style.width='20px';
        imgPropSav.style.height='20px';
        Div.getElementsByTagName("button")[0].appendChild(imgPropSav);


        //Div.getElementsByTagName("button")[0].innerHTML="P";
    } else {
        Div.style.width = remWidth_hidePropal+"px";
        remStatus_hidePropal = "r";
        clearInnerHTML(Div.getElementsByTagName("button")[0]);
        imgPropSav.style.width='20px';
        imgPropSav.style.height='20px';
        Div.getElementsByTagName("button")[0].appendChild(imgPropSav);
    }

}
var remWidth_hideFacture="";
var remStatus_hideFacture="r"; // r :> reduce , m :> magnify
var imgFactSav = false;

function hideFacture(pImgSrc)
{
    if (imgFactSav== false)
    {
        imgFactSav = document.getElementById('factureBut').getElementsByTagName('img')[0].cloneNode(true);
    }
    var Div = document.getElementById("displayFacture");
    if ("x"+remWidth_hideFacture == "x")
    {
        remWidth_hideFacture = Div.offsetWidth;
    }
    if (remStatus_hideFacture == "r")
    {
        Div.style.width = "30px";
        remStatus_hideFacture = "m";
        clearInnerHTML(Div.getElementsByTagName("button")[0]);
        imgFactSav.style.width='20px';
        imgFactSav.style.height='20px';
        Div.getElementsByTagName("button")[0].appendChild(imgFactSav);
    } else {
        Div.style.width = remWidth_hideFacture+"px";
        remStatus_hideFacture = "r";
        clearInnerHTML(Div.getElementsByTagName("button")[0]);
        imgFactSav.style.width='20px';
        imgFactSav.style.height='20px';
        Div.getElementsByTagName("button")[0].appendChild(imgFactSav);
    }

}

function clearInnerHTML(obj)// tool to delete the content of an HTML object.
{
  while(obj.firstChild) obj.removeChild(obj.firstChild);
}
function PBChangeUser(pObj)
{
    //manque la date
    var url=location.href;
        url = window.location.protocol +'//'+ window.location.host +''+ window.location.pathname;
        //alert (url1);
    var selected = "";
    var year="";
    //Get the date
    for (var i=0;i<document.getElementById('Year').length;i++)
    {
        if (document.getElementById('Year')[i].selected)
        {
            year = document.getElementById('Year')[i].value;
        }
    }
    for (var i=0;i<pObj.options.length;i++)
    {
        if (pObj.options[i].selected)
        {
            if (pObj.options[i].value == "0") //reset
            {
                document.location.href = url;
            } else {
                url += "?userid=";
                selected = pObj.options[i].value;
            }
        }
    }
    if ("x"+selected != "x")
    {
        url+=selected+"&Year="+year;
    } else {
        url += "?Year="+year;
    }
    document.location.href = url;
}
</script>
EOF;
    $langs->load("boxes");

    require_once(DOL_DOCUMENT_ROOT."/boxes.php");


    //necessite le profil

    //Affiche Select Box
        print '<table width="100%" class="nobordernopadding"><tr><td nowrap></td></tr>';


        print "   <tr class='liste_titre'>\n";
        print "       <td style='width:50%;' colspan=4>Selection User</td>\n";
        print "   </tr>\n";
        print "   <tr class='impair'>\n";


        print "   <td name='Profils' colspan=4 >\n";
        print "       <SELECT  onChange=\"PBChangeUser(this);\"  Name='UserId'  id='UserId'>\n";
        $requete = "SELECT * " .
                "     FROM ".MAIN_DB_PREFIX."user";
        $sqla=$db->query($requete);
        print "           <option value='0' >Select-></option>\n";
        while ($obj=$db->fetch_object($sqla))
        {
            if ('x'.$obj->rowid == 'x'.$userid)
            {
                print "           <option SELECTED value='".$obj->rowid."' >".$obj->name."</option>\n";
            } else {
                print "           <option value='".$obj->rowid."' >".$obj->name."</option>\n";
            }
        }
        print "  </SELECT>\n";

        $requetepre2 = "SELECT DISTINCT year(date_valid) AS dateYear " .
                "         FROM ".MAIN_DB_PREFIX."propal " .
                "        WHERE fk_statut = 2";
        $sqlpre2 = $db->query($requetepre2);

        print " <SELECT   onChange=\"PBChangeUser(document.getElementById('UserId'));\"  name='Year' Id='Year'>\n";
        while ($objpre2 = $db->fetch_object($sqlpre2))
        {
            if ($objpre2->dateYear == $defaultYear)
            {
                print "<OPTION value='".$objpre2->dateYear."' SELECTED>".$objpre2->dateYear."</OPTION>";
            } else {
                print "<OPTION value='".$objpre2->dateYear."'>".$objpre2->dateYear."</OPTION>";
            }
        }
        print "</SELECT>";

        print   "<SPAN class='button' " .
                "      onMouseOver='this.style.textDecoration=\"underline\"; " .
                "      this.style.cursor=\"Pointer\";' " .
                "      onMouseOut='this.style.textDecoration=\"none\"; " .
                "      this.style.cursor=\"none\";' " .
                "      style='padding-top: 3px; " .
                "      padding-bottom: 2px; " .
                "      padding-left: 10px; " .
                "      padding-right: 10px;' " .
                "     onClick=\"PBChangeUser(document.getElementById('UserId'));\" >OK >>" .
                "</SPAN>";

        print "   </td>\n";
        print " </tr><tr><td>&nbsp;</td></tr>\n";

    //Liste client fk_user_creat => user create + datec => date creation + client => 0 = pas client pas prospect 1 = client 2 = prospect
// contact effectue ".MAIN_DB_PREFIX."c_stcomm
    if ($userid != 0)
    {
        $num_of_prospect = 0;
        $num_of_client = 0;
        $requete = "SELECT client " .
                "     FROM ".MAIN_DB_PREFIX."societe " .
                "    WHERE fk_user_creat =  ".$userid . " " .
                "      AND year(datec) = ".$defaultYear;
        //echo $requete;
        $sqlCnt = $db->query($requete);
        while ($objCnt = $db->fetch_object($sqlCnt) )
        {
            if ($objCnt->client == 2)
            {
                $num_of_prospect++;
            }
            if ($objCnt->client == 1)
            {
                $num_of_client++;
            }
        }
        print " <tr>\n";
        print "   <th align='center' name='headertable' >Compte ouvert</th>\n";
        print "   <th align='center' name='headertable' >Prospect</th>\n";
        print "   <th align='center' name='headertable' >Client</th>\n";
        print " </tr>\n";
        $num_tiers_tot = $num_of_client+$num_of_prospect;
        print " <tr class='pair' ><td class='pair' name='Prospect_Client_ouverture' align = 'center' >\n". $num_tiers_tot  ."</td>\n";
        print "     <td  name='Prospect_Client_ouverture' align = 'center'>\n".$num_of_prospect."</td>\n";
        print "     <td class='impair' name='Prospect_Client_ouverture'align = 'center'>\n".$num_of_client."</td>\n";
        print " </tr>\n";
        print " <tr><td>&nbsp;</tr>\n";
    }


    //affiche prime
    if ($Getprofilid != 0)
    {
        $requete = " SELECT Babel_Prime_Profil.name as Pname," .
                "           Babel_Prime_Profil_details.Annee1 as PdetAnnee," .
                "           Babel_Prime_Profil_details.SalaireFixe as PdetSalaireFixe,      " .
                "           Babel_Prime_Profil_details.SalaireVar as PdetSalaireVar,      " .
                "           Babel_Prime_Profil_details.ObjectifTot as PdetObjectifTot      " .
                "      FROM Babel_Prime_Profil " .
                " left join (Babel_Prime_Profil_details) on Babel_Prime_Profil_details.profil_refid = Babel_Prime_Profil.id  " .
                "     WHERE Babel_Prime_Profil.id = ".$Getprofilid ;
                //echo $requete."<BR>";
        $msql = $db->query($requete);
        //    $i_tmp=$db->num_rows($msql);
        $init = 0;
        while ($objabc = $db->fetch_object($msql))
        {
            $salaireFixe[$objabc->PdetAnnee] = $objabc->PdetSalaireFixe;
            $salaireVar[$objabc->PdetAnnee] = $objabc->PdetSalaireVar;

            $ObjectifTot[$objabc->PdetAnnee] = $objabc->PdetObjectifTot;


            //Fourchette
            $requete1 = "SELECT Babel_Prime_FourchPN.pourcentMin as FourchpourcentMin,      " .
                    "           Babel_Prime_FourchPN.pourcentMax as FourchpourcentMax,      " .
                    "           Babel_Prime_FourchPN.pourcentAnn as FourchpourcentAnn,      " .
                    "           Babel_Prime_CatPN.nameShort as PNCat_shortName      " .
                    "      FROM Babel_Prime_FourchPN," .
                    "           Babel_Prime_CatPN," .
                    "           Babel_Prime_li_CatPN_FourchPN_Babel_Prime_Profil " .
                    "     WHERE Babel_Prime_li_CatPN_FourchPN_Babel_Prime_Profil.FourchPN_refid = Babel_Prime_FourchPN.id  " .
                    "       AND Babel_Prime_CatPN.id = Babel_Prime_FourchPN.Babel_Prime_CatPN_refid".
                    "       AND Profil_refid = ".$Getprofilid;
            $sql1 = $db->query($requete1);
            if ($sql1)
            {
                while ($obj1 = $db->fetch_object($sql1))
                {
                    $pourcentAnn[$obj1->PNCat_shortName]=$obj1->FourchpourcentAnn;
                    $pourcentMax[$obj1->PNCat_shortName]=$obj1->FourchpourcentMax;
                    $pourcentMin[$obj1->PNCat_shortName]=$obj1->FourchpourcentMin;
                }
            }

            //Seuil

                    $requete2 = "SELECT Babel_Prime_Seuil.SeuilPrime, " .
                            "           Babel_Prime_Seuil.SeuilObj,     " .
                            "           Babel_Prime_Seuil.level,     " .
                            "           Babel_Prime_Profil_details.Annee1     " .
                    "      FROM Babel_Prime_Seuil," .
                    "           Babel_Prime_Profil_details," .
                    "           Babel_Prime_Li_Profil_details_Seuil " .
                    "     WHERE Babel_Prime_Li_Profil_details_Seuil.Profil_details_refid = Babel_Prime_Profil_details.id  " .
                    "       AND Babel_Prime_Li_Profil_details_Seuil.Seuil_refid = Babel_Prime_Seuil.id".
                    "       AND Babel_Prime_Profil_details.profil_refid = ".$Getprofilid;
            $sql1a = $db->query($requete2);
            //echo "<BR>".$requete2."<BR>";
            if ($sql1a)
            {
                while ($obj1 = $db->fetch_object($sql1a))
                {
                    $seuil[$obj1->Annee1][$obj1->level]['Prime']=$obj1->SeuilPrime;
                    $seuil[$obj1->Annee1][$obj1->level]['Objectif']=$obj1->SeuilObj;
                }
            }

        }
        print " <tr/>\n";
        print " <tr>\n";
        print "   <th align='center' name='headertable' >&nbsp;</th>\n";
        print "   <th align='center' name='headertable' >Min</th>\n";
        print "   <th align='center' name='headertable' >Max</th>\n";
        print "   <th align='center' name='headertable' >Communiqu&eacute;</th>\n";
        print " </tr>\n";
        print_pourcent($db,0,0,$pourcentMin,$pourcentMax,$pourcentAnn);

        print " <tr><td>&nbsp</tr>\n";
        print_Salaire(1,"1",$salaireFixe[1],$salaireVar[1],$ObjectifTot[1],0);
        print_Seuil(1,1,0,&$seuil);
        print " <tr><td>&nbsp</tr>\n";
        print_Salaire(0,"n",$salaireFixe[0],$salaireVar[0],$ObjectifTot[0],0);
        print_Seuil(0,1,0,&$seuil);
        print " <tr><td>&nbsp</tr>\n";

        print "</tr>\n";
        print "</TABLE>";
    }


    /*
     * Tableau Total Facture par mois
     */
//TODO : on considere la facture, pas la PN !!!
    if ("x".$userid  != "x" && $userid != 0)
    {

         //Get Total par mois valider par l'utilisateur
         $requete = "SELECT sum(total) as stotal_ht, month(datef) as monthFact" .
                "      FROM ".MAIN_DB_PREFIX."facture" .
                "     WHERE ".MAIN_DB_PREFIX."facture.fk_user_author = ".$userid .
                "       AND year(datef) =".$defaultYear .
                "  GROUP BY month(datef)";
        $arrFactur= array();
        //echo $requete;
        $resql = $db->query($requete);
        if ($resql)
        {
            while ($res=$db->fetch_object($resql))
            {
                $arrFactur[$res->monthFact]=$res->stotal_ht;
                print $res->monthFact . " " . $res->stotal_ht;
            }
        }
           // $yearSelected = $defaultYear;
            print "<TABLE  class='nobordernopadding'>";
            print "<TR>";
            print "<TH colspan='6'>R&eacute;sum&eacute; de la facturation";
            print "<TR>";
            for ($i=1;$i<7;$i++)
            {
                print "<TH style='width:110px'>".date("m-y",mktime(0,0,0,$i,1,$defaultYear));
            }
            print "<TR>";
            for ($i=1;$i<7;$i++)
            {
                if ($i%2 == 0) { $class = "pair"; } else  { $class = "impair"; }
                print "<TD class = $class style='width:110px;text-align:center;'>".price($arrFactur[$i]);
            }
            print "<TR>";
            for ($i=7;$i<13;$i++)
            {
                print "<TH style='width:110px'>".date("m-y",mktime(0,0,0,$i,1,$defaultYear));
            }
            print "<TR>";
            for ($i=7;$i<13;$i++)
            {
                if ($i%2 == 0) { $class = "pair"; } else  { $class = "impair"; }
                print "<TD class = $class style='width:110px;text-align:center;'>".price($arrFactur[$i]);
            }
            print "</TABLE><BR>";

    }


//TODO config  comment sont payes les primes ?
//30% a la commande
//50% a la facturation
//20% au paiement par le client

    /*
     * table => periode de paiement
     */
    if ("x".$userid  != "x" && $userid != 0)
    {

        //Set percent

        $perc_on_commande = 0.3;
        $perc_on_facture = 0.5;
        $perc_on_paiement = 0.2;
        $arrPer["commande"]=0.3;
        $arrPer["facture"]=0.5;
        $arrPer["paiement"]=0.2;
//TODO PN not CA
//principe
//Calcul du compte PN de l'utilisateur
//Calcul du montant de la prime % au seuil
//Calcul des versements

        // Recup les donnees
        // Recup donnees montant des commandes validees et non facturees
        // Recup donnees commande validee et facturee
        $requete = "SELECT sum(total) as stotal_ht" .
                "      FROM ".MAIN_DB_PREFIX."facture" .
                "     WHERE ".MAIN_DB_PREFIX."facture.fk_user_author = ".$userid .
                "       AND year(datef) =".$defaultYear .
                "  ";
        $Factur=0;
        //echo $requete;
        $resql = $db->query($requete);
        if ($resql)
        {
            $res=$db->fetch_object($resql);
            $Factur=$res->stotal_ht;
        }
        // Recup des donnees des factures payees
        // Recup donnees des versements effectues


       // $yearSelected = $defaultYear;
        print "<TABLE  class='nobordernopadding'>";
        print "<TR>";
        print "<TH colspan='6'>Planning Versement";
        print "<TR>";
        for ($i=1;$i<7;$i++)
        {
            print "<TH style='width:110px'>".date("m-y",mktime(0,0,0,$i,1,$defaultYear));
        }
        print "<TR>";
        for ($i=1;$i<7;$i++)
        {
            if ($i%2 == 0) { $class = "pair"; } else  { $class = "impair"; }
            print "<TD class = $class style='width:110px;text-align:center;'>".price(rand(0,20000));
        }
        print "<TR>";
        for ($i=7;$i<13;$i++)
        {
            print "<TH style='width:110px'>".date("m-y",mktime(0,0,0,$i,1,$defaultYear));
        }
        print "<TR>";
        for ($i=7;$i<13;$i++)
        {
            if ($i%2 == 0) { $class = "pair"; } else  { $class = "impair"; }
            print "<TD class = $class style='width:110px;text-align:center;'>".price(rand(0,20000));
        }
        print "</TABLE>";

        /*
         * affichage des propales signees
         */
         $table_length=28;
         $requete2 = "SELECT ".MAIN_DB_PREFIX."propal.ref as ref, " .
                 "           ".MAIN_DB_PREFIX."propal.total_ht as tht ," .
                 "           concat_WS('-',day(".MAIN_DB_PREFIX."propal.date_valid),month(".MAIN_DB_PREFIX."propal.date_valid),year(".MAIN_DB_PREFIX."propal.date_valid)) as dvalid, " .
                 "           ".MAIN_DB_PREFIX."societe.nom  as snom" .
                 "      FROM ".MAIN_DB_PREFIX."propal," .
                 "           ".MAIN_DB_PREFIX."societe " .
                 "     WHERE ".MAIN_DB_PREFIX."societe.rowid = ".MAIN_DB_PREFIX."propal.fk_soc" .
                 "       AND fk_user_author = ".$userid." " .
                 "       AND fk_statut = 2" .
                 "       AND year(".MAIN_DB_PREFIX."propal.date_valid)  = ".$defaultYear .
                 "  ORDER BY date_valid DESC";
                //echo $requete2;
        $sql2 = $db->query($requete2);
        print "<DIV id='displayPropal' style='position: fixed; right: 0px; top:25px;  border-style: solid ; border-width: 6px; border-color:#FFFFFF; ' >";
        print "<TABLE class='nobordernopadding' >\n";
        print "<DIV class=\'nobordernopadding\' style='zindex:10000; position: fixed' ><button class='button' id='propalBut' style='padding: 3pt 2pt 3pt 2pt;' onClick='hidePropal()'>".img_picto($langs->trans('showUserPropal'),'object_propal')."-</button></DIV>";
        print "<TR><TH colspan=4>Propositions sign&eacute;es<TR><TH>date<TH>Ref:<TH>Total HT<TH>Client</TR>" ;
        $i=0;
        while ($obj2 = $db->fetch_object($sql2) )
        {
            if ($i == 0)
            {
                $class='pair';
                $i=1;
            } else {
                $class='impair';
                $i=0;
            }
            print "<TR class='".$class."' style='height: 20px;'><TD nowrap  style='padding-left: 5pt; width: 120px;'>".$obj2->dvalid."<TD nowrap style='padding-left: 5pt; width: 120px;'>".$obj2->ref . "<TD nowrap style='width: 80px; text-align:center; '>".price($obj2->tht)."<TD nowrap style='width: 150px; padding-left: 5pt; text-align:left;'>".$obj2->snom."</TR>" ;
            $table_length += 14;
        }
        print "</TABLE></DIV>\n";

        /*
         * affichage des factures payees
         */
         $requete2 = "SELECT ".MAIN_DB_PREFIX."propal.ref as ref, " .
                 "           ".MAIN_DB_PREFIX."propal.total_ht as tht ," .
                 "           concat_WS('-',day(".MAIN_DB_PREFIX."propal.date_valid),month(".MAIN_DB_PREFIX."propal.date_valid),year(".MAIN_DB_PREFIX."propal.date_valid)) as dvalid, " .
                 "           ".MAIN_DB_PREFIX."societe.nom  as snom" .
                 "      FROM ".MAIN_DB_PREFIX."propal," .
                 "           ".MAIN_DB_PREFIX."societe " .
                 "     WHERE ".MAIN_DB_PREFIX."societe.rowid = ".MAIN_DB_PREFIX."propal.fk_soc" .
                 "       AND fk_user_author = ".$userid." " .
                 "       AND fk_statut = 2" .
                 "       AND year(".MAIN_DB_PREFIX."propal.date_valid)  = ".$defaultYear .
                 "  ORDER BY date_valid DESC";
                //echo $requete2;
        $sql2 = $db->query($requete2);
        $table_length += 100;
        print "<DIV id='displayFacture' style='position: fixed; right: 0px; top:". $table_length ."px;  border-style: solid ; border-width: 6px; border-color:#FFFFFF; ' >";
        print "<TABLE class='nobordernopadding' >\n";
        print "<DIV class=\'nobordernopadding\' style='zindex:10000; position: fixed' ><button class='button' id='factureBut'  style='padding: 3pt 2pt 3pt 2pt;'  onClick='hideFacture()'>".img_picto($langs->trans('showUserFacturePaye'),"object_bill")."-</button></DIV>";
        print "<TR><TH colspan=4>Factures pay&eacute;es<TR><TH>date<TH>Ref:<TH>Total HT<TH>Client</TR>" ;
        $i=0;
        while ($obj2 = $db->fetch_object($sql2) )
        {
            if ($i == 0)
            {
                $class='pair';
                $i=1;
            } else {
                $class='impair';
                $i=0;
            }
            print "<TR class='".$class."' style='height: 20px;'><TD nowrap  style='padding-left: 5pt; width: 120px;'>".$obj2->dvalid."<TD nowrap style='padding-left: 5pt; width: 120px;'>".$obj2->ref . "<TD nowrap style='width: 80px; text-align:center; '>".price($obj2->tht)."<TD nowrap style='width: 150px; padding-left: 5pt; text-align:left;'>".$obj2->snom."</TR>" ;
        }
        print "</TABLE></DIV>\n";
    }

//liste des produits et services vendu par le commercial
// produit ou service
// sous traite ou pas
// commande chez fournisseur
// stock
// question : est ce que si commande chez fournisseurs => stock ? est ce que oblige de passer par stock ? (livraison chez le client directe)
$requeteProd = "SELECT * FROM ";





/*requete => categorie, prix et total pour 1 utilisateur pour 1 commande
 * select  Babel_Prime_CatPN.name , qty, ".MAIN_DB_PREFIX."commandedet.price, subprice, ".MAIN_DB_PREFIX."commandedet.total_ht  from Babel_Prime_CatPN,  ".MAIN_DB_PREFIX."product, ".MAIN_DB_PREFIX."commandedet , ".MAIN_DB_PREFIX."commande where ".MAIN_DB_PREFIX."commande.fk_user_author = 2 AND  ".MAIN_DB_PREFIX."commandedet.fk_commande = ".MAIN_DB_PREFIX."commande.rowid AND ".MAIN_DB_PREFIX."commandedet.fk_product = ".MAIN_DB_PREFIX."product.rowid  AND ".MAIN_DB_PREFIX."product.Categorie_refid = Babel_Prime_CatPN.id ;
 *
 *
 */


/*
 * statut  fk_statut
 * -1 annuler
 * 0 brouillon
 * 1 open
 * 2 signer
 * 3 cloturer
 * 4 Classer
 */
 /*
  *
  * select (".MAIN_DB_PREFIX."product.price - ".MAIN_DB_PREFIX."product_fournisseur_price.price) AS PN   from ".MAIN_DB_PREFIX."product_fournisseur_price,  ".MAIN_DB_PREFIX."commandedet, ".MAIN_DB_PREFIX."product, ".MAIN_DB_PREFIX."product_fournisseur  where fk_commande = 10 AND ".MAIN_DB_PREFIX."product.rowid = ".MAIN_DB_PREFIX."commandedet.fk_product AND ".MAIN_DB_PREFIX."product_fournisseur.fk_product = ".MAIN_DB_PREFIX."product.rowid and ".MAIN_DB_PREFIX."product_fournisseur.rowid = ".MAIN_DB_PREFIX."product_fournisseur_price.fk_product_fournisseur ;
  *
  *
  */
?>
<script type="text/javascript">
    hideFacture();
    hidePropal();
</script>