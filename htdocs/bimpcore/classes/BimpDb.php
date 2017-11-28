<?php

class BimpDb
{

    public $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function insert($table, $data, $return_id = false)
    {
        $fields = '(';
        $values = ' VALUES (';

        $first_loop = true;
        foreach ($data as $name => $value) {
            if (!$first_loop) {
                $fields .= ',';
                $values .= ', ';
            } else {
                $first_loop = false;
            }
            $fields .= $name;
            if (is_int($value)) {
                $values .= (int) $value;
            } elseif (is_numeric($value)) {
                $values .= $value;
            } else {
                $values .= '"' . $this->db->escape($value) . '"';
            }
        }
        $fields .= ')';
        $values .= ')';

        $sql = 'INSERT INTO ' . MAIN_DB_PREFIX . $table . $fields . $values;

        $result = $this->db->query($sql);
        if ($result > 0) {
            if ($return_id) {
                $return = $this->db->last_insert_id(MAIN_DB_PREFIX . $table);
            } else {
                $return = 1;
            }
            return $return;
        } else {
            $this->logSqlError();
        }

        return 0;
    }

    public function update($table, $data, $where = '1')
    {
        $sql = 'UPDATE ' . MAIN_DB_PREFIX . $table . ' SET ';
        $first_loop = true;
        foreach ($data as $name => $value) {
            if (!$first_loop) {
                $sql .= ',';
            } else {
                $first_loop = false;
            }
            $sql .= '`' . $name . '` = ';
            if (is_numeric($value)) {
                $sql .= $value;
            } else {
                $sql .= '"' . $this->db->escape($value) . '"';
            }
        }
        $sql .= ' WHERE ' . $where;

        return $this->execute($sql);
    }

    public function execute($sql)
    {
        $this->db->begin();

        $result = $this->db->query($sql);

        if ($result > 0) {
            $this->db->commit();
        } else {
            $this->db->rollback();
            $this->logSqlError();
        }

        return $result;
    }

    public function executeS($sql, $return = 'object')
    {
        $result = $this->db->query($sql);

        $rows = null;
        if ($result) {
            $rows = array();
            for ($i = 0; $i < $this->db->num_rows($result); $i++) {
                switch ($return) {
                    case 'object':
                        $rows[] = $this->db->fetch_object($result);
                        break;

                    case 'array':
                        $rows[] = $this->db->fetch_array($result);
                        break;
                }
            }
        } else {
            $this->logSqlError();
        }

        $this->db->free($result);

        return $rows;
    }

    public function executeFile($file)
    {
        if (file_exists($file)) {
            $sql = file_get_contents($file);
            if ($sql) {
                $result = $this->execute($sql);
                return ($result > 0) ? true : false;
            }
        }
        return false;
    }

    public function getRows($table, $where = '1', $limit = null, $return = 'object', $fields = null)
    {
        $sql = 'SELECT ';

        if (!is_null($fields)) {
            $fl = true;
            foreach ($fields as $field) {
                if (!$fl) {
                    $sql .= ', ';
                } else {
                    $fl = false;
                }
                $sql .= '`' . $field . '`';
            }
        } else {
            $sql .= '*';
        }

        $sql .= ' FROM ' . MAIN_DB_PREFIX . $table;
        $sql .= ' WHERE ' . $where;

        if (!is_null($limit)) {
            $sql .= ' LIMIT ' . $limit;
        }

        return $this->executeS($sql, $return);
    }

    public function getRow($table, $where = '1', $fields = null)
    {
        $sql = 'SELECT ';

        if (is_null($fields)) {
            $sql .= '*';
        } else {
            $firstLoop = true;
            foreach ($fields as $f) {
                if (!$firstLoop) {
                    $sql .= ', ';
                } else {
                    $firstLoop = false;
                }
                $sql .= '`' . $f . '`';
            }
        }

        $sql .= ' FROM ' . MAIN_DB_PREFIX . $table;
        $sql .= ' WHERE ' . $where . ' LIMIT 1';
        $result = $this->db->query($sql);
        if ($result && $this->db->num_rows($result)) {
            $obj = $this->db->fetch_object($result);
            $this->db->free($result);
            return $obj;
        }
        $this->db->free($result);
        return null;
    }

    public function getRowArray($table, $where = '1', $fields = null)
    {
        $obj = $this->getRow($table, $where, $fields);

        if (!is_null($obj) && is_object($obj)) {
            $data = array();
            foreach ($obj as $prop => $value) {
                $data[$prop] = $value;
            }

            return $data;
        }

        return null;
    }

    public function getValue($table, $field, $where = '1')
    {
        $sql = 'SELECT `' . $field . '` FROM ' . MAIN_DB_PREFIX . $table . ' WHERE ' . $where . ' LIMIT 1';

        $result = $this->db->query($sql);

        if ($result && $this->db->num_rows($result)) {
            $obj = $this->db->fetch_object($result);
            $this->db->free($result);
            return $obj->$field;
        }
        $this->db->free($result);
        return null;
    }

    public function getValues($table, $field, $where, $limit = null)
    {
        $sql = 'SELECT `' . $field . '` FROM ' . MAIN_DB_PREFIX . $table . ' WHERE ' . $where;

        if (!is_null($limit)) {
            $sql .= ' LIMIT ' . $limit;
        }

        $result = $this->db->query($sql);

        $rows = null;
        if ($result && $this->db->num_rows($result)) {
            $rows = array();
            for ($i = 0; $i < $this->db->num_rows($result); $i++) {
                $obj = $this->db->fetch_object($result);
                $rows[] = $obj->$field;
            }
        }
        $this->db->free($result);
        return $rows;
    }

    public function delete($table, $where)
    {
        $sql = 'DELETE FROM ' . MAIN_DB_PREFIX . $table;
        $sql .= ' WHERE ' . $where;

        return $this->execute($sql);
    }

    protected function logSqlError($sql = null)
    {
        $msg = 'Erreur SQL' . "\n";
        if (!is_null($sql)) {
            $msg .= 'Requête: ' . $sql . "\n";
        }
        $msg .= 'Msg SQL: ' . $this->db->lasterror();
        dol_syslog($msg, 3);
    }
}
