<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.1
  * Created on : 12 juil. 09
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : migration.php
  * magentoGLE
  */


//TODO : que faire des produits dont une categorie a ete supprimee? => la mettre dans root ? effacer ? proposer ? ou voir ref produit
//TODO : Update check : TODO ajouter active, position, level, parentid
//TODO :> ajouter la ref produit, la ref permettant de faire le lien  entre cat et prod
//TODO jquery -> presentation generale
//            -> tabs pour le menu principal
//TODO expedition , livraison , stock


require_once('pre.inc.php');
//  <link type="text/css" href="http://jqueryui.com/latest/themes/base/ui.all.css" rel="stylesheet" />

//$header .= ' <script src="'.$jqueryuipath.'/ui.selectmenu.js" type="text/javascript"></script>';
//$header .= " <script > jQuery(document).ready(function(){ jQuery('select').selectmenu(); });  </script>\n";


$header = <<<EOF
  <link type="text/css" href="css/multiline.tabs.css" rel="stylesheet" />
  <link type="text/css" href="css/ui.all.css" rel="stylesheet" />

      <script type="text/javascript" src="jquery/jquery-1.3.2.js" ></script>
      <script type="text/javascript" src="js/jquery.bgiframe.js" ></script>
      <script type="text/javascript" src="js/jquery.dimensions.js" ></script>
      <script type="text/javascript" src="js/jquery.tooltip.js" ></script>
      <script type="text/javascript" src="jquery/ui/jquery-ui.js" ></script>
      <script type="text/javascript" src="jquery/ui/ui.tabs.js" ></script>
      <script type="text/javascript">
      var updown=0;
      $(document).ready(function(){
        if ($("#tabs"))
             $("#tabs").tabs({cache: true,fx: { opacity: 'toggle' },
        spinner:"Chargement ...",});
      });
      function selectNone(id)
      {
        $('#catId'+id).find("input").each(function()
        {
            $(this).attr('checked', false);
        });

      }
      function selectAll(id)
      {
        //alert ($('catId'+id+" input"));
        $('#catId'+id).find("input").each(function(i)
        {
            $(this).attr('checked', true);
        });

      }
      function invert(id)
      {
        $('#catId'+id).find("input").each(function()
        {
            var tmp = $(this).attr("checked");
            tmp = !tmp;
            $(this).checked=$(this).attr('checked', tmp);
        });

      }
      function invertCat()
      {
        $("#catTable input").each(function()
        {
            var tmp = $(this).attr("checked");
            tmp = !tmp;
            $(this).checked=$(this).attr('checked', tmp);
//            $(this).attr('checked',$(this).attr('checked', tmp);

        });
      }
      function selectAllCat()
      {
        $("#catTable input").each(function()
        {
            $(this).attr('checked', true);
        });
      }
      function selectNoneCat()
      {
        $("#catTable input").each(function()
        {
            $(this).attr('checked', false);
        });
      }

      </script>
EOF;

$action = $_REQUEST['action'];
$actionPhase2 = $_REQUEST['actionPhase2'];


llxHeader($header);
//Nav bar
print "<div style='text-align: right;'>";
print "<a href='migration.php'>Magento Admin</a>";
print "</div>";

require('Var_Dump.php');
Var_Dump::displayInit(array('display_mode' => 'HTML4_Text'), array('mode' => 'normal','offset' => 4));


switch($action)
{
    case 'productCat':
        require_once(DOL_DOCUMENT_ROOT."/Babel_Magento/magento_product.class.php");
        require_once(DOL_DOCUMENT_ROOT."/Babel_Magento/magento_tools.class.php");
        $mag=new magento_product($conf);
        $mag->connect();
        $mgt = new magento_tools($db);

        if ($_REQUEST['resetGLE']=='on')
        {
            //delete all productCat in GLE
            //delete all images and addon datas
            //import all productCat from magento
            //import all images
        } else if($_REQUEST['syncGLEmag']=='on')
        {
            //list all productCat in GLE not in magento
            //export them
            //export images
        } else if($_REQUEST['syncmagGLE']=='on')
        {
            //category
            $list=$mag->prod_cat_list();
            $mag->parseProdCat($list);

            $mgt->listCatGLE(false);
            $arrTmp = $mgt->catGLEArr['MAGID'];
            //var_dump::Display($arrTmp);
            print "<form action='migration.php' method='POST'> ";
            print "<input type='hidden' name='actionPhase2' value='productCat' >";
            print "<div id='catTable'>";
            print "<table cellspacing=0  style='border-collaspe: collapse; border-spacing: 0; border:1px Solid #FFFFFF; width: 30%; padding: 0;'><thead><tr  style='text-align: center;' class='liste_titre'><td>Cat&eacute;gorie</td><td>Action</td><td align='right'>Selection?</td></tr></thead>";
            print "<tbody >";
            $arrClass= array(true => "pair", false => "impair");
            $switch = false;
            foreach($mag->catArr as $key=>$val)
            {
                $syncOp = $mgt->testSyncCatMagToGLE($val);
                if ($syncOp == "INSERT")
                {
                    $switch=!$switch;
                    print "<tr style='text-align: center; ' class='".$arrClass[$switch]."'><td style='border-right: 1px Solid #FFFFFF;'>".$val['name']."</td><td style='border-right: 1px Solid #FFFFFF;'>Ajout</td><td  align='right'><input name='i".$val['MagcatId']."' type='checkbox'></input></td></tr>";
                    if ($arrTmp[$val['MagcatId']])
                        $arrTmp[$val['MagcatId']]=false;
                } else if ($syncOp == "UPDATE")
                {
                    $switch=!$switch;
                    print "<tr  style='text-align: center;' class='".$arrClass[$switch]."'><td style='border-right: 1px Solid #FFFFFF;'>".$val['name']."</td><td style='border-right: 1px Solid #FFFFFF;'>Mise &agrave; jour</td><td align='right'><input name='u".$val['MagcatId']."' type='checkbox'></input></td></tr>";
                    if ($arrTmp[$val['MagcatId']])
                        $arrTmp[$val['MagcatId']]=false;
                }  else if ($syncOp == "OK")
                {
//                    print "<tr><td>";
                    //print "<tr><td>Mise &agrave; jour de la cat&eacute;gory ".$val['name']."</td><td><input name='".$val['MagcatId']."' type='checkbox'></input></td></tr>";
                    if ($arrTmp[$val['MagcatId']])
                    {
                        $arrTmp[$val['MagcatId']]=false;
                    }
                }
            }
            foreach($arrTmp as $magId=>$label)
            {
                if ($label)
                {
                    $switch=!$switch;
                    print "<tr  style='text-align: center;' class='".$arrClass[$switch]."'><td style='border-right: 1px Solid #FFFFFF;'>".$label."</td><td style='border-right: 1px Solid #FFFFFF;'>Efface</td><td align='right'><input name='e".$magId."' type='checkbox'></input></td></tr>";
                }
            }
            print "<tr><td colspan='3'><a href='#' onclick=invertCat()>Intervetir </a><a href='#' onclick=selectAllCat()> Tous </a><a href='#' onclick=selectNoneCat()> Aucun </a></td></tr>";
            print "</tbody></table>";
            print "</div>";
            print "<input type='submit'></input>";
            print "</form>";

            //cf magnto_tools.class.php
            //list all productCat in magento not in GLE
            //import them
            //import images

        }
        $mag->disconnect();
    break;
    case 'product':
        require_once(DOL_DOCUMENT_ROOT."/Babel_Magento/magento_product.class.php");
        require_once(DOL_DOCUMENT_ROOT."/Babel_Magento/magento_tools.class.php");
        $mag=new magento_product();
        $mag->connect();
        $mgt = new magento_tools($db);

        if ($_REQUEST['resetGLE']=='on')
        {
            //delete all productCat in GLE
            //delete all images and addon datas
            //import all productCat from magento
            //import all images
        } else if($_REQUEST['syncGLEmag']=='on')
        {
            //list all productCat in GLE not in magento
            //export them
            //export images
        } else if($_REQUEST['syncmagGLE']=='on')
        {
            print "<span>loading</span>";
            //category
            $list=$mag->prod_prod_list();
            $mag->parseProdList($list);
            //Var_Dump::display($mag->ProdByCatArr);
            //Var_Dump::Display($list);

            //Get Categorie:
            //Si categorie pas dans GLE => exit => ask for cat sync
            foreach($mag->ProdByCatArr as $key => $val)
            {
                $requete = "SELECT *
                              FROM babel_categorie
                             WHERE magento_id = ".$key;
                $sql = $db->query($requete);
                $res = $db->fetch_object($sql);
                if ($res->rowid ."x" == "x")
                {
                    print "Toutes les cat&eacute;gories Magento n'existent pas dans GLE. Merci de synchroniser les cat&eacute;gories";
                    break;
                }
            }
            $mode = "";
            //TODO Prob, un produit peut etre dans plusieurs categories
            //TODO efface produit
            //TODO table ".MAIN_DB_PREFIX."categorie_product
            //TODO stock
            $cntProd = array();
            $bool=false;
            $color[false]="#FF0000";
            $color[true]="#0000FF";
            $list= $mag->prod_cat_list();
            $mag->parseCat($list);
            //var_dump::Display($mag->catNameArr);
            print "<form action='migration.php' method='POST'> ";
            print "<input type='hidden' name='actionPhase2' value='product' >";
            print "<div id='tabs'>";
            print '<ul class="tabContainer">';
            foreach ($mag->ProdByCatArr as $key=>$val)
            {
                $catName = $mag->catNameArr[$key];
                print '<li style="float: left; padding-bottom: 8px ;padding-right: 5px ; margin-top: -5px; margin-left: -6px;"><a href="#catId'.$key.'"><span>'.$catName.'</span></a></li>';
            }
                print '</ul>';
            foreach ($mag->ProdByCatArr as $key=>$val)
            {
                $bool=!$bool;
                $catName = $mag->catNameArr[$key];
                //prod_prod_info
                print "<div id='catId".$key."'>";
                print "<br>".$catName." ".$key."<br>";
                print "<a href='#' onClick='invert(".$key.")'>Invers&eacute;</a>";
                print "<a href='#' onClick='selectAll(".$key.")'>Tous</a>";
                print "<a href='#' onClick='selectNone(".$key.")'>Aucun</a>";
                print "<table style='background-color: ".$color[$bool]."'><tbody>";
                $mgt->testSyncProdToGLE($val,$key);
                foreach ($mgt->modeProd as $key1 => $val1)
                {
                    if ($val1['mode'] == "UPDATE")
                    {
                        print "<tr><td>Mise &agrave; jour du produit ".$val1['name']."</td><td><input name='u".$val1['id']."' type='checkbox'></input></td></tr>";
                    } else if ($val1['mode'] == "INSERT")
                    {
                        print "<tr><td>Ajout du produit ".$val1['name']."</td><td><input name='i".$val1['id']."' type='checkbox'></input></td></tr>";
                    } else if ($val1['mode'] == "OK")
                    {
                        //print "<tr><td>OK produit ".$val1['name']."</td><td><input name='u".$val1['id']."' type='checkbox'></input></td></tr>";
                    }
                }
                print "</tbody></table>";
                print "</div>";
            }
            print "</tbody></table>";
            print "</div>";
            print "<input type='submit'></input>";
            print "</form>";
        }
        $mag->disconnect();
    break;
    case 'user':
    {
        //TODO group, billing address default shipping
        require_once(DOL_DOCUMENT_ROOT."/Babel_Magento/magento_customer.class.php");
        require_once(DOL_DOCUMENT_ROOT."/Babel_Magento/magento_tools.class.php");
        $mag=new magento_customer($conf);
        $mag->connect();
        $mgt = new magento_tools($db);
        //get List of user
        $list = $mag->customer_list();
        $list1 = $mag->sales_list();
        //var_dump::display($list1);
        foreach( $list as $key=>$val )
        {
            $custId = $val["customer_id"];
            $created_at = $val["created_at"];
            $increment_id = $val["increment_id"];
            $store_id = $val["store_id"];
            $website_id = $val["website_id"];
            $default_billing = $val["default_billing"];
            $default_shipping = $val["default_shipping"];
            $email = $val["email"];
            $firstname = $val["firstname"];
            $group_id = $val["group_id"];
            $lastname = $val["lastname"];
            $middlename = $val["middlename"];
            $prefix = $val["prefix"];
            $suffix = $val["suffix"];
            $numTVA = $val["taxvat"];
            $birth = $val['dob'];
            $typent = 8; //particulier
            $isSoc=false;
            if ($numTva . "x" != "x")
            {
                $typent = 1; //societe ou 2 3 4 5 startup -> societe
                $isSoc=true;
            }
            //a t il commandé?
            $isAprospect = true;
            foreach($list1 as $ky => $vl)
            {
                if ($custId == $vl["customer_id"])
                {
                    $isAprospect=false;
                    break;
                }
            }
            if (!$isAprospect)
            {
                //soc datas
                require_once(DOL_DOCUMENT_ROOT."/societe/class/societe.class.php");
                $newsoc = new Societe($db);
                $newsoc->code_client =-1;
                $newsoc->code_fournisseur = 0;
                $newsoc->nom = $lastname . " ". $firstname;
                $newsoc->address = $address;
                $newsoc->zip = $address;
                $newsoc->town = $ville;
                $newsoc->pays_id = $pays_id;
                $newsoc->departement_id = $departement_id;
                $newsoc->phone = $tel;
                $newsoc->email = $email;
                $newsoc->note = $note;
                $newsoc->tva_intra = $numTva;
                $newsoc->forme_juridique_code = "???";
                $newsoc->fk_typent = $typent;

            //extra info
            print "cust address<br>";
            var_dump::display($mag->customer_address($custId));
            print "cust address info<br>";
            var_dump::display($mag->customer_address_info($custId));
            $custaddinfo = $mag->customer_address_info($custId);
            //get the default
            $defaultId = 0;
            foreach ($custaddinfo as $key=>$val)
            {
                if ($val["is_default_billing"])
                {
                    $defautlId = $key;
                    break;
                }
            }
            $city = $custaddinfo[$defaultId]['city'];
            $company_name = $custaddinfo[$defaultId]['company'];
            $country_id = $custaddinfo[$defaultId]['country_id'];
            $fax = $custaddinfo[$defaultId]['fax'];
            $firstname = $custaddinfo[$defaultId]['firstname'];
            $lastname = $custaddinfo[$defaultId]['lastname'];
            $postcode = $custaddinfo[$defaultId]['postcode'];
            $region = $custaddinfo[$defaultId]['region'];
            $region_id = $custaddinfo[$defaultId]['region_id'];
            $street = $custaddinfo[$defaultId]['street'];
            $tel = $custaddinfo[$defaultId]['telephone'];
            //manque address, savoir si il a commander, et si entrerpise ou pas
            //Dans address, on a le company name, si c'est le cas, on a la societe
            //si pas compagny => particulier

                if (!$isSoc)
                {
                    $newsoc->nom .= $email;
                }
                $newsoc->create();
            //contact Datas
            require_once(DOL_DOCUMENT_ROOT."/contact/class/contact.class.php");
            $cont = new Contact($db);

            //Pour tous les autre add de contact
            $arrContact = array();
            foreach ($custaddinfo as $key=>$val)
            {
                if ($key != $defaultId)
                {
                    $cont_city = $custaddinfo[$defaultId]['city'];
                    $cont_company_name = $custaddinfo[$defaultId]['company'];
                    $cont_country_id = $custaddinfo[$defaultId]['country_id'];
                    $cont_fax = $custaddinfo[$defaultId]['fax'];
                    $cont_firstname = $custaddinfo[$defaultId]['firstname'];
                    $cont_lastname = $custaddinfo[$defaultId]['lastname'];
                    $cont_postcode = $custaddinfo[$defaultId]['postcode'];
                    $cont_region = $custaddinfo[$defaultId]['region'];
                    $cont_region_id = $custaddinfo[$defaultId]['region_id'];
                    $cont_street = $custaddinfo[$defaultId]['street'];
                    $cont_tel = $custaddinfo[$defaultId]['telephone'];
                    //$cont->create();
                    //if societe ou pas
                    // remplit objet
                    //create contact
                }
            }
//main contact

                $cont->name = $lastname;
                $cont->socid = 0;// si pas entrepris
                $cont->priv=0;
                $cont->firstname = $firstname;
                $cont->email = $email;
                $cont->birthday = $birth;
                $cont->ville = "";
                $cont->fk_pays = "";
                $cont->address = "";
                $cont->civility = "";
                $cont->cp = "";
                $cont->civility = "";
                $cont->note = "";
//$cont->create();


            }


            //synchro que des clients

            //creer societe et contact
        }

        //compare with prospect and customers
        //

    }
    break;
    case 'sales':
    {
        require_once(DOL_DOCUMENT_ROOT."/Babel_Magento/magento_sales.class.php");
        require_once(DOL_DOCUMENT_ROOT."/Babel_Magento/magento_tools.class.php");
        $mag=new magento_sales($conf);
        $mag->connect();
        $mgt = new magento_tools($db);

        //chaque demande est une commande


    }
    break;

}
if ($actionPhase2 ."x" != "x")
{
    require_once(DOL_DOCUMENT_ROOT."/Babel_Magento/magento_tools.class.php");
    $mgt = new magento_tools($db);
//var_dump($_REQUEST);
    switch ($actionPhase2)
    {
        case "productCat":
        {
            require_once(DOL_DOCUMENT_ROOT."/Babel_Magento/magento_product.class.php");
            $mag=new magento_product($conf);
            $mag->connect();
                $list=$mag->prod_cat_list();
            $mag->parseProdCat($list);
            foreach($_REQUEST as $key=>$val)
            {
                if (preg_match("/^([uie]{1})([0-9]*)$/",$key,$arr))
                {
                    $magid = $arr[2];
                    $catName = $mag->catArr[$magid]['name'];
                    $is_active = $mag->catArr[$magid]['is_active'];
                    $position = $mag->catArr[$magid]['position'];
                    $level = $mag->catArr[$magid]['level'];
                    $magparent_id = $mag->catArr[$magid]['parent_id'];

                    if ($arr[1] == "i")
                    {
                        print "INSERT INTO babel_categorie ". $magid."<br>";
                        $requete = "INSERT INTO babel_categorie (label, description, visible, type, magento_id, position, level, magento_product)
                                                         VALUES ('".$catName."','Categorie Magento ".$catName."','".$is_active."',1,'".$magid."','".$position."','".$level."',1)";

                        $db->query($requete);
                    } else if ($arr[1] == "u")
                    {
                        print "UPDATE babel_categorie ". $magid."<br>";
                        $requete = "UPDATE babel_categorie SET label='".$catName."',
                                                           description='Categorie Magento ".$catName."',
                                                           visible='".$is_active."',
                                                           type=1,
                                                           magento_id='".$magid."',
                                                           position = '".$position."',
                                                           level = '".$level."',
                                                           magento_product = 1
                                        WHERE magento_id = ".$magid;
                        $db->query($requete);

                    } if ($arr[1] == "e")
                    {
                        print "DELETE FROM babel_categorie ". $magid."<br>";
                        $requete = "DELETE FROM ".MAIN_DB_PREFIX."categorie_association WHERE fk_categorie = ".$magid ." OR fk_categorie = ".$magid ;
                        $db->query($requete);
                        $requete = "DELETE FROM ".MAIN_DB_PREFIX."categorie
                                           WHERE magento_id = ".$magid;
                        $db->query($requete);
                    }

                }
            }
            //Get GLE parent
            foreach($_REQUEST as $key=>$val)
            {
                if (preg_match("/^([ui]{1})([0-9]*)$/",$key,$arr))
                {
                    $magid = $arr[2];
                    $MagentoParent_id = $mag->catArr[$magid]['parent_id'];
                    $requeteId = "SELECT rowid AS gleId
                                    FROM babel_categorie
                                   WHERE magento_id = ".$magid;
                    $sql = $db->query($requeteId);
                    $res = $db->fetch_object($sql);
                    $gleId = $res->gleId;
                    $requeteId = "SELECT rowid AS gleParentId
                                    FROM babel_categorie
                                   WHERE magento_id = ".$MagentoParent_id;
                    $sql = $db->query($requeteId);
                    $res = $db->fetch_object($sql);
                    $gleParentId = $res->gleParentId;
                    if ($res->gleParentId > 0)
                    {
                        $requete = "DELETE FROM ".MAIN_DB_PREFIX."categorie_association
                                          WHERE fk_categorie_fille = ".$gleId;
                        $db->query($requete);
                        $requete = "INSERT INTO ".MAIN_DB_PREFIX."categorie_association
                                                (fk_categorie_mere, fk_categorie_fille)
                                         VALUES ($gleParentId,$gleId)";
                        $sql = $db->query($requete);
                    }
                }
            }
        }
        break;
        case "product":
        {
            require_once(DOL_DOCUMENT_ROOT."/Babel_Magento/magento_product.class.php");
            $mag=new magento_product($conf);
            $mag->connect();

            foreach($_REQUEST as $key=>$val)
            {
                if (preg_match("/^([uie]{1})([0-9]*)$/",$key,$arr))
                {
                    $magProdid = $arr[2];
                    $prodInfo = $mag->prod_prod_info($magProdid);
                    switch ($arr[1])
                    {
                        case "u":
                            require_once (DOL_DOCUMENT_ROOT."/product/class/product.class.php");
                            $prod = new Product($db);
                            $requete = "SELECT  * FROM ".MAIN_DB_PREFIX."product WHERE magento_id =". $magProdid;
                            $sql = $db->query($requete);
                            $res = $db->fetch_object($sql);
                            $gleId = $res->rowid;
                            $prod->ref = $prodInfo["sku"];
                            $prod->libelle = $prodInfo["name"];
                            //Prob TVA
                            $tva = 19.6;
                            if ($prodInfo["tax_class_id"]==1)
                            {
                                $tva = 19.6;
                            }
                            $prod->tva_tx = $tva;
                            $prod->price = $prodInfo["price"];
                            $prod->status = $prodInfo["status"];
                            $prod->catid =  $prodInfo["categories"][0];
                            $prod->magCat =  $prodInfo["categories"];
                            $prod->description =  $prodInfo["description"];
                            $prod->note =  $prodInfo["short_description"];
                            //
                            $prod->stock_loc =  "";
                            //
                            $prod->weight =  $prodInfo["weight"];
                            $prod->weight_units =  "kg";
                            $prod->volume =  "";
                            $prod->volume_units = "";
                            $prod->id = $gleId;
                            $test = $prod->update_magento($gleId,$user);
                            if ($test == -2)
                                print $prod->error;
                            //TODO add to category
                            //TODO Stock

                        break;
                        case "i":
                            require_once (DOL_DOCUMENT_ROOT."/product/class/product.class.php");
                            $prod = new Product($db);
                            $prod->magento_Sku = $prodInfo['sku'];
                            $prod->magento_Id = $prodInfo['product_id'];
                            $prod->magento_Type = $prodInfo['type'];
                            $prod->magento_Cat = $prodInfo['categories'][0];

                            $prod->ref = $prodInfo["sku"];
                            $prod->libelle = $prodInfo["name"];
                            //Prob TVA
                            $tva = 19.6;
                            if ($prodInfo["tax_class_id"]==1)
                            {
                                $tva = 19.6;
                            }
                            $prod->tva_tx = $tva;
                            $prod->price = $prodInfo["price"];

                            $prod->status = $prodInfo["status"];
                            $prod->catid =  $prodInfo["categories"][0];
                            $prod->magCat =  $prodInfo["categories"];
                            $prod->description =  $prodInfo["description"];
                            $prod->note =  $prodInfo["short_description"];
                            //
                            $prod->stock_loc =  "";
                            $prod->type=1;
                            //
                            $prod->weight =  $prodInfo["weight"];
                            $prod->weight_units =  "kg";
                            $prod->volume =  "";
                            $prod->volume_units = "";
                            $id = $prod->create_magento($user);
                            if ($id > 0)
                            {
                                $prod->id = $id;
                                $prod->update_magento($id,$user);
                            }
                            //TODO add to category
                            //TODO Stock
                            //TODO Photos
                        break;
                        case "e":
                            $requete = "SELECT  *
                                          FROM ".MAIN_DB_PREFIX."product
                                         WHERE magento_id =". $magProdid;
                            $sql = $db->query($requete);
                            $res = $db->fetch_object($sql);
                            $gleId = $res->rowid;
                            $prod = new Product($db);
                            $prod->id = $magProdid;
                            //TODO delete photo
                            $prod->delete_magento($magProdid);

                        break;
                    }
                }
            }
        }
        break;
        case 'user':
        {
            require_once(DOL_DOCUMENT_ROOT."/Babel_Magento/magento_customer.class.php");
            $mag=new magento_customer($conf);
            $mag->connect();

        }
        break;
        case 'sales':
        {
            require_once(DOL_DOCUMENT_ROOT."/Babel_Magento/magento_sales.class.php");
            $mag=new magento_sales($conf);
            $mag->connect();

        }
        break;

    }//fin switch
}//fin if

if ($action == "" && $actionPhase2 == "")
{
    //Goal :
    //Sync product between database
print <<<EOF
<form action='migration.php?action=productCat' method='POST' name='productCat' id='productCat' style="width: 400px;">
<fieldset>
<legend>&nbsp;&nbsp;Cat&eacute;gories&nbsp;&nbsp;</legend>
<table width=100% style='border-collapse: collapse; text-align: center;' border=1><tr class="pair"><td>
<label for='resetGLE'>Reset GLE</label></td><td>
<input type=checkbox name="resetGLE"></input>
</td></tr><tr class="impair"><td>
<label for='syncGLEmag'>Synchro GLE -> Magento</label></td><td>
<input type=checkbox name="syncGLEmag"></input>
</td></tr><tr  class="pair"><td>
<label for='syncmagGLE'>Synchro Magento -> GLE</label></td><td>
<input type=checkbox name="syncmagGLE"></input>
</td></tr><tr><td colspan="2">
<input type="button" class="butAction" onClick="document.getElementById('productCat').submit();" value="GO!"></input>
</td></tr>
</table>
</fieldset>

</form>

EOF;
//Sync Prod
print <<<EOF
<form action='migration.php?action=product' method='POST' name='product' id='product' style="width: 400px;">
<fieldset>
<legend>&nbsp;&nbsp;Produits&nbsp;&nbsp;</legend>
<table width=100% style='border-collapse: collapse; text-align: center;' border=1><tr class="pair"><td>
<label for='resetGLE'>Reset GLE</label></td><td>
<input type=checkbox name="resetGLE"></input>
</td></tr><tr class="impair"><td>
<label for='syncGLEmag'>Synchro GLE -> Magento</label></td><td>
<input type=checkbox name="syncGLEmag"></input>
</td></tr><tr  class="pair"><td>
<label for='syncmagGLE'>Synchro Magento -> GLE</label></td><td>
<input type=checkbox name="syncmagGLE"></input>
</td></tr><tr><td colspan="2">
<input type="button" class="butAction" onClick="document.getElementById('product').submit();" value="GO!"></input>
</td></tr>
</table>
</fieldset>

</form>

EOF;

    //Sync users
print <<<EOF
<form action='migration.php?action=user' method='POST'  name='user' id='user' style="width: 400px;">
<fieldset>
<legend>&nbsp;&nbsp;Utilisateurs&nbsp;&nbsp;</legend>
<table width=100% style='border-collapse: collapse; text-align: center;' border=1><tr class="pair"><td>
<label for='resetGLE'>Reset GLE</label></td><td>
<input type=checkbox name="resetGLE"></input>
</td></tr><tr class="impair"><td>
<label for='syncGLEmag'>Synchro GLE -> Magento</label></td><td>
<input type=checkbox name="syncGLEmag"></input>
</td></tr><tr  class="pair"><td>
<label for='syncmagGLE'>Synchro Magento -> GLE</label></td><td>
<input type=checkbox name="syncmagGLE"></input>
</td></tr><tr><td colspan="2">
<input type="button" class="butAction"  onClick="document.getElementById('user').submit();" value="GO!"></input>
</td></tr>
</table>
</fieldset>

</form>

EOF;
    //Sync ventes / commande ...
print <<<EOF
<form action='migration.php?action=sales' method='POST'  name='sales' id='sales' style="width: 400px;">
<fieldset>
<legend>&nbsp;&nbsp;Ventes / Commandes&nbsp;&nbsp;</legend>
<table width=100% style='border-collapse: collapse; text-align: center;' border=1><tr class="pair"><td>
<label for='resetGLE'>Reset GLE</label></td><td>
<input type=checkbox name="resetGLE"></input>
</td></tr><tr class="impair"><td>
<label for='syncGLEmag'>Synchro GLE -> Magento</label></td><td>
<input type=checkbox name="syncGLEmag"></input>
</td></tr><tr  class="pair"><td>
<label for='syncmagGLE'>Synchro Magento -> GLE</label></td><td>
<input type=checkbox name="syncmagGLE"></input>
</td></tr><tr><td colspan="2">
<input type="button" class="butAction"  onClick="document.getElementById('sales').submit();" value="GO!"></input>
</td></tr>
</table>
</fieldset>

</form>

EOF;

}

?>