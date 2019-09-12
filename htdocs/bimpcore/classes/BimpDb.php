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
        if (empty($data)) {
            return 1;
        }

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
            } else {
                if (is_array($value)) {
                    $value = json_encode($value);
                }
                $values .= '"' . $this->db->escape($value) . '"';
            }
        }
        $fields .= ')';
        $values .= ')';

        $sql = 'INSERT INTO ' . MAIN_DB_PREFIX . $table . $fields . $values;

        if (BimpDebug::isActive('bimpcore/objects/print_insert_sql')) {
            echo 'SQL: ' . $sql . '<br/>';
        }
        $result = $this->db->query($sql);
        if ($result > 0) {
            if ($return_id) {
                return $this->db->last_insert_id(MAIN_DB_PREFIX . $table);
            }
            return 1;
        }

        $this->logSqlError();
        return 0;
    }

    public function update($table, $data, $where = '')
    {
        if (empty($data)) {
            return 1;
        }

        if (!$where) {
            $this->db->lasterror = 'UPDATE ' . $table . ' Clause WHERE absente';
            return -1;
        }

        $sql = 'UPDATE ' . MAIN_DB_PREFIX . $table . ' SET ';
        $first_loop = true;
        foreach ($data as $name => $value) {
            if (!$first_loop) {
                $sql .= ',';
            } else {
                $first_loop = false;
            }
            $sql .= '`' . $name . '` = ';
            if (is_array($value)) {
                $value = json_encode($value);
            }
            $sql .= '"' . $this->db->escape($value) . '"';
        }
        $sql .= ' WHERE ' . $where;

        if (BimpDebug::isActive('bimpcore/objects/print_update_sql')) {
            echo 'SQL: ' . $sql . '<br/>';
        }

        return $this->execute($sql);
    }

    public function execute($sql)
    {
        $transac = (stripos(trim($sql), "SELECT") === 0) ? 0 : 1;
        if ($transac)
            $this->db->begin();

        $result = $this->db->query($sql);

        if ($transac) {
            if ($result > 0) {
                $this->db->commit();
            } else {
                $this->db->rollback();
            }
        }
        if (!$result)
            $this->logSqlError();

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
                $tabSql = explode(";", $sql);
                foreach ($tabSql as $req) {
                    if ($req != "")
                        if ($result = $this->execute($req) < 0)
                            return false;
                }
            }
            return true;
        }
        return false;
    }

    public function getRows($table, $where = '1', $limit = null, $return = 'object', $fields = null, $order_by = null, $order_way = null)
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

        if (!is_null($order_by)) {
            $sql .= ' ORDER BY `' . $order_by . '`';
            if (!is_null($order_way)) {
                $sql .= strtoupper($order_way);
            }
        }

        if (!is_null($limit)) {
            $sql .= ' LIMIT ' . $limit;
        }

        return $this->executeS($sql, $return);
    }

    public function getRow($table, $where = '1', $fields = null, $return = 'object')
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
            if ($return === 'object') {
                $obj = $this->db->fetch_object($result);
            } else {
                $obj = $this->db->fetch_array($result);
            }
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

    public function getMax($table, $field, $where = '1')
    {
        $sql = 'SELECT MAX(`' . $field . '`) as max FROM ' . MAIN_DB_PREFIX . $table . ' WHERE ' . $where;

        $result = $this->db->executeS($sql, 'array');

        if (isset($result[0]['max'])) {
            return $result[0]['max'];
        }

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
