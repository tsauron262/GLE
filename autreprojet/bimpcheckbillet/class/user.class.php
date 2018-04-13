<?php

class User {

    public $errors;
    public $db;
    public $id;
    public $first_name;
    public $last_name;
    private $email;
    public $status;
    public $id_events;

    const STATUT_ADMIN = 1;
    const STATUT_SUPER_ADMIN = 2;

    public function __construct($db) {
        $this->db = $db;
        $this->errors = array();
        $this->id_events = array();
    }

    public function fetch($id, $fetch_event = true) {

        if ($id < 0) {
            $this->errors[] = "Identifiant invalide :" . $id;
            return false;
        }

        $sql = 'SELECT id, first_name, last_name, email, login, pass_word, status';
        $sql .= ' FROM user';
        $sql .= ' WHERE id=' . $id;


        $result = $this->db->query($sql);
        if ($result and $result->rowCount() > 0) {
            while ($obj = $result->fetchObject()) {
                $this->id = $id;
                $this->first_name = $obj->first_name;
                $this->last_name = $obj->last_name;
                $this->email = $obj->email;
                $this->login = $obj->login;
                $this->pass_word = $obj->pass_word;
                $this->status = $obj->status;
                if ($fetch_event)
                    $this->fetch_event();
                return 1;
            }
        } else {
            $this->errors[] = "Aucun utilisateur n'a l'identifiant " . $id;
            return -2;
        }
        return -1;
    }

    private function fetch_event() {

        if ($this->id < 0) {
            $this->errors[] = "Identifiant invalide :" . $this->id;
            return false;
        }

        $sql = 'SELECT fk_event';
        $sql .= ' FROM event_admin';
        $sql .= ' WHERE fk_user=' . $this->id;


        $result = $this->db->query($sql);
        if ($result and $result->rowCount() > 0) {
            while ($obj = $result->fetchObject()) {
                $this->id_events[] = $obj->fk_event;
            }
            return 1;
        } elseif (!$result) {
            $this->errors[] = "Erreur sql, fonction : fetch_event().";
            return -2;
        }
        return -1;
    }

//    public function create($first_name, $last_name, $email, $date_born) {
//
//        if ($first_name == '')
//            $this->errors[] = "Le champ prénom est obligatoire";
//        if ($last_name == '')
//            $this->errors[] = "Le champ nom est obligatoire";
//        if ($email == '')
//            $this->errors[] = "Le champ email est obligatoire";
//        if ($date_born == '')
//            $this->errors[] = "Le champ date de naissance est obligatoire";
//        if (sizeof($this->errors) != 0)
//            return -3;
//
//        $date_born_obj = DateTime::createFromFormat('d/m/Y', $date_born);
//
//
//        $sql = 'INSERT INTO `user` (';
//        $sql.= '`first_name`';
//        $sql.= ', `date_registration`';
//        $sql.= ', `date_born`';
//        $sql.= ', `last_name`';
//        $sql.= ', `email`';
//        $sql.= ') ';
//        $sql.= 'VALUES ("' . $first_name . '"';
//        $sql.= ', now()';
//        $sql.= ', "' . $date_born_obj->format("Y-m-d") . '"';
//        $sql.= ', "' . $last_name . '"';
//        $sql.= ', "' . $email . '"';
//        $sql.= ')';
//
//
//        try {
//            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
//            $this->db->beginTransaction();
//            $this->db->exec($sql);
//            $last_insert_id = $this->db->lastInsertId();
//            $this->db->commit();
//            return $last_insert_id;
//        } catch (Exception $e) {
//            if ($e->errorInfo[1] == 1062)
//                $this->errors[] = "Cet email est déjà utilisé.";
//            else
//                $this->errors[] = "Impossible de créer l'évènement. " . $e;
//            $this->db->rollBack();
//            return -2;
//        }
//        return -1;
//    }

    public function create($first_name, $last_name, $email, $login, $pass_word, $status = null) {
        if ($first_name == '')
            $this->errors[] = "Le champ prénom est obligatoire";
        if ($last_name == '')
            $this->errors[] = "Le champ nomest obligatoire";
        if ($email == '')
            $this->errors[] = "Le champ email est obligatoire";
        if ($login == '')
            $this->errors[] = "Le champ identifiant est obligatoire";
        if ($pass_word == '')
            $this->errors[] = "Le champ mot de passe est obligatoire";
        if (sizeof($this->errors) != 0)
            return -3;

        if ($status == null)
            $status = $this::STATUT_ADMIN;

        $sql = 'INSERT INTO `user` (';
        $sql.= '`first_name`';
        $sql.= ', `last_name`';
        $sql.= ', `email`';
        $sql.= ', `login`';
        $sql.= ', `pass_word`';
        $sql.= ', `status`';
        $sql.= ') ';
        $sql.= 'VALUES ("' . $first_name . '"';
        $sql.= ', "' . $last_name . '"';
        $sql.= ', "' . $email . '"';
        $sql.= ', "' . $login . '"';
        $sql.= ', "' . $pass_word . '"';
        $sql.= ', ' . $status;
        $sql.= ')';

//        echo $sql;
        try {
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->db->beginTransaction();
            $this->db->exec($sql);
            $last_insert_id = $this->db->lastInsertId();
            $this->db->commit();
            return $last_insert_id;
        } catch (Exception $e) {
            if ($e->errorInfo[1] == 1062) {
                if (strpos($e->errorInfo[2], 'email') !== false)
                    $this->errors[] = "Cet email est déjà utilisé";
                if (strpos($e->errorInfo[2], 'login') !== false)
                    $this->errors[] = "Ce login est déjà utilisé.";
            } else
                $this->errors[] = "Impossible de créer l'utilisateur. " . $e;
            $this->db->rollBack();
            return -2;
        }
        return -1;
    }

    public function connect($login, $pass_word) {

        $sql = 'SELECT id';
        $sql .= ' FROM user';
        $sql .= ' WHERE login="' . $login . '"';
        $sql .= ' AND pass_word="' . $pass_word . '"';


        $result = $this->db->query($sql);
        if ($result and $result->rowCount() > 0) {
            while ($obj = $result->fetchObject()) {
                return $obj->id;
            }
        } else {
            $this->errors[] = "Login ou mot de passe incorrect.";
            return -2;
        }
        return -1;
    }

}
