<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
require_once DOL_DOCUMENT_ROOT . '/synopsistools/SynDiversFunction.php';
require_once DOL_DOCUMENT_ROOT . '/bimpcore/classes/BimpCron.php';

class BimpCommercialCronExec extends BimpCron
{

    public $db;

    public function __construct($db)
    {
        $this->db = $db;
    }
    
    public function sendRappelFacturesMargesNegatives()
    {
        // Déplacé ici pour outils BimpCron (Envoi mail si Erreur Fatale) 
        global $db;
        $facts = array();
        $html = '';

        $sql = $db->query("SELECT (a.total_ht / a.qty) - (buy_price_ht * a.qty / ABS(a.qty)) as marge, a.* FROM llx_facturedet a LEFT JOIN llx_facture f ON a.fk_facture = f.rowid LEFT JOIN llx_product_extrafields p ON p.fk_object = a.fk_product WHERE ((a.total_ht / a.qty)+0.01) < (buy_price_ht * a.qty / ABS(a.qty)) AND f.datef > '2022-04-01' AND p.type_compta NOT IN (3);");
        while ($ln = $db->fetch_object($sql)) {
            $factLine = BimpCache::findBimpObjectInstance('bimpcommercial', 'Bimp_FactureLine', array('id_line' => $ln->rowid));
            $margeF = $factLine->getTotalMarge();
            if ($margeF < 0) {
//                $this->output .= $ln->fk_facture . ' MARGE I : '.$factLine->getMargin() .' MARGE F : '.$factLine->getTotalMarge(). '<br/>';
                $facts[$ln->fk_facture][$ln->rowid] = $factLine;
            }
        }

        foreach ($facts as $idFact => $lines) {
            $fact = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', $idFact);
            $html .= '<h2>' . $fact->getLink() . '</h2>';
            foreach ($lines as $line) {
                $html .= $line->getLink() . '<br/>';
            }
            $html .= '<br/>';
        }

        mailSyn2('Ligne de facture à marge négative', 'f.pineri@bimp.fr, tommy@bimp.fr, a.alimi@bimp.fr', null, $html);

        $this->output = $html;
        return 0;
    }
}
