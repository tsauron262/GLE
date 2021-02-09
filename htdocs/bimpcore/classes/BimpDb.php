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
            } elseif (is_null($value)) {
                $values .= 'NULL';
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

        if (BimpDebug::isActive()) {
            BimpDebug::addDebug('bimpdb_sql', 'INSERT - ' . $table, BimpRender::renderSql($sql));
        }
        $result = $this->db->query($sql);
        if ($result > 0) {
            if ($return_id) {
                return $this->db->last_insert_id(MAIN_DB_PREFIX . $table);
            }
            return 1;
        }

        $this->logSqlError($sql);
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

            if (is_null($value)) {
                $sql .= 'NULL';
            } else {
                $sql .= '"' . $this->db->escape($value) . '"';
            }
        }
        $sql .= ' WHERE ' . $where;

        if (BimpDebug::isActive()) {
            BimpDebug::addDebug('bimpdb_sql', 'UPDATE - ' . $table, BimpRender::renderSql($sql));
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
            $this->logSqlError($sql);

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
            $this->logSqlError($sql);
        }

        $this->db->free($result);

        return $rows;
    }

    public function executeFile($file)
    {
        if (file_exists($file)) {
            $sql = file_get_contents($file);
            $sql = str_replace("llx_", MAIN_DB_PREFIX, $sql);
            $sql = str_replace("MAIN_DB_PREFIX", MAIN_DB_PREFIX, $sql);
            if ($sql) {
//                $sql = str_replace("; \n", ";\n", $sql);
                $sql = preg_replace("/;( )*\n/U", ";\n", $sql);
                $tabSql = explode(";\n", $sql);
                foreach ($tabSql as $req) {
                    if ($req != "")
                        if ($result = $this->execute($req) < 0) {
                            BimpCore::addlog('Erreur SQL maj', 3, 'sql', null, array(
                                'Requête' => (!is_null($req) ? $req : ''),
                                'Erreur'  => $this->lasterror()
                            ));
                            return false;
                        }
                }
            }
            return true;
        }
        return false;
    }

    public function getRows($table, $where = '1', $limit = null, $return = 'object', $fields = null, $order_by = null, $order_way = null, $joins = array())
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

                if (!preg_match('/\./', $field)) {
                    $sql .= '`' . $field . '`';
                } else {
                    $sql .= $field;
                }
            }
        } else {
            $sql .= '*';
        }

        $sql .= ' FROM ' . MAIN_DB_PREFIX . $table;

        foreach ($joins as $join) {
            $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . $join['table'] . ' ' . $join['alias'];
            $sql .= ' ON ' . $join['on'];
        }

        $sql .= ' WHERE ' . $where;

        if (!is_null($order_by)) {
            $sql .= ' ORDER BY ';
            if (!preg_match('/\./', $order_by)) {
                $sql .= '`' . $order_by . '`';
            } else {
                $sql .= $order_by;
            }
            if (!is_null($order_way)) {
                $sql .= ' ' . strtoupper($order_way);
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

    public function getValue($table, $field, $where = '1', $order_by = '', $order_way = 'DESC')
    {
        $sql = 'SELECT `' . $field . '` FROM ' . MAIN_DB_PREFIX . $table . ' WHERE ' . $where;
        if ($order_by) {
            $sql .= ' ORDER BY `' . $order_by . '` ' . $order_way;
        }
        $sql .= ' LIMIT 1';
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

        $result = $this->executeS($sql, 'array');

        if (isset($result[0]['max'])) {
            return $result[0]['max'];
        }

        return null;
    }

    public function getSum($table, $field, $where = '1')
    {
        $sql = 'SELECT SUM(`' . $field . '`) as sum FROM ' . MAIN_DB_PREFIX . $table;
        $sql .= ' WHERE ' . $where;

        $result = $this->executeS($sql, 'array');

        if (isset($result[0]['sum'])) {
            return $result[0]['sum'];
        }

        return 0;
    }

    public function getCount($table, $where = '1', $primary = 'id')
    {
        $sql = 'SELECT COUNT(DISTINCT `' . $primary . '`) as nb_rows FROM ' . MAIN_DB_PREFIX . $table;
        $sql .= ' WHERE ' . $where;

        $result = $this->executeS($sql, 'array');

        if (isset($result[0]['nb_rows'])) {
            return (int) $result[0]['nb_rows'];
        }

        return 0;
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
        if (!(string) $where || (string) $where == '1') {
            BimpCore::addlog('Delete SQL sans WHERE', Bimp_Log::BIMP_LOG_URGENT, 'bimpcore', null, array(
                'table' => $table
            ));
            return 0;
        }

        $sql = 'DELETE FROM ' . MAIN_DB_PREFIX . $table;
        $sql .= ' WHERE ' . $where;

        return $this->execute($sql);
    }

    protected function logSqlError($sql = null)
    {
        BimpCore::addlog('Erreur SQL', 3, 'sql', null, array(
            'Requête' => (!is_null($sql) ? $sql : ''),
            'Erreur'  => $this->db->lasterror()
        ));
    }

    public function err()
    {
        return $this->db->lasterror();
    }
}
