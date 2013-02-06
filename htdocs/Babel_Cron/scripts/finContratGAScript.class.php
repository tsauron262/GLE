<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.0
  * Created on : 28 avr. 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : test-script.class.php GLE-1.1
  */

    class finContratGAScript extends CommonObject
    {
        public $db;
        public $interval = 86400; // Interval de repetition du script en second
        public $finContratDelai = 90; // Interval de detection en jour

        public $Description;

        public function finContratGAScript($DB){
            $this->db = $DB;
            $this->Description = 'Fin de contrat de location';
            $this->Version = "1.0";
        }
        public function description(){
            return ($this->Description);
        }
        public function version(){
            return ($this->Version);
        }
        public function do_action(){
            $result = 0;
            //Recherche les actions concernees
            $requete = "SELECT Babel_Cron_Schedule.id
                          FROM Babel_Cron_Schedule, Babel_Cron
                         WHERE nextRun > 0
                           AND nextRun is not null
                           AND nextRun < now()
                           AND Babel_Cron_Schedule.cron_refid = Babel_Cron.id
                           AND Babel_Cron.object = 'finContratGAScript'";
            $sql = $this->db->query($requete);
            while ($res = $this->db->fetch_object($sql))
            {
                $result = 1;
                $requete = "SELECT ".MAIN_DB_PREFIX."contrat.rowid
                              FROM ".MAIN_DB_PREFIX."contrat, ".MAIN_DB_PREFIX."contratdet, Babel_GA_contrat
                             WHERE Babel_GA_contrat.contratdet_refid = ".MAIN_DB_PREFIX."contratdet.rowid
                               AND ".MAIN_DB_PREFIX."contratdet.fk_contrat = ".MAIN_DB_PREFIX."contrat.rowid
                               AND ".MAIN_DB_PREFIX."contrat.statut = 1
                               AND ".MAIN_DB_PREFIX."contrat.warned = 0
                               AND date_add(date_sub(".MAIN_DB_PREFIX."contrat.date_valid, interval 3 month ),interval Babel_GA_contrat.duree month) < now()";
                $sql = $this->db->query($requete);
                require_once(DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php');
                $contrat = new Contrat($this->db);
                while ($res1 = $this->db->fetch_object($sql))
                {
                    //Send Email
                    $contrat->fetch($res1->rowid);
                    $subject = "[GLE] Avertissement de fin du contrat ".$contrat->ref;
                    $tmpUser = new User($this->db);
                    $tmpUser->fetch($contrat->fk_user_author);
                    $to = $tmpUser->email;

                    $ccArr = array();
                    if ($contrat->commercial_signature_id . "x" != "x" && $contrat->commercial_signature_id != $contrat->fk_user_author)
                    {
                        $tmpUser->fetch($contrat->commercial_signature_id);
                        $cc = $tmpUser->email;
                        $ccArr[]=$cc;
                    }
                    if ($contrat->commercial_suivi_id . "x" != "x" && ($contrat->commercial_suivi_id != $contrat->commercial_signature_id) ||($contrat->fk_user_author != $contrat->commercial_suivi_id))
                    {
                        $tmpUser->fetch($contrat->commercial_suivi_id);
                        $cc = $tmpUser->email;
                        $ccArr[]=$cc;
                    }

                    $sendtocc = join(',',$ccArr);
                    $from = "GLE <".$conf->global->MAIN_MAIL_EMAIL_FROM.">";
                    $filepath = array();
                    $mimetype=array();
                    $filename =array();
                    $message = "Bonjour,<br/>\n<br/>\n    Le contrat de financement ".$contrat->ref.' arrive bient&ocirc;t &agrave; terme<br/>\n<br/>\nCordialement,';

                    $sql1 = false;
                    require_once(DOL_DOCUMENT_ROOT.'/Synopsis_Tools/class/CMailFile.class.php');
                    $mailfile = new CMailFile($subject,$to,$from,$message,$filepath,$mimetype,$filename,$sendtocc,'',$deliveryreceipt=false);
                    if ($mailfile->error)
                    {
                        $mesg='<div class="error ui-state-error">'.$mailfile->error.'</div>';
                    } else {
                        $result=$mailfile->sendfile();
                        if ($result)
                        {
                            $mesg='<div class="ok">'.$langs->trans('MailSuccessfulySent',$from,$to).'.</div>';

                            $error=0;

                            // Initialisation donnees
                            global $user,$langs,$conf;
    //                        // Appel des triggers
                            include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
                            $interface=new Interfaces($this->db);
                            $result=$interface->run_triggers('CONTRAT_WARNBYMAIL',$contrat,$user,$langs,$conf);
                            if ($result < 0) { $error++; $this->errors=$interface->errors; }
                            // Fin appel triggers

                            if ($error)
                            {
                                dol_print_error($this->db);
                            } else {
                                // Redirect here
                                // This avoid sending mail twice if going out and then back to page
                                $requete = "UPDATE ".MAIN_DB_PREFIX."contrat set warned = 1 WHERE rowid = ".$res1->rowid;
                                $sql1 = $db->query($requete);

                            }
                        } else {
                            if ($mailfile->error)
                            {
                                global $langs;
                                $mesg.=$langs->trans('ErrorFailedToSendMail',$from,$to);
                                $mesg.='<br>'.$mailfile->error;
                            } else {
                                $mesg.='No mail sent. Feature is disabled by option MAIN_DISABLE_ALL_MAILS';
                            }
                        }
                        //Set warned = 1
                    }
                    if ($sql1)
                    {
                        $this->update_Result(1,$res->id);
                    } else {
                        $this->update_Result(0,$res->id);
                    }
                }
            }
            //pour chaque action
            //action
            //MAJ results
        }
        public function when_run(){
            return($this->getNextRun());
        }
        private function getNextRun()
        {
            $requete = "SELECT nextRun,  Babel_Cron_Schedule.id
                          FROM Babel_Cron_Schedule,
                               Babel_Cron
                         WHERE Babel_Cron.object = 'finContratGAScript'
                           AND Babel_Cron_Schedule.cron_refid = Babel_Cron.id";
            $sql = $this->db->query($requete);
            $arr = array();
            while ($res = $this->db->fetch_object())
            {
                $arr[$res->id]=$res->nextRun;
            }
            return $arr;
        }
        private function setNextRun($id)
        {
            $nextRun = $this->setNextRun_1($id);
//            print 'step'.$id.'<br/>';
            $requete = "UPDATE Babel_Cron_Schedule
                           SET nextRun = FROM_UNIXTIME(".$nextRun . ")
                         WHERE id =".$id;
            $sql = $this->db->query($requete);
//            $nextRun = $this->setNextRun_2($id);
//            $requete = "UPDATE Babel_Cron_Schedule
//                           SET nextRun = ".$nextRun . "
//                         WHERE id =".$id;
//            $sql = $this->db->query($requete);
            return($sql);

        }
        private function setNextRun_2($id)
        {
            //Next Monday
            $requete = "SELECT UNIX_TIMESTAMP(lastRun) as lastRun
                          FROM Babel_Cron_Schedule
                         WHERE Babel_Cron_Schedule.id=".$id;
            $sql = $this->db->query($requete);

            $res = $this->db->fetch_object();
            $lastRun = $res->lastRun;
            $now = time();
            $nextRun = 0;
            while($nextRun < $now && date('N',$nextRun != 1))
            {
                $nextRun = $nextRun + $lastRun + (3600 * 24);
                $lastRun=0;
            }
            return($nextRun);
        }


        private function setNextRun_1($id)
        {
                        //Last Run + n second
            $requete = "SELECT UNIX_TIMESTAMP(lastRun) as lastRun
                          FROM Babel_Cron_Schedule
                         WHERE Babel_Cron_Schedule.id=".$id;
            $sql = $this->db->query($requete);

            $res = $this->db->fetch_object();
            $lastRun = $res->lastRun;
            $now = time();
            $nextRun = 0;
            while($nextRun < $now)
            {
                $nextRun = $nextRun + $lastRun + $this->interval;
                $nextRun = mktime(12,0,0,date('m',$nextRun),date('d',$nextRun),date('Y',$nextRun));
                $lastRun=0;
            }
            $nextRun -= 12 * 3600;
            return($nextRun);
        }
        private function update_Result($result,$id)
        {
            $requete = "UPDATE Babel_Cron_Schedule SET lastRun=now(), has_run = has_run + 1 , last_result = ".$result . " WHERE id =".$id;
            //print $requete;
            $sql = $this->db->query($requete);
            if ($sql)
            {
                $sql1 = $this->setNextRun($id);
                return($sql1);
            } else {
                return false;
            }
        }
        public function init()
        {
            $requete = " INSERT INTO Babel_Cron
                                    (nom,
                                     description,
                                     object,
                                     active)
                            VALUES ( 'finContratGAScript - Demo' ,
                                     '".$this->Description."' ,
                                     'finContratGAScript' ,
                                     0 );";
            $this->db->query($requete);
            $lastId = $this->db->last_insert_id('Babel_Cron');
            $requete = " INSERT INTO Babel_Cron_Schedule
                                   ( cron_refid )
                            VALUES ( ".$lastId." )";
            $this->db->query($requete);
            $lastId2 = $this->db->last_insert_id('Babel_Cron_Schedule');
            $this->setNextRun($lastId2);
        }
        public function delete()
        {
            $requete = "DELETE FROM Babel_Cron WHERE object = 'finContratGAScript'";
            $sql = $this->db->query($requete);
            return($sql);
        }

        public function activate()
        {
            $requete = "UPDATE Babel_Cron SET active = 1 WHERE object='finContratGAScript'";
            $sql =$this->db->query($requete);
            return($sql);
        }
        public function deactivate()
        {
            $requete = "UPDATE Babel_Cron SET active = 0 WHERE object='finContratGAScript'";
            $sql =$this->db->query($requete);
            return($sql);
        }
  }


?>