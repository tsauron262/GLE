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
            $date_debut = new DateTime($year . '-' . ((int) $month-1). '-01');
            $nb_day_of_month = cal_days_in_month(CAL_GREGORIAN, $date_debut->format('m'), $date_debut->format('Y'));
            $date_fin = new DateTime($year . '-' . ((int) $month-1). '-01');
            $date_fin ->add(new DateInterval('P1M'));
            $date_fin ->sub(new DateInterval('P1D'));
        } catch (Exception $e) {
            $this->errors[] = $e->getMessage();
            return -1;
        }


        $days_worked = num_open_dayUser($id_user, $date_debut->getTimestamp(), $date_fin->getTimestamp(),0,1);

        $holidays = getNbHolidays($date_debut, $date_fin, $id_user);

        
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

//        echo $date_target->getTimestamp();
//        echo '<br/>$date_work_plus_1' . $date_work_plus_1->format('d/m/Y');
//        echo '<br/>$date_work' . $date_work->format('d/m/Y');
//        echo '<br/>$date_holiday' . $date_holiday->format('d/m/Y');
//        die('ok');
        // TODO create csv
        // TODO send email
        
        return $nbTicket;
    }

    

}
