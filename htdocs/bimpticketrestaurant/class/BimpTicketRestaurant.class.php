<?php
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';

class BimpTicketRestaurant {

    private $db;
    public $errors;

    function __construct($db) {
        $this->db = $db;
    }

    public function getTicket() {

        // Now
        $row_date = new DateTime('now');
        $month = $row_date->format('m');
        $year = $row_date->format('Y');

        $year -= 1; // TODO remove
        // Get date
        try {
            $date_work_plus_1 = new DateTime($year . '-' . ((int) $month + 2));
            $date_work = new DateTime($year . '-' . ((int) $month + 1));
            $date_holiday = new DateTime($year . '-' . (int) $month);
//            $date_expense_report = new DateTime($year . '-' . ((int) $month -1));
        } catch (Exception $e) {
            $this->errors[] = $e->getMessage();
            return -1;
        }

        $days_worked = num_open_day($date_work->getTimestamp(), $date_work_plus_1->getTimestamp(), 0, 1);

        $holidays = $this->getHolidays($date_holiday);

        $users = $this->getUsersAndInitTicketRestau($days_worked);

        foreach ($holidays as $h) {
            if (isset($users[$h->fk_user]))
                $users[$h->fk_user]->ticket_restau -= $h->ticket_to_remove;
        }

        $path_to_file = $this->exportCSV($users, $date_work);
        $this->sendMailTR($path_to_file, $date_work);
        // TODO send email
    }

    private function getHolidays($date_month) {

        $holidays = array();
        $month_to_check = (int) $date_month->format('m');

        $nb_day_of_month = cal_days_in_month(CAL_GREGORIAN, $date_month->format('m'), $date_month->format('Y'));


        $sql = 'SELECT fk_user, date_debut, date_fin, type_conges';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'holiday';
        $sql .= ' WHERE statut=6';
        $sql .= ' AND  (';
        $sql .= '("' . $date_month->format('Y-m-1') . '" BETWEEN date_debut AND date_fin)';
        $sql .= ' OR ("' . $date_month->format('Y-m-' . $nb_day_of_month) . '" BETWEEN date_debut AND date_fin)';
        $sql .= ')';


        // Retrieve holy days
        $result = $this->db->query($sql);
        while ($obj = $this->db->fetch_object($result)) {
            $holidays[] = $obj;
        }

        foreach ($holidays as $i => $h) {
            $date_start = new DateTime($h->date_debut);
            $date_end = new DateTime($h->date_fin);

            $h_month_start = (int) $date_start->format('m');
            $h_month_end = (int) $date_end->format('m');

            // Start and end are in the same month
            if ($h_month_start == $h_month_end) {
                $holidays[$i]->ticket_to_remove = num_open_day($date_start->getTimestamp(), $date_end->getTimestamp(), 0, 1);
//                echo 'entre ' . $date_start->format('Y-m-d') . ' et ' . $date_end->format('Y-m-d') . ' il y a ' . $holidays[$i]->ticket_to_remove . '<br/>';
                $holidays[$i]->id = 1;
            }
            // Overlap with previous month
            elseif ($h_month_start < $month_to_check and ( $h_month_start + 1) == $month_to_check) {
                $holidays[$i]->ticket_to_remove = num_open_day($date_month->getTimestamp(), $date_end->getTimestamp(), 0, 1);
//                echo 'entre ' . $date_month->format('Y-m-d') . ' et ' . $date_end->format('Y-m-d') . ' il y a ' . $holidays[$i]->ticket_to_remove . '<br/>';
                $holidays[$i]->id = 2;
            }
            // Overlap with next month
            elseif ($month_to_check < $h_month_end and ( $month_to_check + 1) == $h_month_end) {
                $date_end_month = new DateTime($date_start->format('Y-m-' . $nb_day_of_month));
                $holidays[$i]->ticket_to_remove = num_open_day($date_start->getTimestamp(), $date_end_month->getTimestamp(), 0, 1);
//                echo 'entre ' . $date_start->format('Y-m-d') . ' et ' . $date_end_month->format('Y-m-d') . ' il y a ' . $holidays[$i]->ticket_to_remove . '<br/>';
                $holidays[$i]->id = 3;
            }
            // start and end in other month
            else {
                $date_end_month = new DateTime($date_start->format('Y-m-' . $nb_day_of_month));
                $holidays[$i]->ticket_to_remove = num_open_day($date_month->getTimestamp(), $date_end_month->getTimestamp(), 0, 1);
//                echo 'entre ' . $date_month->format('Y-m-d') . ' et ' . $date_end_month->format('Y-m-d') . ' il y a ' . $holidays[$i]->ticket_to_remove . '<br/>';
                $holidays[$i]->id = 4;
            }
        }
        return $holidays;
    }

    private function getUsersAndInitTicketRestau($ticket_restau) {
        $users = array();

        $user_exclude = array(
            1, // GLE
            100, // SALLE REUNION 1
            101, // SALLE REUNION 2
            102, // SALLE REUNION 3
            103, // VÉHICULE DE SERVICE TWINGO
            104, // VÉHICULE DE SERVICE 207
            115, // TEST
            123, // VÉHICULE DE SERVICE C
            130, // MAG 26;Extra 26
            147, // VÉHICULE DE SERVICE TRAFIC
            200, // TESTSYNCH
            205, // BIMP;Accueil13
            207, // BIMP;Hotline;
            212, // BIMP;HotlineMetaldyne
            215, // BIMP;Gle_suivi
            216, // BIMP;XIVO
            263, // COMPTE;FERME;
            272, // VÉHICULE DE SERVICE TWIZY
            326, // EXTERNE;USER;
            352, // TEST;TEMP;20;
            353, // TEST;Temp2;20;
            364, // VÉHICULE DE SERVICE TRANSIT
            383  // VÉHICULE DE SERVICE QASHQAI
        ); // 222;JON;Jon;20; ???

        $sql = 'SELECT rowid, lastname, firstname';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'user';
        $sql .= ' WHERE statut > 0';
        $sql .= ' AND rowid NOT IN(' . implode($user_exclude, ',') . ')';

        $result = $this->db->query($sql);
        while ($obj = $this->db->fetch_object($result)) {
            $obj->ticket_restau = $ticket_restau;
            $users[$obj->rowid] = $obj;
        }
        return $users;
    }

    private function exportCSV($users, $date) {

        // CSV config
        $sep_line = "\n";
        $sep = ";";

        $out = "";
        $dir = DOL_DATA_ROOT . '/bimpticketrestaurant/';
        if (!file_exists($dir))
            mkdir($dir);
        $path_to_file = $dir . 'TR-' . $date->format('m-Y') . '.csv';

        // Header
//        $out .= 'ID' . $sep;
        $out .= 'Nom' . $sep;
        $out .= 'Prénom' . $sep;
        $out .= 'Nombre de ticket' . $sep;
        $out .= $sep_line;

        // Content
        foreach ($users as $u) {
//            $out .= $u->rowid . $sep;
            $out .= $u->lastname . $sep;
            $out .= $u->firstname . $sep;
            $out .= $u->ticket_restau . $sep;
            $out .= $sep_line;
        }

        echo $out;

        file_put_contents($path_to_file, $out);

        return $path_to_file;
    }

    private function sendMailTR($path_to_file, $date) {
        global $conf;

        $exploded_path_to_file = explode('/', $path_to_file);
        $filename = end($exploded_path_to_file);

        $subject = "Ticket restaurant du " . $date->format('m-Y');
        $from = 'Application BIMP-ERP ' . $conf->global->MAIN_INFO_SOCIETE_NOM . ' <gle@' . strtolower(str_replace(" ", "", $conf->global->MAIN_INFO_SOCIETE_NOM)) . '.fr>';
        $to = "Romain PELEGRIN <r.pelegrin@bimp.fr>";
        $message = "Bonjour<br/><br/>La liste des tickets restaurants pour le " . $date->format('m-Y') . " se trouve en pièce-jointe.<br/>";
        $filename_list = array($path_to_file);
        $mimetype_list = array('csv');
        $mimefilename_list = array($filename);

//        echo $subject;
//        echo $to;
//        echo $from;
//        echo $message;
//        print_r($filename_list);
//        print_r($mimetype_list);
//        print_r($mimefilename_list);
//        die();

        if (isset($to) && $to != '') {
            require_once DOL_DOCUMENT_ROOT . '/core/class/CMailFile.class.php';
            $mailfile = new CMailFile($subject, $to, $from, $message, $filename_list, $mimetype_list, $mimefilename_list);
            $return = $mailfile->sendfile();
            if (!$return)
                dol_syslog("Problème lors de l'envoie du compte rendu des tickets restaurants", 3);

            return $return;
        }
    }

}
