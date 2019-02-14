<?php

$path = dirname(__FILE__) . '/';

require_once($path . '../../main.inc.php');
include_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
include_once DOL_DOCUMENT_ROOT . '/contrat/class/contrat.class.php';

//require_once(DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php');
//class RemindEndService extends CommonObject {

class RemindEndService {

    private $db;
    public $errors;

    public function __construct($db) {
        $this->db = $db;
        $this->errors = array();
    }

    /**
     * @param $days number day to reach urgence
     * @return array of service
     */
    public function getUrgentService($days) {

        $services = array();

        // contrat
        $sql = 'SELECT c.rowid as c_rowid, ';
        // contradet 
        $sql .= ' cd.rowid as cd_rowid, cd.statut as statut_line,';
        // societe_commerciaux
        $sql .= ' sc.fk_user as fk_user';

        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'contrat as c';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'contratdet as cd ON c.rowid=cd.fk_contrat';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'societe_commerciaux as sc ON c.fk_soc=sc.fk_soc';
        $sql .= ' WHERE cd.statut=4 '; // contrat line open
        $sql .= ' AND cd.date_fin_validite <= NOW() + INTERVAL ' . $days . ' DAY';
        $sql .= ' AND c.statut>0'; // contrat isn't draft
        $sql .= ' ORDER BY c.rowid';

        $result = $this->db->query($sql);
        if ($result) {
            while ($obj = $this->db->fetch_object($result)) {
                $contrat = new Contrat($this->db);
                $contrat->fetch($obj->c_rowid);
                $services[] = array(
                    'id_user' => $obj->fk_user,
                    'statut_line' => $obj->statut_line,
                    'id_contrat' => $obj->c_rowid,
                    'id_line' => $obj->cd_rowid,
                    'nom_url' => $contrat->getNomUrl(1));
            }
        }
        return $services;
    }

    /**
     * 
     * @global type $conf dolibarr conf
     * @param  type $days  number day to reach urgence
     * @return type return number of task sent or -($number_of_errors) if there are some
     */
    public function setTaskForService($days) {
        $services = $this->getUrgentService($days);
        global $conf;
        $newTasksSends = 0;

        foreach ($services as $service) {
            if (isset($conf->global->MAIN_MODULE_BIMPTASK)) {
                // prevent multiple task for 1 contrat
                if ($service['id_contrat'] != $previous_service['id_contrat']) {
                    $task = BimpObject::getInstance("bimptask", "BIMP_Task");
                    $test = "contratdet:rowid=" . $service['id_line'] . ' AND statut=5';
                    $tasks = $task->getList(array('test_ferme' => $test));
                    if (count($tasks) == 0) {
                        $param = array(
                            "src" => "",
                            "dst" => "suivicontrat@bimp.fr",
                            "subj" => "Service à relancer",
                            "id_user_owner" => $service['id_user'],
                            "txt" => $service['nom_url'],
                            "test_ferme" => $test);
                        $errors_befor = sizeof($this->errors);
                        $this->errors = array_merge($this->errors, $task->validateArray($param)); // check params
                        $this->errors = array_merge($this->errors, $task->create()); // create task
                        if ($errors_befor == sizeof($this->errors))
                            $newTasksSends++;
                    }
                }
                $previous_service = $service;
            } else {
                $this->errors[] = "Le module bimptask n'est pas activé,"
                        . " le rappel des tâche urgente pour les commerciaux n'est pas pû être effectué";
                dol_print_error("Le module bimptask n'est pas activé,"
                        . " le rappel des tâche urgente pour les commerciaux n'est pas pû être effectué");
                return -1;
            }
        }
        $errors = sizeof($this->errors);

        $this->output = "Nombre de tâche envoyé: " . $newTasksSends . '' . implode(',', $this->errors);

        if ($errors != 0)
            return -$errors;
        else
            return 0;
    }

}
