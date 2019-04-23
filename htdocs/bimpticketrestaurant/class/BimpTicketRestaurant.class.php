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

        $days_worked = num_open_day($date_work->getTimestamp(), $date_work_plus_1->getTimestamp());

        $holidays = $this->getHolidays($date_holiday);

        echo '<br/>Entre le ' . $date_work->format('d/m/Y') . ' et le ' . $date_work_plus_1->format('d/m/Y') . ' il y a ' . $days_worked . ' jour de travail';

        echo '<pre>';
        print_r($holidays);

//        echo $date_target->getTimestamp();
//        echo '<br/>$date_work_plus_1' . $date_work_plus_1->format('d/m/Y');
//        echo '<br/>$date_work' . $date_work->format('d/m/Y');
//        echo '<br/>$date_holiday' . $date_holiday->format('d/m/Y');
//        die('ok');
        // TODO create csv
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
//            if (isset($holidays[$obj->fk_user]))
//                $holidays[$obj->fk_user] = $obj;
//            else
//                $holidays[$obj->fk_user] = array($obj);
        }


        foreach ($holidays as $i => $h) {
            $date_start = new DateTime($h->date_debut);
            $date_end = new DateTime($h->date_fin);

            $h_month_start = (int) $date_start->format('m');
            $h_month_end = (int) $date_end->format('m');

            // Start and end are in the same month
            if ($h_month_start == $h_month_end) {
                $holidays[$i]->ticket_to_remove = num_open_day($date_start->getTimestamp(), $date_end->getTimestamp());
                $holidays[$i]->id = 1;
            }
            // Overlap with previous month
            elseif ($h_month_start < $month_to_check and ( $h_month_start + 1) == $month_to_check) {
                $holidays[$i]->ticket_to_remove = num_open_day($date_month->getTimestamp(), $date_end->getTimestamp());
                $holidays[$i]->id = 2;
            }
            // Overlap with next month
            elseif ($month_to_check < $h_month_end and ( $month_to_check + 1) == $h_month_end) {
                $date_end_month = new DateTime($date_start->format('Y-m-' . $nb_day_of_month));
                $holidays[$i]->ticket_to_remove = num_open_day($date_start->getTimestamp(), $date_end_month->getTimestamp());
                $holidays[$i]->id = 3;
            }
            // start and end in other month
            else {
                $date_end_month = new DateTime($date_start->format('Y-m-' . $nb_day_of_month));
                $holidays[$i]->ticket_to_remove = num_open_day($date_month->getTimestamp(), $date_end_month->getTimestamp());
                $holidays[$i]->id = 4;
            }
        }

        return $holidays;
    }

}
