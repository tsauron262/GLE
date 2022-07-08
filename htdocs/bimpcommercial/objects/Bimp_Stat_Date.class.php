<?php

class Bimp_Stat_Date extends BimpObject
{

    public $isOk = false;
    public $datas = array();
    public $datasPropal = array();
    public $datasCommande = array();
    public $datasFacture = array();
    public $signatureFilter = "";
    public $filterCusom = array();
    public $asGraph = true;
    public $filterCusomExclud = array();
    public static $factTypes = array(
        0 => 'Facture standard',
        1 => 'Facture de remplacement',
        2 => 'Avoir',
        3 => 'Facture d\'acompte',
        4 => 'Facture proforma',
        5 => 'Facture de situation'
    );

    public function displayOldValue($field, $nb_month)
    {
        global $modeCSV, $modeGraph;
        if ($this->isLoaded()) {
            $date = new DateTime($this->getData('date'));
            $date->sub(new DateInterval('P' . $nb_month . 'M'));

            $sql = $this->db->db->query("SELECT * FROM `llx_bimp_stat_date` WHERE `date` = '" . $date->format('Y-m-d') . "' AND `filter` = '" . $this->getData('filter') . "'");
            if ($this->db->db->num_rows($sql) > 0) {
                $ln = $this->db->db->fetch_object($sql);
                if (stripos($field, 'total') && !$modeCSV)
                    return price($ln->$field) . " €";
                elseif ($modeCSV && !$modeGraph)
                    return str_replace(".", ",", $ln->$field);
                else
                    return $ln->$field;
            } elseif (!$modeGraph)
                return BimpRender::renderAlerts("Pas de calcul pour le " . $date->format('Y-m-d'));
            else
                return 0;
        }
    }

    public function addConfigExtraParams()
    {
        $cols = array();

        foreach (array("qty" => 'Nb', "total" => 'Total') as $type => $labelType) {
            foreach (array("devis" => 'Devis', "commande" => 'Commande', "facture" => 'Facture') as $elem => $label) {
                foreach (array(1, 3, 6, 12, 24) as $nb_month) {
                    $cols[$type . "_" . $elem . '_' . $nb_month . '_mois'] = array(
                        'label' => $labelType . ' ' . $label . ' à ' . $nb_month . ' mois',
                        'value' => array(
                            'callback' => array(
                                'method' => 'displayOldValue',
                                'params' => array(
                                    $elem . "_" . $type,
                                    $nb_month
                                )
                            )
                        )
                    );
                }
            }
        }



        $this->config->addParams('lists_cols', $cols);
    }

    public function getListCount($filters = array(), $joins = array())
    {
        if (isset($filters["a.date"]) && isset($filters["a.date"]["or_field"][0]) && !isset($filters["a.date"]["or_field"][1])) {
            $filter = $filters["a.date"]["or_field"][0];
            if (isset($filter['min']) && isset($filter['max'])) {
                $this->isOk = true;
                $date = strtotime($filter['min']) + 3600 * 2;
                $dateFin = strtotime($filter['max']);
                $this->majTable($date, $dateFin);
            }
        }

        return parent::getListCount($filters, $joins);
    }

//    public function displayData($field, $display_name = 'default', $display_input_value = true, $no_html = false) {
//        if($field == 'date'){
//            return $this->displayDate();
//        }
//        
//        return parent::displayData($field, $display_name, $display_input_value, $no_html);
//    }

    public function displayDate()
    {
        $date = new DateTime($this->getData('date'));
        if (static::$modeDateGraph == 'month')
            return $date->format('M Y');
        elseif (static::$modeDateGraph == 'year')
            return $date->format('Y');

        return parent::displayData('date');
    }

    public function getInfoGraph()
    {
        $data = parent::getInfoGraph();
        $data["data1"] = 'Facture HT';
        $data["data2"] = 'Commande HT';
        $data["data3"] = 'Devis HT';
        if (static::$modeDateGraph != 'year')
            $data["data11"] = 'Facture HT a 1an';
        $data["axeX"] = '';
        $data["axeY"] = 'K €';
        $data["title"] = 'Facture Commande et Devis par ';
        if (static::$modeDateGraph == 'day')
            $data["title"] .= 'Jour';
        elseif (static::$modeDateGraph == 'month')
            $data["title"] .= 'Mois';
        elseif (static::$modeDateGraph == 'year')
            $data["title"] .= 'Ans';

        return $data;
    }

    public function getGraphDataPoint($numero_data = 1)
    {
        $tabDate = explode("-", $this->getData('date'));
        if (static::$modeDateGraph == 'day')
            $tabDate[1]--;
        elseif (static::$modeDateGraph == 'month') {
            if ($tabDate[1] == 1) {
                $tabDate[1] = 12;
                $tabDate[0]--;
            } else
                $tabDate[1]--;
        }
        if (static::$modeDateGraph == 'year')
            $x = "new Date(" . $tabDate[0] . ", 0)";
        else
            $x = "new Date(" . implode(", ", $tabDate) . ")";
        if ($numero_data == 1)
            $y = $this->getData('facture_total');
        elseif ($numero_data == 2)
            $y = $this->getData('commande_total');
        elseif ($numero_data == 3)
            $y = $this->getData('devis_total');
        elseif ($numero_data == 11)
            $y = str_replace(",", ".", $this->displayOldValue('facture_total', 12));

        return '{ x: ' . $x . ', y: ' . $y . ' },';
    }

    public function traiteFilters(&$filters)
    {
        global $memoireFilter;
        if (!isset($memoireFilter)) {
            $memoireFilter = $filters;
            unset($memoireFilter['a.date']);
        }
//        print_r($filters);die('tttt');
        if (strtotime($filters["a.date"]["or_field"][0]['max']) > ( time() - 86400))
            $filters["a.date"]["or_field"][0]['max'] = date('Y-m-d', ( time() - 86400));
        if (isset($filters['a.fk_soc'])) {
            if (!is_array($filters['a.fk_soc']))
                $filters['a.fk_soc'] = array($filters['a.fk_soc']);
            $this->filterCusom['a.fk_soc'] = $filters['a.fk_soc'];
            unset($filters['a.fk_soc']);
        }
        if (isset($filters['a.mode'])) {
            static::$modeDateGraph = $filters['a.mode'];
            unset($filters['a.mode']);
        }


        $this->signatureFilter = json_encode($this->filterCusom);
        $this->signatureFilter .= json_encode($this->filterCusomExclud);
        $this->signatureFilter .= json_encode($memoireFilter);
        $this->signatureFilter .= json_encode(static::$modeDateGraph);
        $filters["a.filter"] = $this->signatureFilter;
//       print_r($filters);die;
    }

    public function getCustomFilterSqlFilters($field_name, $values, &$filters, &$joins, $main_alias = 'a', &$errors = array(), $excluded = false)
    {
        if ($excluded)
            $this->filterCusomExclud[$field_name] = $values;
        else
            $this->filterCusom[$field_name] = $values;

        parent::getCustomFilterSqlFilters($field_name, $values, $filters, $joins, $main_alias, $errors, $excluded);
    }

    public function majTable($date, $dateFin)
    {
        $i = 0;
        $this->cacheTables();
        while ($date < $dateFin) {
            if ($i > 10000)
                die('trop de boucles');
            $i++;
            $dateFinJ = $date + 3600 * 24;

            $dateStr = gmdate("Y-m-d", $date);
            if (!isset($this->datas[$dateStr . $this->signatureFilter]) && (static::$modeDateGraph != 'month' || (int) gmdate("d", $date) == 1) && (static::$modeDateGraph != 'year' || ((int) gmdate("d", $date) == 1 && (int) gmdate("m", $date) == 1))) {
                $nbProp = (isset($this->datasPropal[$dateStr . $this->signatureFilter])) ? $this->datasPropal[$dateStr . $this->signatureFilter]->nb : 0;
                $totProp = (isset($this->datasPropal[$dateStr . $this->signatureFilter])) ? $this->datasPropal[$dateStr . $this->signatureFilter]->tot : 0;

                $nbComm = (isset($this->datasCommande[$dateStr . $this->signatureFilter])) ? $this->datasCommande[$dateStr . $this->signatureFilter]->nb : 0;
                $totComm = (isset($this->datasCommande[$dateStr . $this->signatureFilter])) ? $this->datasCommande[$dateStr . $this->signatureFilter]->tot : 0;

                $nbFact = (isset($this->datasFacture[$dateStr . $this->signatureFilter])) ? $this->datasFacture[$dateStr . $this->signatureFilter]->nb : 0;
                $totFact = (isset($this->datasFacture[$dateStr . $this->signatureFilter])) ? $this->datasFacture[$dateStr . $this->signatureFilter]->tot : 0;

                $this->db->db->query("INSERT INTO `llx_bimp_stat_date`(`date`, `devis_qty`, `devis_total`, `commande_qty`, `commande_total`, `facture_qty`, `facture_total`,`filter`) "
                        . "VALUES ('" . $dateStr . "', " . $nbProp . ", " . $totProp . ", " . $nbComm . ", " . $totComm . ", " . $nbFact . ", " . $totFact . ", '" . $this->signatureFilter . "')");
            }


            $date = $dateFinJ;
        }
    }

    public function cacheTables()
    {
        $this->datas = array();

        if (static::$modeDateGraph == 'month') {
            $selectDate = 'CONCAT(DATE_FORMAT(date_valid, "%Y-%m"),"-01") ';
            $groupBy = 'DATE_FORMAT(date_valid, "%m%Y")';
        } elseif (static::$modeDateGraph == 'year') {
            $selectDate = 'CONCAT(DATE_FORMAT(date_valid, "%Y"),"-01-01")';
            $groupBy = 'DATE_FORMAT(date_valid, "%Y")';
        } else {
            $selectDate = 'DATE(`date_valid`)';
            $groupBy = 'DATE(`date_valid`)';
        }

        $and = $andFact = "";
        $extrafield = $contact = false;
        foreach (array("IN" => $this->filterCusom, "NOT IN" => $this->filterCusomExclud) as $typeF => $filters) {
            foreach ($filters as $filter => $values) {
                if (stripos($filter, "ef_") !== false) {
                    $filter = str_replace("ef_", "f.", $filter);
                    $extrafield = true;
                } elseif (stripos($filter, "ec_") !== false) {
                    $filter = str_replace("ec_", "ec.", $filter);
                    $contact = true;
                } elseif (stripos($filter, 'a.') === false) {
                    $filter = "a." . $filter;
                }
                if (stripos($filter, "facture_") !== false) {
                    $andFact .= " AND " . str_replace("facture_", "", $filter) . " " . $typeF . " ('" . implode("','", $values) . "')";
                } else {
//                    echo '<br/>'.$filter.' : '.print_r($values,1);
                    $and .= " AND " . $filter . " " . $typeF . " ('" . implode("','", $values) . "')";
                }
            }
        }
        $sql = $this->db->db->query("SELECT * FROM `llx_bimp_stat_date` WHERE filter = '" . $this->signatureFilter . "' GROUP BY date ASC");
        while ($ln = $this->db->db->fetch_object($sql)) {
            $this->datas[$ln->date . $this->signatureFilter] = $ln;
        }

        $req = "SELECT " . $selectDate . " as date, count(*) as nb, SUM(total_ht) as tot FROM `llx_propal` a";
        if ($extrafield)
            $req .= " LEFT JOIN llx_propal_extrafields f ON  a.rowid = f.fk_object ";
        if ($contact)
            $req .= " LEFT JOIN `llx_element_contact` ec ON ec.element_id = a.rowid LEFT JOIN `llx_c_type_contact` c ON `code` LIKE 'SALESREPFOLL' AND `fk_c_type_contact` = c.rowid AND c.element = 'propal' ";
        $req .= " WHERE 1 " . $and . " group by " . $groupBy;
        $sql = $this->db->db->query($req);
        while ($ln = $this->db->db->fetch_object($sql)) {
            $this->datasPropal[$ln->date . $this->signatureFilter] = $ln;
        }

        $req = "SELECT " . $selectDate . " as date, count(*) as nb, SUM(total_ht) as tot FROM `llx_commande` a";
        if ($extrafield)
            $req .= " LEFT JOIN llx_commande_extrafields f ON a.rowid = f.fk_object ";
        if ($contact)
            $req .= " LEFT JOIN `llx_element_contact` ec ON ec.element_id = a.rowid LEFT JOIN `llx_c_type_contact` c ON `code` LIKE 'SALESREPFOLL' AND `fk_c_type_contact` = c.rowid AND c.element = 'commande' ";
        $req .= " WHERE 1 " . $and . " group by " . $groupBy;
        $sql = $this->db->db->query($req);
        while ($ln = $this->db->db->fetch_object($sql)) {
            $this->datasCommande[$ln->date . $this->signatureFilter] = $ln;
        }

        $req = "SELECT " . $selectDate . " as date, count(*) as nb, SUM(total) as tot FROM `llx_facture` a";
        if ($extrafield)
            $req .= " LEFT JOIN llx_facture_extrafields f ON a.rowid = f.fk_object ";
        if ($contact)
            $req .= " LEFT JOIN `llx_element_contact` ec ON ec.element_id = a.rowid LEFT JOIN `llx_c_type_contact` c ON `code` LIKE 'SALESREPFOLL' AND `fk_c_type_contact` = c.rowid AND c.element = 'facture' ";
        $req .= " WHERE 1 " . $and . $andFact . " group by " . $groupBy;
        $sql = $this->db->db->query($req);
        while ($ln = $this->db->db->fetch_object($sql)) {
            $this->datasFacture[$ln->date . $this->signatureFilter] = $ln;
        }
    }

    public function getList($filters = array(), $n = null, $p = null, $order_by = 'id', $order_way = 'DESC', $return = 'array', $return_fields = null, $joins = array(), $extra_order_by = null, $extra_order_way = 'ASC')
    {
        $filters["a.filter"] = $this->signatureFilter;
        if ($this->isOk)
            return parent::getList($filters, $n, $p, $order_by, $order_way, $return, $return_fields, $joins, $extra_order_by, $extra_order_way);
        else
            return array();
    }

    public function getLabel($type = "")
    {
        $return = parent::getLabel($type);
        if ($type == 'name' && !$this->isOk)
            return $return . " (Choisir une plage de date)";
        return $return;
    }

    public function getListHeaderButtons()
    {
        $buttons = array();

        $buttons[] = array(
            'label'   => 'Rapports mensuels',
            'icon'    => 'fas_file-excel',
            'onclick' => $this->getJsActionOnclick('generateMonthlyReport', array(
                'month' => (int) date('m'),
                'year'  => (int) date('Y')
                    ), array(
                'form_name' => 'monthy_report'
            ))
        );

        return $buttons;
    }

    public function getReportYearsArray($max_past_years = 10)
    {
        $years = array();

        $y = (int) date('Y');

        $years[$y] = $y;

        for ($i = 1; $i <= $max_past_years; $i++) {
            $y--;
            $years[$y] = $y;
        }

        return $years;
    }

    public function actionGenerateMonthlyReport($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';
        $sc = '';

        $month = (int) BimpTools::getArrayValueFromPath($data, 'month', 0);
        if (!$month) {
            $errors[] = 'Mois absent';
        }

        $year = (int) BimpTools::getArrayValueFromPath($data, 'year', 0);
        if (!$year) {
            $errors[] = 'Année absente';
        }

        if (!count($errors)) {
            BimpCore::loadPhpExcel();
            $excel = new PHPExcel();

            $file_name = 'rapport_commercial_mensuel_' . $month . '_' . $year;
            $dir = DOL_DATA_ROOT . '/bimpcommercial/monthly_reports';
            $file_path = $dir . '/' . $file_name . '.xlsx';

            if (!is_dir($dir)) {
                $dir_error = BimpTools::makeDirectories($dir);

                if ($dir_error) {
                    $errors[] = 'Echec création du dossier "bimpcommercial/monthly_reports" - ' . $dir_error;
                }
            }

            if (!count($errors)) {
                $data_types = array(
                    'nb_new_clients'                   => 'Nb nouveaux clients',
                    'nb_new_propales'                  => 'Nb nouveaux devis',
                    'nb_new_commandes'                 => 'Nb nouvelles commandes',
                    'nb_new_commandes_for_new_clients' => 'Nb nouvelles commandes des nouveaux clients',
                    'ca_ht'                            => 'CA HT',
                    'marges'                           => 'Total Marges',
                    'tx_marque'                        => 'Taux de marque'
                );
                BimpObject::loadClass('bimpcommercial', 'BimpComm');

                $data = BimpComm::getMonthlyReportData($month, $year);

                // Données globales: 
                $sheet = $excel->getActiveSheet();
                $sheet->setTitle('Données globales');

                $sheet->setCellValueByColumnAndRow(0, 1, 'Donnée');
                $sheet->setCellValueByColumnAndRow(1, 1, 'Valeur');

                $row = 1;
                foreach ($data_types as $name => $label) {
                    $row++;
                    $sheet->setCellValueByColumnAndRow(0, $row, $label);
                    $sheet->setCellValueByColumnAndRow(1, $row, $data['total'][$name]);
                }


                // Données par utilisateurs: 
                $sheet = $excel->createSheet();
                $sheet->setTitle('Commerciaux');

                $col = 0;
                $row = 1;
                $sheet->setCellValueByColumnAndRow($col, $row, 'Commercial');

                foreach ($data_types as $name => $label) {
                    $col++;
                    $sheet->setCellValueByColumnAndRow($col, $row, $label);
                }


                foreach ($data['users'] as $id_user => $user_data) {
                    $row++;
                    $col = 0;

                    $user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', $id_user);
                    $user_name = '';
                    if (BimpObject::objectLoaded($user)) {
                        $user_name = $user->getName();
                    } else {
                        $user_name = 'Utilisateur #' . $id_user;
                    }
                    $sheet->setCellValueByColumnAndRow($col, $row, $user_name);

                    foreach ($data_types as $name => $label) {
                        $col++;

                        if (isset($user_data[$name])) {
                            $sheet->setCellValueByColumnAndRow($col, $row, $user_data[$name]);
                        }
                    }
                }

                $data_types = array(
                    'ca_ht'     => 'CA HT',
                    'marges'    => 'Total Marges',
                    'tx_marque' => 'Taux de marque'
                );

                // Données par métiers: 
                $sheet = $excel->createSheet();
                $sheet->setTitle('Métiers');

                $col = 0;
                $row = 1;
                $sheet->setCellValueByColumnAndRow($col, $row, 'Métier');

                foreach ($data_types as $name => $label) {
                    $col++;
                    $sheet->setCellValueByColumnAndRow($col, $row, $label);
                }

                $metiers = BimpComm::$expertise;
                foreach ($data['metiers'] as $metier => $metier_data) {
                    $row++;
                    $col = 0;

                    $metier_name = '';
                    if ($metier) {
                        if (isset($metiers[$metier])) {
                            $metier_name = $metiers[$metier];
                        } else {
                            $metier_name = 'Inconnu (' . $id_user . ')';
                        }
                    } else {
                        $metier_name = 'Non Spécifié';
                    }

                    $sheet->setCellValueByColumnAndRow($col, $row, $metier_name);

                    foreach ($data_types as $name => $label) {
                        $col++;

                        if (isset($metier_data[$name])) {
                            $sheet->setCellValueByColumnAndRow($col, $row, $metier_data[$name]);
                        }
                    }
                }

                // Données par région: 
                $sheet = $excel->createSheet();
                $sheet->setTitle('Régions');

                $col = 0;
                $row = 1;
                $sheet->setCellValueByColumnAndRow($col, $row, 'Région');

                foreach ($data_types as $name => $label) {
                    $col++;
                    $sheet->setCellValueByColumnAndRow($col, $row, $label);
                }

                foreach ($data['regions'] as $region => $region_data) {
                    $row++;
                    $col = 0;

                    $sheet->setCellValueByColumnAndRow($col, $row, $region);

                    foreach ($data_types as $name => $label) {
                        $col++;

                        if (isset($region_data[$name])) {
                            $sheet->setCellValueByColumnAndRow($col, $row, $region_data[$name]);
                        }
                    }
                }

                $writer = PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
                $writer->save($file_path);

                $url = DOL_URL_ROOT . '/document.php?modulepart=bimpcommercial&file=' . htmlentities('monthly_reports/' . $file_name . '.xlsx');
                $sc = 'window.open(\'' . $url . '\')';
            }
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $sc
        );
    }
}
