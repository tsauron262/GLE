<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/classes/BimpCron.php';

class BimpCommercialCronExec extends BimpCron
{

    public function checkCommandeLinesEcheances($delay_days = 60)
    {
        BimpObject::loadClass('bimpcommercial', 'Bimp_Commande');
        $this->output = Bimp_Commande::checkLinesEcheances($delay_days);
        return 0;
    }

    public function sendRappelFacturesMargesNegatives()
    {
        // Déplacé ici pour outils BimpCron (Envoi mail si Erreur Fatale) 
        $facts = array();
        $html = '';

        $sql = 'SELECT a.*'; //, (a.total_ht / a.qty) - (buy_price_ht * a.qty / ABS(a.qty)) as marge';
        $sql .= ', p.ref as prod_ref';
        $sql .= BimpTools::getSqlFrom('facturedet', array(
                    'f'   => array(
                        'alias' => 'f',
                        'table' => 'facture',
                        'on'    => 'a.fk_facture = f.rowid'
                    ),
                    'p'   => array(
                        'alias' => 'p',
                        'table' => 'product',
                        'on'    => 'p.rowid = a.fk_product'
                    ),
                    'pef' => array(
                        'alias' => 'pef',
                        'table' => 'product_extrafields',
                        'on'    => 'pef.fk_object = a.fk_product'
                    )
        ));
        $sql .= " WHERE ((a.total_ht / a.qty)+0.01) < (buy_price_ht * a.qty / ABS(a.qty)) AND f.datef > '2022-04-01' AND pef.type_compta NOT IN (3)";

        $sql = $this->db->query($sql);
        while ($ln = $this->db->fetch_object($sql)) {
            $factLine = BimpCache::findBimpObjectInstance('bimpcommercial', 'Bimp_FactureLine', array('id_line' => $ln->rowid));
            $margeF = $factLine->getTotalMarge();
            if ($margeF < 0) {
//                $this->output .= $ln->fk_facture . ' MARGE I : '.$factLine->getMargin() .' MARGE F : '.$factLine->getTotalMarge(). '<br/>';
                $facts[$ln->fk_facture][$ln->rowid] = 'Ligne n° ' . $ln->rang . ' - ' . $ln->prod_ref . ' ' . BimpRender::renderObjectIcons($factLine, 0, 'default', '$url') . ' (Marge: ' . $margeF . ')';
            }
        }

        foreach ($facts as $idFact => $lines) {
            $fact = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', $idFact);
            $html .= '<h2>' . $fact->getLink() . '</h2>';
            foreach ($lines as $line) {
                $html .= $line . '<br/>';
            }
            $html .= '<br/>';
        }

        $to = 'f.pineri@bimp.fr, tommy@bimp.fr, a.alimi@bimp.fr';

        mailSyn2('Ligne de facture à marge négative', $to, null, $html);

        $this->output = $html;
        return 0;
    }
}
