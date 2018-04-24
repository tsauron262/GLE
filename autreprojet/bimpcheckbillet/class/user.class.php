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
    public $events;
    public $login;
    public $pass_word;
    public $create_event_tariff;
    public $reserve_ticket;
    public $validate_event;

    const STATUT_ADMIN = 1;
    const STATUT_SUPER_ADMIN = 2;

    public function __construct($db) {
        $this->db = $db;
        $this->errors = array();
        $this->id_events = array();
        $this->events = array();
    }

    public function fetch($id, $fetchEvent = true) {

        if ($id < 0) {
            $this->errors[] = "Identifiant invalide :" . $id;
            return false;
        }

        $sql = 'SELECT id, first_name, last_name, email, login, pass_word, status, create_event_tariff, reserve_ticket, validate_event';
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
                $this->create_event_tariff = $obj->create_event_tariff;
                $this->reserve_ticket = $obj->reserve_ticket;
                $this->validate_event = $obj->validate_event;
                if ($fetchEvent)
                    $this->fetchEvent();
                return 1;
            }
        } else {
            $this->errors[] = "Aucun utilisateur n'a l'identifiant " . $id;
            return -2;
        }
        return -1;
    }

    private function fetchEvent() {

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
            $this->errors[] = "Erreur sql, fonction : fetchEvent().";
            return -2;
        }
        return -1;
    }

    public function create($first_name, $last_name, $email, $login, $pass_word, $status = null) {
        if ($first_name == '')
            $this->errors[] = "Le champ prénom est obligatoire";
        if ($last_name == '')
            $this->errors[] = "Le champ nom est obligatoire";
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

    public function getUser() {

        $users = array();
        $sql = 'SELECT id';
        $sql .= ' FROM user';

        $result = $this->db->query($sql);
        if ($result and $result->rowCount() > 0) {
            while ($obj = $result->fetchObject()) {
                $user = new User($this->db);
                $user->fetch($obj->id);
                foreach ($user->id_events as $id_event) {
                    $event = new Event($this->db);
                    $event->fetch($id_event);
                    $user->events[] = $event;
                }
                $users[$user->id] = $user;
            }
            return $users;
        } elseif (!$result) {
            $this->errors[] = "Erreur SQL 8564";
            return -2;
        }
        return -1;
    }

    public function changeLoginAndPassWord($id_user, $login, $pass_word) {

        if ($id_user < 0)
            $this->errors[] = "L'identifiant de l'utilisateur est invalie: " . $id_user;
        if ($login == '')
            $this->errors[] = "Le login de l'utilisateur est vide";
        if ($pass_word == '')
            $this->errors[] = "Le mot de passe de l'utilisateur est vide";
        if (sizeof($this->errors) != 0)
            return -3;

        $sql = 'UPDATE user';
        $sql .= ' SET login="' . $login . '"';
        $sql .= ', pass_word="' . $pass_word . '"';
        $sql .= ' WHERE id=' . $id_user;

        try {
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->db->beginTransaction();
            $this->db->exec($sql);
            $this->db->commit();
            return 1;
        } catch (Exception $e) {
            $this->errors[] = "Impossible de modifier le login et mot de passe de l'utilisateur. " . $e;
            $this->db->rollBack();
            return -2;
        }
        return -1;
    }

    public function updateUser($id_user, $field, $value) {

        if ($id_user < 0) {
            $this->errors[] = "L'identifiant de l'utilisateur est invalie: " . $id_user;
            return -3;
        }

        if ($value == "true")
            $value = 1;
        else if ($value == "false")
            $value = 0;

        $sql = 'UPDATE user';
        $sql .= ' SET ' . $field . '="' . $value . '"';
        $sql .= ' WHERE id=' . $id_user;

//        echo $sql;

        try {
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->db->beginTransaction();
            $this->db->exec($sql);
            $this->db->commit();
            return 1;
        } catch (Exception $e) {
            $this->errors[] = "Impossible de modifier profil de l'utilisateur. " . $e;
            $this->db->rollBack();
            return -2;
        }
        return -1;
    }

}
