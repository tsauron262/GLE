<?php

if (!defined('BIMP_LIB')) {
	require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
}
require_once DOL_DOCUMENT_ROOT . '/bimpcore/pdf/classes/PropalPDF.php';

class PropalSavPDF extends PropalPDF
{

	public static $type = 'sav';
	public static $use_cgv = false;
	public $sav = null;

	public function __construct($db)
	{
		parent::__construct($db);

		$primary = BimpCore::getParam('pdf/primary_sav', '');

		if ($primary) {
			$this->primary = $primary;
		}
	}

	public function init($object)
	{
		if (is_null($this->sav) && !is_null($object) && is_a($object, 'Propal') && (int) $object->id) {
			$this->sav = BimpCache::findBimpObjectInstance('bimpsupport', 'BS_SAV', array(
				'id_propal' => (int) $object->id
			));

			if (!BimpObject::objectLoaded($this->sav)) {
				unset($this->sav);
				$this->sav = null;
				$this->errors[] = 'Aucun SAV associé à cette propale trouvé';
			}
		}

		parent::init($object);
	}

	protected function initData()
	{
		parent::initData();
		if (isset($this->object) && is_a($this->object, 'Propal')) {
			$this->bimpCommObject = BimpObject::getInstance('bimpsupport', 'BS_SavPropal', (int) $this->object->id);

			$cgv_file = '';
			switch (BimpCore::getExtendsEntity()) {
				case 'bimp':
				case 'champagne':
					if (BimpObject::objectLoaded($this->sav)) {
						$code_centre = $this->sav->getData('code_centre');

						if (!$code_centre) {
							$this->errors[] = 'Centre absent pour ' . $this->bimpCommObject->getLabel('this');
						} else {
							$centres = BimpCache::getCentresData();
							if (isset($centres[$code_centre]) and is_array($centres[$code_centre])) {
								$centre = $centres[$code_centre];
								$this->fromCompany->address = $centre['address'];
								$this->fromCompany->zip = $centre['zip'];
								$this->fromCompany->town = $centre['town'];
								$this->fromCompany->phone = $centre['tel'];
								$this->fromCompany->email = $centre['mail'];

								$obj_cgv = BimpCache::findBimpObjectInstance('bimpcore', 'BimpCGV', array(
									'types_pieces' => array('in_braces' => 'devis'),
									'date_start' => array('custom' => 'a.date_start <= \'' . ($this->bimpCommObject->getData('date_valid')?$this->bimpCommObject->getData('date_valid'):$this->bimpCommObject->getData('datec')) . '\''),
									'secteurs' => array('in_braces' => 'S'),
									'id_centre' => $centre['id'],
								), true, false, false, 'date_start', 'DESC');

								if (BimpObject::objectLoaded($obj_cgv)) {
									$cgv_file = $obj_cgv->getFilesDir();
									$cgv_file .= 'CGV_file.pdf';
								}
							}

							if(!$cgv_file)
								$cgv_file = DOL_DOCUMENT_ROOT . '/bimpsupport/pdf/cgv_boutiques/cgv_' . $code_centre . '.pdf';
						}
					} else {
						$this->errors[] = 'Aucun SAV associé à ' . $this->bimpCommObject->getLabel('this');
					}
					break;

				case 'actimac':
					$code_centre = $this->sav->getData('code_centre');
					if (!$code_centre) {
						$this->errors[] = 'Centre absent pour ' . $this->bimpCommObject->getLabel('this');
					} else {
						$centres = BimpCache::getCentresData();
						if (isset($centres[$code_centre]) and is_array($centres[$code_centre])) {
							$centre = $centres[$code_centre];
							$this->fromCompany->address = $centre['address'];
							$this->fromCompany->zip = $centre['zip'];
							$this->fromCompany->town = $centre['town'];
							$this->fromCompany->phone = $centre['tel'];
							$this->fromCompany->email = $centre['mail'];
						}
					}
					$cgv_file = DOL_DOCUMENT_ROOT . '/bimpsupport/pdf/cgv_actimac.pdf';
					break;
			}

			if ($cgv_file && file_exists($cgv_file)) {
				$this->pdf->extra_concat_files[] = $cgv_file;
			} else {
				// CGV par défaut :
				static::$use_cgv = true;
			}
		}
	}

	protected function initHeader()
	{
		parent::initHeader();

		$rows = '';

		if (!is_null($this->sav)) {
			$rows .= '<span style="color: #' . $this->primary . '">' . $this->sav->getData('ref') . '</span><br/>';
			$equipment = $this->sav->getchildObject('equipment');
			if (!is_null($equipment) && $equipment->isLoaded()) {
				if ($equipment->getData('product_label') != "") {
					$rows .= '<span style="font-size: 9px;">' . $equipment->getData('product_label') . "</span><br/>";
				}
				$rows .= '<span style="font-size: 9px;">' . $equipment->getData('serial') . '</span>';
				$imei = $equipment->getData('imei');
				if ($imei != '' && $imei != "n/a") {
					$rows .= "<br/>" . '<span style="font-size: 9px;">' . $imei . '</span>';
				}
			}

			$infoCentre = $this->sav->getCentreData();
			global $mysoc;
			$mysoc->email = $infoCentre['mail'];
			$mysoc->phone = $infoCentre['tel'];
		}

//        $this->header_vars['apple_img'] = DOL_DOCUMENT_ROOT . "/synopsistools/img/agree.jpg";
		$this->header_vars['header_right'] = $rows;
	}

	public function getFromUsersInfosHtml()
	{
		$html = parent::getFromUsersInfosHtml();

		if (!is_null($this->sav)) {
			if ((int) $this->sav->getData('id_user_tech')) {
				$tech = $this->sav->getChildObject('user_tech');
				if (!is_null($tech) && $tech->isLoaded()) {
					$html .= '<div class="row" style="border-top: solid 1px #' . $this->primary . '"><span style="font-weight: bold; color: #' . $this->primary . ';">';
					$html .= 'Technicien en charge :</span> ' . $tech->dol_object->firstname . '</div>';
				}
			}
		}

		return $html;
	}

	public function renderAfterBottom()
	{
		$html = '<table cellpadding="20px"><tr><td>';
//        $html .= '<p style="font-size: 7px; color: #002E50">';
		$html .= '<div style="text-indent: 15px; font-size: 7px; color: #002E50">';
		$html .= 'Si le service est requis conformément à une obligation de réparation d’un tiers, ces informations seront ';
		$html .= 'transférées au tiers pour vérification et des objectifs de qualité, notamment la confirmation de la transaction de réparation et la ';
		$html .= 'soumission d’une enquéte client. En signant, vous acceptez ce transfert ainsi que l’utilisation de ces informations par un tiers.';
		$html .= '<br/>';
		$html .= 'Les pièces de maintenance ou les produits utilisés pour la réparation de votre produit sont neufs ou d\'un état équivalent à neuf ';
		$html .= 'en termes de performance et de fiabilité. ';
		$html .= '<br/>';
		$html .= 'Pour du matériel couvert par Apple, la garantie initiale s\'applique. Pour du matériel non couvert par Apple, ';
		$html .= 'la garantie est de 3 mois pour les pièces et la main d\'oeuvre. Les pannes logicielles ne sont pas couvertes par ';
		$html .= 'la garantie du fabricant. Une garantie de 30 jours est appliquée pour les réparations logicielles.';
		$html .= '<br/>';
		$html .= 'Les informations personnelles requises suivantes (nom, adresse, téléphone et adresse mail) sont nécessaires pour poursuivre la ';
		$html .= 'demande de réparation.';
		$html .= '</div>';
//        $html .= '</p>';
		$html .= '</td></tr></table>';

		$this->writeContent($html);
	}
}

class SavRestitutePDF extends PropalSavPDF
{

	public $restitution_sav = 1;
	public $signature_bloc_label = '';

	public function initData()
	{
		parent::initData();

		$this->pdf->addCgvPages = false;

		if (BimpObject::objectLoaded($this->object) && BimpObject::objectLoaded($this->sav)) {
			Propal::STATUS_BILLED;
			$line = new PropaleLigne($this->db);
			$line->desc = 'Résolution: ' . $this->sav->getData('resolution');
			$this->object->lines[] = $line;
		}
	}

	public function renderSignatureBloc()
	{
		// /!\ !!!!! Ne pas modifier ce bloc : réglé précisément pour incrustation signature électronique.

		$html = '<br/>';

		$html .= '<table style="width: 95%;font-size: 7px;" cellpadding="3">';
		$html .= '<tr>';
		$html .= '<td style="width: 50%"></td>';
		$html .= '<td style="width: 50%">';

		$html .= '<table cellpadding="3">';

		$html .= '<tr>';
		$html .= '<td style="text-align:center;"><i><b>Je reconnais avoir récupéré ce jour mon matériel :</b></i></td>';

		$html .= '<td></td>';
		$html .= '</tr>';
		$html .= '<tr>';
		$html .= '<td>Nom et prénom:</td>';

		$html .= '<td rowspan="4" style="border-top-color: #505050; border-left-color: #505050; border-right-color: #505050; border-bottom-color: #505050;"><br/><br/><br/><br/><br/></td>';
		$html .= '</tr>';

		$html .= '<tr>';
		$html .= '<td></td>';
		$html .= '</tr>';

		$html .= '<tr>';
		$html .= '<td>Date :</td>';
		$html .= '</tr>';

		$html .= '</table>';

		$html .= '</td>';
		$html .= '</tr>';
		$html .= '</table>';

		$page = 0;
		$yPos = 0;

		$this->writeFullBlock($html, $page, $yPos);

		$this->signature_params = array(
			'page'          => $page,
			'y_pos'         => $yPos + 10,
			'x_pos'         => 148,
			'width'         => 40,
			'nom_x_offset'  => -47,
			'nom_y_offset'  => 5,
			'date_x_offset' => -38,
			'date_y_offset' => 12,
		);

		if (BimpObject::objectLoaded($this->sav)) {
			$this->sav->updateField('signature_resti_params', $this->signature_params);
		}

		return $html;
	}

	public function getBottomLeftHtml() {}
}
