<?php
require_once DOL_DOCUMENT_ROOT . '/bimpcore/classes/BimpModelNumRef.php';

/**
  \class      mod_chrono_serpentine
  \brief      Classe du modele de numerotation de reference de facture serpentine
 */
class SAV_ModelNumRef extends BimpModelNumRef
{

    public $version = '1.0';        // 'development', 'experimental', 'dolibarr'
    public $error = '';
    public $nom = 'serpentine';

    /**     \brief      Renvoi la description du modele de numerotation
     *      \return     string      Texte descripif
     */
    function info()
    {
        global $conf, $langs;

        $langs->load("bills");

        $form = new Form($this->db);

        $texte = $langs->trans('GenericNumRefModelDesc') . "<br>\n";
        $texte.= '<form action="' . $_SERVER["PHP_SELF"] . '" method="POST">';
        $texte.= '<input type="hidden" name="action" value="updateMask">';
        $texte.= '<input type="hidden" name="maskconst" value="CHRONO_SERPENTINE_MASK">';
        $texte.= '<table class="nobordernopadding" width="100%">';

        // Parametrage du prefix des factures
        $texte.= '<tr><td>' . $langs->trans("Mask") . ' (' . $langs->trans("InvoiceStandard") . '):</td>';
        $texte.= '<td align="right">' . $form->textwithtooltip('<input type="text" class="flat" size="24" name="maskchrono" value="' . $conf->global->CHRONO_SERPENTINE_MASK . '">', $langs->trans("GenericMaskCodes", $langs->transnoentities("contrat"), $langs->transnoentities("contrat"), $langs->transnoentities("contrat")), 1, 1) . '</td>';

        $texte.= '<td align="left" rowspan="2">&nbsp; <input type="submit" class="button" value="' . $langs->trans("Modify") . '" name="Button"></td>';

        $texte.= '</tr>';

        $texte.= '</table>';
        $texte.= '</form>';

        return $texte;
    }

    /**     \brief      Renvoi un exemple de numerotation
     *      \return     string      Example
     */
    function getExample($mask, $modelId)
    {
        global $conf, $langs, $mysoc;

        $obj = BimpObject::getInstance('bimpsupport', 'BS_SAV');
        $old_code_client = $mysoc->code_client;
        $mysoc->code_client = 'CCCCCCCCCC';
        $numExample = $this->getNextValue($mysoc, $obj, $mask);
        $mysoc->code_client = $old_code_client;
        if (!$numExample) {
            $numExample = $langs->trans('NotConfigured');
        }
        if ($numExample == "0") {
            $numExample = $langs->trans('Error');
        }
        return $numExample;
    }

    /**        \brief      Return next value
     *          \param      objsoc      Object third party
     *          \param      chrono        Object chrono
     *          \return     string      Value if OK, 0 if KO
     */
    function getNextValue($objsoc, $obj, $mask)
    {
        global $db, $conf;

        require_once(DOL_DOCUMENT_ROOT . "/core/lib/functions2.lib.php");
        // On defini critere recherche compteur
        if (!$mask) {
            $this->error = 'NotConfigured';
            return 0;
        }
        $maskTmp = "{00000}";
        if (stripos($mask, $maskTmp) > 0)
            $where = " AND `ref` REGEXP '" . str_replace($maskTmp, "[0-9]+", $mask) . "'";

        $numFinal = get_next_value($db, $mask, 'bs_sav', 'ref', $where, $objsoc->code_client, '', 'next', false);

        return $numFinal;
    }

    /**        \brief      Return next free value
     *          \param      objsoc      Object third party
     *         \param        objforref    Object for number to search
     *       \return     string      Next free value
     */
    function getNumRef($objsoc, $objforref)
    {
        return $this->getNextValue($objsoc, $objforref);
    }

    function getVersion()
    {
        return($this->version);
    }
}

?>