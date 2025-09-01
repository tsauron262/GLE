<?php

class Bimpcoopmvt extends BimpObject
{
    public function renderCoopUserView($idUser){

        global $user;

//        if ($user->admin || $user->id === $this->id) {

            $tabs[] = array(
                'id'            => 'lists_capital_tab',
                'title'         => 'Capital',
                'ajax'          => 1,
                'ajax_callback' => $this->getJsLoadCustomContent('renderListObjects', '$(\'#lists_capital_tab .nav_tab_ajax_result\')', array($idUser, 1), array('button' => ''))
            );

            $tabs[] = array(
                'id'            => 'lists_cca_tab',
                'title'         => 'CCA',
                'ajax'          => 1,
                'ajax_callback' => $this->getJsLoadCustomContent('renderListObjects', '$(\'#lists_cca_tab .nav_tab_ajax_result\')', array($idUser, 2), array('button' => ''))
            );

            return BimpRender::renderNavTabs($tabs, 'params_tabs');
//        }

        return BimpRender::renderAlerts('Vous n\'avez pas la permission de voir ce contenu');
    }

    public function renderCapitalCCA(){
        global $db;
        $panels = array();
        $rows = array(1=>array(), 2=>array());
        $totals = array(1=>0, 2=>0);
        $sql = $db->query('SELECT SUM(value) as value, fk_user, type FROM '.MAIN_DB_PREFIX.'bimp_coop_mvt GROUP BY fk_user, type;');
        while ($ln = $db->fetch_object($sql)){
            $userT = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', $ln->fk_user);
            if($ln->value != 0){
                $rows[$ln->type][] = array(
                    'name' => $userT->getFullName(),
                    'montant' => BimpTools::displayMoneyValue($ln->value),
                );
            }
        }


        $sql = $db->query('SELECT SUM(value) as value, fk_user, type FROM '.MAIN_DB_PREFIX.'bimp_coop_mvt GROUP BY type;');
        while ($ln = $db->fetch_object($sql)){
            $userT = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', $ln->fk_user);
            if($ln->value != 0){
                $totals[$ln->type] = BimpTools::displayMoneyValue($ln->value);
            }
        }
        $totUrg = 0;
        $sql = $db->query('SELECT SUM(value) as value, fk_user, type FROM '.MAIN_DB_PREFIX.'bimp_coop_mvt WHERE info LIKE "urgence" AND type = 2;');
        while ($ln = $db->fetch_object($sql)){
            $userT = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', $ln->fk_user);
            if($ln->value != 0){
                $totUrg = BimpTools::displayMoneyValue($ln->value);
            }
        }
//        echo '<pre>';
//        print_r($rows);die;


        $header = array(
            'name'     => 'Nom',
            'montant'   => 'Value'
        );

        $panels['Capital'] = array('content'=>BimpRender::renderBimpListTable($rows[1], $header, array(
                            'searchable'  => true,
                            'sortable'    => true,
                            'search_mode' => 'show'
                )).'TOTAL : '.$totals[1], 'xs'=>12,'sm'=>12,'md'=>6);


        $panels['CCA'] = array('content'=>BimpRender::renderBimpListTable($rows[2], $header, array(
                            'searchable'  => true,
                            'sortable'    => true,
                            'search_mode' => 'show'
            )).'TOTAL : '.$totals[2].'<br/>Dont '.$totUrg.' de compte d\'urgence', 'xs'=>12,'sm'=>12,'md'=>6);

        return $panels;
    }

    public function renderStat(){
        global $db;
        $panels = array();




        //bank
        $sql = $db->query("SELECT * FROM `".MAIN_DB_PREFIX."bank_account`;");
        $bank  = array();
        while($ln = $db->fetch_object($sql)){
            $bank[$ln->rowid] = $ln->label;
        }

//        $html .= BimpRender::renderPanel('Chiffres ', $content, '', array('open' => 1));
        //47000





        //categorie
        $sql = $db->query("SELECT * FROM `".MAIN_DB_PREFIX."bimp_c_values8sens` WHERE `type` = 'categorie';");
        $categ  = array();
        while($ln = $db->fetch_object($sql)){
            $categ[$ln->id] = $ln->label;
        }


		//Recette (stat vente)
		$tabInfoR = array();
		$sql = $db->query('SELECT a_product_ef.categorie AS categorie,  SUM( CASE WHEN f.fk_statut IN ("1","2") THEN a.total_ttc ELSE 0 END) AS tot
FROM '.MAIN_DB_PREFIX.'facturedet a
LEFT JOIN '.MAIN_DB_PREFIX.'facture f ON f.rowid = a.fk_facture
LEFT JOIN '.MAIN_DB_PREFIX.'product_extrafields a_product_ef ON a_product_ef.fk_object = a.fk_product
WHERE f.type IN ("0","1","2") AND f.remain_to_pay != f.total_ttc'.
			(BimpTools::getPostFieldValue('dateD', null)? ' AND f.datef >= "'.BimpTools::getPostFieldValue('dateD').'" ':'').
			(BimpTools::getPostFieldValue('dateF', null)? ' AND f.datef < "'.BimpTools::getPostFieldValue('dateF').'" ':'').
			(BimpTools::getPostFieldValue('notDev', 0)? ' AND categorie != 6' : '').
			' GROUP BY categorie
ORDER BY a.rowid DESC;');
		while($ln = $db->fetch_object($sql)){
			if($ln->tot != 0){
				$label = $categ[$ln->categorie];
				if($label == '')
					$label = 'Categ inconnue '.$ln->categorie;
				$tabInfoR[$label] = $ln->tot;
			}
		}


		//Impaye (stat vente)
		$sql = $db->query('SELECT SUM(`remain_to_pay`) as tot
FROM '.MAIN_DB_PREFIX.'facture f
WHERE f.type IN ("0","1","2") AND paye=0 AND remain_to_pay < total_ttc'.
			(BimpTools::getPostFieldValue('dateD', null)? ' AND f.datef >= "'.BimpTools::getPostFieldValue('dateD').'" ':'').
			(BimpTools::getPostFieldValue('dateF', null)? ' AND f.datef < "'.BimpTools::getPostFieldValue('dateF').'" ':'').
			';');
		while($ln = $db->fetch_object($sql)){
			if($ln->tot != 0){
				$label = 'Impaye ';
				$tabInfoR[$label] = -$ln->tot;
			}
		}

        $sql = $db->query('SELECT categorie AS categorie,  SUM(value) AS tot
FROM '.MAIN_DB_PREFIX.'bimp_coop_nonrep a
WHERE value > 0 AND date IS NOT NULL '.
                (BimpTools::getPostFieldValue('dateD', null)? ' AND date >= "'.BimpTools::getPostFieldValue('dateD').'" ':'').
			(BimpTools::getPostFieldValue('dateF', null)? ' AND date < "'.BimpTools::getPostFieldValue('dateF').'" ':'').
			(BimpTools::getPostFieldValue('notDev', 0)? ' AND categorie != 6' : '').
' GROUP BY categorie;');
        while($ln = $db->fetch_object($sql)){
            if($ln->tot != 0){
                $label = $categ[$ln->categorie];
                if($label == '')
                    $label = 'Categ inconnue '.$ln->categorie;
                $tabInfoR[$label] += $ln->tot;
            }
        }

        $sql = $db->query('SELECT SUM(amount_ttc) as tot FROM `'.MAIN_DB_PREFIX.'societe_remise_except` r LEFT JOIN '.MAIN_DB_PREFIX.'facture f ON r.fk_facture_source = f.rowid WHERE (fk_facture < 0 OR fk_facture IS NULL) AND f.paye = 1'.
                (BimpTools::getPostFieldValue('dateD', null)? ' AND r.datec >= "'.BimpTools::getPostFieldValue('dateD').'" ':'').
                (BimpTools::getPostFieldValue('dateF', null)? ' AND r.datec < "'.BimpTools::getPostFieldValue('dateF').'" ':'').
'');
        while($ln = $db->fetch_object($sql)){
            if($ln->tot != 0)
                $tabInfoR['Acompte'] += $ln->tot;
        }



        //depensse (stat achat)
        $tabInfoD = array();
        $tabInfoAPrevoir = array();
        $sql = $db->query('SELECT a_product_ef.categorie AS categorie, SUM( CASE WHEN f.fk_statut IN ("1","2") THEN a.total_ttc ELSE 0 END) AS tot
FROM '.MAIN_DB_PREFIX.'facture_fourn_det a
LEFT JOIN '.MAIN_DB_PREFIX.'facture_fourn f ON f.rowid = a.fk_facture_fourn
LEFT JOIN '.MAIN_DB_PREFIX.'product_extrafields a_product_ef ON a_product_ef.fk_object = a.fk_product
WHERE 1'.
                (BimpTools::getPostFieldValue('dateD', null)? ' AND f.datef >= "'.BimpTools::getPostFieldValue('dateD').'" ':'').
                (BimpTools::getPostFieldValue('dateF', null)? ' AND f.datef < "'.BimpTools::getPostFieldValue('dateF').'" ':'').
			(BimpTools::getPostFieldValue('notDev', 0)? ' AND categorie != 6' : '').
'
GROUP BY categorie;');
        while($ln = $db->fetch_object($sql)){
            if($ln->tot != 0){
                $label = $categ[$ln->categorie];
                if($label == '')
                    $label = 'Categ inconnue '.$ln->categorie;
                $tabInfoD[$label] = $ln->tot;
            }
        }

        $sql = $db->query('SELECT categorie AS categorie,  SUM(value) AS tot
FROM '.MAIN_DB_PREFIX.'bimp_coop_nonrep a
WHERE value < 0 AND date IS NOT NULL '.
                (BimpTools::getPostFieldValue('dateD', null)? ' AND date >= "'.BimpTools::getPostFieldValue('dateD').'" ':'').
                (BimpTools::getPostFieldValue('dateF', null)? ' AND date < "'.BimpTools::getPostFieldValue('dateF').'" ':'').
			(BimpTools::getPostFieldValue('notDev', 0)? ' AND categorie != 6' : '').
' GROUP BY categorie;');
        while($ln = $db->fetch_object($sql)){
            if($ln->tot != 0){
                $label = $categ[$ln->categorie];
                if($label == '')
                    $label = 'Categ inconnue '.$ln->categorie;
                $tabInfoD[$label] += -$ln->tot;
            }
        }



        $sql = $db->query('SELECT SUM(total_ttc) as tot, categorie
FROM '.MAIN_DB_PREFIX.'expensereport e LEFT JOIN '.MAIN_DB_PREFIX.'expensereport_extrafields le ON le.fk_object = e.rowid WHERE paid = 1 '.
                (BimpTools::getPostFieldValue('dateD', null)? ' AND date_approve >= "'.BimpTools::getPostFieldValue('dateD').'" ':'').
                (BimpTools::getPostFieldValue('dateF', null)? ' AND date_approve < "'.BimpTools::getPostFieldValue('dateF').'" ':'').
' GROUP BY categorie;');
        while($ln = $db->fetch_object($sql)){
            if($ln->tot != 0){
                $label = $categ[$ln->categorie];
                if($label == '')
                    $label = 'Categ inconnue '.$ln->categorie;
                $tabInfoD[$label] += $ln->tot;
            }
        }


        $sql = $db->query('SELECT SUM(total_ttc) as tot, categorie
FROM '.MAIN_DB_PREFIX.'expensereport e LEFT JOIN '.MAIN_DB_PREFIX.'expensereport_extrafields le ON le.fk_object = e.rowid WHERE paid = 0  GROUP BY categorie;');
        while($ln = $db->fetch_object($sql)){
            if($ln->tot != 0){
                $label = 'NDF '.$categ[$ln->categorie];
                if($label == '')
                    $label = 'Categ inconnue '.$ln->categorie;
                $tabInfoAPrevoir[$label] += -$ln->tot;
            }
        }
        $sql = $db->query('SELECT categorie AS categorie,  SUM(value) AS tot
FROM '.MAIN_DB_PREFIX.'bimp_coop_nonrep a
WHERE date IS NULL '.
' GROUP BY categorie;');
        while($ln = $db->fetch_object($sql)){
            if($ln->tot != 0){
                $label = $categ[$ln->categorie];
                if($label == '')
                    $label = 'Categ inconnue '.$ln->categorie;
                $tabInfoAPrevoir[$label] += $ln->tot;
            }
        }





        $sql = $db->query('SELECT SUM(amount)as solde, fk_account FROM '.MAIN_DB_PREFIX.'bank'
                . ' WHERE 1 '.
				 $this->getWhereCategPaiement() .
                (BimpTools::getPostFieldValue('dateF', null)? ' AND datev < "'.BimpTools::getPostFieldValue('dateF').'" ':'').
                 ' GROUP BY fk_account');
        $tot = 0;

        $tabInfoSolde = array();
        while($ln = $db->fetch_object($sql)){
            if($ln->solde != 0){
                $label = $bank[$ln->fk_account];
                if($label == '')
                    $label = 'Banque '.$ln->fk_account;
                $tabInfoSolde['Solde '.$label] = array('amount'=> $ln->solde, 'link'=> DOL_URL_ROOT.'/compta/bank/bankentries_list.php?id='.$ln->fk_account);
                $tot += $ln->solde;
            }
        }
        if(BimpTools::getPostFieldValue('ajPret', 0) == 1){
            $tabInfoSolde['Solde Banque POP']['amount'] -= 1446.38;
            $tot -= 1446.38;
            $tabInfoSolde['Solde NEF']['amount'] -= 1430.73;
            $tot -= 1430.73;
			$tabInfoD['Banque'] += (1446.38 + 1430.73);
        }


        $sql = $db->query('SELECT SUM(value) as solde FROM '.MAIN_DB_PREFIX.'bimp_coop_nonrep'
                . ' WHERE date IS NOT NULL '.
                (BimpTools::getPostFieldValue('dateF', null)? ' AND date < "'.BimpTools::getPostFieldValue('dateF').'" ':'').
                 '');
        while($ln = $db->fetch_object($sql)){
            $tabInfoSolde['Solde Bl'] = array('amount'=> $ln->solde, 'link'=> DOL_URL_ROOT.'/bimpcoop/index.php?fc=index&tab=non_rep');
            $tot += $ln->solde;
        }

        $tabInfoSolde[''] = '';
        $tabInfoSolde['TOTAL'] = $tot;
        $tabInfoSolde[' '] = '';
        $tabInfoSolde['DEPUIS DEBUT'] = $tot - 49344.26;

        if(!BimpTools::getPostFieldValue('dateF', null) && !BimpTools::getPostFieldValue('dateD', null)){
            $tabInfoSolde['  '] = '';
            $tabInfoSolde['Dif Prévi'] = $tot - 10000 + $tabInfoD['Travaux'] - 9000 - BimpCore::getConf('b_previ', 0, 'bimpcoop');
        }
		$tabInfoSolde['Dette'] = $this->getDetes(BimpTools::getPostFieldValue('dateF', ''));
		$tabInfoSolde['Dette - Treso'] = $tabInfoSolde['Dette'] - $tabInfoSolde['TOTAL'];




        $contentSolde = '<a class="btn btn-default" href="'.DOL_URL_ROOT.'/compta/bank/list.php">Comptes</a>';
        $contentSolde .= '<table class="bimp_list_table">';
        foreach($tabInfoSolde as $nom => $val){
			$link = '';
			if(is_array($val)){
				$link = $val['link'];
				$val = $val['amount'];
			}
            $contentSolde .= '<tr><th>'.$nom.'</th><td>';
			if($link != ''){
				$contentSolde .= '<a href="'.$link.'">';
			}
			$contentSolde .= BimpTools::displayMoneyValue($val);
			if($link != ''){
				$contentSolde .= '</a>';
			}
			$contentSolde .= '</td></tr>';
        }
        $contentSolde .= '</table>';





        $panels['Compta']['Paramétres'] = array('content'=>'<form method="POST">'.
			'Simuler un prelevement d\'emprunt dans les soldes : '.BimpInput::renderInput('toggle', 'ajPret', BimpTools::getPostFieldValue('ajPret')).
			'<br/>Ne pas tenir compte des frais de remboursmeent anticipée : '.BimpInput::renderInput('toggle', 'notRmbAnt', BimpTools::getPostFieldValue('notRmbAnt')).
			'<br/>Ne pas tenir compte des entrée de dév : '.BimpInput::renderInput('toggle', 'notDev', BimpTools::getPostFieldValue('notDev')).
			'<br/>Du'.BimpInput::renderInput('date', 'dateD', BimpTools::getPostFieldValue('dateD')).
			'<br/>Au'.BimpInput::renderInput('date', 'dateF', BimpTools::getPostFieldValue('dateF')).
			'<input type="submit" value="Valider" /></form><br/>'.
			BimpRender::renderAlerts(BimpCore::getConf('b_comment', '', 'bimpcoop'), 'warning'), 'xs'=>12,'sm'=>12,'md'=>12);


		$_POST['params_def'] = json_encode(array(
			'dateD' => BimpTools::getPostFieldValue('dateD', null),
			'dateF' => BimpTools::getPostFieldValue('dateD', null),
			'notRmbAnt' => BimpTools::getPostFieldValue('notRmbAnt', null),
			'ajPret' => BimpTools::getPostFieldValue('ajPret', null),
			'notDev' => BimpTools::getPostFieldValue('notDev', null),

		));


        $panels['Compta']['Soldes'] = $contentSolde;

        if(BimpTools::getPostFieldValue('dateD', null)){//mouvement sur comptes
            $tabInfoSolde = array();
             $sql = $db->query('SELECT SUM(amount)as solde, fk_account FROM '.MAIN_DB_PREFIX.'bank'
                    . ' WHERE 1 '.
				 	$this->getWhereCategPaiement() .
                    (BimpTools::getPostFieldValue('dateD', null)? ' AND datev >= "'.BimpTools::getPostFieldValue('dateD').'" ':'').
                    (BimpTools::getPostFieldValue('dateF', null)? ' AND datev < "'.BimpTools::getPostFieldValue('dateF').'" ':'').
                     ' GROUP BY fk_account');
            $tot = 0;
            while($ln = $db->fetch_object($sql)){
                if($ln->solde != 0){
                    $label = $bank[$ln->fk_account];
                    if($label == '')
                        $label = 'Banque '.$ln->fk_account;
                    $tabInfoSolde['Mouvement '.$label] = $ln->solde;
                    $tot += $ln->solde;
                }
            }
            if(BimpTools::getPostFieldValue('ajPret', 0) == 1){
                $tabInfoSolde['Mouvement Banque POP'] -= 1446.38;
                $tot -= 1446.38;
                $tabInfoSolde['Mouvement NEF'] -= 1430.73;
                $tot -= 1430.73;
            }

            $sql = $db->query('SELECT SUM(value) as solde FROM '.MAIN_DB_PREFIX.'bimp_coop_nonrep'
                    . ' WHERE  date IS NOT NULL '.
                    (BimpTools::getPostFieldValue('dateD', null)? ' AND date >= "'.BimpTools::getPostFieldValue('dateD').'" ':'').
                    (BimpTools::getPostFieldValue('dateF', null)? ' AND date < "'.BimpTools::getPostFieldValue('dateF').'" ':'').
                     '');
            while($ln = $db->fetch_object($sql)){
                $tabInfoSolde['Solde Bl'] =$ln->solde;
                $tot += $ln->solde;
            }

            $tabInfoSolde[''] = '';
            $tabInfoSolde['TOTAL'] = $tot;



            $contentSolde = '<table class="bimp_list_table">';
            foreach($tabInfoSolde as $nom => $val){
                $contentSolde .= '<tr><th>'.$nom.'</th><td>'.BimpTools::displayMoneyValue($val).'</td></tr>';
            }
            $contentSolde .= '</table>';
            $panels['Compta']['Mouvement compte'] = $contentSolde;
        }



        $panels['Compta']['Recette'] = '<a class="btn btn-default" href="'.DOL_URL_ROOT.'/bimpcommercial/index.php?fc=ventes&tab=stats">Stat Ventes</a>'.$this->traiteTab($tabInfoR);
        $panels['Compta']['Dépenses'] = '<a class="btn btn-default" href="'.DOL_URL_ROOT.'/bimpcommercial/index.php?fc=achats&tab=stats_achats">Stat Achats</a>'.$this->traiteTab($tabInfoD);
        $panels['Compta']['A prevoir'] = '<a class="btn btn-default" href="'.DOL_URL_ROOT.'/hrm/index.php">Note de frais</a>'.$this->traiteTab($tabInfoAPrevoir);



        $panels['Repartition Capital CCA'] = array('content'=>$this->renderCapitalCCA(), 'xs'=>12,'sm'=>8,'md'=>8);


        return '<div style="display: inline-block;">'.$this->renderPanels($panels).'</div>';
    }

	public function getTabSoldes($dateMini, $dateMax, $onlyTotal = false, $groupMonth = false){
		$dataByDate = array();
		$dataByDate[0][0] = 0;
		$memoire = array();
		$and = $this->getWhereCategPaiement();
		$and2 = '';
		if(isset($dateMini) && $dateMini != ''){
			$sql = $this->db->db->query("SELECT SUM(amount) as amount, fk_account FROM lou_bank WHERE datev < '".$dateMini."'" .$and." GROUP BY fk_account;");
			while($ln = $this->db->db->fetch_object($sql)){
				$dataByDate[0][$ln->fk_account] = $ln->amount;
				$memoire[$ln->fk_account] = $ln->amount;
			}
			$sql = $this->db->db->query("SELECT SUM(value) as amount FROM lou_bimp_coop_nonrep WHERE date > 0 AND date < '".$dateMini."'".$and2);
			while($ln = $this->db->db->fetch_object($sql)){
				$dataByDate[0][999] = $ln->amount;
				$memoire[999] = $ln->amount;
			}
			$and .= ' AND datev >= "'.$dateMini.'"';
			$and2 .= ' AND date >= "'.$dateMini.'"';
		}

		if(isset($dateMax) && $dateMax != '') {
			$and .= ' AND datev <= "' . $dateMax . '"';
			$and2 .= ' AND date <= "' . $dateMax . '"';
		}
		$filedDate = "datev";
		if($groupMonth)
			$filedDate = "DATE_FORMAT(".$filedDate.", '%Y-%m-01')";

		$sql = $this->db->db->query("SELECT SUM(amount) as amount, ".$filedDate." as datev, fk_account FROM lou_bank WHERE 1 ".$and."  GROUP BY ".$filedDate.", fk_account;");


		while($ln = $this->db->db->fetch_object($sql)){
			$memoire[$ln->fk_account] += $ln->amount;
			$dataByDate[strtotime($ln->datev)][$ln->fk_account] = $memoire[$ln->fk_account];
		}


		$filedDate = "date";
		if($groupMonth)
			$filedDate = "DATE_FORMAT(".$filedDate.", '%Y-%m-01')";

		$sql = $this->db->db->query("SELECT SUM(value) as amount, ".$filedDate." as datev FROM lou_bimp_coop_nonrep WHERE date > 0 ".$and2."  GROUP BY ".$filedDate." ORDER BY date;");
		while($ln = $this->db->db->fetch_object($sql)){
			$memoire[999] += $ln->amount;
			$dataByDate[strtotime($ln->datev)][999] = $memoire[999];
		}

		ksort($dataByDate);
		return $dataByDate;
	}

	public function getTabDettes($dateMini, $dateMax){
		if($dateMax == '' || $dateMax == '1'){
			$dateMax = date('Y-m-d');
		}

		$dataByDate= array(
			0=>array(
				1=>0, //bp
				2=>0, //nef
				101=>0,//capital
				102=>0,// cca
				11=>0, // frais de resiliation bp
				12=>0, //frais de resiltiation nef
			)
		);

		$memoire = array();
		if(isset($dateMini) && $dateMini != ''){
			$and = ' AND Date_ECHEANCE >= "'.$dateMini.'"';
			$and2 = ' AND date >= "'.$dateMini.'"';

			$sql = $this->db->db->query("SELECT SUM(value) as amount, type FROM lou_bimp_coop_mvt WHERE date > 0 AND date < '".$dateMini."' GROUP BY type");
			while($ln = $this->db->db->fetch_object($sql)){
				$memoire[100 + $ln->type] = $ln->amount;
				$dataByDate[0][100 + $ln->type] = $ln->amount;
			}
		}

		$sql = $this->db->db->query("SELECT CAPITAL_RESTANT_DU as amount, if(date_paiement_reel, date_paiement_reel, Date_ECHEANCE) as datev, ID_EMPRUNT FROM `lou_ammortissement` WHERE `Date_ECHEANCE` <= '".$dateMax."' ".$and."  ORDER BY datev;");
		while($ln = $this->db->db->fetch_object($sql)){
			$emprunt = BimpCache::getBimpObjectInstance('bimpcoop', 'emprunt', $ln->ID_EMPRUNT);
			if($dataByDate[0][$ln->ID_EMPRUNT] == 0)
				$dataByDate[0][$ln->ID_EMPRUNT] = $ln->amount;
			$dataByDate[strtotime($ln->datev)][$ln->ID_EMPRUNT] = $ln->amount;


			$paramsDefGraph = json_decode(BimpTools::getPostFieldValue('extra_data/param_params_def', null),1);
			if($emprunt->getData('pourcentage_frais') && !BimpTools::getPostFieldValue('notRmbAnt', 0) && (!isset($paramsDefGraph['notRmbAnt']) || $paramsDefGraph['notRmbAnt'] != 1)){
				if($dataByDate[0][$ln->ID_EMPRUNT+10] == 0)
					$dataByDate[0][$ln->ID_EMPRUNT+10] = $ln->amount * $emprunt->getData('pourcentage_frais') / 100; // frais de resiliation
				$dataByDate[strtotime($ln->datev)][$ln->ID_EMPRUNT+10] = $ln->amount * $emprunt->getData('pourcentage_frais') / 100; // frais de resiliation
			}
		}

		$sql = $this->db->db->query("SELECT SUM(value) as amount, date as datev, type FROM `lou_bimp_coop_mvt` WHERE `date` <= '".$dateMax."' ".$and2." GROUP BY type, date ORDER BY date;");
		while($ln = $this->db->db->fetch_object($sql)){
			$memoire[100 + $ln->type] += $ln->amount;
			$dataByDate[strtotime($ln->datev)][$ln->type + 100] = $memoire[100 + $ln->type];
		}

		ksort($dataByDate);
		return $dataByDate;
	}

	public function compileDataSerie($array1, $array2, $reverseValueArray2 = false){
		$newTab2 = array();
		foreach($array2 as $dateTime => $datas){
			foreach($datas as $key => $value) {
				$newTab2[$dateTime][$key+1000] = ($reverseValueArray2? -$value : $value);
			}
		}
		$result = array_replace_recursive($array1, $newTab2);
		ksort($result);
		return $result;
	}

	public function dataByDateToDataPoint($dataByDate, $onlyTotal = false){
		$dataByAccount = array();
//		echo '<pre>';print_r($dataByDate);echo '</pre>';die;
		foreach($dataByDate as $timestamp => $data){
			foreach($data as $fk_account => $inut) {
				if(!isset($dataByAccount[$fk_account])){
					$dataByAccount[$fk_account] = array(0=>$dataByDate[0][$fk_account]);
				}
			}


			$dataByAccount[0][$timestamp] = 0;
			foreach ($dataByAccount as $fk_account => $inut) {
				if($fk_account > 0 && $timestamp > 0) {
					if(isset($data[$fk_account])){
						$dataByAccount[$fk_account][0] = $data[$fk_account];
					}
					if(!$onlyTotal)
						$dataByAccount[$fk_account][$timestamp] = $dataByAccount[$fk_account][0];
					$dataByAccount[0][$timestamp] += $dataByAccount[$fk_account][0];
				}
			}
		}
		return $dataByAccount;
	}

	public function getDetes($dateMax){
		$dette = 0;
		if($dateMax == '' || $dateMax == '1'){
			$dateMax = date('Y-m-d');
		}
		$emprunts = BimpCache::getBimpObjectObjects('bimpcoop', 'emprunt');
		foreach($emprunts as $emprunt){
			$sql = $this->db->db->query("SELECT CAPITAL_RESTANT_DU as mt FROM `lou_ammortissement` WHERE `Date_ECHEANCE` <= '".$dateMax."' AND ID_EMPRUNT = ".$emprunt->id." ORDER BY Date_ECHEANCE DESC LIMIT 0,1;");
			$ln = $this->db->db->fetch_object($sql);
			$dette += $ln->mt;
			$paramsDefGraph = json_decode(BimpTools::getPostFieldValue('extra_data/param_params_def', null),1);
			if($emprunt->getData('pourcentage_frais') && !BimpTools::getPostFieldValue('notRmbAnt', 0) && (!isset($paramsDefGraph['notRmbAnt']) || $paramsDefGraph['notRmbAnt'] != 1)){
				$dette += $ln->mt *$emprunt->getData('pourcentage_frais')  / 100;
			}
		}

		$sql = $this->db->db->query("SELECT SUM(value) as mt FROM `lou_bimp_coop_mvt` WHERE date <= '".$dateMax."';");
		$ln = $this->db->db->fetch_object($sql);
		$dette += $ln->mt;

		return $dette;
	}

	public function getDataGraphTreso($userOption){
		$datas = array();
		$tabBank = array(
			'0' => 'Solde global',
			'999' => 'Bl',
		);
		$sql = $this->db->db->query("SELECT * FROM lou_bank_account;");
		while($ln = $this->db->db->fetch_object($sql)){
			$tabBank[$ln->rowid] = $ln->label;
		}

		$dataByDate = $this->getTabSoldes($userOption['date1'], $userOption['date2'], false, $userOption['xDateConfig'] == 'month');
		$dataByAccount = $this->dataByDateToDataPoint($dataByDate);
		foreach($dataByAccount as $idBank => $datasT){
			$dataPoint = array();
			foreach($datasT as $timestamp => $amount){
				if($timestamp > 0) {
					if($amount != 0){
						$dataPoint[] = array(
							'x' => "new Date('" . date('Y-m-d', $timestamp) . "')", // JavaScript date expects milliseconds
							'y' => $amount,
//					'name' => $idOrName,
						);
					}
				}
			}
			$data = array(
				'name'      => $tabBank[$idBank],
				'type' => ($idBank == 0)? 'line' : 'line',//'stackedArea',
				'visible'   => 1,
				'dataPoints'=> $dataPoint
			);
			$datas[] = $data;
		}
//		echo '<pre>';print_r($datas);
		return $datas;
	}

	public function getDataGraphDette($userOption){
		$datas = array();
		$tabBank = array(
			'0' => 'Solde global',
			'101' => 'Capital',
			'102' => 'CCA',
		);
		$sql = $this->db->db->query("SELECT * FROM lou_bank_account;");
		while($ln = $this->db->db->fetch_object($sql)){
			$tabBank[$ln->rowid] = $ln->label;
			$tabBank[$ln->rowid+10] = 'Resil '.$ln->label;
		}
		$dataByDate = $this->getTabDettes($userOption['date1'], $userOption['date2']);
		$dataByAccount = $this->dataByDateToDataPoint($dataByDate);
		foreach($dataByAccount as $idBank => $datasT){
			$dataPoint = array();
			foreach($datasT as $timestamp => $amount){
				if($timestamp > 0) {
					if($amount != 0){
						$dataPoint[] = array(
							'x' => "new Date('" . date('Y-m-d', $timestamp) . "')", // JavaScript date expects milliseconds
							'y' => $amount,
						);
					}
				}
			}
			$data = array(
				'name'      => $tabBank[$idBank],
				'type' => ($idBank == 0)? 'line' : 'line',//'stackedArea',
				'visible'   => 1,
				'round' => 2,
				'dataPoints'=> $dataPoint
			);
			$datas[] = $data;
		}
//		echo '<pre>';print_r($datas);
		return $datas;
	}

	public function getDataGraphDetteTreso($userOption){
		$datas = array();
		$tabBank = array(
			'0' => 'Doit - Solde global',
			'101' => 'Capital',
			'102' => 'CCA',
			'11' => 'Resil BP',
			'12' => 'Resil NEF',
			'13' => 'CCA Nef',
			'999' => 'Bl',
		);
		$sql = $this->db->db->query("SELECT * FROM lou_bank_account;");
		while($ln = $this->db->db->fetch_object($sql)){
			$tabBank[$ln->rowid] = 'Reste '.$ln->label;
			$tabBank[$ln->rowid+1000] = $ln->label;
		}

		$dataByDate = $this->getTabSoldes($userOption['date1'], $userOption['date2'], false, $userOption['xDateConfig'] == 'month');
//		$dataByAccount1 = $this->dataByDateToDataPoint($dataByDate);
		$dataByDate2 = $this->getTabDettes($userOption['date1'], $userOption['date2'], false, $userOption['xDateConfig'] == 'month');
		$dataByAccount = $this->dataByDateToDataPoint($this->compileDataSerie($dataByDate2, $dataByDate, true), true);
//		echo '<pre>';print_r($dataByDate);echo '</pre>';
//		echo '<pre>';print_r($dataByDate2);echo '</pre>';
//		echo '<pre>';print_r(array_replace_recursive($dataByDate, $dataByDate2));echo '</pre>';
//		$dataByAccount = array(
//			1 => $dataByAccount1[0],
//			2 => $dataByAccount2[0],
//		);
		foreach($dataByAccount as $idBank => $datasT){
			$dataPoint = array();
			foreach($datasT as $timestamp => $amount){
				if($timestamp > 0) {
					if($amount != 0){
						$dataPoint[] = array(
							'x' => "new Date('" . date('Y-m-d', $timestamp) . "')", // JavaScript date expects milliseconds
							'y' => $amount,
						);
					}
				}
			}
			$data = array(
				'name'      => $tabBank[$idBank],
				'type' => ($idBank == 0)? 'line' : 'line',//'stackedArea',
				'visible'   => 1,
				'round' => 2,
				'dataPoints'=> $dataPoint
			);
			$datas[] = $data;
		}
//		echo '<pre>';print_r($datas);
		return $datas;
	}

    public function renderPanels($panels, $xsD=6, $smD=4, $mdD=4, $open = 1){
        $html = '';
        foreach($panels as $name => $content){
            $xs = $xsD;
            $sm = $smD;
            $md = $mdD;
            if(is_array($content)){
                if(isset($content['xs']))
                    $xs = $content['xs'];
                if(isset($content['sm']))
                    $sm = $content['sm'];
                if(isset($content['md']))
                    $md = $content['md'];
                if(isset($content['content'])){
                    if(is_array($content['content']))
                        $content = $this->renderPanels($content['content']);
                    else
                        $content = $content['content'];
                }
                else{
                    $content = $this->renderPanels($content);
                    $xs = $sm = $md = 12;
                }
            }
//            else
                $content = BimpRender::renderPanel($name, $content, '', array('open' => $open));

            $html .= '<div class="col_xs-'.$xs.' col-sm-'.$sm.' col-md-'.$md.'">'.$content.'</div>';
        }
        return $html;
    }

    public function traiteTab($tab){
        $tot = 0;
		arsort($tab);
        foreach($tab as $nom => $val){
            $tot += $val;
        }
        $tab[''] = '';
        $tab['TOTAL'] = $tot;
        $content = '<table class="bimp_list_table">';
        foreach($tab as $nom => $val){
            $content .= '<tr><th>'.$nom.'</th><td>'.BimpTools::displayMoneyValue($val).'</td></tr>';
        }
        $content .= '</table>';
        return $content;
    }

    public function renderListObjects($userId, $type){
        $list = new BC_ListTable($this, 'default', 1, null, 'Mouvements', 'fas_users');
        $list->addFieldFilterValue('type', $type);
        $list->addFieldFilterValue('fk_user', $userId);
        $list->addIdentifierSuffix('type_'.$type);
        return $list->renderHtml();
    }

    public function displayPaiement(){
        if($this->getData('id_paiement')){
            $paiement = $this->getChildObject('paiementDiv');
            return $paiement->getNomUrl(1);
        }
        else{
            return BimpRender::renderButton(array(
                'label'   => 'Créer paiement',
                'icon'    => 'far_paper-plane',
                'onclick' => $this->getJsActionOnclick('create_paiement', array(), array('form_name' => 'create_paiement'))
            ));
        }
        return 'bouton';
    }

    public function getListsExtraBulkActions(){
        $buttons = array();
        $buttons[] = array(
                'label'   => 'Crées paiements',
                'icon'    => 'fas_check',
                'onclick' => $this->getJsBulkActionOnclick('create_paiement', array(), array('form_name' => 'create_paiement'))
            );
        return $buttons;
    }

    public function actionCreate_paiement($data, &$success = ''){
        global $user;
        $success = 'Paiement créer avec succés';
        $errors = array();


        if($this->isLoaded()){
            $objs = array($this);
        }
        else{
            $objs = array();
            foreach (BimpTools::getArrayValueFromPath($data, 'id_objects', array()) as $id){
                $objs[] = BimpCache::getBimpObjectInstance($this->module, $this->object_name, $id);
            }
        }

        $amount = 0;
        $userM = null;
        $paiement = $this->getChildObject('paiementDiv');
        $type = null;
        $notes = array();
        foreach($objs as $obj){
            if(is_null($userM))
                $userM = $obj->getChildObject('userM');
//            elseif($userM != $obj->getChildObject('userM'))
//                $errors[] = 'PLusieurs utilisateurs diférent';
            $amount += $obj->getData('value');
            if(is_null($paiement->datep))
                $paiement->datep = $obj->getData('date');
            elseif($paiement->datep != $obj->getData('date'))
                $errors[] = 'PLusieurs date diférente';
            if(is_null($type))
                $type = $obj->displayData('type', null, null, true);
            elseif($type != $obj->displayData('type', null, null, true))
                $errors[] = 'PLusieurs type diférent';
            $notes[] = $obj->getData('note');
            $paiementTemp = $obj->getChildObject('paiementDiv');
            if($paiementTemp->id > 0)
                $errors[] = 'La ligne comporte deja un paiement';
        }
        $paiement->amount = abs($amount);
        $paiement->sens = ($amount > 0)? 1 : 0;
        $paiement->label = $type.' '.$userM->getFullName();
        $paiement->fk_account = $data['id_account'];
        $paiement->type_payment = $data['id_mode_paiement'];
        $paiement->accountancy_code = '422';
        $paiement->subledger_account = $userM->getData('code_compta');
        $paiement->note = implode('\n', $notes);

        if($paiement->create($user) < 1)
            $errors[] = 'erreur '.$paiement->error;
        else{
            foreach ($objs as $obj){
                $obj->updateField('id_paiement', $paiement->id);
            }
        }

        return array('errors'=> $errors, 'warnings'=>array());
    }

    public function isEditable($force_edit = false, &$errors = []): int {
        $paiementTemp = $this->getChildObject('paiementDiv');
        if($paiementTemp->id > 0)
            return 0;


        return parent::isEditable($force_edit, $errors);
    }

    public function isDeletable($force_delete = false, &$errors = []) {
        return $this->isEditable($force_delete, $errors);
    }


    //graph
	public function getFieldsGraphRep($type = 1, $label = ''){
		$fields = array();
		$filter = array(
			'type'      => $type
		);
		if($label != '')
			$filter['info'] = 'URGENCE';
		$cmds = BimpCache::getBimpObjectObjects($this->module, $this->object_name, $filter);
		foreach($cmds as $cmdData){
			$userM = $cmdData->getChildObject('userM');
			if($userM->isLoaded())
				$title = $userM->getFullName();
			else
				$title = 'n/c';

			$filter2 = array_merge($filter, array('fk_user' => $userM->id));
			$fields[$userM->id] = array(
				"title"      => $title,
				'field'     => 'value',
				'calc'      => 'SUM',
				'filters'    => $filter2
			);
		}
		return $fields;
	}
	public function getFieldsGraphEvol($type = 1, $label = ''){
		$fields = array();
		$filter = array(
			'type'      => $type
		);
		if($label != '')
			$filter['info'] = 'URGENCE';
		$fields[0] = array(
			"title"      => 'Total',
			'field'     => 'value',
			'type'       => 'line',
			'filters'    => $filter
		);
		$cmds = BimpCache::getBimpObjectObjects($this->module, $this->object_name, $filter);
		foreach($cmds as $cmdData){
			$userM = $cmdData->getChildObject('userM');
			if($userM->isLoaded())
				$title = $userM->getFullName();
			else
				$title = 'n/c';

			$filter2 = array_merge($filter, array('fk_user' => $userM->id));
			$fields[$userM->id] = array(
				"title"      => $title,
				'field'     => 'value',
				'type'       => 'line',
				'filters'    => $filter2
			);
		}
		return $fields;
	}

	public function getWhereCategPaiement(){
		$whereCateg = '';
		$paramsDefGraph = json_decode(BimpTools::getPostFieldValue('extra_data/param_params_def', null),1);
		if(BimpTools::getPostFieldValue('notDev', 0) || (isset($paramsDefGraph['notDev']) && $paramsDefGraph['notDev'])) {
			$whereCateg .= ' AND rowid NOT IN (SELECT lineid FROM ' . MAIN_DB_PREFIX . 'bank_class WHERE fk_categ = 1) ';
		}
		return $whereCateg;
	}
}




