<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.1
  * Created on : 5 sept. 2009
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : HTressource_xml-response.php
  * GLE-1.1
  */
  $user_id = $_REQUEST['user_id'];
  $user_id = preg_replace("/^Actor-/","",$user_id);
  $projet_id = $_REQUEST['projet_id'];
  require_once('../../main.inc.php');
  //recupere la liste des taches et le role
  //         le cout de la ressource pour chaque tache
  //         le planning sous forme debarre pour chaque utilisateur
  //         les vacances prévues de la personne
  //         le temps passé sur chaque tache
  //         la team
  require_once(DOL_DOCUMENT_ROOT.'/Synopsis_Hrm/hrm.class.php');

  $xml = "";

  $hrm = new hrm($db);
  //tache et role
  $requete = "SELECT ".MAIN_DB_PREFIX."Synopsis_projet_task_actors.fk_user,
                     ".MAIN_DB_PREFIX."Synopsis_projet_task_actors.role,
                     ".MAIN_DB_PREFIX."Synopsis_projet_task_actors.percent,
                     ".MAIN_DB_PREFIX."projet_task.label,
                     ".MAIN_DB_PREFIX."projet_task.rowid as taskId
                FROM ".MAIN_DB_PREFIX."Synopsis_projet_task_actors,
                     ".MAIN_DB_PREFIX."projet_task
               WHERE ".MAIN_DB_PREFIX."Synopsis_projet_task_actors.fk_user = ".$user_id. "
                 AND ".MAIN_DB_PREFIX."projet_task.fk_projet = ".$projet_id. "
                 AND ".MAIN_DB_PREFIX."Synopsis_projet_task_actors.fk_projet_task = ".MAIN_DB_PREFIX."projet_task.rowid ORDER BY ".MAIN_DB_PREFIX."projet_task.label ";
  $sql = $db->query($requete);
  $xml .= "<actors>\n";
  while ($res = $db->fetch_object($sql))
  {
        $xml .= "\t<actor>\n";
        $xml .=   "\t\t<id><![CDATA[".$res->fk_user."]]></id>\n";
        $xml .=   "\t\t<task><![CDATA[".$res->title."]]></task>\n";
        $xml .=   "\t\t<role><![CDATA[".$res->role."]]></role>\n";
        $xml .=   "\t\t<taskId><![CDATA[".$res->taskId."]]></taskId>\n";
        $xml .=   "\t\t<occupation><![CDATA[".$res->percent."]]></occupation>\n";
        $xml .= "\t</actor>\n";
        $xml .= "\t<actorDetail>\n";
        $xml .=   "\t\t<id><![CDATA[".$res->fk_user."]]></id>\n";
        $fuser=new User($db);
        $fuser->fetch($res->fk_user);
        $requete = "SELECT couthoraire
                              FROM ".MAIN_DB_PREFIX."Synopsis_hrm_user
                             WHERE user_id = $res->fk_user
                          ORDER BY startDate DESC
                             LIMIT 1";
                $sql2 = $db->query($requete);
                $coutHoraire=0;
                if ($sql2)
                {
                    $res2=$db->fetch_object($sql2);
                    $coutHoraire = $res2->couthoraire;
                }

        $xml .=   "\t\t<fullname><![CDATA[".$fuser->getNomUrl(1)."]]></fullname>\n";
        $xml .=   "\t\t<couthoraire><![CDATA[".$coutHoraire."]]></couthoraire>\n";
        $xml .= "\t</actorDetail>\n";
  }
  $xml .= "</actors>\n";


   //planning de l utilisateur pour la tache
   $requete = "SELECT ".MAIN_DB_PREFIX."projet_task.rowid as taskId,
                      ".MAIN_DB_PREFIX."projet_task_time.fk_user,
                      unix_timestamp(".MAIN_DB_PREFIX."projet_task_time.task_date) as FtaskDate,
                      ".MAIN_DB_PREFIX."projet_task_time.task_duration
                 FROM ".MAIN_DB_PREFIX."projet_task_time,
                      ".MAIN_DB_PREFIX."projet_task
                WHERE ".MAIN_DB_PREFIX."projet_task.fk_projet = ".$projet_id. "
                  AND ".MAIN_DB_PREFIX."projet_task_time.fk_task = ".MAIN_DB_PREFIX."projet_task.rowid
                  AND ".MAIN_DB_PREFIX."projet_task_time.fk_user =".$user_id;
  $sql = $db->query($requete);
  $xml .= "<planning>\n";
  while ($res = $db->fetch_object($sql))
  {
        $xml .= "\t<time>\n";
        $xml .=   "\t\t<taskId><![CDATA[".$res->taskId."]]></taskId>\n";
        $xml .=   "\t\t<id><![CDATA[".$res->fk_user."]]></id>\n";
        $xml .=   "\t\t<dateo><![CDATA[".$res->FtaskDate."]]></dateo>\n";
        $xml .=   "\t\t<duration><![CDATA[".$res->task_duration."]]></duration>\n";
        $xml .= "\t</time>\n";
  }

   //  et le temps passé
   $requete = "SELECT ".MAIN_DB_PREFIX."projet_task.rowid as taskId,
                      ".MAIN_DB_PREFIX."Synopsis_projet_task_time_effective.fk_user,
                      unix_timestamp(".MAIN_DB_PREFIX."Synopsis_projet_task_time_effective.task_date_effective) as FtaskDate,
                      ".MAIN_DB_PREFIX."Synopsis_projet_task_time_effective.task_duration_effective
                 FROM ".MAIN_DB_PREFIX."Synopsis_projet_task_time_effective,
                      ".MAIN_DB_PREFIX."projet_task
                WHERE ".MAIN_DB_PREFIX."projet_task.fk_projet = ".$projet_id. "
                  AND ".MAIN_DB_PREFIX."Synopsis_projet_task_time_effective.fk_task = ".MAIN_DB_PREFIX."projet_task.rowid
                  AND ".MAIN_DB_PREFIX."Synopsis_projet_task_time_effective.fk_user =".$user_id;
  $sql = $db->query($requete);

  while ($res = $db->fetch_object($sql))
  {
        $xml .= "\t<effectiveTime>\n";
        $xml .=   "\t\t<taskId><![CDATA[".$res->taskId."]]></taskId>\n";
        $xml .=   "\t\t<id><![CDATA[".$res->fk_user."]]></id>\n";
        $xml .=   "\t\t<startOn><![CDATA[".$res->FtaskDate."]]></startOn>\n";
        $xml .=   "\t\t<duration><![CDATA[".$res->task_duration_effective."]]></duration>\n";
        $xml .= "\t</effectiveTime>\n";
  }

  $decalHoraire = intval(date('O') / 100);

   //vacance prévu de l'utilisateur
   //get hrm EmpNum
   $empNumber = $hrm->GleId2HrmId($user_id);
   $requeteHrm ="SELECT unix_timestamp(leave_date)  + (3600 * $decalHoraire ) as Fleave_date,
                        leave_length_days,
                        leave_status
                   FROM hs_hr_leave
                  WHERE leave_status > 1
                    AND employee_id = ".$empNumber. "
               ORDER BY leave_date ";

   //TODO filtre % aux dates prevues du projet
   //TODO check status
  $sql = $hrm->hrmdb->query($requeteHrm);

  $xml .= "\t<vacation>\n";
  while ($res = $hrm->hrmdb->fetch_object($sql))
  {
      $xml .= "\t\t<date><dateo><![CDATA[".$res->Fleave_date."]]></dateo>\n";
      $xml .= "\t\t<duration><![CDATA[".$res->leave_length_days * 3600 * $conf->global->PROJECT_HOUR_PER_DAY ."]]></duration>\n";
      $xml .= "\t\t<status><![CDATA[".$res->leave_status."]]></status></date>\n";
  }

  $xml .= "\t</vacation>\n";
  $xml .= "\t<dayOff>\n";
  //ajoute les jours fériés :hs_hr_holidays
  $requeteHrm ="SELECT unix_timestamp(date) + (3600 * $decalHoraire )  as Fdate
                  FROM hs_hr_holidays";
  //TODO filtre % aux dates prevues du projet
  $sql = $hrm->hrmdb->query($requeteHrm);
  while ($res = $hrm->hrmdb->fetch_object($sql))
  {
      $xml .= "\t\t<date><dateo><![CDATA[".$res->Fdate."]]></dateo>\n";
      $xml .= "\t\t<duration><![CDATA[". 3600 * $conf->global->PROJECT_HOUR_PER_DAY ."]]></duration></date>\n";
  }
  $xml .= "\t</dayOff>\n";

  $xml .="\t<configuration>\n";
  $requete = "SELECT unix_timestamp(min(task_date)) as minDate,
                     unix_timestamp(max(task_date)) as maxDate
                FROM ".MAIN_DB_PREFIX."projet_task_time,
                     ".MAIN_DB_PREFIX."projet_task
               WHERE task_date is not null
                 AND ".MAIN_DB_PREFIX."projet_task.fk_projet = ".$projet_id. "
                 AND ".MAIN_DB_PREFIX."projet_task_time.fk_task = ".MAIN_DB_PREFIX."projet_task.rowid
                 AND ".MAIN_DB_PREFIX."projet_task_time.fk_user =".$user_id;
  $sql = $db->query($requete);
  $res = $db->fetch_object($sql);
  $xml .= "\t\t<ProjStart>".$res->minDate."</ProjStart>\n";
  $xml .= "\t\t<ProjStop>".$res->maxDate."</ProjStop>\n";
  $xml .= "\t</configuration>\n";

  $xml .= "\t</planning>\n";

   //la team
   $requeteHrm = "SELECT unix_timestamp(start_date)  + (3600 * $decalHoraire ) as FstartDate, name
                    FROM hs_hr_emp_subdivision_history
                   WHERE start_date < now()
                     AND   ( end_date is null
                          OR end_date > now()
                           )
                     AND emp_number = ".$empNumber."
                ORDER BY start_date DESC
                   LIMIT 1 ";
  $sql = $hrm->hrmdb->query($requeteHrm);
  $xml .= "<team>\n";
  while ($res = $hrm->hrmdb->fetch_object($sql))
  {
      $xml .= "\t<name><![CDATA[".$res->name."]]></name>\n";
      $xml .= "\t<since><![CDATA[".$res->FstartDate."]]></since>\n";
  }
  $xml .= "</team>\n";
   header("Content-Type: text/xml");
    $xmlStr = '<'.'?xml version="1.0" encoding="ISO-8859-1"?'.'>';
    $xmlStr .= '<ajax-response><response>'."\n";
    $xmlStr .= "<xml>".$xml."</xml>";
    $xmlStr .= '</response></ajax-response>'."\n";
    print $xmlStr;
?>