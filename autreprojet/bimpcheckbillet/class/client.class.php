<?php

class Client {

    public $errors;
    private $db;
    private $id;
    private $first_name;
    private $date_registration;
    private $last_name;
    private $email;

    public function __construct($db) {
        $this->db = $db;
        $this->errors = array();
    }

    public function fetch($id) {

        if ($id < 0) {
            $this->errors[] = "Identifiant invalide :" . $id;
            return false;
        }

        $sql = 'SELECT id, first_name, date_registration, last_name, email';
        $sql .= ' FROM client';
        $sql .= ' WHERE id=' . $id;


        $result = $this->db->query($sql);
        if ($result and $result->rowCount() > 0) {
            while ($obj = $result->fetchObject()) {
                $this->id = $id;
                $this->first_name = $obj->first_name;
                $this->date_registration = $obj->date_registration;
                $this->last_name = $obj->last_name;
                $this->email = $obj->email;
                return 1;
            }
        } else {
            $this->errors[] = "Aucun client n'a l'identifiant " . $id;
            return -2;
        }
        return -1;
    }

    public function create($first_name, $last_name, $email, $date_born) {

        // TODO test param

        $date_born_obj = DateTime::createFromFormat('d/m/Y', $date_born);


        $sql = 'INSERT INTO `client` (';
        $sql.= '`first_name`';
        $sql.= ', `date_registration`';
        $sql.= ', `date_born`';
        $sql.= ', `last_name`';
        $sql.= ', `email`';
        $sql.= ') ';
        $sql.= 'VALUES ("' . $first_name . '"';
        $sql.= ', now()';
        $sql.= ', "' . $date_born_obj->format("Y-m-d") . '"';
        $sql.= ', "' . $last_name . '"';
        $sql.= ', "' . $email . '"';
        $sql.= ')';


        try {
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->db->beginTransaction();
            $this->db->exec($sql);
            $last_insert_id = $this->db->lastInsertId();
            $this->db->commit();
            return $last_insert_id;
        } catch (Exception $e) {
            if ($e->errorInfo[1] == 1062)
                $this->errors[] = "Cet email est déjà utilisé.";
            else
                $this->errors[] = "Impossible de créer l'évènement. " . $e;
            $this->db->rollBack();
            return -2;
        }
        return -1;
    }

}
