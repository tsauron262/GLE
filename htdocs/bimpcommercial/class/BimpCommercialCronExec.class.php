<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/classes/BimpCron.php';

class BimpCommercialCronExec extends BimpCron
{

    public function sendRappelFacturesMargesNegatives()
    {
        // Déplacé ici pour outils BimpCron (Envoi mail si Erreur Fatale) 
        $facts = array();
        $html = '';

        $sql = 'SELECT (a.total_ht / a.qty) - (buy_price_ht * a.qty / ABS(a.qty)) as marge, a.*';
        $sql .= BimpTools::getSqlFrom('facturedet', array(
                    'f' => array(
                        'alias' => 'f',
                        'table' => 'facture',
                        'on'    => 'a.fk_facture = f.rowid'
                    ),
                    'p' => array(
                        'alias' => 'p',
                        'table' => 'product_extrafields',
                        'on'    => 'p.fk_object = a.fk_product'
                    )
        ));
        $sql .= " WHERE ((a.total_ht / a.qty)+0.01) < (buy_price_ht * a.qty / ABS(a.qty)) AND f.datef > '2022-04-01' AND p.type_compta NOT IN (3)";

        $sql = $this->db->query($sql);
        while ($ln = $this->db->fetch_object($sql)) {
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

//        $to = 'f.pineri@bimp.fr, tommy@bimp.fr, a.alimi@bimp.fr';
        $to = 'f.martinez@bimp.fr';

        mailSyn2('Ligne de facture à marge négative', $to, null, $html);

        $this->output = $html;
        return 0;
    }
}
