<?php

require_once DOL_DOCUMENT_ROOT . '/bimpfinancement/pdf/DocFinancementPDF.php';

class ContratFinancementPDF extends DocFinancementPDF
{


	public static $doc_type = 'contrat';
	public $type_pdf = '';
	public $signature_bloc = true;
	public $use_docsign = true;
	public $signature_bloc_label = '';
	public $object_signature_params_field_name = 'signature_contrat_params';
	public $signature_title = 'Signature';
	public $signature_pro_title = 'Signature + Cachet avec SIRET';
	public $client_data;
	public $loueur_data;
	public $cessionnaire_data;
	public $pvr_file = '';
	public $pvr_page_start = 0;
	public $pvr_pages_number = 0;
	public $cg_file = DOL_DOCUMENT_ROOT . '/bimpfinancement/pdf/cg_contrat.pdf';
	public $cg_page_start = 0;
	public $cg_pages_number = 6;
	public static $nb_cgv_pages = 'six';
	public $display_line_amounts = false;


	# Params:
	public static $full_blocs = array(
		'renderAfterLines' => 0
	);

	public function __construct($db, $demande, $client_data = array(), $loueur_data = array(), $cessionnaire_data = array(), $type = 'papier', $pvr_file = '')
	{
		$this->type_pdf = $type;
		$this->client_data = $client_data;
		$this->loueur_data = $loueur_data;
		$this->cessionnaire_data = $cessionnaire_data;
		$this->pvr_file = $pvr_file;

		parent::__construct($db, $demande);

		$this->doc_name = 'Contrat de location';
	}

	public function initData() {}

	public function initHeader()
	{
		parent::initHeader();
		$this->header_vars['doc_name'] = 'CONTRAT DE LOCATION';
		$this->header_vars['doc_ref'] = 'N° ' . str_replace('DF', '', $this->demande->getRef());
		$this->pdf->topMargin = 25;
	}

//	public function renderHeader()
//	{
//		$html = '<table>';
//		$html .= '<tr>';
//		$html .= '<td style="width: 20%">';
//		if (isset($this->header_vars['logo_img']) && $this->header_vars['logo_img']) {
//			$html .= '<img src="' . $this->header_vars['logo_img'] . '" style="width: auto; height: 45px;"/>';
//		}
//		$html .= '</td>';
//		$html .= '<td style="width: 60%; text-align: center;">';
//
//		if (isset($this->header_vars['doc_name'])) {
//			$html .= '<span style="font-size: 11px; color: #' . $this->primary . '">' . $this->header_vars['doc_name'] . '</span>';
//		}
//
//		if (isset($this->header_vars['doc_ref'])) {
//			$html .= '<br/><span style="font-size: 9px;">' . $this->header_vars['doc_ref'] . '</span>';
//		}
//		$html .= '</td>';
//
//		$html .= '<td style="20%;">';
//		if (isset($this->header_vars['header_infos'])) {
//			$html .= $this->header_vars['header_infos'];
//		}
//		$html .= '</td>';
//		$html .= '</tr>';
//		$html .= '</table>';
//
//		return $html;
//	}

	public function isTargetCompany()
	{
		if (isset($this->client_data['is_company'])) {
			return (int) $this->client_data['is_company'];
		}

		return 0;
	}

	public function renderTop()
	{
		$errors = array();
		$is_company = (int) BimpTools::getArrayValueFromPath($this->client_data, 'is_company', 0);

		$html = '<span style="font-size: 9px; font-weight: bold; color: #' . $this->primary . ';border-top: solid 1px #000000;">Le locataire</span><br/>';
		$html .= '<span style="font-size: 8px; text-align: justify;">';
		if ($is_company) {
			$nom = BimpTools::getArrayValueFromPath($this->client_data, 'nom', '', $errors, true, 'Nom du client absent');
			$address = BimpTools::getArrayValueFromPath($this->client_data, 'address', '', $errors, true, 'Adresse du siège du client absente');
			$forme_jur = BimpTools::getArrayValueFromPath($this->client_data, 'forme_juridique', '', $errors, true, 'Forme juridique du client absente');
			$capital = BimpTools::getArrayValueFromPath($this->client_data, 'capital', '', $errors, true, 'Capital social du client absent');
			$siren = BimpTools::getArrayValueFromPath($this->client_data, 'siren', '', $errors, true, 'N° SIREN du client absent');
			$rcs = BimpTools::getArrayValueFromPath($this->client_data, 'rcs', '');
			$representant = BimpTools::getArrayValueFromPath($this->client_data, 'representant', '', $errors, true, 'Représentant du client absent');
			$repr_qualite = BimpTools::getArrayValueFromPath($this->client_data, 'repr_qualite', '', $errors, true, 'Qualité du représentant du client absent');

			if (!count($errors)) {
				if (is_string($capital)) {
					$capital = str_replace(',', '.', $capital);
					$capital = preg_replace('/[^0-9\.]]/', '', $capital);
				}

				if (!(float) $capital) {
					$errors[] = 'Capital de l\'entreprise du client invalide';
				} else {
					$capital = BimpTools::displayMoneyValue((float) $capital, 'EUR', 0, 0, 1, 2, 0, ',', 1, 2);
				}

				$siren = preg_replace('/[^0-9]/', '', $siren);
				if (preg_match('/^([0-9]{3})([0-9]{3})([0-9]{3})$/', $siren, $matches)) {
					$siren = $matches[1] . ' ' . $matches[2] . ' ' . $matches[3];
				} else {
					$errors[] = 'N° SIREN du client invalide';
				}

				if (!count($errors)) {
					$html .= '"' . $nom . '", ' . $forme_jur . ' au capital de ' . $capital . '.<br/>';
					$html .= 'Entreprise immatriculée sous le numéro ' . $siren;
					if ((int) BimpTools::getArrayValueFromPath($this->client_data, 'insee', 0)) {
						$html .= ' à l\'INSEE ';
					} elseif ($rcs) {
						$html .= ' au RCS de ' . $rcs . ' ';
					}

					$html .= 'dont le siège social est situé : ' . $address . ' - ';
					$html .= 'Représentée par ' . $representant . ' en qualité de ' . $repr_qualite . '.';
				}
			}
		} else {
			$nom = BimpTools::getArrayValueFromPath($this->client_data, 'nom', '', $errors, true, 'Nom du client absent');
			$address = BimpTools::getArrayValueFromPath($this->client_data, 'address', '', $errors, true, 'Adresse du client absente');

			$html .= '"' . $nom . '", particulier.<br/>';
			if ($address) {
				$html .= 'Domicilié à l\'adresse : ' . $address . '.';
			}
		}
		$html .= '</span>';

		if (!count($errors)) {
			$this->pdf->writeHTML($html, false);
			$this->pdf->Ln(5);

			$html = '<span style="font-size: 9px; font-weight: bold; color: #' . $this->primary . '">Le loueur</span><br/>';
			$html .= '<span style="font-size: 8px; text-align: justify;">';
			$html .= 'La société LDLC PRO LEASE, SAS au capital de 100 000,00 € dont le siège social est situé à LIMONEST (69760), 2 rue des érables, ';
			$html .= 'enregistrée sous le numéro siren 838 651 594 auprès du RCS de Lyon, représentée par M. Olivier VILLEMONTE de la CLERGERIE, ';
			$html .= 'intervenant en qualité de Président.<br/>';

			$html .= 'Le loueur donne en location, l’équipement désigné ci-dessous (ci-après « équipement »), au locataire qui l’accepte, ';
			$html .= 'aux Conditions Particulières du contrat de location n°' . str_replace('DF', '', $this->demande->getRef()) . ' et aux Conditions Générales composées de ' . self::$nb_cgv_pages . ' pages recto.';

			$html .= '</span>';

			$this->pdf->writeHTML($html, false);
			$this->pdf->Ln(5);
		}

		if (count($errors)) {
			$this->errors = BimpTools::merge_array($this->errors, $errors);
		}
	}

	public function renderLines()
	{
		$table = new BimpPDF_Table($this->pdf, true, $this->primary);
		$table->addCol('desc', 'Désignation', 0, 'vertical-align: middle', '', '');
		$table->addCol('qte', 'Quantité', 20, 'text-align: center; vertical-align: middle', '', 'text-align: center');

		$lines = $this->demande->getLines();
		$n = 0;
		$n_suppl = 0;

		foreach ($lines as $line) {
			if ((int) $line->getData('type') === BF_Line::TYPE_TEXT) {
				continue;
			}

			$n++;
			if ($n >= 12) {
				$n_suppl++;
				continue;
			}

			$table->rows[] = array(
				'desc' => $line->displayDesc(false, true, false, true),
				'qte'  => $line->getData('qty')
			);
		}

		if ($n_suppl) {
			$s = ($n_suppl > 1 ? 's' : '');
			$table->rows[] = array(
				'desc' => array(
					'colspan' => 99,
					'style'   => 'background-color: #F5F5F5;',
					'content' => '... ' . $n_suppl . ' référence' . $s . ' supplémentaire' . $s . '... - se référer au devis',
				)
			);
		}

		if (count($table->rows)) {
			$this->pdf->Ln(2);
			$this->pdf->writeHTML('<span style="font-size: 9px; font-weight: bold; color: #' . $this->primary . '">Description des équipements et quantités : </span><br/>', false);
			$table->write();
		}

		unset($table);
	}

	public function renderDocInfos() {}

	public function renderAfterLines()
	{
		$this->pdf->Ln(2);
		$html = '<span style="font-size: 9px; font-weight: bold; color: #' . $this->primary . '">Durées et loyers</span><br/>';
		$html .= '<span style="font-size: 8px; text-align: justify;">';
		$html .= 'Le loyer est ferme et non révisable en cours de contrat, payable ';
		if ((int) $this->demande->getData('mode_calcul') > 0) {
			$html .= 'par terme à échoir';
		} else {
			$html .= 'à terme échu';
		}

		$html .= ', par ' . lcfirst($this->demande->displayData('mode_paiement', 'default', false, true)) . '.';
		$html .= '</span><br/>';
		$this->pdf->writeHTML($html, false);
		$this->pdf->Ln(1);

		switch ($this->demande->getData('formule')) {
			case 'evo':
			case 'evo_afs':
				$nb_loyers = $this->demande->getNbLoyers();
				$periodicity = (int) $this->demande->getData('periodicity');
				$periodicity_label = $this->demande->displayData('periodicity', 'default', 0, 1);
				$loyer_ht = (float) $this->demande->getData('loyer_mensuel_evo_ht') * $periodicity;
				$loyer_ttc = $loyer_ht * 1.2;

				$html .= '<table cellpadding="3px" style="text-align: center;font-size: 8px">';
				$html .= '<tr>';
				$html .= '<th style="width: 134px; background-color: #' . $this->primary . '; color: #fff;font-size: 8px">Nombre de loyers</th>';
				$html .= '<th style="width: 134px; background-color: #' . $this->primary . '; color: #fff;font-size: 8px">Montant HT</th>';
				$html .= '<th style="width: 134px; background-color: #' . $this->primary . '; color: #fff;font-size: 8px">Périodicité</th>';
				$html .= '<th style="width: 134px; background-color: #' . $this->primary . '; color: #fff;font-size: 8px">Montant TTC</th>';
				$html .= '</tr>';
				$html .= '<tr>';
				$html .= '<td style="background-color: #F2F2F2;font-size: 8px"><b>' . $nb_loyers . '</b></td>';
				$html .= '<td style="background-color: #F2F2F2;font-size: 8px"><b>' . BimpTools::displayMoneyValue($loyer_ht) . '</b></td>';
				$html .= '<td style="background-color: #F2F2F2;font-size: 8px"><b>' . $periodicity_label . '</b></td>';
				$html .= '<td style="background-color: #F2F2F2;font-size: 8px"><b>' . BimpTools::displayMoneyValue($loyer_ttc) . '</b></td>';
				$html .= '</tr>';
				$html .= '</table>';
				$this->pdf->writeHTML($html, false);
				break;

			case 'dyn':
				$nb_loyers = $this->demande->getNbLoyers();
				$periodicity = (int) $this->demande->getData('periodicity');
				$periodicity_label = $this->demande->displayData('periodicity', 'default', 0, 1);
				$loyer_ht = (float) $this->demande->getData('loyer_mensuel_dyn_ht') * $periodicity;
				$loyer_ttc = $loyer_ht * 1.2;
				$loyer_suppl_ht = (float) $this->demande->getData('loyer_mensuel_suppl_ht') * $periodicity;
				$loyer_suppl_ttc = $loyer_suppl_ht * 1.2;

				$html = '<table cellpadding="3px" style="text-align: center; width: 100%">';
				$html .= '<tr>';
				$html .= '<th style="width: 134px; background-color: #' . $this->primary . '; color: #fff;font-size: 8px">Nombre de loyers</th>';
				$html .= '<th style="width: 134px; background-color: #' . $this->primary . '; color: #fff;font-size: 8px">Montant HT</th>';
				$html .= '<th style="width: 134px; background-color: #' . $this->primary . '; color: #fff;font-size: 8px">Périodicité</th>';
				$html .= '<th style="width: 134px; background-color: #' . $this->primary . '; color: #fff;font-size: 8px">Montant TTC</th>';
				$html .= '</tr>';
				$html .= '<tr>';
				$html .= '<td style="background-color: #F2F2F2;font-size: 8px"><b>' . $nb_loyers . '</b></td>';
				$html .= '<td style="background-color: #F2F2F2;font-size: 8px"><b>' . BimpTools::displayMoneyValue($loyer_ht) . '</b></td>';
				$html .= '<td style="background-color: #F2F2F2;font-size: 8px"><b>' . $periodicity_label . '</b></td>';
				$html .= '<td style="background-color: #F2F2F2;font-size: 8px"><b>' . BimpTools::displayMoneyValue($loyer_ttc) . '</b></td>';
				$html .= '</tr>';
				$html .= '</table>';
				$this->pdf->writeHTML($html, false);
				$this->pdf->Ln(1);

				$this->pdf->writeHTML('<span style="font-size: 8px">Suivi de : </span><br/>', false);
				$this->pdf->Ln(1);

				$html = '<table cellpadding="3px" style="text-align: center; width: 100%">';
				$html .= '<tr>';
				$html .= '<th style="width: 134px; background-color: #' . $this->primary . '; color: #fff;font-size: 8px">Nombre de loyers</th>';
				$html .= '<th style="width: 134px; background-color: #' . $this->primary . '; color: #fff;font-size: 8px">Montant HT</th>';
				$html .= '<th style="width: 134px; background-color: #' . $this->primary . '; color: #fff;font-size: 8px">Périodicité</th>';
				$html .= '<th style="width: 134px; background-color: #' . $this->primary . '; color: #fff;font-size: 8px">Montant TTC</th>';
				$html .= '</tr>';
				$html .= '<tr>';
				$html .= '<td style="background-color: #F2F2F2;font-size: 8px"><b>' . (12 / $periodicity) . '</b></td>';
				$html .= '<td style="background-color: #F2F2F2;font-size: 8px"><b>' . BimpTools::displayMoneyValue($loyer_suppl_ht) . '</b></td>';
				$html .= '<td style="background-color: #F2F2F2;font-size: 8px"><b>' . $periodicity_label . '</b></td>';
				$html .= '<td style="background-color: #F2F2F2;font-size: 8px"><b>' . BimpTools::displayMoneyValue($loyer_suppl_ttc) . '</b></td>';
				$html .= '</tr>';
				$html .= '</table>';
				$this->pdf->writeHTML($html, false);
				break;
		}

		$livraisons = BimpTools::getArrayValueFromPath($this->client_data, 'livraisons', '');
		if ($livraisons) {
			$this->pdf->Ln(3);
			$html = '<span style="font-size: 9px; font-weight: bold; color: #' . $this->primary . '">Site(s) de livraison / installation</span><br/>';
			$html .= '<span style="font-size: 8px; font-weight: bold">';
			$html .= $livraisons;
			$html .= '</span>';
			$this->pdf->writeHTML($html, false);
		}

		$this->pdf->Ln(5);
		$html = '<span style="font-size: 8px; text-align: justify">';
		$html .= '<br/>Le locataire déclare avoir été parfaitement informé de l’opération lors de la phase précontractuelle, avoir pris connaissance, reçues et acceptées toutes les conditions particulières et générales. ';
		$html .= 'Il atteste que le contrat est en rapport direct avec son activité professionnelle et souscrit pour les besoins de cette dernière. Le signataire atteste être habilité à l’effet d’engager le locataire au titre du présent contrat. ';
		$html .= 'Le locataire reconnait avoir une copie des Conditions Générales, les avoir acceptées sans réserve y compris les clauses attribution de compétence et CNIL.';
		$html .= '</span>';
		$this->pdf->writeHTML($html, false);
		$this->pdf->Ln(6);

		if ($this->type_pdf == 'elec') {
			$this->pdf->writeHTML('<br/><span style="font-size: 8px;">Document à signer électroniquement par les trois parties</span>', false);
			$this->pdf->Ln(2);
		}
	}

	public function getSignatureBlocHtml(&$errors = array())
	{
		$html = '<table style="width: 95%;font-size: 8px;" cellpadding="3">';
		$html .= '<tr>';

		// Signatue locataire:
		$html .= '<td style="width: 33%">';
		$html .= '<span style="font-size: 9px; font-weight: bold">Pour le locataire :</span><br/>';
		$is_company = (int) BimpTools::getArrayValueFromPath($this->client_data, 'is_company', 0);
		$html .= BimpTools::getArrayValueFromPath($this->client_data, 'representant', '', $errors, true, 'Représentant du client absent') . '<br/>';
		if ($is_company) {
			$html .= BimpTools::ucfirst(BimpTools::getArrayValueFromPath($this->client_data, 'repr_qualite', '', $errors, true, 'Qualité du représentant du client absent')) . '<br/>';
		}
		$html .= '<br/><span style="font-style: italic">"Lu et approuvé"</span>';
		$html .= '</td>';

		// Signature Loueur:
		$html .= '<td style="width: 33%">';
		$html .= '<span style="font-size: 9px; font-weight: bold">Pour le loueur :</span><br/>';
		$html .= BimpTools::getArrayValueFromPath($this->loueur_data, 'nom', '', $errors, true, 'Nom du signataire loueur absent') . '<br/>';
		$html .= BimpTools::getArrayValueFromPath($this->loueur_data, 'qualite', '', $errors, true, 'Qualité du signataire loueur absente');
		$html .= '</td>';

		// Signature cessionnaire:
		$raison_cessionnaire = BimpTools::getArrayValueFromPath($this->cessionnaire_data, 'raison_social', '');
		$siren_cessionnaire = BimpTools::getArrayValueFromPath($this->cessionnaire_data, 'siren', '');
		$nom_cessionnaire = BimpTools::getArrayValueFromPath($this->cessionnaire_data, 'nom', '');
		$qualite_cessionnaire = BimpTools::getArrayValueFromPath($this->cessionnaire_data, 'qualite', '');

		$html .= '<td style="width: 33%">';
		$html .= '<span style="font-size: 9px; font-weight: bold">Pour le cessionnaire :</span><br/>';
		$html .= ($raison_cessionnaire ? : 'Nom: ') . '<br/>';
		$html .= 'SIREN : ' . $siren_cessionnaire . '<br/>';
		$html .= 'Représenté par : ' . $nom_cessionnaire . '<br/>';
		$html .= 'En qualité de : ' . $qualite_cessionnaire;
		$html .= '</td>';
		$html .= '</tr>';
		$html .= '<tr>';
		$html .= '<td>Date : <br/>Signature :<br/><br/><br/><br/><br/></td>';
		$html .= '<td>Date : <br/>Signature :<br/><br/><br/><br/><br/></td>';
		$html .= '<td>Date : <br/>Signature :<br/><br/><br/><br/><br/></td>';
		$html .= '</tr>';
		$html .= '</table>';

		return $html;
	}

	public function renderSignatureBloc()
	{
		// /!\ !!!!! Ne pas modifier ce bloc : réglé précisément pour incrustation signature électronique.

		if ($this->signature_bloc) {
			$errors = array();

			$html = $this->getSignatureBlocHtml($errors);
			$this->writeFullBlock($html);
		}
	}

	public function renderContent()
	{
		parent::renderContent();

		$x_paraphs = 10;
		$y_paraphs = 280;
		$cur_page = $this->pdf->getPage();

		if ($this->type_pdf == 'elec') {
			for ($i = 1; $i < $cur_page; $i++) {
				$this->pdf->setPage($i);
				$this->pdf->SetXY($x_paraphs, $y_paraphs);
				$this->pdf->writeHTML('<span style="font-size: 1px;color: #ffffff">ds_paraphe</span>');
			}
			$this->pdf->setPage($cur_page);
		}

		$this->pdf->createHeader('');

		if ($this->type_pdf == 'papier') {
			if (!$this->pvr_file || !file_exists($this->pvr_file)) {
				$this->errors[] = 'Fichier PVR absent';
			} else {
				$this->pvr_page_start = $cur_page + 1;
				$pvr_pdf = new BimpConcatPdf();
				$this->pvr_pages_number = $pvr_pdf->setSourceFile($this->pvr_file);
				for ($i = 1; $i <= $this->pvr_pages_number; $i++) {
					$this->pdf->AddPage();
				}
				$cur_page += $this->pvr_pages_number;
			}
		}

		$this->cg_page_start = $cur_page + 1;

		for ($i = 1; $i <= $this->cg_pages_number; $i++) {
			$this->pdf->AddPage();

			if ($i === $this->cg_pages_number) {
				$this->pdf->SetXY(10, 120);
				$this->writeFullBlock($this->getSignatureBlocHtml());
			} elseif ($this->type_pdf == 'elec') {
				$this->pdf->SetXY($x_paraphs, $y_paraphs);
				$this->pdf->writeHTML('<span style="font-size: 1px;color: #ffffff">ds_paraphe</span>');
			}
		}

		if ($this->type_pdf == 'elec') {
			$mandat_page = $this->cg_page_start + $this->cg_pages_number;
			$this->saveSignatureParams($mandat_page);
		}
	}

	public function render($file_name, $display, $display_only = false)
	{
		if (parent::render($file_name, $display, $display_only)) {
			if ($this->type_pdf == 'papier' && $this->pvr_file && file_exists($this->pvr_file)) {
				// Merge PVR:
				$pdf = new BimpConcatPdf();
				$pdf->mergeFiles($file_name, $this->pvr_file, $file_name, $display, $this->pvr_page_start, 1);
			}

			// Merge CGV:
			$pdf = new BimpConcatPdf();
			$pdf->mergeFiles($file_name, $this->cg_file, $file_name, $display, $this->cg_page_start, 1);
			return 1;
		}

		return 0;
	}

	public function saveSignatureParams($mandat_page)
	{
		$this->signature_params['locataire'] = array(
			'docusign' => array(
				'anch'             => 'Pour le locataire :',
				'fs'               => 'Size7',
				'x'                => 5,
				'y'                => 107,
				'date'             => array(
					'x' => 22,
					'y' => 57
				),
				'paraphe'          => array(
					'x' => 0,
					'y' => 150
				),
				'texts'            => array(
					'iban' => array(
						'label' => 'IBAN',
						'anch'  => 'IBAN :',
						'p'     => $mandat_page,
						'fs'    => 'Size11',
						'x'     => 25,
						'y'     => -5,
						'w'     => 400,
						'h'     => 19,
						'regex' => array(
							'pattern' => '^[a-zA-Z]{2} ?[0-9]{2} ?[0-9]{5} ?[0-9]{5} ?[0-9a-zA-Z]{11} ?[0-9]{2}$',
							'msg'     => 'Format valide : 2 lettres - 2 chiffre - 5 chiffres - 5 chiffres - 11 chiffres ou lettres - 2 chiffres'
						)
					),
					'bic'  => array(
						'label' => 'BIC',
						'anch'  => 'BIC :',
						'p'     => $mandat_page,
						'fs'    => 'Size11',
						'x'     => 20,
						'y'     => -5,
						'w'     => 400,
						'h'     => 19,
						'regex' => array(
							'pattern' => '^[0-9a-zA-Z]{8,11}$',
							'msg'     => 'Format valide : 8 à 11 chiffres ou lettres'
						)
					)
				),
				'extra_signatures' => array(
					array(
						'p'    => $mandat_page,
						'anch' => 'Joindre un RIB',
						'x'    => 50,
						'y'    => -20,
						'date' => array(
							'x'  => 25,
							'y'  => -69,
							'fs' => 'Size11',
						),
					)
				),
				'files'            => array(
					'rib'       => array(
						'name' => 'Merci de joindre un RIB',
						'anch' => 'Joindre un RIB',
						'p'    => $mandat_page,
						'x'    => 0,
						'y'    => 10,
						'w'    => 50,
						'h'    => 35
					),
					'cni_recto' => array(
						'name' => 'Carte Nationale d\'Identité (recto/verso ou recto)',
						'anch' => 'Joindre un RIB',
						'p'    => $mandat_page,
						'x'    => 80,
						'y'    => 10,
						'w'    => 50,
						'h'    => 35
					),
					'cni_verso' => array(
						'name' => 'Carte Nationale d\'Identité (verso, facultatif s\'il est inclus dans le fichier précédent)',
						'anch' => 'Joindre un RIB',
						'p'    => $mandat_page,
						'x'    => 160,
						'y'    => 10,
						'w'    => 50,
						'h'    => 35,
						'opt'  => 1
					)
				)
			)
		);
		$this->signature_params['cessionnaire'] = array(
			'docusign' => array(
				'anch'    => 'Pour le cessionnaire :',
				'fs'      => 'Size7',
				'x'       => 5,
				'y'       => 107,
				'date'    => array(
					'x' => 22,
					'y' => 57
				),
				'paraphe' => array(
					'x' => 60,
					'y' => 150
				)
			)
		);

		$nom_cessionnaire = BimpTools::getArrayValueFromPath($this->cessionnaire_data, 'nom', '');
		$qualite_cessionnaire = BimpTools::getArrayValueFromPath($this->cessionnaire_data, 'qualite', '');

		if (!$nom_cessionnaire) {
			$this->signature_params['cessionnaire']['elec']['display_nom'] = 1;
			$this->signature_params['cessionnaire']['elec']['nom_x_offset'] = 21;
			$this->signature_params['cessionnaire']['elec']['nom_y_offset'] = -17;

			$this->signature_params['cessionnaire']['docusign']['texts'] = array(
				'nom_cessionnaire' => array(
					'label' => 'Nom signataire',
					'x'     => 57,
					'y'     => 30,
					'w'     => 120,
					'h'     => 13
				)
			);
		}
		if (!$qualite_cessionnaire) {
			$this->signature_params['cessionnaire']['elec']['display_fonction'] = 1;
			$this->signature_params['cessionnaire']['elec']['fonction_x_offset'] = 18;
			$this->signature_params['cessionnaire']['elec']['fonction_y_offset'] = -14;

			$this->signature_params['cessionnaire']['docusign']['fonction'] = array(
				'x' => 48,
				'y' => 39,
				'h' => 13,
				'w' => 120
			);
		}

		$this->signature_params['loueur'] = array(
			'docusign' => array(
				'anch'    => 'Pour le loueur :',
				'fs'      => 'Size7',
				'x'       => 5,
				'y'       => 107,
				'date'    => array(
					'x' => 22,
					'y' => 57
				),
				'paraphe' => array(
					'x' => 30,
					'y' => 150
				)
			)
		);

		if (is_a($this->bimpObject, 'BimpObject')) {
			if ($this->bimpObject->field_exists($this->object_signature_params_field_name)) {
				$this->bimpObject->updateField($this->object_signature_params_field_name, $this->signature_params);
			}
		}
	}
}
