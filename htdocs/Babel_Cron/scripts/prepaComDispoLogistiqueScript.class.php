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

    class prepaComDispoLogistiqueScript extends CommonObject
    {
        public $db;
        public $interval = 86400; // Interval de repetition du script en second
        public $finContratDelai = 90; // Interval de detection en jour

        public $Description;

        public function prepaComDispoLogistiqueScript($DB){
            $this->db = $DB;
            $this->Description = 'Disponibilit&eacute; de la logistique';
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
                           AND Babel_Cron.object = 'prepaComDispoLogistiqueScript'";
            $sql = $this->db->query($requete);
            while ($res1 = $this->db->fetch_object($sql))
            {
                $result = 1;
                $requete = "SELECT fk_commande, rowid, fk_product
                              FROM ".MAIN_DB_PREFIX."commandedet
                             WHERE logistique_ok = 0
                               AND logistique_date_dispo is not null
                               AND logistique_date_dispo < now()";
                $sql = $this->db->query($requete);
                require_once(DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php');
                $commande = new Commande($this->db);
                $arrCom = array();
                $arrComDet = array();
                while ($res = $this->db->fetch_object($sql))
                {
                    $tmpProd = new Product($this->db);
                    $tmpProd->fetch($res->fk_product);

                    $tmpCom = new Commande($this->db);
                    $tmpCom->fetch($res->fk_commande);

                    $arrCom[$res->fk_commande]=$tmpCom;
                    $arrComDet[$res->fk_commande][$res->rowid]=$tmpProd;
                }
                if (count($arrCom) > 0 ){
                    //Send Email
                    $commande->fetch($res->rowid);
                    $subject = "[GLE] Avertissement logistique";
                    global $conf;
                    $sendto = $conf->global->BIMP_MAIL_GESTLOGISTIQUE;


                    $from = "GLE <".$conf->global->MAIN_MAIL_EMAIL_FROM.">";
                    $filepath = array();
                    $mimetype=array();
                    $filename =array();
                    $deliveryreceipt=false;

                    $message = "";

                    $message = "Bonjour,<br/>\n<br/>\n";
                    $message .= "    Une ou plusieurs commandes ne sont pas &agrave; jour :<br/>\n";

                    $message .= "<table><br/>\n";
                    foreach($arrCom as $key=>$val){
                        $message .= "<tr><td>".$tmpCom->getNomUrl(1,6)."<td>";
                        $arr=array();
                        foreach($arrComDet[$key] as $key1=>$val1){
                            $arr[]= $val1->getNomUrl(1,6);
                        }
                        $message .= join('<br/>'."\n",$arr);
                    }
                    $message .= "</table>";

                    $message .= " Cordialement, <br/>\n";

                    $sendtocc="";
//                    var_dump("subject");
//                    var_dump($subject);
//                    var_dump("to");
//                    var_dump($sendto);
//                    var_dump("from");
//                    var_dump($from);
//                    var_dump("message");
//                    var_dump($message);
//                    var_dump("filepath");
//                    var_dump($filepath);
//                    var_dump("mimetype");
//                    var_dump($mimetype);
//                    var_dump("filename");
//                    var_dump($filename);
//                    var_dump("sendtocc");
//                    var_dump($sendtocc);
//                    var_dump("deliveryreceipt");
//                    var_dump($deliveryreceipt);
                    $sql1 = false;
                    require_once(DOL_DOCUMENT_ROOT.'/Synopsis_Tools/class/CMailFile.class.php');
                    $mailfile = new CMailFile($subject,$sendto,$from,$message,$filepath,$mimetype,$filename,$sendtocc,"",$deliveryreceipt,1);
                    if ($mailfile->error)
                    {
                        $mesg='<div class="error ui-state-error">'.$mailfile->error.'</div>';
                        //var_dump($mailfile->error);
                    } else {
                        $result=$mailfile->sendfile();
                        if($result)
                            $sql1=true;
                        //Set warned = 1
                    }
                    if ($sql1)
                    {
                        $this->update_Result(1,$res1->id);
                    } else {
                        $this->update_Result(0,$res1->id);
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
                         WHERE Babel_Cron.object = 'prepaComDispoLogistiqueScript'
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
                            VALUES ( 'Dispo Logistique - Pr&eacute;pa. Commande' ,
                                     '".$this->Description."' ,
                                     'prepaComDispoLogistiqueScript' ,
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
            $requete = "DELETE FROM Babel_Cron WHERE object = 'prepaComDispoLogistiqueScript'";
            $sql = $this->db->query($requete);
            return($sql);
        }

        public function activate()
        {
            $requete = "UPDATE Babel_Cron SET active = 1 WHERE object='prepaComDispoLogistiqueScript'";
            $sql =$this->db->query($requete);
            return($sql);
        }
        public function deactivate()
        {
            $requete = "UPDATE Babel_Cron SET active = 0 WHERE object='prepaComDispoLogistiqueScript'";
            $sql =$this->db->query($requete);
            return($sql);
        }
  }


?>