<?php

require_once DOL_DOCUMENT_ROOT . '/bimpfinancement/BF_Lib.php';
require_once DOL_DOCUMENT_ROOT . '/bimpcore/pdf/classes/BimpDocumentPDF.php';

class PropositionLocationPDF extends BimpDocumentPDF
{

    public static $type = 'proposition_location';

    # Params:
    public static $full_blocs = array(
        'renderBottom' => 0
    );

    # Données: 
    public $title;
    public $montant_materiels;
    public $montant_services;
    public $duration;
    public $periodicity;
    public $mode_calcul;
    public $lines;
    public $options = array();

    public function __construct($data)
    {
        $this->title = BimpTools::getArrayValueFromPath($data, 'title', '');

        if (!$this->title) {
            $this->title = 'Proposition de location de vos équipements informatiques';
        }
        $this->montant_materiels = (float) BimpTools::getArrayValueFromPath($data, 'montant_materiels', 0);
        $this->montant_services = (float) BimpTools::getArrayValueFromPath($data, 'montant_services', 0);

        if (!($this->montant_materiels + $this->montant_services)) {
            $this->errors[] = 'Montant total nul';
        }

        $this->duration = BimpTools::getArrayValueFromPath($data, 'duration', (int) BimpCore::getConf('def_duration', null, 'bimpfinancement'));

        if (!(int) $this->duration) {
            $this->errors[] = 'Durée totale absente';
        }

        $this->periodicity = BimpTools::getArrayValueFromPath($data, 'periodicity', (int) BimpCore::getConf('def_periodicity', null, 'bimpfinancement'));

        if (!(int) $this->periodicity) {
            $this->errors[] = 'Périodicité des loyers absente';
        }

        $this->mode_calcul = BimpTools::getArrayValueFromPath($data, 'mode_calcul', (int) BimpCore::getConf('def_mode_calcul', null, 'bimpfinancement'));
        $this->lines = BimpTools::getArrayValueFromPath($data, 'lines', array());

        global $db;
        parent::__construct($db);
    }

    public function initData()
    {
        
    }

    public function initHeader()
    {
        global $conf;

        $logo_file = $conf->mycompany->dir_output . '/logos/' . $this->fromCompany->logo;
        if ($this->file_logo != '' && is_file($conf->mycompany->dir_output . '/logos/' . $this->file_logo)) {
            $logo_file = $conf->mycompany->dir_output . '/logos/' . $this->file_logo;
        }

        $logo_width = 0;
        if (!file_exists($logo_file)) {
            $logo_file = '';
        } else {
            $sizes = dol_getImageSize($logo_file, false);
            $tabTaille = $this->calculeWidthHeightLogo($sizes['width'], $sizes['height'], $this->maxLogoWidth, $this->maxLogoHeight);
            $logo_width = $tabTaille[0];
            $logo_height = $tabTaille[1];
        }

        $this->pdf->topMargin = 44;

        $this->header_vars = array(
            'primary_color' => $this->primary,
            'logo_img'      => $logo_file,
            'logo_width'    => $logo_width,
            'logo_height'   => $logo_height,
            'doc_name'      => '',
            'doc_ref'       => '',
            'ref_extra'     => '',
            'header_infos'  => '',
            'header_right'  => '',
        );
    }

    public function getDocInfosHtml()
    {
        return '';
    }

    public function getTargetInfosHtml()
    {
        return '';
    }

    public function renderDocInfos()
    {
        
    }

    public function renderTop()
    {
        if (count($this->errors)) {
            return;
        }

        $html = '';

        $html .= '<div style="text-align: center;font-size: 12px; font-weight: bold; color: #' . $this->primary . '">';
        $html .= $this->title;
        $html .= '</div>';

        $html .= '<p style="font-size: 8px">';
        $html .= 'Madame, Monsieur, <br/><br/>';
        $html .= 'Nous vous prions de trouver ci-dessous, notre proposition de location concernant votre projet d\'équipement.<br/>';

        $html .= 'Nous avons retenu pour cette simulation, les élements dont le détail figure ci-dessous';
        $html .= '</p>';

        $html .= '<div style="font-size: 8px">';
        $html .= '<p style="font-weight: bold">Rappel de l\'offre <span style="color: #' . $this->primary . '">LDLC PRO LEASE</span></p>';
        $html .= '<p>Les offres de financement proposées par LDLC.PRO LEASE permettent de gérer au mieux le cycle de vie des matériels informatiques.</p>';

        $has_evo = (int) BimpTools::getArrayValueFromPath($this->options, 'formules/evo', 1);
        $has_dyn = (int) BimpTools::getArrayValueFromPath($this->options, 'formules/dyn', 1);

        if ($has_evo || (!$has_evo && !$has_dyn)) {
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

        $html .= '</div>';

        if (empty($this->lines)) {
            $html .= '<div style="font-size: 10px; font-weight: bold; color: #' . $this->primary . '">';
            $html .= 'Montants à financer';
            $html .= '</div>';

            $html .= '<p style="font-size: 8px">';
            $html .= '<b>Total matériels HT : </b>' . BimpTools::displayMoneyValue($this->montant_materiels) . '<br/>';
            $html .= '<b>Total services HT : </b>' . BimpTools::displayMoneyValue($this->montant_services);
            $html .= '</p>';
        }

        $this->writeContent($html);
    }

    public function renderLines()
    {
        if (empty($this->lines)) {
            return;
        }

        $table = new BimpPDF_Table($this->pdf);
        $table->addCol('desc', 'Désignation', 0, '', '', '');
        $table->addCol('qte', 'Quantité', 25, 'text-align: center', '', 'text-align: center');

        foreach ($this->lines as $line) {
            $row = array();
            if ((int) BimpTools::getArrayValueFromPath($line, 'text', 0)) {
                $row['desc'] = array(
                    'colspan' => 99,
                    'style'   => ' background-color: #F5F5F5;',
                    'content' => self::cleanHtml(BimpTools::getArrayValueFromPath($line, 'label', ''))
                );
            } else {
                $row['desc'] = self::cleanHtml(BimpTools::getArrayValueFromPath($line, 'label', ''));
                $row['qte'] = (float) BimpTools::getArrayValueFromPath($line, 'qty', 0);
            }

            $table->rows[] = $row;
        }

        if (count($table->rows)) {
            $this->writeContent('<div style="font-weight: bold; font-size: 9px;">Description des équipements et services :</div>');
            $this->pdf->addVMargin(1);
            $table->write();
        }

        unset($table);
    }

    public function renderBottom()
    {
        if (count($this->errors)) {
            return;
        }

        $html = '';

        $total_demande = $this->montant_materiels + $this->montant_services;
        $nb_mois = $this->duration;
        $periodicity = $this->periodicity;
        $nb_loyers = $nb_mois / $periodicity;
        $mode_calcul = $this->mode_calcul;
        $vr_achat = BimpCore::getConf('def_vr_achat', null, 'bimpfinancement');

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

        BimpObject::loadClass('bimpfinancement', 'BF_Refinanceur');
        BimpObject::loadClass('bimpfinancement', 'BF_Demande');

        $tx_cession = BF_Refinanceur::getTauxMoyen($total_demande);
        $marge = BF_Demande::getDefaultMargePercent($total_demande);

        $values = BFTools::getCalcValues($this->montant_materiels, $this->montant_services, $tx_cession, $nb_mois, $marge / 100, $vr_achat, $mode_calcul, $periodicity, $this->errors);

        $has_evo = (int) BimpTools::getArrayValueFromPath($this->options, 'formules/evo', 1);
        $has_dyn = (int) BimpTools::getArrayValueFromPath($this->options, 'formules/dyn', 1);

        if (count($this->errors)) {
            return;
        }

        if ($has_evo) {
            $loyer_evo_ht = BimpTools::getArrayValueFromPath($values, 'loyer_evo', 0);

            if ($loyer_evo_ht) {
                $html .= '<br/><br/><div style="font-size: 8px">';
                $html .= '<div style="font-size: 10px; font-weight: bold; color: #' . $this->primary . '">Offres de location</div>';

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
                $html .= ($mode_calcul ? 'terme à échoir' : 'à terme échu') . '.';
                $html .= '</p>';
                $html .= '</div>';
                $this->writeFullBlock($html);
                $html = '';
            }
        }

        if ($has_dyn) {
            $loyer_dyn_ht = BimpTools::getArrayValueFromPath($values, 'loyer_dyn', 0);
            $loyer_dyn_suppl = BimpTools::getArrayValueFromPath($values, 'loyer_dyn_suppl', 0);

            if ($loyer_dyn_ht) {
                $html .= '<div style="font-size: 8px">';

                $html .= '<p>';
                $html .= '<span style="font-size: 9px"><b>L\'offre Location "Formule Dynamique" de <span style="color: #' . $this->primary . '">LDLC.PRO LEASE</span></b></span><br/>permet le lissage de la charge ';
                $html .= 'financière, tout en profitant de la capacité de LDLC.PRO LEASE à commercialiser les matériels à la fin de la période optimale d\'utilisation.<br/>';
                $html .= '</p>';
//                $html .= '<p>';
//                $html .= 'Au terme de la période d\'utilisation optimale, le client a le choix :';
//                $html .= '</p>';
//
//                $html .= '<ul>';
//                $html .= '<li>de faire évoluer sa configuration avec LDLC.PRO LEASE (contrat "annule et remplace"),</li>';
//                $html .= '<li>ou poursuivre la location avec un loyer réduit pendant les 12 derniers mois du contrat.</li>';
//                $html .= '</ul>';
//
//                $html .= '<p>';
//                $html .= 'Les contrats sont donc établis dans ce cas avec les paramètres suivants :';
//                $html .= '</p>';
//
//                $html .= '<ul>';
//                $html .= '<li>1ère période de mise à disposition : le loyer lié aux matériels est calculé avec un <b>taux à 0%</b> sur cette durée,</li>';
//                $html .= '<li>une 2ème période de 12 mois complémentaires, avec un loyer réduit.</li>';
//                $html .= '</ul>';
//
//                $html .= '<p>';
//                $html .= 'Cette offre permet donc en plus des avantages déjà évoqués pour l\'offre "Formule EVOLUTIVE", et <b>à coût global équivalent</b>, de :';
//                $html .= '</p>';
//                $html .= '<ul>';
//                $html .= '<li>choisir le terme de renouvellement du parc matériel en optimisant les performances (gestion dynamique)</li>';
//                $html .= '<li>garantir le suivi des évolutions technologiques</li>';
//                $html .= '<li>réduire le montant des loyers, donc le coût d\'exploitation des matériels pour la période optimale</li>';
//                $html .= '</ul>';

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
                $html .= ($mode_calcul ? 'terme à échoir' : 'à terme échu') . '.';
                $html .= '</p>';
                $html .= '</div>';
                $this->writeFullBlock($html);
                $html = '';
            }
        }

        if ($has_evo && $has_dyn) {
            $eco = ($loyer_evo_ht * $nb_loyers) - ($loyer_dyn_ht * $nb_loyers);
            $eco_percent = 0;
            if ($total_demande) {
                $eco_percent = $eco / $total_demande * 100;
            }

            $html .= '<div style="font-size: 10px; font-weight: bold; color: #' . $this->primary . '">Comparatif</div>';

            $html .= '<div style="font-size: 8px">';
            $html .= '<table cellpadding="3px">';
            $html .= '<thead>';
            $html .= '<tr>';
            $html .= '<th style="background-color: #' . $this->primary . '; color: #fff;">Formule</th>';
            $html .= '<th style="background-color: #' . $this->primary . '; color: #fff;">Loyer ' . BFTools::$periodicities_masc[$periodicity] . '</th>';
            $html .= '<th style="background-color: #' . $this->primary . '; color: #fff;">Coût à ' . $nb_mois . ' mois*</th>';
            $html .= '</tr>';
            $html .= '</thead>';
            $html .= '<tbody>';
            $html .= '<tr>';
            $html .= '<td style="background-color: #F2F2F2"><b>Evolutive</b></td>';
            $html .= '<td style="background-color: #F2F2F2">' . BimpTools::displayMoneyValue($loyer_evo_ht) . '</td>';
            $html .= '<td style="background-color: #F2F2F2"><b>' . BimpTools::displayMoneyValue($loyer_evo_ht * $nb_loyers) . '</b></td>';
            $html .= '</tr>';

            $html .= '<tr>';
            $html .= '<td style="background-color: #F2F2F2"><b>Dynamique</b></td>';
            $html .= '<td style="background-color: #F2F2F2">' . BimpTools::displayMoneyValue($loyer_dyn_ht) . '</td>';
            $html .= '<td style="background-color: #F2F2F2"><b>';
            $html .= BimpTools::displayMoneyValue($loyer_dyn_ht * $nb_loyers);
            $html .= '</b><br/>Soit une économie de <b>' . BimpTools::displayMoneyValue($eco) . '</b>';
            $html .= ($eco_percent ? ' (' . BimpTools::displayFloatValue($eco_percent) . ' %)' : '');
            $html .= '</td>';
            $html .= '</tr>';
            $html .= '</tbody>';
            $html .= '</table>';
            $html .= '<p style="font-style: italic; font-size: 7px">*Frais financiers inclus</p>';
            $html .= '</div>';
        }

        $html .= '<p style="font-size: 8px; font-weight: bold; text-align: center">';
        $html .= 'L\'ensemble de nos propositions est soumis à l\'acceptation de notre comité des engagements';
        $html .= '</p>';

        $html .= '<p style="font-size: 8px;">';
        $html .= 'Nous sommes à votre entière disposition pour tout complément d\'information, et vous prions d’agréer, ';
        $html .= 'Madame, Monsieur, l\'expression de nos meilleures salutations.';
        $html .= '</p>';

        $this->writeFullBlock($html);
    }
}
