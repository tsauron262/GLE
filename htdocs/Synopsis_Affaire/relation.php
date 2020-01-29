<?php
/*
  ** BIMP-ERP by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.2
  * Created on : 27 juil. 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : relation.php
  * BIMP-ERP-1.2
  */

  //TOO graphiz
  require_once('pre.inc.php');
  require_once 'Image/GraphViz.php';

  $action = $_REQUEST['action'];

  require_once('Affaire.class.php');
  if ($action <> 'image' && $action <> 'map')
  {
       require_once('fct_affaire.php');
       $langs->load('affaires');
       if (!$user->rights->affaire->lire) accessforbidden();
       $affaireid=$_REQUEST['id'];
       $affaire = new Affaire($db);
       $affaire->fetch($affaireid);
       llxHeader("","Affaire - PN","",1);
       print_cartoucheAffaire($affaire,'Relation',$_REQUEST['action']);
       print "<br/>";
       print "<div>";
       print "<img  USEMAP='#G' style=' border:0px;    -moz-box-shadow: 1px 1px 3px 1px #5555ff,1px 0px #5FF5ff; -webkit-box-shadow: 1px 1px 2px #5555ff;' src='".$_SERVER['PHP_SELF']."?action=image&id=".$affaireid."'/>";
       $graph = drawGraph($affaire);
       $graph->saveParsedGraph('/tmp/log1.log');
       print $graph->image('cmapx','dot');
       print "</div>";
  }
  if ($action == 'image' || $action == 'map')
  {
      $affaireid=$_REQUEST['id'];
      $affaire = new Affaire($db);
      $affaire->fetch($affaireid);
      $graph = drawGraph($affaire);

    //$gv->addEdge(array('wake up'        => 'visit bathroom'));
    //$gv->addEdge(array('visit bathroom' => 'make coffee'));
    //$gv->image();
    if ($action == 'image')
    {
        $graph->image('png','dot');
       $graph->saveParsedGraph('/tmp/log2.log');
    } else {
        $graph->image('cmapx','dot');
       $graph->saveParsedGraph('/tmp/log3.log');
    }
    echo "ee";
}

function drawGraph($affaire)
{
    global $db,$langs;
      $graph = new Image_GraphViz(true,array(),'G',true,true);
      $graph->setAttributes(array('bgcolor'=>'#ffffff', 'splines'=>true));
      $graph->graph['attributes']=array('bgcolor'=>'#ffffff', 'splines'=>true,'margin'=>"0,0.1",'pad'=>"0.5,0.75");
    #
         $graph->addNode(
           $affaire->nom,
           array(
             'URL'   => DOL_URL_ROOT.'/Synopsis_Affaire/card.php?id='.$affaire->id,
             'label' => ''.$affaire->nom,
             'shape' => 'folder',
             'fontsize' => '16',
             'color' => '#E4733F',
             'style' => 'filled'
           )
         );

    //$gv = new Image_GraphViz();
      $requete = "SELECT *
                    FROM ".MAIN_DB_PREFIX."commande,
                         ".MAIN_DB_PREFIX."co_pr
                   WHERE ".MAIN_DB_PREFIX."co_pr.fk_commande = ".MAIN_DB_PREFIX."commande.rowid
                     AND ".MAIN_DB_PREFIX."commande.rowid in (SELECT element_id FROM ".MAIN_DB_PREFIX."Synopsis_Affaire_Element WHERE type='commande' AND affaire_refid = ".$affaire->id.")";
      $sql = $db->query($requete);
      while ($res=$db->fetch_object($sql))
      {
    #
         $graph->addNode(
           $res->ref,
           array(
             'URL'   => DOL_URL_ROOT.'/commande/card.php?id='.$res->fk_commande,
             'label' => ''.$res->ref,
             'shape' => 'doubleoctagon',
             'fontsize' => '10',
             'color' => 'red',
             'style' => 'filled'
           )
         );

        $requete1 = "SELECT * FROM ".MAIN_DB_PREFIX."co_pr, ".MAIN_DB_PREFIX."propal WHERE ".MAIN_DB_PREFIX."propal.rowid = ".MAIN_DB_PREFIX."co_pr.fk_propale AND ".MAIN_DB_PREFIX."co_pr.fk_commande = ".$res->rowid;
        $sql1 = $db->query($requete1);
        if ($db->num_rows($sql1) > 0)
        {
            while ($res1=$db->fetch_object($sql1))
            {
                 $graph->addNode(
                   $res1->ref,
                   array(
                     'URL'   => DOL_URL_ROOT.'/comm/propal.php?propalid='.$res1->rowid,
                     'label' => ''.$res1->ref,
                     'shape' => 'polygon',
                     'fontsize' => '10',
                     'color' => 'blue',
                     'style' => 'filled'
                   )
                 );
                  $graph->addEdge(
                  array(
                     $res1->ref => $res->ref
                   ),
                   array(
                     'label' => "   ".date('d/m/Y',strtotime($res->date_commande))."   ",
                     'fontsize' => '8',
                   )
                 );
                  $graph->addEdge(
                  array(
                     $affaire->nom => $res1->ref
                   ),
                   array(
                     'label' => date('d/m/Y',strtotime($res1->datep)),
                     'fontsize' => '8',
                   )
                 );
        //        $gv->addEdge(array($res->ref => $res1->ref));
            }

        } else {
          $graph->addEdge(
          array(
             $affaire->nom => $res->ref
           ),
           array(
             'label' => "   ".date('d/m/Y',strtotime($res->date_commande))."   ",
             'fontsize' => '8',
           )
         );

        }
        $requete2 = "SELECT *  FROM ".MAIN_DB_PREFIX."facture, ".MAIN_DB_PREFIX."co_fa WHERE ".MAIN_DB_PREFIX."facture.rowid = ".MAIN_DB_PREFIX."co_fa.fk_facture AND ".MAIN_DB_PREFIX."co_fa.fk_commande =".$res->rowid;
        $sql2 = $db->query($requete2);
        while ($res2=$db->fetch_object($sql2))
        {
             $graph->addNode(
               $res2->ref,
               array(
                 'URL'   => DOL_URL_ROOT.'/facture.php?id='.$res2->rowid,
                 'label' => ''.$res2->ref,
                 'shape' => 'ellipse',
                 'fontsize' => '10',
                 'color' => 'purple',
                 'style' => 'filled'
               )
             );
              $graph->addEdge(
              array(
                 $res->ref => $res2->ref
               ),
               array(
                 'label' => "   ".date('d/m/Y',strtotime($res1->datef))."   ",
                 'fontsize' => '8',
               )
             );
    //        $gv->addEdge(array($res->ref => $res1->ref));
        }
        $requete3 = "SELECT * FROM ".MAIN_DB_PREFIX."expedition, ".MAIN_DB_PREFIX."co_exp WHERE ".MAIN_DB_PREFIX."expedition.rowid = ".MAIN_DB_PREFIX."co_exp.fk_expedition AND ".MAIN_DB_PREFIX."co_exp.fk_commande = ".$res->rowid;
        $sql3 = $db->query($requete3);
        while ($res3=$db->fetch_object($sql3))
        {
             $graph->addNode(
               $res3->ref,
               array(
                 'URL'   => DOL_URL_ROOT.'/expedition/card.php?id='.$res3->rowid,
                 'label' => ''.$res3->ref,
                 'shape' => 'diamond',
                 'fontsize' => '10',
                 'color' => 'brown',
                 'style' => 'filled'
               )
             );
              $graph->addEdge(
              array(
                 $res->ref => $res3->ref
               ),
               array(
                 'label' => "   ".date('d/m/Y',strtotime($res3->date_expedition))."   ",
                 'fontsize' => '8',
               )
             );
            $requete3a = 'SELECT * FROM ".MAIN_DB_PREFIX."livraison WHERE fk_expedition = '.$res3->rowid;
            $sql3a = $db->query($requete3a);
            while ($res3a = $db->fetch_object($sql3a)){
                 $graph->addNode(
                   $res3a->ref,
                   array(
                     'URL'   => DOL_URL_ROOT.'/livraison/card.php?id='.$res3a->rowid,
                     'label' => ''.$res3a->ref,
                     'shape' => 'hexagon',
                     'fontsize' => '10',
                     'color' => 'pink',
                     'style' => 'filled'
                   )
                 );
                  $graph->addEdge(
                  array(
                     $res3->ref => $res3a->ref
                   ),
                   array(
                     'label' => "   ".date('d/m/Y',strtotime($res3a->date_livraison))."   ",
                     'fontsize' => '8',
                   )
                 );

            }
        }
        //Contrat
        //$requete4 = "SELECT * FROM ".MAIN_DB_PREFIX."contrat WHERE ";

        //Commande / facture fourn

        //DI / FI

        //GA

        //projet + projet vers referents

        //filtre addnode

        //paiement

        //produits ?
    }
    return ($graph);
}

?>
