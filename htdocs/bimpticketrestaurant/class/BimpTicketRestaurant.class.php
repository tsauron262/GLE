<?php
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';

class BimpTicketRestaurant {

    private $db;
    public $errors;

    function __construct($db) {
        $this->db = $db;
    }

    public function getTicket($id_user) {

        // Now
        $row_date = new DateTime('now');
        $month = $row_date->format('m');
        $year = $row_date->format('Y');
        

//        $year -= 1; // TODO remove
        // Get date
        try {
            $date_debut = new DateTime($year . '-' . ((int) $month). '-01');
            $date_debut ->sub(new DateInterval('P6M'));
//            $nb_day_of_month = cal_days_in_month(CAL_GREGORIAN, $date_debut->format('m'), $date_debut->format('Y'));
            $date_fin = clone($date_debut);
            $date_fin ->add(new DateInterval('P1M'));
            $date_fin ->sub(new DateInterval('P1D'));
            $date_fin ->sub(new DateInterval('P1D'));
        } catch (Exception $e) {
            $this->errors[] = $e->getMessage();
            return -1;
        }


        $days_worked = num_open_dayUser($id_user, $date_debut->getTimestamp(), $date_fin->getTimestamp(),0,1);

        $holidays =  getNbHolidays($date_debut, $date_fin, $id_user);
        
        $nbNotefrais = 0;

        
        $nbTicket = $days_worked - $holidays - $nbNotefrais;
        
        if(1){//debug
            echo '<br/>Entre le ' . $date_debut->format('d/m/Y') . ' et le ' . $date_fin->format('d/m/Y') . ' il y a :';
            echo '<br/>' . $days_worked . ' jour de travail';
            echo '<br/>' . $holidays . ' congé';
            echo '<br/>' . $nbNotefrais . ' note de frais';


            echo '<br/>Soit ' . $nbTicket . ' tickets restaurant';
        }
        
//        echo '<pre>';
//        print_r($holidays);

        //$this->exportCSV($users, $date_work);

        // TODO send email
        
        return $nbTicket;
    }

    

    private function getUsersAndInitTicketRestau($ticket_restau) {
        $users = array();

        $user_exclude = array(
            1, // GLE
            100, // SALLE REUNION 1
            101, // SALLE REUNION 2
            102, // SALLE REUNION 3
            103, // VÉHICULE DE SERVICE TWINGO
            272, // VÉHICULE DE SERVICE TWIZY
            364, // VÉHICULE DE SERVICE TRANSIT
            383  // VÉHICULE DE SERVICE QASHQAI
        );

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
        $path_to_file = $dir . $date->format('m-Y') . '.csv';

        // Header
        $out .= 'ID' . $sep;
        $out .= 'Nom' . $sep;
        $out .= 'Prénom' . $sep;
        $out .= 'Nombre de ticket' . $sep;
        $out .= $sep_line;

        // Content
        foreach ($users as $u) {
            $out .= $u->rowid . $sep;
            $out .= $u->lastname . $sep;
            $out .= $u->firstname . $sep;
            $out .= $u->ticket_restau . $sep;
            $out .= $sep_line;
        }

        file_put_contents($path_to_file, $out);
    }

}
