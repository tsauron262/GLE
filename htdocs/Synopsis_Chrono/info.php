<?php
/*
  * GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.2
  * Created on : 15 fevr. 2011
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : info.php
  * GLE-1.2
  */
  require_once('pre.inc.php');
  require_once(DOL_DOCUMENT_ROOT."/Synopsis_Chrono/Chrono.class.php");
  require_once(DOL_DOCUMENT_ROOT."/core/lib/synopsis_chrono.lib.php");
  $js = "";
   $id = $_REQUEST['id'];
  llxHeader($js,'Info chrono');
  print "<div class='titre'>Info chrono</div><br/>";

  if ($msg ."x" != 'x')
  {
      print "<div style='padding: 3px;'><span class='ui-icon ui-icon-info' style='float: left;'></span>".$msg."</div>";
  }
  $chr = new Chrono($db);
  if ($id > 0)
  {
      $chr->fetch($id);
      $tmpChr = 'chrono'.$chr->model_refid;
      $rightChrono = $user->rights->chrono_user->$tmpChr;

      if (!($rightChrono->voir == 1 || $user->rights->synopsischrono->read == 1))
      {

          accessforbidden("Ce type de chrono ne vous est pas accessible",0);
          exit;
      }


      //saveHistoUser($chr->id, "chrono",$chr->ref);

      $head = chrono_prepare_head($chr);
      $html=new Form($db);
      dol_fiche_head($head, 'info', $langs->trans("Chrono"));

        $chr->user_author->fetch($chr->user_author->id);

      print "<table width=100% cellpadding=10>";


          print '<tr><th class="ui-state-default ui-widget-header" nowrap  class="ui-state-default">Cr&eacute;er le';
          print '    <td  class="ui-widget-content" colspan="1">'.date('d/m/Y \&\a\g\r\a\v\e\; H:i',$chr->date).'</td>';
          print '    <th class="ui-state-default ui-widget-header" nowrap  class="ui-state-default">Par';
          print '    <td  class="ui-widget-content" colspan="1">'.$chr->user_author->getNomUrl(1).'</td>';

          if ($chr->user_modif_id > 0)
          {
        $chr->user_modif->fetch($chr->user_modif->id);
              print '<tr><th class="ui-state-default ui-widget-header" nowrap  class="ui-state-default">Derni&egrave;re modification le';
              print '    <td  class="ui-widget-content" colspan="1">'.date('d/m/Y \&\a\g\r\a\v\e\; H:i',$chr->date_modif).'</td>';
              print '    <th class="ui-state-default ui-widget-header" nowrap  class="ui-state-default">Par';
              print '    <td  class="ui-widget-content" colspan="1">'.$chr->user_modif->getNomUrl(1).'</td>';
          }




      //Historique de validation




      $requete = "SELECT d.label,M.user_refid, M.tms, M.validation,M.note
                    FROM ".MAIN_DB_PREFIX."Synopsis_Chrono_Multivalidation as M,
                         ".MAIN_DB_PREFIX."Synopsis_Chrono_rights_def as d
                   WHERE chrono_refid = ".$id. "
                     AND M.right_refid = d.id
                     AND validation_number is NULL";
      $sql = $db->query($requete);
      $rowspan = intval($db->num_rows($sql) + 1);
      if ($db->num_rows($sql) > 0)
      {
          print "<tr><th class='ui-widget-header ui-state-default' rowspan='". $rowspan ."'>Validation initiale</th>";
          while ($res = $db->fetch_object($sql))
          {
              print "<tr><td align=left  width=20% class='ui-widget-content'>".$res->label;
              print "<td align=left class='ui-widget-content'>";
              $tmpUser = new User($db);
              $tmpUser->id = $res->user_refid;
              $tmpUser->fetch($tmpUser->id);
              if ($res->validation == 1){ print img_tick("Valider"); print " par ".$tmpUser->getNomUrl(1) . " le ".date('d/m/Y',strtotime($res->tms)); }
              else { print img_error("Non valider"); print " par ".$tmpUser->getNomUrl(1) . " le ".date('d/m/Y',strtotime($res->tms));}
              print "<td align=left class='ui-widget-content'>".nl2br($res->note);
          }

          $requete = "SELECT d.label,M.user_refid, M.tms, M.validation, validation_number
                        FROM ".MAIN_DB_PREFIX."Synopsis_Chrono_Multivalidation as M,
                             ".MAIN_DB_PREFIX."Synopsis_Chrono_rights_def as d
                       WHERE chrono_refid = ".$id. "
                         AND M.right_refid = d.id
                         AND validation_number is NOT NULL ORDER BY validation_number ASC";
          $sql = $db->query($requete);
          $iter = 0;
          while ($res = $db->fetch_object($sql))
          {
              if ($iter ==0) print "<tr><th class='ui-widget-header ui-state-default' style='border-top-style: double;border-top-width: 3px;' rowspan='". $rowspan ."'>Validation ".$res->validation_number."</th>";
              print "<tr><td align=left ".($iter==0?"style='border-top-style: double;border-top-width: 3px;'":"")." width=20% class='ui-widget-content'>".$res->label;
              print "<td align=left ".($iter==0?"style='border-top-style: double;border-top-width: 3px;'":"")." class='ui-widget-content'>";
              $iter++;
              if ($iter == $rowspan - 1) $iter=0;

              $tmpUser = new User($db);
              $tmpUser->id = $res->user_refid;
              $tmpUser->fetch($tmpUser->id);
              if ($res->validation == 1){ print img_tick("Valider"); print " par ".$tmpUser->getNomUrl(1) . " le ".date('d/m/Y',strtotime($res->tms)); }
              else { print img_error("Non valider"); print " par ".$tmpUser->getNomUrl(1) . " le ".date('d/m/Y',strtotime($res->tms));}
              print "<td align=left class='ui-widget-content'>".nl2br($res->note);
          }


      }

      print "</table>";

  }
  llxFooter();

?>