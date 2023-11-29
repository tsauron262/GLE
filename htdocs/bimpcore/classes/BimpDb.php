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

//        if (BimpDebug::isActive()) {
//            BimpDebug::addDebug('bimpdb_sql', 'INSERT - ' . $table, BimpRender::renderSql($sql));
//        }
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

//        if (BimpDebug::isActive()) {
//            BimpDebug::addDebug('bimpdb_sql', 'UPDATE - ' . $table, BimpRender::renderSql($sql));
//        }

        return $this->execute($sql);
    }

    public function execute($sql)
    {
        global $conf;
        $sql = str_replace('__entity__', $conf->entity, $sql);
        $result = $this->db->query($sql);
        if (!$result)
            $this->logSqlError($sql);

        return $result;
    }

    public function executeS($sql, $return = 'object', $returned_field = null)
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
                        if (is_null($returned_field)) {
                            $rows[] = $this->db->fetch_array($result);
                        } else {
                            $res = $this->db->fetch_array($result);
                            $rows[] = $res[$returned_field];
                        }
                        break;
                }
            }

            $this->db->free($result);
        } else {
            $this->logSqlError($sql);
        }

        return $rows;
    }

    public function executeFile($file, &$errors = array())
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
                    $req = trim($req);
                    if ($req != "") {
                        if ($result = $this->execute($req) < 0) {
                            BimpCore::addlog('Erreur SQL maj', 3, 'sql', null, array(
                                'Requête' => (!is_null($req) ? $req : ''),
                                'Erreur'  => $this->err()
                            ));
                            $errors[] = 'Echec requête "' . $req . '" - ' . $this->err();
                            return false;
                        }
                    }
                }
            }
            return true;
        }

        $errors[] = 'Le fichier "' . $file . '" n\'existe pas';
        return false;
    }

    public function getRows($table, $where = '1', $limit = null, $return = 'object', $fields = null, $order_by = null, $order_way = null, $joins = array())
    {
        $sql = 'SELECT ';

        if ($where == '') {
            $where = '1';
        }

        if (!is_null($fields)) {
            $fl = true;
            foreach ($fields as $field) {
                if (!$fl) {
                    $sql .= ', ';
                } else {
                    $fl = false;
                }

                if (!preg_match('/[\. ]/', $field)) {
                    $sql .= '`' . $field . '`';
                } else {
                    $sql .= $field;
                }
            }
        } else {
            $sql .= '*';
        }

        $sql .= ' FROM ' . MAIN_DB_PREFIX . $table;

        foreach ($joins as $key => $join) {
            if (isset($join['alias'])) {
                $join_alias = $join['alias'];
            } else {
                $join_alias = $key;
            }
            $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . $join['table'] . ' ' . $join_alias;
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

    public function getRow($table, $where = '1', $fields = null, $return = 'object', $order_by = '', $order_way = 'ASC')
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
        $sql .= ' WHERE ' . $where;

        if ($order_by) {
            $sql .= ' ORDER BY `' . $order_by . '` ' . $order_way;
        }

        $sql .= ' LIMIT 1';

//        if ($table == 'stock_mouvement') {
//            die($sql);
//        }

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
        if ($result)
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

        if (is_object($result)) {
            $this->db->free($result);
        }
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
    
    public function getMin($table, $field, $where = '1')
    {
        $sql = 'SELECT MIN(`' . $field . '`) as min FROM ' . MAIN_DB_PREFIX . $table . ' WHERE ' . $where;

        $result = $this->executeS($sql, 'array');

        if (isset($result[0]['min'])) {
            return $result[0]['min'];
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

    public function getCount($table, $where = '1', $primary = 'id', $joins = array())
    {
        $sql = 'SELECT COUNT(DISTINCT `' . $primary . '`) as nb_rows FROM ' . MAIN_DB_PREFIX . $table;

        if (!empty($joins)) {
            foreach ($joins as $key => $join) {
                $table = (isset($join['table']) ? $join['table'] : '');
                $on = (isset($join['on']) ? $join['on'] : '');

                if ($table && $on) {
                    $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . $table . ' ' . (isset($join['alias']) ? $join['alias'] : $key) . ' ON ' . $on;
                }
            }
        }


        $sql .= ' WHERE ' . $where;
//        echo $sql;
        $result = $this->executeS($sql, 'array');

//        echo '<pre>';
//        print_r($result);
//        exit;

        if (isset($result[0]['nb_rows'])) {
            return (int) $result[0]['nb_rows'];
        }

        return 0;
    }

    public function getAvg($table, $field, $where = '1')
    {
        $sql = 'SELECT AVG(`' . $field . '`) as avg FROM ' . MAIN_DB_PREFIX . $table . ' WHERE ' . $where;

        $result = $this->executeS($sql, 'array');

        if (isset($result[0]['avg'])) {
            return $result[0]['avg'];
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

    public function rowExists($table, $id, $primary = 'id')
    {
        return ((int) $this->getCount($table, $primary . ' = ' . $id, $primary) > 0 ? true : false);
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
//        BimpCore::addlog('Erreur SQL', 3, 'sql', null, array(
//            'Requête' => (!is_null($sql) ? $sql : ''),
//            'Erreur'  => $this->db->lasterror()
//        ));
    }

    public function err()
    {
        return $this->db->lasterror();
    }
}
