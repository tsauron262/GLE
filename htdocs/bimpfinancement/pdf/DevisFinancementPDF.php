<?php

require_once DOL_DOCUMENT_ROOT . '/bimpfinancement/pdf/DocFinancementPDF.php';

class DevisFinancementPDF extends DocFinancementPDF
{

	public static $doc_type = 'devis';
	public $signature_bloc = true;
	public $use_docsign = true;
	public $object_signature_params_field_name = 'signature_devis_params';

	public function __construct($db, $demande, $extra_data = array(), $options = array())
	{
		parent::__construct($db, $demande, $extra_data, $options);

		$this->doc_name = 'Offre de location';
	}

	public function initHeader()
	{
		parent::initHeader();
		if (BimpObject::objectLoaded($this->demande) && (int) $this->demande->getData('status') !== BF_Demande::STATUS_ACCEPTED) {
			$this->header_vars['ref_extra'] .= ($this->header_vars['ref_extra'] ? '<br/>' : '');
			$this->header_vars['ref_extra'] .= '<span style="color: #' . $this->primary . '; font-weight: bold">Sous réserve d\'accord définitif</span>';
		}
	}

	public function renderTop()
	{
		if (count($this->errors)) {
			return;
		}

		$has_evo = (int) BimpTools::getArrayValueFromPath($this->options, 'formules/evo', 0);
		$has_dyn = (int) BimpTools::getArrayValueFromPath($this->options, 'formules/dyn', 0);

		if (!$has_evo && !$has_dyn) {
			$has_evo = 1;
		}

		$html = '';

		$html .= '<div style="text-align: right; font-size: 8px">';
		$html .= 'Limonest, le ' . date('d / m / Y');
		$html .= '</div>';
		$html .= '<br/>';

		$html .= '<div style="font-size: 9px; font-weight: bold">';
		$html .= 'Objet: Proposition de location de vos équipements informatiques';
		$html .= '</div>';

		$html .= '<p style="font-size: 8px">';
		$html .= 'Madame, Monsieur, <br/><br/>';
		$html .= 'Nous vous prions de trouver ci-dessous, notre proposition de location concernant votre projet d\'équipement.<br/>';

		$html .= 'Nous avons retenu pour cette simulation, les élements';
		if (!empty($this->sources)) {
			$html .= ' transmis dans ';

			$i = 1;
			foreach ($this->sources as $source) {
				if ($i > 1) {
					if ($i == count($this->sources)) {
						$html .= ' et ';
					} else {
						$html .= ', ';
					}
				}

				$html .= $source->displayOrigine(1, 0, 1);
			}

			$html .= ' (détail ci-desous).';
		} else {
			$html .= ' dont le détail figure ci-dessous';
		}
		$html .= '</p>';

		$html .= '<div style="font-size: 8px">';

		$html .= '<p style="font-weight: bold">Rappel de l\'offre <span style="color: #' . $this->primary . '">LDLC.PRO LEASE</span></p>';
		$html .= '<p>Les offres de location longue durée proposées par LDLC.PRO LEASE permettent de gérer au mieux le cycle de vie des matériels informatiques.</p>';

		$html .= '<p>';
		$html .= 'LDLC FRO LEASE, filiale à 100% du Groupe LDLC, a été créé en 2018.<br/>';
		$html .= 'Elle accompagne les équipes commerciales du Groupe LDLC, en proposant des solutions de location longue durée.<br/>';

		$html .= 'Plusieurs formules permettent d\'adapter les offres sur-mesure, en fonction : </p>';

		$html .= '<ul>';
		$html .= '<li>du profil du client et de ses intentions.</li>';
		$html .= '<li>de la nature des équipements loués.</li>';
		$html .= '</ul>';

		$html .= '<p>Les plus courantes sont : </p>';

		if ($has_evo) {
			$html .= '<p>La "<b>Formule Evolutive</b>" permet notamment : </p>';
			$html .= '<ul>';
			$html .= '<li>un mode de financement indépendant des autres concours bancaires</li>';
			$html .= '<li>d\'établir un contrat adapté à la nature des matériels et de leur durée de vie</li>';
			$html .= '<li>de gérer facilement la fin de vie des équipements</li>';
			$html .= '</ul>';
		}

		if ($has_dyn) {
			$html .= '<p>';
			if ($has_evo) {
				$html .= 'En complèment de cette offre, LDLC.PRO LEASE propose également une solution de gestion active des parcs de matériels : <br/>';
				$html .= 'la "<b>Formule Dynamique</b>". ';
				$html .= 'Celle-ci permet quant à elle :';
			} else {
				$html .= 'La "<b>Formule Dynamique</b>" permet notamment :';
			}
			$html .= '</p>';

			$html .= '<ul>';
			$html .= '<li>d\'optimiser la gestion des parcs de matériels avec une réelle économie financière</li>';
			$html .= '<li>de financer les équipements sur une 1ère période sans intérêts</li>';
			$html .= '<li>de choisir au terme de cette 1ère période, entre renouveler les matériels avec des produits ';
			$html .= 'de dernière génération pour bénéficier des progrès technologiques, ou de prolonger l\'exploitation ';
			$html .= 'de la configuration en profitant de loyers réduits. Dans le 1er cas, LDLC.PRO LEASE se chargera de ';
			$html .= 'commercialiser les configurations auprès d\'un second utilisateur. <br/><br/>';
			$html .= 'Dans les 2 cas, la location permet d’afficher une meilleure présentation de votre bilan, en conservant ';
			$html .= 'votre capacité d\'endettement, en préservant votre trésorerie et en diversifiant vos sources de financement.';
			$html .= '</li>';
			$html .= '</ul>';
		}

		$html .= '<p>D\'autres possibilités peuvent être accessibles sous conditions (' . (!$has_evo ? 'Formule évolutive, ' : '') . (!$has_dyn ? 'Formule dynamique, ' : '') . 'DAAS, AFS)</p>';

		$html .= '<p style="font-size: 8px;">';
		$html .= 'Nous sommes à votre entière disposition pour tout complément d\'information, et vous prions d’agréer, ';
		$html .= 'Madame, Monsieur, l\'expression de nos meilleures salutations.';
		$html .= '</p>';

		$html .= '<p></p>';
		$html .= '</div>';

		$this->writeContent($html);
		$this->pdf->newPage();
	}

	public function getBottomLeftHtml()
	{
		$html = 'Périodicité : <b>' . $this->demande->displayData('periodicity', 'default', false, true) . '</b><br/>';
		$html .= 'Terme des paiements : <b>' . $this->demande->displayData('mode_calcul', 'default', false, true) . '</b><br/>';
		return $html;
	}

	public function getBottomRightHtml()
	{
		$rows = array();

		$montant_materiels = $this->demande->getData('montant_materiels');
		$montant_services = $this->demande->getData('montant_services');
		$montant_logiciels = $this->demande->getData('montant_logiciels');
		$montant_total = $montant_materiels + $montant_services + $montant_logiciels;

		$odd = true;
		if ($montant_materiels) {
			$rows[] = array(
				'label' => 'Montant matériels HT',
				'value' => BimpTools::displayMoneyValue($montant_materiels, 'EUR', 0, 0, 1, 2, 0, ',', 1, 2),
				'bk'    => 'EBEBEB'
			);
			$odd = false;
		}

		if ($montant_services) {
			$rows[] = array(
				'label' => 'Montant services HT',
				'value' => BimpTools::displayMoneyValue($montant_services, 'EUR', 0, 0, 1, 2, 0, ',', 1, 2),
				'bk'    => ($odd ? 'EBEBEB' : 'F2F2F2')
			);
			$odd = !$odd;
		}

		if ($montant_logiciels) {
			$rows[] = array(
				'label' => 'Montant logiciels HT',
				'value' => BimpTools::displayMoneyValue($montant_logiciels, 'EUR', 0, 0, 1, 2, 0, ',', 1, 2),
				'bk'    => ($odd ? 'EBEBEB' : 'F2F2F2')
			);
		}

		if ($montant_total) {
			$rows[] = array(
				'label' => 'Total HT',
				'value' => BimpTools::displayMoneyValue($montant_total, 'EUR', 0, 0, 1, 2, 0, ',', 1, 2),
				'bk'    => 'E0E0E0'
			);
		}

		$html = '<table style="width: 100%" cellpadding="5">';

		foreach ($rows as $r) {
			$bold = (int) BimpTools::getArrayValueFromPath($r, 'bold', 0);

			$html .= '<tr>';
			$html .= '<td style="background-color: #' . $r['bk'] . ';' . ($bold ? ' font-weight: bold;' : '') . '">' . $r['label'] . '</td>';
			$html .= '<td style="text-align: right; background-color: #' . $r['bk'] . ';' . ($bold ? ' font-weight: bold;' : '') . '">';

			if (BimpTools::getArrayValueFromPath($r, 'money', 0)) {
				$html .= BimpTools::displayMoneyValue($r['value'], '', 0, 0, 1) . '';
			} else {
				$html .= $r['value'];
			}

			$html .= '</td>';
			$html .= '</tr>';
		}

		$html .= '</table>';
		$html .= '<br/>';

		return $html;
	}

	public function renderAfterBottom()
	{
		$html = '';
		$total_demande = $this->demande->getTotalDemandeHT();

		$periodicity = 0;
		$nb_mois = 0;

		if (BimpObject::objectLoaded($this->demande_refin)) {
			$df_values = $this->values;

			$nb_mois = $df_values['nb_mois'];
			$periodicity = $df_values['periodicity'];
		} else {
			$nb_mois = $this->demande->getData('duration');
			$periodicity = (int) $this->demande->getData('periodicity');
		}

		if (count($this->errors)) {
			return;
		}
		$nb_loyers = $nb_mois / $periodicity;

		$duration_label = '';
		$dyn_duration_label = '';
		if (in_array($nb_mois, array(12, 24, 36, 48, 60, 72))) {
			$nb_years = $nb_mois / 12;
			$duration_label = $nb_years . ' an' . ($nb_years > 1 ? 's' : '');
			$dyn_duration_label = ($nb_years + 1) . ' ans';
		} else {
			$duration_label = $nb_mois . ' mois';
			$dyn_duration_label = $nb_mois + 12 . ' mois';
		}

		$has_evo = (int) BimpTools::getArrayValueFromPath($this->options, 'formules/evo', 0);
		$has_dyn = (int) BimpTools::getArrayValueFromPath($this->options, 'formules/dyn', 0);

		if ($has_evo) {
			$loyer_evo_ht = (isset($df_values['loyer_evo_mensuel']) ? $df_values['loyer_evo_mensuel'] : $this->demande->getData('loyer_mensuel_evo_ht')) * $periodicity;

			if ($loyer_evo_ht) {
				$html .= '<div style="font-size: 8px">';

				$html .= '<p>';
				$html .= '<span style="font-size: 9px"><b>L\'offre Location "Formule Evolutive" de <span style="color: #' . $this->primary . '">LDLC.PRO LEASE</span></b></span><br/>permet le lissage de la charge ';
				$html .= 'financière de l\'investissement sur une période de 2 à 5 ans';
				$html .= '</p>';

				$html .= '<table cellpadding="3px" style="margin-left: 80px">';
				$html .= '<tr>';
				$html .= '<th style="background-color: #' . $this->primary . '; color: #fff; width: 100px">Durée</th>';
				$html .= '<th style="background-color: #' . $this->primary . '; color: #fff; width: 300px">Loyers</th>';
				$html .= '</tr>';
				$html .= '<tr>';
				$html .= '<td style="background-color: #F2F2F2"><b>' . $duration_label . '</b></td>';
				$html .= '<td style="background-color: #F2F2F2">' . $nb_loyers . ' loyers ' . BFTools::$periodicities_masc[$periodicity] . 's* de ';
				$html .= '<b>' . BimpTools::displayMoneyValue($loyer_evo_ht) . '</b>';
				$html .= '  pour un coût total de <b>' . BimpTools::displayMoneyValue($loyer_evo_ht * $nb_loyers) . '</b>';
				$html .= '</td>';
				$html .= '</tr>';
				$html .= '</table>';
				$html .= '<p style="font-style: italic; font-size: 7px">';
				$html .= '*Loyers bruts en € HT, hors assurance, prélevés ' . BFTools::$periodicities_masc[$periodicity] . 'lement, ';
				$html .= ((int) $this->demande->getData('mode_calcul') ? 'terme à échoir' : 'à terme échu') . '.';
				$html .= '</p>';
				$html .= '</div>';
			}
		}

		if ($has_dyn) {
			$loyer_dyn_ht = (isset($df_values['loyer_dyn_mensuel']) ? $df_values['loyer_dyn_mensuel'] : $this->demande->getData('loyer_mensuel_dyn_ht')) * $periodicity;
			$loyer_dyn_suppl = (isset($df_values['loyer_dyn_suppl_mensuel']) ? $df_values['loyer_dyn_suppl_mensuel'] : $this->demande->getData('loyer_mensuel_suppl_ht')) * $periodicity;

			if ($loyer_dyn_ht) {
				$html .= '<div style="font-size: 8px">';

				$html .= '<p>';
				$html .= '<span style="font-size: 9px"><b>L\'offre Location "Formule Dynamique" de <span style="color: #' . $this->primary . '">LDLC.PRO LEASE</span></b></span><br/>permet le lissage de la charge ';
				$html .= 'financière, tout en profitant de la capacité de LDLC.PRO LEASE à commercialiser les matériels à la fin de la période optimale d\'utilisation.<br/>';
				$html .= '</p>';
				$html .= '<p>';
				$html .= 'Au terme de la période d\'utilisation optimale, le client a le choix :';
				$html .= '</p>';

				$html .= '<ul>';
				$html .= '<li>de faire évoluer sa configuration avec LDLC.PRO LEASE (contrat "annule et remplace"),</li>';
				$html .= '<li>ou poursuivre la location avec un loyer réduit pendant les 12 derniers mois du contrat.</li>';
				$html .= '</ul>';

				$html .= '<p>';
				$html .= 'Les contrats sont donc établis dans ce cas avec les paramètres suivants :';
				$html .= '</p>';

				$html .= '<ul>';
				$html .= '<li>1ère période de mise à disposition : le loyer lié aux matériels est calculé avec un <b>taux à 0%</b> sur la part matérielle, sur cette durée,</li>';
				$html .= '<li>une 2ème période de 12 mois complémentaires, avec un loyer réduit.</li>';
				$html .= '</ul>';

				$html .= '<p>';
				$html .= 'Cette offre permet donc en plus des avantages déjà évoqués pour l\'offre "Formule EVOLUTIVE", et <b>à coût global équivalent</b>, de :';
				$html .= '</p>';
				$html .= '<ul>';
				$html .= '<li>choisir le terme de renouvellement du parc matériel en optimisant les performances (gestion dynamique)</li>';
				$html .= '<li>garantir le suivi des évolutions technologiques</li>';
				$html .= '<li>réduire le montant des loyers, donc le coût d\'exploitation des matériels pour la période optimale</li>';
				$html .= '</ul>';

				$html .= '<table cellpadding="3px" style="margin-left: 80px">';
				$html .= '<tr>';
				$html .= '<th style="background-color: #' . $this->primary . '; color: #fff; width: 100px">Durée totale</th>';
				$html .= '<th style="background-color: #' . $this->primary . '; color: #fff; width: 200px">Période optimale</th>';
				$html .= '<th style="background-color: #' . $this->primary . '; color: #fff; width: 200px">Prolongation</th>';
				$html .= '</tr>';
				$html .= '<tr>';
				$html .= '<td style="background-color: #F2F2F2"><b>' . $dyn_duration_label . '</b></td>';
				$html .= '<td style="background-color: #F2F2F2">' . $nb_loyers . ' loyers ' . BFTools::$periodicities_masc[$periodicity] . 's* de ';
				$html .= '<b>' . BimpTools::displayMoneyValue($loyer_dyn_ht) . '</b>';
				$html .= '</td>';
				$html .= '<td>+ ' . (12 / $periodicity) . ' loyers ' . BFTools::$periodicities_masc[$periodicity] . 's* de <b>' . BimpTools::displayMoneyValue($loyer_dyn_suppl) . '</b></td>';
				$html .= '</tr>';
				$html .= '</table>';
				$html .= '<p style="font-style: italic; font-size: 7px">';
				$html .= '*Loyers bruts en € HT, hors assurance, prélevés ' . BFTools::$periodicities_masc[$periodicity] . 'lement, ';
				$html .= ((int) $this->demande->getData('mode_calcul') ? 'terme à échoir' : 'à terme échu') . '.';
				$html .= '</p>';
				$html .= '</div>';
			}
		}

		if ($has_evo && $has_dyn) {
			$eco = ($loyer_evo_ht * $nb_loyers) - ($loyer_dyn_ht * $nb_loyers);
			$eco_percent = 0;
			if ($total_demande) {
				$eco_percent = $eco / $total_demande * 100;
			}

			$html .= '<p style="font-size: 9px; font-weight: bold">';
			$html .= 'Comparatif à ' . $nb_mois . ' mois';
			$html .= '</p>';

//			$html .= '<div>';
			$html .= '<table cellpadding="3px">';
			$html .= '<thead>';
			$html .= '<tr>';
			$html .= '<th style="background-color: #' . $this->primary . '; color: #fff;font-size: 8px">Formule</th>';
			$html .= '<th style="background-color: #' . $this->primary . '; color: #fff;font-size: 8px">Loyer ' . BFTools::$periodicities_masc[$periodicity] . '</th>';
			$html .= '<th style="background-color: #' . $this->primary . '; color: #fff;font-size: 8px">Coût à ' . $nb_mois . ' mois*</th>';
			$html .= '</tr>';
			$html .= '</thead>';
			$html .= '<tbody>';
			$html .= '<tr>';
			$html .= '<td style="background-color: #F2F2F2;font-size: 8px"><b>Evolutive</b></td>';
			$html .= '<td style="background-color: #F2F2F2;font-size: 8px">' . BimpTools::displayMoneyValue($loyer_evo_ht) . '</td>';
			$html .= '<td style="background-color: #F2F2F2;font-size: 8px"><b>' . BimpTools::displayMoneyValue($loyer_evo_ht * $nb_loyers) . '</b></td>';
			$html .= '</tr>';

			$html .= '<tr>';
			$html .= '<td style="background-color: #F2F2F2;font-size: 8px"><b>Dynamique</b><br/>(Avec renouvellement des équipements)</td>';
			$html .= '<td style="background-color: #F2F2F2;font-size: 8px">' . BimpTools::displayMoneyValue($loyer_dyn_ht) . '</td>';
			$html .= '<td style="background-color: #F2F2F2;font-size: 8px"><b>';
			$html .= BimpTools::displayMoneyValue($loyer_dyn_ht * $nb_loyers);
			$html .= '</b><br/>Soit une économie de <b>' . BimpTools::displayMoneyValue($eco) . '</b>';
			$html .= ($eco_percent ? ' (' . BimpTools::displayFloatValue($eco_percent) . ' %)' : '');
			$html .= '</td>';
			$html .= '</tr>';
			$html .= '</tbody>';
			$html .= '</table>';
			$html .= '<p style="font-style: italic; font-size: 7px">*Frais financiers inclus</p>';
//			$html .= '</div>';
		}

		$html .= '<span style="font-size: 8px; font-weight: bold; text-align: center">';
		$html .= 'L\'ensemble de nos propositions est soumis à l\'acceptation de notre comité des engagements';
		$html .= '</span>';

		if ($has_evo && $has_dyn) {
			$html .= '<p style="font-size: 8px;">';
			$html .= '<b>Merci d\'indiquer ci-dessous la formule que vous choisissez (évolutive ou dynamique) : </b>';
			$html .= '<br/><br/>';
			$html .= '________________________________________';
			$html .= '</p>';
		}

		$this->writeContent($html);
	}
}
