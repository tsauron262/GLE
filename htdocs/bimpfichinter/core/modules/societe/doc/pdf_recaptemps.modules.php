<?php

/* Copyright (C) 2003		Rodolphe Quiedeville		<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010	Laurent Destailleur			<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin				<regis.houssin@capnetworks.com>
 * Copyright (C) 2008		Raphael Bertrand (Resultic)	<raphael.bertrand@resultic.fr>
 * Copyright (C) 2011		Fabrice CHERRIER
 * Copyright (C) 2013		Cédric Salvador				<csalvador@gpcsolutions.fr>
 * Copyright (C) 2015       Marcos García               <marcosgdf@gmail.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

/**
 * 	\file       htdocs/bimpfichinter/core/modules/fichinter/doc/pdf_recaptemps.modules.php
 * 	\ingroup    bimpfichinter
 * 	\brief      Fichier de la classe permettant de generer les fiches d'intervention au modele recaptemps
 */
require_once DOL_DOCUMENT_ROOT . '/core/modules/fichinter/modules_fichinter.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
require_once DOL_DOCUMENT_ROOT . '/contrat/class/contrat.class.php';
require_once DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT . '/includes/tecnickcom/tcpdf/tcpdf.php';

/**
 * 	Class to build interventions documents with model Recaptemps
 */
class pdf_recaptemps extends ModelePDFFicheinter {

    var $db;
    var $name;
    var $description;
    var $type;
    var $phpmin = array(4, 3, 0); // Minimum version of PHP required by module
    var $version = 'dolibarr';
    var $page_largeur;
    var $page_hauteur;
    var $format;
    var $marge_gauche;
    var $marge_droite;
    var $marge_haute;
    var $marge_basse;

    /**
     * 	Constructor
     *
     *  @param		DoliDB		$db      Database handler
     */
    function __construct($db) {
        global $conf, $langs, $mysoc;

        $this->db = $db;
        $this->name = 'Recaptemps';
        $this->description = $langs->trans("DocumentModelStandardPDF");

        // Dimension page pour format A4
        $this->type = 'pdf';
        $formatarray = pdf_getFormat();
        $this->page_largeur = $formatarray['height'];
        $this->page_hauteur = $formatarray['width'];
        $this->format = array($this->page_largeur, $this->page_hauteur);
        $this->marge_gauche = isset($conf->global->MAIN_PDF_MARGIN_LEFT) ? $conf->global->MAIN_PDF_MARGIN_LEFT : 10;
        $this->marge_droite = isset($conf->global->MAIN_PDF_MARGIN_RIGHT) ? $conf->global->MAIN_PDF_MARGIN_RIGHT : 10;
        $this->marge_haute = isset($conf->global->MAIN_PDF_MARGIN_TOP) ? $conf->global->MAIN_PDF_MARGIN_TOP : 10;
        $this->marge_basse = isset($conf->global->MAIN_PDF_MARGIN_BOTTOM) ? $conf->global->MAIN_PDF_MARGIN_BOTTOM : 10;

        $this->option_logo = 0;                    // Affiche logo
        $this->option_tva = 0;                     // Gere option tva FACTURE_TVAOPTION
        $this->option_modereg = 0;                 // Affiche mode reglement
        $this->option_condreg = 0;                 // Affiche conditions reglement
        $this->option_codeproduitservice = 0;      // Affiche code produit-service
        $this->option_multilang = 1;               // Dispo en plusieurs langues
        $this->option_draft_watermark = 1;     //Support add of a watermark on drafts
        // Get source company
        $this->emetteur = $mysoc;
        if (empty($this->emetteur->country_code))
            $this->emetteur->country_code = substr($langs->defaultlang, -2);    // By default, if not defined





            
// Define position of columns
        $this->posxdesc = $this->marge_gauche + 1;
    }

    /**
     *  Function to build pdf onto disk
     *
     *  @param		Object			$object				Object to generate
     *  @param		Translate		$outputlangs		Lang output object
     *  @param		string			$srctemplatepath	Full path of source filename for generator using a template file
     *  @param		int				$hidedetails		Do not show line details
     *  @param		int				$hidedesc			Do not show desc
     *  @param		int				$hideref			Do not show ref
     *  @return		int									1=OK, 0=KO
     */
    function write_file($object, $outputlangs, $srctemplatepath = '', $hidedetails = 0, $hidedesc = 0, $hideref = 0) {

        global $user, $langs, $conf, $mysoc, $db, $hookmanager;

        if (!is_object($outputlangs))
            $outputlangs = $langs;
        // For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
        if (!empty($conf->global->MAIN_USE_FPDF))
            $outputlangs->charset_output = 'ISO-8859-1';

        // Translations
        $outputlangs->loadLangs(array("main", "interventions", "dict", "companies"));


        if ($conf->societe->dir_output) {
            $object->fetch_thirdparty();

            // Definition of $dir and $file
            if ($object->specimen) {
                $dir = $conf->societe->dir_output;
                $file = $dir . "/SPECIMEN.pdf";
            } else {
                $objectref = "recaptemps-" . dol_sanitizeFileName($object->code_client);
                $dir = $conf->societe->dir_output . "/" . $object->ref;
                $file = $dir . "/" . $objectref . ".pdf";
            }

            if (!file_exists($dir)) {
                if (dol_mkdir($dir) < 0) {
                    $this->error = $langs->transnoentities("ErrorCanNotCreateDir", $dir);
                    return 0;
                }
            }

            if (file_exists($dir)) {
                // Add pdfgeneration hook
                if (!is_object($hookmanager)) {
                    include_once DOL_DOCUMENT_ROOT . '/core/class/hookmanager.class.php';
                    $hookmanager = new HookManager($this->db);
                }

                $hookmanager->initHooks(array('pdfgeneration'));
                $parameters = array('file' => $file, 'object' => $object, 'outputlangs' => $outputlangs);
                global $action;
                $reshook = $hookmanager->executeHooks('beforePDFCreation', $parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks

                $nblignes = count($object->lines);

                $pdfa = false;
                if (!empty($conf->global->PDF_USE_1A))
                    $pdfa = true;

//	 * @param $ln (string) header image logo
//	 * @param $lw (string) header image logo width in mm
//	 * @param $ht (string) string to print as title on document header
//	 * @param $hs (string) string to print on document header
//	 * @param $tc (array) RGB array color for text.
//	 * @param $lc (array) RGB array color for line.
                // Create pdf instance
                $pdf = new CustomPDF('L', 'mm', $this->format, true, 'UTF-8', false, $pdfa);
                $pdf->setHeaderData($ln = '', $lw = 20, $ht = 'dzadzaddz', $hs = '<table cellspacing="0" cellpadding="1" border="1">tr><td rowspan="3">test</td><td>test</td></tr></table>', $tc = array(125, 120, 120), $lc = array(120, 120, 120));

//                $pdf = pdf_getInstance($this->format);
                $default_font_size = pdf_getPDFFontSize($outputlangs); // Must be after pdf_getInstance
                $heightforinfotot = 50; // Height reserved to output the info and total part
                $heightforfreetext = (isset($conf->global->MAIN_PDF_FREETEXT_HEIGHT) ? $conf->global->MAIN_PDF_FREETEXT_HEIGHT : 5); // Height reserved to output the free text on last page
                $heightforfooter = $this->marge_basse + 8; // Height reserved to output the footer (value include bottom margin)
                $pdf->SetAutoPageBreak(1, 0);

                if (class_exists('TCPDF')) {
                    $pdf->setPrintHeader(false);
                    $pdf->setPrintFooter(false);
                }
                $pdf->SetFont(pdf_getPDFFont($outputlangs));
                // Set path to the background PDF File
                if (!empty($conf->global->MAIN_ADD_PDF_BACKGROUND)) {
                    $pagecount = $pdf->setSourceFile($conf->mycompany->dir_output . '/' . $conf->global->MAIN_ADD_PDF_BACKGROUND);
                    $tplidx = $pdf->importPage(1);
                }


                $pdf->Open();
                $pagenb = 0;
                $pdf->SetDrawColor(128, 128, 128);

                $pdf->SetTitle($outputlangs->convToOutputCharset($object->ref));
                $pdf->SetSubject($outputlangs->transnoentities("InterventionCard"));
                $pdf->SetCreator("Dolibarr " . DOL_VERSION);
                $pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
                $pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref) . " " . $outputlangs->transnoentities("InterventionCard"));
                if (!empty($conf->global->MAIN_DISABLE_PDF_COMPRESSION))
                    $pdf->SetCompression(false);

                $pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right
                // New page
                $pdf->AddPage('L');
                if (!empty($tplidx))
                    $pdf->useTemplate($tplidx);
                $pagenb++;
//                $this->setHeader($pdf, $object, 1, $outputlangs); // TODO
                $pdf->SetFont('', '', $default_font_size - 1);
                $pdf->SetTextColor(0, 0, 0);

                $tab_top = 10;
                $tab_top_newpage = (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD) ? 42 : 10);
                $tab_height = 130;
                $tab_height_newpage = 150;

                // Affiche notes
                $notetoshow = empty($object->note_public) ? '' : $object->note_public;
                if ($notetoshow) {
                    $substitutionarray = pdf_getSubstitutionArray($outputlangs, null, $object);
                    complete_substitutions_array($substitutionarray, $outputlangs, $object);
                    $notetoshow = make_substitutions($notetoshow, $substitutionarray, $outputlangs);

                    $tab_top = 88;

                    $pdf->SetFont('', '', $default_font_size - 1);
                    $pdf->writeHTMLCell(190, 3, $this->posxdesc - 1, $tab_top, dol_htmlentitiesbr($notetoshow), 0, 1);
                    $nexY = $pdf->GetY();
                    $height_note = $nexY - $tab_top;

                    // Rect prend une longueur en 3eme param
                    $pdf->SetDrawColor(192, 192, 192);
                    $pdf->Rect($this->marge_gauche, $tab_top - 1, $this->page_largeur - $this->marge_gauche - $this->marge_droite, $height_note + 1);

                    $tab_height = $tab_height - $height_note;
                    $tab_top = $nexY + 6;
                } else {
                    $height_note = 0;
                }

                $width_table = $this->page_largeur - $this->marge_gauche - $this->marge_droite;

                // Bimp
                // Check if extrafield "duree_h" exists
                $sql = 'SELECT rowid';
                $sql .= ' FROM ' . MAIN_DB_PREFIX . 'extrafields';
                $sql .= ' WHERE name="duree_h"';
                $resql = $this->db->query($sql);

                if ($resql) {
                    $result = $this->db->fetch_array($resql);
                    if ($result == NULL)
                        setEventMessage("Le champs supplémentaire \"Durée en heure n'est pas activé\"", 'warnings');
                }

                $pdf->SetFont('', '', 12);

                $fichinter_obj = BimpObject::getInstance("bimpfichinter", "Bimp_Fichinter");
                $fichinters = $fichinter_obj->getList(array('fk_soc' => $object->id));

                // sql = SELECT * FROM llx_synopsis_fichinter a WHERE a.fk_soc = 8627 ORDER BY a.rowid DESC

                $societe = new Societe($this->db);
                $societe->fetch($object->id);


                $contrats = $commandes = $libres = array();


                foreach ($fichinters as $fichinter) {
                    $date_split = explode('-', $fichinter['datei']);
                    $year_creation = $date_split[0];
//                    if ($year_creation != date("Y")) // TODO reset this filter
//                        continue;

                    $fk_contrat = $fichinter['fk_contrat'];
                    $fk_commande = $fichinter['fk_commande'];
                    if ($fk_contrat > 0) {
                        $contrats[$fk_contrat][] = $fichinter;
                    } elseif ($fk_commande > 0) {
                        $commandes[$fk_commande][] = $fichinter;
                    } else {
                        $libres[] = $fichinter;
                    }
                }

                $head_css = $this->getTableCss();

                $this->intervenants = array();

                // Contrats
                if (sizeof($contrats) != 0) {
                    $html = $head_css . $this->getTableContrats($contrats, $societe->nom);
                    $pdf->writeHTML($html, true, false, true, false, '');
                } else {
                    $html = '<h3>Aucune intervention liée à un contrat pour ce tier en ' . date("Y") . '</h3>';
                    $pdf->writeHTML($html, true, false, true, false, '');
                }

                // Commandes
                if (sizeof($contrats) != 0) {
                    $pdf->AddPage('', '', true);
                    $html = $head_css . $this->getTableCommandes($commandes, $societe->nom);
                    $pdf->writeHTML($html, true, false, true, false, '');
                } else {
                    $html = '<h3>Aucune intervention liée à une commande pour ce tier en ' . date("Y") . '</h3>';
                    $pdf->writeHTML($html, true, false, true, false, '');
                }

                // Libres
                if (sizeof($contrats) != 0) {
                    $pdf->AddPage('', '', true);
                    $html = $head_css . $this->getTableLibres($libres, $societe->nom);
                    $pdf->writeHTML($html, true, false, true, false, '');
                } else {
                    $html = '<h3>Aucune intervention libre (sans contrat ou commande) pour ce tier en ' . date("Y") . '</h3>';
                    $pdf->writeHTML($html, true, false, true, false, '');
                }

                $iniY = $tab_top + 7;
                $curY = $tab_top + 7;
                $nexY = $tab_top + 7;

                $pdf->SetXY($this->marge_gauche, $tab_top);
                $pdf->SetFont('', '', $default_font_size - 1);

                $pdf->SetXY($this->marge_gauche, $tab_top + 5);
                $text = $object->description;
                if ($object->duration > 0) {
                    $totaltime = convertSecondToTime($object->duration, 'all', $conf->global->MAIN_DURATION_OF_WORKDAY);
                    $text .= ($text ? ' - ' : '') . $langs->trans("Total") . ": " . $totaltime;
                }
                $desc = dol_htmlentitiesbr($text, 1);


                $nexY = $pdf->GetY();

                // Show square
                if ($pagenb == 1) {
                    $this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforinfotot - $heightforfreetext - $heightforfooter, 0, $outputlangs, 0, 0);
                    $bottomlasttab = $this->page_hauteur - $heightforinfotot - $heightforfreetext - $heightforfooter + 1;
                } else {
                    $this->_tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforinfotot - $heightforfreetext - $heightforfooter, 0, $outputlangs, 1, 0);
                    $bottomlasttab = $this->page_hauteur - $heightforinfotot - $heightforfreetext - $heightforfooter + 1;
                }

                $this->_pagefoot($pdf, $object, $outputlangs);
                if (method_exists($pdf, 'AliasNbPages'))
                    $pdf->AliasNbPages();

                $pdf->Close();
                $pdf->Output($file, 'F');

                // Add pdfgeneration hook
                $hookmanager->initHooks(array('pdfgeneration'));
                $parameters = array('file' => $file, 'object' => $object, 'outputlangs' => $outputlangs);
                global $action;
                $reshook = $hookmanager->executeHooks('afterPDFCreation', $parameters, $this, $action);    // Note that $action and $object may have been modified by some hooks

                if (!empty($conf->global->MAIN_UMASK))
                    @chmod($file, octdec($conf->global->MAIN_UMASK));

                $this->result = array('fullpath' => $file);

                return 1;
            }
            else {
                $this->error = $langs->trans("ErrorCanNotCreateDir", $dir);
                return 0;
            }
        } else {
            $this->error = $langs->trans("ErrorConstantNotDefined", "FICHEINTER_OUTPUTDIR");
            return 0;
        }
    }

    function getTableCss() {
        $head_css = '<head>
<style type="text/css">

    tr.title th {
        height: 15px;
        line-height:10px;
    }
    .orange {
        background-color: #f8cbad;
    }
    .blue {
        background-color: #b4c6e7;
    }
    .grey {
        background-color: #e7e6e6;
    }
    .greyDisabled {
        background-color: #a8a4a4;
    }
    .green {
        background-color: #c6e0b4;
    }
    
    table > tr > th, table > tr > td {
        border: 0.5px solid black;
        text-align: center;
        padding: 25px;
        font-size: 8px; 
    }
    .white {
        border: none;
    }
}

</style>
</head>';
        return $head_css;
    }

    function getTableContrats($contrats, $societe_name) {
        $html = '<table>';
        $html .= '<tr class="orange title">';
        $html .= '<th colspan="10"><strong>INTERVENTIONS CONTRATS ' . $societe_name . '</strong></th>';
        $html .= '</tr>';
        $html .= '<tr class="blue title">';
        $html .= '<th><strong>TITRE</strong></th>';
        $html .= '<th><strong>N°CONTRAT</strong></th>';
        $html .= '<th><strong>DATE CONTRAT</strong></th>';
        $html .= '<th><strong>NOMBRE DE JOURNÉE COMMANDÉE</strong></th>';
        $html .= '<th><strong>N°FICHE D\'INTERVENTION (FI)</strong></th>';
        $html .= '<th><strong>DATE FI</strong></th>';
        $html .= '<th><strong>INTERVENANT</strong></th>';
        $html .= '<th><strong>TEMPS ESTIMÉ/JOUR</strong></th>';
        $html .= '<th><strong>TEMPS RÉEL CONSOMMÉ</strong></th>';
        $html .= '<th><strong>SOLDE</strong></th>';

        $html .= '</tr>';
        $solde_total = 0;
        $planned_day_total = 0;
        $used_total = 0;
        $hour_total = 0;
        $cnt_contrat = 0;
        $hours_per_day = 7 * 3600;

        foreach ($contrats as $id_contrat => $inters) {
            $planned_day = 0;

            $contrat = new Contrat($this->db);
            $contrat->fetch($id_contrat);
            $contrat->fetch_lines();
            foreach ($contrat->lines as $line) {
                $product = new Product($this->db);
                $product->fetch($line->fk_product);
                if ($product->array_options['options_duree_h'] != NULL) {
                    $planned_day += $line->qty * $product->array_options['options_duree_h'] / 7;
                }
            }

            $used = 0;
            ++$cnt_contrat;
            $planned_day_total += $planned_day;
            $solde_init = $solde = $planned_day * $hours_per_day;
            $hour_total += $solde_init;

            $html .= '<tr class="grey title">';
            $html .= '<th></th>';
            $html .= '<th><strong>' . $contrat->ref . '</strong></th>';
            $html .= '<th><strong>' . date('d/m/Y', $contrat->date_contrat) . '</strong></th>';
            $html .= '<th><strong>' . $planned_day . '</strong></th>';
            $html .= '<th></th>';
            $html .= '<th></th>';
            $html .= '<th></th>';
            $html .= '<th><strong>' . $this->getTime($hours_per_day) . '</strong></th>';
            $html .= '<th></th>';
            $html .= '<th><strong>' . $this->getTime($solde_init) . '</strong></th>';
            $html .= '</tr>';


            foreach ($inters as $inter) {
                $fk_user = $inter['fk_user_author'];
                if (!isset($this->intervenants[$fk_user])) {
                    $user = new User($this->db);
                    $user->fetch($fk_user);
                    $intervenant[$fk_user] = $user;
                }

                $used += $inter['duree'];
                $solde -= $inter['duree'];
                $html .= '<tr>';
                $html .= '<td>' . $inter['description'] . '</td>';
                $html .= '<td></td>';
                $html .= '<td></td>';
                $html .= '<td></td>';
                $html .= '<td>' . $inter['ref'] . '</td>';
                $html .= '<td>' . $this->getDate($inter['datei']) . '</td>';
                $html .= '<td>' . $intervenant[$fk_user]->firstname . ' ' . $intervenant[$fk_user]->lastname . '</td>';
                $html .= '<td></td>';
                $html .= '<td>' . $this->getTime($inter['duree']) . '</td>';
                $html .= '<td>' . $this->getTime($solde) . '</td>';
                $html .= '</tr>';
            }
            $solde_total += $solde;
            $used_total += $used;
            // Solde
            $html .= '<tr>';
            $html .= '<td class="greyDisabled"></td>';
            $html .= '<td class="greyDisabled"></td>';
            $html .= '<td class="greyDisabled"></td>';
            $html .= '<td class="greyDisabled"></td>';
            $html .= '<td class="greyDisabled"></td>';
            $html .= '<td class="greyDisabled"></td>';
            $html .= '<td class="greyDisabled"></td>';
            $html .= '<td class="greyDisabled"></td>';
            $html .= '<td class="green"><strong>SOLDE ' . $cnt_contrat . ' </strong></td>';
            $html .= '<td class="green"><strong>' . $this->getTime($solde) . '</strong></td>';
            $html .= '</tr>';

            // Used
            $html .= '<tr>';
            $html .= '<td class="greyDisabled"></td>';
            $html .= '<td class="greyDisabled"></td>';
            $html .= '<td class="greyDisabled"></td>';
            $html .= '<td class="greyDisabled"></td>';
            $html .= '<td class="greyDisabled"></td>';
            $html .= '<td class="greyDisabled"></td>';
            $html .= '<td class="greyDisabled"></td>';
            $html .= '<td class="greyDisabled"></td>';
            $html .= '<td class="orange"><strong>CONSOMMÉ ' . $cnt_contrat . '</strong></td>';
            $html .= '<td class="orange"><strong>' . $this->getTime($used) . '</strong></td>';
            $html .= '</tr>';
        }
        $contrat_number = '';
        for ($i = 1; $i <= $cnt_contrat; $i++)
            $contrat_number .= $i . '-';
        $contrat_number = substr($contrat_number, 0, -1);

        // Total planned hour
        $html .= '<tr>';
        $html .= '<td style="text-align: right;" colspan="3" class="grey"><strong>TOTAL DES JOURNEES DE DELEGATION </strong><img src="' . DOL_DOCUMENT_ROOT . '/bimpfichinter/img/blanc.png" width="5px" height="5px"/></td>';
        $html .= '<td class="grey"><strong>' . $planned_day_total . '</strong></td>';
        $html .= '<td class="grey"></td>';
        $html .= '<td class="grey"></td>';
        $html .= '<td class="grey"></td>';
        $html .= '<td class="grey"><strong></strong></td>';
        $html .= '<td class="grey"><strong>TOTAL HEURES ' . mb_strimwidth($contrat_number, 0, 15, "...") . '</strong></td>';
        $html .= '<td class="grey"><strong>' . $this->getTime($hour_total) . '</strong></td>';
        $html .= '</tr>';

        // Total used
        $html .= '<tr>';
        $html .= '<td class="white"></td>';
        $html .= '<td class="white"></td>';
        $html .= '<td class="white"></td>';
        $html .= '<td class="white"></td>';
        $html .= '<td class="white"></td>';
        $html .= '<td class="white"></td>';
        $html .= '<td class="white"></td>';
        $html .= '<td class="white"></td>';
        $html .= '<td class="orange"><strong>CONSOMMÉ ' . mb_strimwidth($contrat_number, 0, 15, "...") . '</strong></td>';
        $html .= '<td class="orange"><strong>' . $this->getTime($used_total) . '</strong></td>';
        $html .= '</tr>';

        // Total solde
        $html .= '<tr>';
        $html .= '<td class="white"></td>';
        $html .= '<td class="white"></td>';
        $html .= '<td class="white"></td>';
        $html .= '<td class="white"></td>';
        $html .= '<td class="white"></td>';
        $html .= '<td class="white"></td>';
        $html .= '<td class="white"></td>';
        $html .= '<td class="white"></td>';
        $html .= '<td class="green"><strong>SOLDE TOTAL ' . mb_strimwidth($contrat_number, 0, 15, "...") . '</strong></td>';
        $html .= '<td class="green"><strong>' . $this->getTime($solde_total) . '</strong></td>';
        $html .= '</tr>';

        $html .= '</table>';
        return $html;
    }

    function getTableCommandes($commandes, $societe_name) {
        $html = '<table>';
        $html .= '<tr class="orange title">';
        $html .= '<th colspan="10"><strong>INTERVENTIONS COMMANDES ' . $societe_name . '</strong></th>';
        $html .= '</tr>';
        $html .= '<tr class="blue title">';
        $html .= '<th><strong>TITRE</strong></th>';
        $html .= '<th><strong>N°COMMANDE</strong></th>';
        $html .= '<th><strong>DATE COMMANDE</strong></th>';
        $html .= '<th><strong>NOMBRE DE JOURNÉE COMMANDÉE</strong></th>';
        $html .= '<th><strong>N°FICHE D\'INTERVENTION (FI)</strong></th>';
        $html .= '<th><strong>DATE FI</strong></th>';
        $html .= '<th><strong>INTERVENANT</strong></th>';
        $html .= '<th><strong>TEMPS ESTIMÉ/JOUR</strong></th>';
        $html .= '<th><strong>TEMPS RÉEL CONSOMMÉ</strong></th>';
        $html .= '<th><strong>SOLDE</strong></th>';

        $html .= '</tr>';
        $solde_total = 0;
        $planned_day_total = 0;
        $used_total = 0;
        $hour_total = 0;
        $cnt_commande = 0;
        $hours_per_day = 7 * 3600;

        foreach ($commandes as $id_commande => $inters) {
            $used = 0;
            ++$cnt_commande;
            $planned_day = 0;


            $commande = new Commande($this->db);
            $commande->fetch($id_commande);
            $commande->fetch_lines();
            foreach ($commande->lines as $line) {
                $product = new Product($this->db);
                $product->fetch($line->fk_product);
                if ($product->array_options['options_duree_h'] != NULL) {
                    $planned_day += $line->qty * $product->array_options['options_duree_h'] / 7;
                }
            }
            $planned_day_total += $planned_day;
            $solde_init = $solde = $planned_day * $hours_per_day;
            $hour_total += $solde_init;

            $html .= '<tr class="grey title">';
            $html .= '<th></th>';
            $html .= '<th><strong>' . $commande->ref . '</strong></th>';
            $html .= '<th><strong>' . date('d/m/Y', $commande->date) . '</strong></th>';
            $html .= '<th><strong>' . $planned_day . '</strong></th>';
            $html .= '<th></th>';
            $html .= '<th></th>';
            $html .= '<th></th>';
            $html .= '<th><strong>' . $this->getTime($hours_per_day) . '</strong></th>';
            $html .= '<th></th>';
            $html .= '<th><strong>' . $this->getTime($solde_init) . '</strong></th>';
            $html .= '</tr>';

            foreach ($inters as $inter) {
                $fk_user = $inter['fk_user_author'];
                if (!isset($this->intervenants[$fk_user])) {
                    $user = new User($this->db);
                    $user->fetch($fk_user);
                    $intervenant[$fk_user] = $user;
                }

                $used += $inter['duree'];
                $solde -= $inter['duree'];
                $html .= '<tr>';
                $html .= '<td>' . $inter['description'] . '</td>';
                $html .= '<td></td>';
                $html .= '<td></td>';
                $html .= '<td></td>';
                $html .= '<td>' . $inter['ref'] . '</td>';
                $html .= '<td>' . $this->getDate($inter['datei']) . '</td>';
                $html .= '<td>' . $intervenant[$fk_user]->firstname . ' ' . $intervenant[$fk_user]->lastname . '</td>';
                $html .= '<td></td>';
                $html .= '<td>' . $this->getTime($inter['duree']) . '</td>';
                $html .= '<td>' . $this->getTime($solde) . '</td>';
                $html .= '</tr>';
            }
            $solde_total += $solde;
            $used_total += $used;

            // Solde
            $html .= '<tr>';
            $html .= '<td class="greyDisabled"></td>';
            $html .= '<td class="greyDisabled"></td>';
            $html .= '<td class="greyDisabled"></td>';
            $html .= '<td class="greyDisabled"></td>';
            $html .= '<td class="greyDisabled"></td>';
            $html .= '<td class="greyDisabled"></td>';
            $html .= '<td class="greyDisabled"></td>';
            $html .= '<td class="greyDisabled"></td>';
            $html .= '<td class="green"><strong>SOLDE ' . $cnt_commande . ' </strong></td>';
            $html .= '<td class="green"><strong>' . $this->getTime($solde) . '</strong></td>';
            $html .= '</tr>';

            // Used
            $html .= '<tr>';
            $html .= '<td class="greyDisabled"></td>';
            $html .= '<td class="greyDisabled"></td>';
            $html .= '<td class="greyDisabled"></td>';
            $html .= '<td class="greyDisabled"></td>';
            $html .= '<td class="greyDisabled"></td>';
            $html .= '<td class="greyDisabled"></td>';
            $html .= '<td class="greyDisabled"></td>';
            $html .= '<td class="greyDisabled"></td>';
            $html .= '<td class="orange"><strong>CONSOMMÉ ' . $cnt_commande . '</strong></td>';
            $html .= '<td class="orange"><strong>' . $this->getTime($used) . '</strong></td>';
            $html .= '</tr>';
        }
        $commande_number = '';
        for ($i = 1; $i <= $cnt_commande; $i++)
            $commande_number .= $i . '-';
        $commande_number = substr($commande_number, 0, -1);

        // Total planned hour
        $html .= '<tr>';
        $html .= '<td style="text-align: right;" colspan="3" class="grey"><strong>TOTAL DES JOURNEES DE DELEGATION </strong><img src="' . DOL_DOCUMENT_ROOT . '/bimpfichinter/img/blanc.png" width="5px" height="5px"/></td>';
        $html .= '<td class="grey"><strong>' . $planned_day_total . '</strong></td>';
        $html .= '<td class="grey"></td>';
        $html .= '<td class="grey"></td>';
        $html .= '<td class="grey"></td>';
        $html .= '<td class="grey"><strong></strong></td>';
        $html .= '<td class="grey"><strong>TOTAL HEURES ' . mb_strimwidth($commande_number, 0, 15, "...") . '</strong></td>';
        $html .= '<td class="grey"><strong>' . $this->getTime($hour_total) . '</strong></td>';
        $html .= '</tr>';

        // Total used
        $html .= '<tr>';
        $html .= '<td class="white"></td>';
        $html .= '<td class="white"></td>';
        $html .= '<td class="white"></td>';
        $html .= '<td class="white"></td>';
        $html .= '<td class="white"></td>';
        $html .= '<td class="white"></td>';
        $html .= '<td class="white"></td>';
        $html .= '<td class="white"></td>';
        $html .= '<td class="orange"><strong>CONSOMMÉ ' . mb_strimwidth($commande_number, 0, 15, "...") . '</strong></td>';
        $html .= '<td class="orange"><strong>' . $this->getTime($used_total) . '</strong></td>';
        $html .= '</tr>';

        // Total solde
        $html .= '<tr>';
        $html .= '<td class="white"></td>';
        $html .= '<td class="white"></td>';
        $html .= '<td class="white"></td>';
        $html .= '<td class="white"></td>';
        $html .= '<td class="white"></td>';
        $html .= '<td class="white"></td>';
        $html .= '<td class="white"></td>';
        $html .= '<td class="white"></td>';
        $html .= '<td class="green"><strong>SOLDE TOTAL ' . mb_strimwidth($commande_number, 0, 15, "...") . '</strong></td>';
        $html .= '<td class="green"><strong>' . $this->getTime($solde_total) . '</strong></td>';
        $html .= '</tr>';

        $html .= '</table>';
        return $html;
    }

    function getTableLibres($libres, $societe_name, $display_empty_column = false) {

        $html = '<table>';
        $html .= '<tr class="orange title">';
        if ($display_empty_column) {
            $html .= '<th colspan="10"><strong>INTERVENTIONS INDÉPENDANTES ' . $societe_name . '</strong></th>';
        } else {
            $html .= '<th colspan="8"><strong>INTERVENTIONS INDÉPENDANTES ' . $societe_name . '</strong></th>';
        }
        $html .= '</tr>';
        $html .= '<tr class="blue title">';
        $html .= '<th><strong>TITRE</strong></th>';
        if ($display_empty_column) {
            $html .= '<th><strong></strong></th>';
            $html .= '<th><strong></strong></th>';
        }
        $html .= '<th><strong>NOMBRE DE JOURNÉE COMMANDÉE</strong></th>';
        $html .= '<th><strong>N°FICHE D\'INTERVENTION (FI)</strong></th>';
        $html .= '<th><strong>DATE FI</strong></th>';
        $html .= '<th><strong>INTERVENANT</strong></th>';
        $html .= '<th><strong>TEMPS ESTIMÉ/JOUR</strong></th>';
        $html .= '<th><strong>TEMPS RÉEL CONSOMMÉ</strong></th>';
        $html .= '<th><strong>SOLDE</strong></th>';
        $html .= '</tr>';

        $solde_total = 0;
        $planned_day_total = 0;
        $used_total = 0;
        $hour_total = 0;
        $cnt_libre = 0;
        $hours_per_day = 7 * 3600;

        foreach ($libres as $inter) {

            $sql = BimpTools::getSqlSelect(array('fk_target'));
            $sql .= BimpTools::getSqlFrom('element_element');
            $sql .= BimpTools::getSqlWhere(array(
                        'fk_source' => (int) $inter['rowid'],
                        'sourcetype' => 'FI',
                        'targettype' => 'facture'));

            $bimp_db = new BimpDb($this->db);
            $rows = $bimp_db->executeS($sql);

            if (!empty($rows)) {
                $id_facture = $rows[0]->fk_target;
            }

            $used = 0;
            ++$cnt_libre;
            $facture = new Facture($this->db);
            $facture->fetch($id_facture);
            $facture->fetch_lines();
            foreach ($facture->lines as $line) {
//                echo 'id_facture ='.$id_facture.'<br/>';
                $product = new Product($this->db);
                $product->fetch($line->fk_product);
                if ($product->array_options['options_duree_h'] != NULL) {
                    $planned_day += $line->qty * $product->array_options['options_duree_h'] / 7;
                }
//                echo '<pre>';
//                print_r($product);
//                die();
            }
            $planned_day_total += $planned_day;
            $solde_init = $solde = $planned_day * $hours_per_day;
            $hour_total += $solde_init;

            $html .= '<tr class="grey title">';
            $html .= '<th></th>';
            if ($display_empty_column) {
                $html .= '<th><strong></strong></th>';
                $html .= '<th><strong></strong></th>';
            }
            $html .= '<th><strong>' . $planned_day . '</strong></th>';
            $html .= '<th></th>';
            $html .= '<th></th>';
            $html .= '<th></th>';
            $html .= '<th><strong>' . $this->getTime($hours_per_day) . '</strong></th>';
            $html .= '<th></th>';
            $html .= '<th><strong>' . $this->getTime($solde_init) . '</strong></th>';
            $html .= '</tr>';

            $fk_user = $inter['fk_user_author'];
            if (!isset($this->intervenants[$fk_user])) {
                $user = new User($this->db);
                $user->fetch($fk_user);
                $intervenant[$fk_user] = $user;
            }

            $used += $inter['duree'];
            $solde -= $inter['duree'];
            $html .= '<tr>';
            $html .= '<td>' . $inter['description'] . '</td>';
            if ($display_empty_column) {
                $html .= '<td></td>';
                $html .= '<td></td>';
            }
            $html .= '<td></td>';
            $html .= '<td>' . $inter['ref'] . '</td>';
            $html .= '<td>' . $this->getDate($inter['datei']) . '</td>';
            $html .= '<td>' . $intervenant[$fk_user]->firstname . ' ' . $intervenant[$fk_user]->lastname . '</td>';
            $html .= '<td></td>';
            $html .= '<td>' . $this->getTime($inter['duree']) . '</td>';
            $html .= '<td>' . $this->getTime($solde) . '</td>';
            $html .= '</tr>';

            $solde_total += $solde;
            $used_total += $used;
            // Solde
            $html .= '<tr>';
            $html .= '<td class="greyDisabled"></td>';
            if ($display_empty_column) {
                $html .= '<td class="greyDisabled"></td>';
                $html .= '<td class="greyDisabled"></td>';
            }
            $html .= '<td class="greyDisabled"></td>';
            $html .= '<td class="greyDisabled"></td>';
            $html .= '<td class="greyDisabled"></td>';
            $html .= '<td class="greyDisabled"></td>';
            $html .= '<td class="greyDisabled"></td>';
            $html .= '<td class="green"><strong>SOLDE ' . $cnt_libre . ' </strong></td>';
            $html .= '<td class="green"><strong>' . $this->getTime($solde) . '</strong></td>';
            $html .= '</tr>';

            // Used
            $html .= '<tr>';
            $html .= '<td class="greyDisabled"></td>';
            if ($display_empty_column) {
                $html .= '<td class="greyDisabled"></td>';
                $html .= '<td class="greyDisabled"></td>';
            }
            $html .= '<td class="greyDisabled"></td>';
            $html .= '<td class="greyDisabled"></td>';
            $html .= '<td class="greyDisabled"></td>';
            $html .= '<td class="greyDisabled"></td>';
            $html .= '<td class="greyDisabled"></td>';
            $html .= '<td class="orange"><strong>CONSOMMÉ ' . $cnt_libre . '</strong></td>';
            $html .= '<td class="orange"><strong>' . $this->getTime($used) . '</strong></td>';
            $html .= '</tr>';
        }
        $libres_number = '';
        for ($i = 1; $i <= $cnt_libre; $i++)
            $libres_number .= $i . '-';
        $libres_number = substr($libres_number, 0, -1);

        // Total planned hour
        $html .= '<tr>';
        if ($display_empty_column)
            $html .= '<td style="text-align: right;" colspan="3" class="grey"><strong>TOTAL DES JOURNEES DE DELEGATION </strong><img src="' . DOL_DOCUMENT_ROOT . '/bimpfichinter/img/blanc.png" width="5px" height="5px"/></td>';
        else
            $html .= '<td style="text-align: right;" class="grey"><strong>TOTAL DES JOURNEES DE DELEGATION </strong><img src="' . DOL_DOCUMENT_ROOT . '/bimpfichinter/img/blanc.png" width="5px" height="5px"/></td>';
        $html .= '<td class="grey"><strong>' . $planned_day_total . '</strong></td>';
        $html .= '<td class="grey"></td>';
        $html .= '<td class="grey"></td>';
        $html .= '<td class="grey"></td>';
        $html .= '<td class="grey"><strong></strong></td>';
        $html .= '<td class="grey"><strong>TOTAL HEURES ' . mb_strimwidth($libres_number, 0, 15, "...") . '</strong></td>';
        $html .= '<td class="grey"><strong>' . $this->getTime($hour_total) . '</strong></td>';
        $html .= '</tr>';

        // Total used
        $html .= '<tr>';
        $html .= '<td class="white"></td>';
        $html .= '<td class="white"></td>';
        $html .= '<td class="white"></td>';
        $html .= '<td class="white"></td>';
        $html .= '<td class="white"></td>';
        $html .= '<td class="white"></td>';
        $html .= '<td class="orange"><strong>CONSOMMÉ ' . mb_strimwidth($libres_number, 0, 15, "...") . '</strong></td>';
        $html .= '<td class="orange"><strong>' . $this->getTime($used_total) . '</strong></td>';
        $html .= '</tr>';

        // Total solde
        $html .= '<tr>';
        $html .= '<td class="white"></td>';
        $html .= '<td class="white"></td>';
        $html .= '<td class="white"></td>';
        $html .= '<td class="white"></td>';
        $html .= '<td class="white"></td>';
        $html .= '<td class="white"></td>';
        $html .= '<td class="green"><strong>SOLDE TOTAL ' . mb_strimwidth($libres_number, 0, 15, "...") . '</strong></td>';
        $html .= '<td class="green"><strong>' . $this->getTime($solde_total) . '</strong></td>';
        $html .= '</tr>';
        $html .= '</table>';
        
        return $html;
    }

    /**
     *   Show table for lines
     *
     *   @param		PDF			$pdf     		Object PDF
     *   @param		string		$tab_top		Top position of table
     *   @param		string		$tab_height		Height of table (rectangle)
     *   @param		int			$nexY			Y
     *   @param		Translate	$outputlangs	Langs object
     *   @param		int			$hidetop		Hide top bar of array
     *   @param		int			$hidebottom		Hide bottom bar of array
     *   @return	void
     */
    function _tableau(&$pdf, $tab_top, $tab_height, $nexY, $outputlangs, $hidetop = 0, $hidebottom = 0) {
        global $conf;

        $default_font_size = pdf_getPDFFontSize($outputlangs);
    }

    function getTime($duree) {
        $time_per_day = 24 * 3600;

        // Manage duration negative
        if ($duree < 0) {
            $prefix = '-';
            $duree *= -1;
        } else
            $prefix = '';

        // Define the output format
        if ($duree > 3599)
            $out = gmdate("H\Hi", $duree);
        else
            $out = gmdate("i\m", $duree);

        // Manage hours over 24
        if ($duree > $time_per_day) {
            $time_over_24 = $duree % $time_per_day;
            $days = (int) (($duree - $time_over_24) / $time_per_day);
            $array_date = explode('H', $out);
            $hours = (int) $array_date[0] + (int) $days * 24;
            $out = $hours . 'H' . $array_date[1];
        }

        // remove first 0 of hours if exists
        if (strpos($out, '0') === 0) // Require === instead of == to ignore false
            return $prefix . substr($out, 1);
        return $prefix . $out;
    }

    function getDate($date) {
        $date_obj = DateTime::createFromFormat('Y-m-d H:i:s', $date);
        if (!$date_obj)
            $date_obj = DateTime::createFromFormat('Y-m-d', $date);

        if (!$date_obj)
            return "Format date inconnu";

        return $date_obj->format('d/m/Y');
    }

    /**
     *  Show top header of page.
     *
     *  @param	PDF			$pdf     		Object PDF
     *  @param  Object		$object     	Object to show
     *  @param  int	    	$showaddress    0=no, 1=yes
     *  @param  Translate	$outputlangs	Object lang for output
     *  @return	void
     */
    function setHeader(&$pdf, $object, $showaddress, $outputlangs) {
        $pdf->MultiCell(50, 50, 'test');
//        global $conf, $langs;
//        $default_font_size = pdf_getPDFFontSize($outputlangs);
//
//        $outputlangs->load("main");
//        $outputlangs->load("dict");
//        $outputlangs->load("companies");
//        $outputlangs->load("interventions");
//
//        pdf_pagehead($pdf, $outputlangs, $this->page_hauteur);
//
//        //Affiche le filigrane brouillon - Print Draft Watermark
//        if ($object->statut == 0 && (!empty($conf->global->FICHINTER_DRAFT_WATERMARK))) {
//            pdf_watermark($pdf, $outputlangs, $this->page_hauteur, $this->page_largeur, 'mm', $conf->global->FICHINTER_DRAFT_WATERMARK);
//        }
//
//        //Prepare la suite
//        $pdf->SetTextColor(0, 0, 60);
//        $pdf->SetFont('', 'B', $default_font_size + 3);
//
//        $posx = $this->page_largeur - $this->marge_droite - 100;
//        $posy = $this->marge_haute;
//
//        $pdf->SetXY($this->marge_gauche, $posy);
//
//        // Logo
//        $logo = $conf->mycompany->dir_output . '/logos/' . $this->emetteur->logo;
//        if ($this->emetteur->logo) {
//            if (is_readable($logo)) {
//                $height = pdf_getHeightForLogo($logo);
//                $pdf->Image($logo, $this->marge_gauche, $posy, 0, $height); // width=0 (auto)
//            } else {
//                $pdf->SetTextColor(200, 0, 0);
//                $pdf->SetFont('', 'B', $default_font_size - 2);
//                $pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorLogoFileNotFound", $logo), 0, 'L');
//                $pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorGoToGlobalSetup"), 0, 'L');
//            }
//        } else {
//            $text = $this->emetteur->name;
//            $pdf->MultiCell(100, 4, $outputlangs->convToOutputCharset($text), 0, 'L');
//        }
//
//        $pdf->SetFont('', 'B', $default_font_size + 3);
//        $pdf->SetXY($posx, $posy);
//        $pdf->SetTextColor(0, 0, 60);
//        $title = $outputlangs->transnoentities("InterventionCard");
//        $pdf->MultiCell(100, 4, $title, '', 'R');
//
//        $pdf->SetFont('', 'B', $default_font_size + 2);
//
//        $posy += 5;
//        $pdf->SetXY($posx, $posy);
//        $pdf->SetTextColor(0, 0, 60);
//        $pdf->MultiCell(100, 4, $outputlangs->transnoentities("Ref") . " : " . $outputlangs->convToOutputCharset($object->ref), '', 'R');
//
//        $posy += 1;
//        $pdf->SetFont('', '', $default_font_size);
//
//        $posy += 4;
//        $pdf->SetXY($posx, $posy);
//        $pdf->SetTextColor(0, 0, 60);
//        $pdf->MultiCell(100, 3, $outputlangs->transnoentities("Date") . " : " . dol_print_date($object->datec, "day", false, $outputlangs, true), '', 'R');
//
//        if ($object->thirdparty->code_client) {
//            $posy += 4;
//            $pdf->SetXY($posx, $posy);
//            $pdf->SetTextColor(0, 0, 60);
//            $pdf->MultiCell(100, 3, $outputlangs->transnoentities("CustomerCode") . " : " . $outputlangs->transnoentities($object->thirdparty->code_client), '', 'R');
//        }
//
//        if ($showaddress) {
//            // Sender properties
//            $carac_emetteur = '';
//            // Add internal contact of proposal if defined
//            $arrayidcontact = $object->getIdContact('internal', 'INTERREPFOLL');
//            if (count($arrayidcontact) > 0) {
//                $object->fetch_user($arrayidcontact[0]);
//                $carac_emetteur .= ($carac_emetteur ? "\n" : '' ) . $outputlangs->transnoentities("Name") . ": " . $outputlangs->convToOutputCharset($object->user->getFullName($outputlangs)) . "\n";
//            }
//
//            $carac_emetteur .= pdf_build_address($outputlangs, $this->emetteur, $object->thirdparty, '', 0, 'source', $object);
//
//            // Show sender
//            $posy = 42;
//            $posx = $this->marge_gauche;
//            if (!empty($conf->global->MAIN_INVERT_SENDER_RECIPIENT))
//                $posx = $this->page_largeur - $this->marge_droite - 80;
//            $hautcadre = 40;
//
//            // Show sender frame
//            $pdf->SetTextColor(0, 0, 0);
//            $pdf->SetFont('', '', $default_font_size - 2);
//            $pdf->SetXY($posx, $posy - 5);
//            $pdf->SetXY($posx, $posy);
//            $pdf->SetFillColor(230, 230, 230);
//            $pdf->MultiCell(82, $hautcadre, "", 0, 'R', 1);
//
//            // Show sender name
//            $pdf->SetXY($posx + 2, $posy + 3);
//            $pdf->SetTextColor(0, 0, 60);
//            $pdf->SetFont('', 'B', $default_font_size);
//            $pdf->MultiCell(80, 3, $outputlangs->convToOutputCharset($this->emetteur->name), 0, 'L');
//            $posy = $pdf->getY();
//
//            // Show sender information
//            $pdf->SetFont('', '', $default_font_size - 1);
//            $pdf->SetXY($posx + 2, $posy);
//            $pdf->MultiCell(80, 4, $carac_emetteur, 0, 'L');
//
//
//            // If CUSTOMER contact defined, we use it
//            $usecontact = false;
//            $arrayidcontact = $object->getIdContact('external', 'CUSTOMER');
//            if (count($arrayidcontact) > 0) {
//                $usecontact = true;
//                $result = $object->fetch_contact($arrayidcontact[0]);
//            }
//
//            //Recipient name
//            // On peut utiliser le nom de la societe du contact
//            if ($usecontact && !empty($conf->global->MAIN_USE_COMPANY_NAME_OF_CONTACT)) {
//                $thirdparty = $object->contact;
//            } else {
//                $thirdparty = $object->thirdparty;
//            }
//
//            $carac_client_name = pdfBuildThirdpartyName($thirdparty, $outputlangs);
//
//            $carac_client = pdf_build_address($outputlangs, $this->emetteur, $object->thirdparty, (isset($object->contact) ? $object->contact : ''), $usecontact, 'target', $object);
//
//            // Show recipient
//            $widthrecbox = 100;
//            if ($this->page_largeur < 210)
//                $widthrecbox = 84; // To work with US executive format
//            $posy = 42;
//            $posx = $this->page_largeur - $this->marge_droite - $widthrecbox;
//            if (!empty($conf->global->MAIN_INVERT_SENDER_RECIPIENT))
//                $posx = $this->marge_gauche;
//
//            // Show recipient frame
//            $pdf->SetTextColor(0, 0, 0);
//            $pdf->SetFont('', '', $default_font_size - 2);
//            $pdf->SetXY($posx + 2, $posy - 5);
//            $pdf->Rect($posx, $posy, $widthrecbox, $hautcadre);
//            $pdf->SetTextColor(0, 0, 0);
//
//            // Show recipient name
//            $pdf->SetXY($posx + 2, $posy + 3);
//            $pdf->SetFont('', 'B', $default_font_size);
//            $pdf->MultiCell($widthrecbox, 4, $carac_client_name, 0, 'L');
//
//            $posy = $pdf->getY();
//
//            // Show recipient information
//            $pdf->SetFont('', '', $default_font_size - 1);
//            $pdf->SetXY($posx + 2, $posy);
//            $pdf->MultiCell($widthrecbox, 4, $carac_client, 0, 'L');
//        }
    }

    /**
     *   	Show footer of page. Need this->emetteur object
     *
     *   	@param	PDF			$pdf     			PDF
     * 		@param	Object		$object				Object to show
     *      @param	Translate	$outputlangs		Object lang for output
     *      @param	int			$hidefreetext		1=Hide free text
     *      @return	integer
     */
    function _pagefoot(&$pdf, $object, $outputlangs, $hidefreetext = 0) {
        
    }

}

class CustomPDF extends TCPDF {

    public function Header() {
        die('Test header perso');
        $this->MultiCell(50, 50, 'test header dans class custom');
    }

}
