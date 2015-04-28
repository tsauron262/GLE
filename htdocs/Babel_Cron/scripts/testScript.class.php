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

    class testScript extends CommonObject
    {
        public $db;
        public $interval = 86400; // Interal de repetition du script
        public $Description;

        public function testScript($DB){
            $this->db = $DB;
            $this->Description = 'Test Script';
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
                           AND Babel_Cron.object = 'testScript'";
            $sql = $this->db->query($requete);
            while ($res = $this->db->fetch_object($sql))
            {
                $result = 1;
//                print "test";
                $this->update_Result($result,$res->id);
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
                         WHERE Babel_Cron.object = 'testScript'
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
//            print $requete;
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
                            VALUES ( 'TestScript - Demo' ,
                                     '".$this->Description."' ,
                                     'testScript' ,
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
            $requete = "DELETE FROM Babel_Cron WHERE object = 'testScript'";
            $sql = $this->db->query($requete);
            return($sql);
        }

        public function activate()
        {
            $requete = "UPDATE Babel_Cron SET active = 1 WHERE object='testScript'";
            $sql =$this->db->query($requete);
            return($sql);
        }
        public function deactivate()
        {
            $requete = "UPDATE Babel_Cron SET active = 0 WHERE object='testScript'";
            $sql =$this->db->query($requete);
            return($sql);
        }
  }


?>