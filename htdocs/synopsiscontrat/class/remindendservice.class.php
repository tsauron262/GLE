<?php

if(!isset($conf))
    require_once('../../main.inc.php');
include_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
include_once DOL_DOCUMENT_ROOT . '/contrat/class/contrat.class.php';

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
        $this->output = '';
        $services = array();

        // Select contrat
        $sql = 'SELECT c.rowid as c_rowid, ';
        // Select contradet 
        $sql .= ' cd.rowid as cd_rowid, cd.statut as statut_line,';
        // Select societe_commerciaux
        $sql .= ' sc.fk_user as fk_user, sc.fk_soc as sc_fk_soc';

        // From contrat
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'contrat as c';


        // Join societe_commerciaux
        $sql .= ' LEFT JOIN (';
        $sql .= ' SELECT * FROM ' . MAIN_DB_PREFIX . 'societe_commerciaux';
        $sql .= ' ) as sc';
        $sql .= ' ON c.fk_soc=sc.fk_soc';

        
        // Join contradet
        $sql .= ' JOIN (';
        $sql .= ' SELECT * FROM ' . MAIN_DB_PREFIX . 'contratdet';
        $sql .= '   WHERE rowid IN (';
        $sql .= '       SELECT MAX(rowid) FROM ' . MAIN_DB_PREFIX . 'contratdet GROUP BY fk_contrat';
        $sql .= '   )';
        $sql .= ' ) as cd';
        $sql .= ' ON c.rowid=cd.fk_contrat';

        $sql .= ' WHERE cd.statut=4 '; // contrat line open
        $sql .= ' AND cd.date_fin_validite <= NOW() + INTERVAL ' . $days . ' DAY';
        $sql .= ' AND c.statut > 0'; // contrat isn't draft
        $sql .= ' ORDER BY c.rowid';
//die($sql);
        $result = $this->db->query($sql);
        if ($result) {
            while ($obj = $this->db->fetch_object($result)) {
                $contrat = new Contrat($this->db);
                $contrat->fetch($obj->c_rowid);
                $societe = new Societe($this->db);
                $societe->fetch($obj->sc_fk_soc);
                $services[] = array(
                    'id_user' => $obj->fk_user,
                    'statut_line' => $obj->statut_line,
                    'id_contrat' => $obj->c_rowid,
                    'id_line' => $obj->cd_rowid,
                    'nom' => $societe->getNomUrl(1),
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
    public function setTaskForService($days = 3) {
        $services = $this->getUrgentService($days);
        global $conf;
        $newTasksSends = 0;

        foreach ($services as $service) {
            if (isset($conf->global->MAIN_MODULE_BIMPTASK)) {
                // prevent multiple task for 1 contrat
                if ($service['id_user'] < 1)
                    $service['id_user'] = 62;
                $task = BimpObject::getInstance("bimptask", "BIMP_Task");
                $test = "contratdet:rowid=" . $service['id_line'] . ' AND statut=5';
                $tasks = $task->getList(array('test_ferme' => $test, "id_user_owner" => $service['id_user']));
                if (count($tasks) == 0) {
                    $param = array(
                        "src" => "",
                        "dst" => "suivicontrat@bimp.fr",
                        "subj" => "Service à relancer",
                        "id_user_owner" => $service['id_user'],
                        "txt" => $service['nom'] . $service['nom_url'],
                        "test_ferme" => $test);
                    $errors_befor = sizeof($this->errors);
                    $this->errors = array_merge($this->errors, $task->validateArray($param)); // check params
                    $this->errors = array_merge($this->errors, $task->create()); // create task
                    if ($errors_befor == sizeof($this->errors))
                        $newTasksSends++;
                    else
                        $this->errors[] = "Erreur sur le contrat " . $service['id_contrat'];
                }
            } else {
                $this->errors[] = "Le module bimptask n'est pas activé,"
                        . " le rappel des tâches urgentes pour les commerciaux n'a pas pû être effectué";
            }
        }

        $errors = sizeof($this->errors);
        $errorString = (sizeof($this->errors) == 0) ? ' Aucune' : implode(',', $this->errors);
        $this->output = "Nombre de tâche envoyé: " . $newTasksSends . '. Erreurs:' . $errorString;

        if ($errors != 0)
            return -$errors;
        else
            return 0;
    }

}
