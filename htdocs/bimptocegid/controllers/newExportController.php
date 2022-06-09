<?php
//

require_once DOL_DOCUMENT_ROOT . '/bimptocegid/objects/TRA_factureFournisseur.class.php';
require_once DOL_DOCUMENT_ROOT . '/bimptocegid/objects/TRA_payInc.class.php';
require_once DOL_DOCUMENT_ROOT . '/bimptocegid/objects/TRA_importPaiement.class.php';
require_once DOL_DOCUMENT_ROOT . '/bimptocegid/class/export.class.php';

class newExportController extends BimpController {
    private $dir = "/exportCegid/BY_DATE/";
    public function displayHeaderInterface() {
        
        global $db, $user;
        $u = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', $user->id);
        $bdd = new BimpDb($db);
//        $tra = new TRA_factureFournisseur(new BimpDb($db),  PATH_TMP . $this->dir . 'tiers.tra');
//
        $html = '';
        
        $html .= '<h2 style=\'color:orange;\' >BIMP<sup style=\'color:grey\'>export comptable</sup></h2>';
//        
//        $html .= '<pre>';
//        
//        $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_FactureFourn', 15420);
//        
//        $html .= $tra->constructTra($facture);
        
        if($_GET['PAYNI']) {
            $tra_constructor = new TRA_payInc($bdd);
            $file = PATH_TMP . $this->dir . '/payni_no_exported_' . date('d-m-Y') . '.tra';
            $exploded_payni = explode(';', $_GET['PAYNI']);
            $data = [];
            $i=1; $msg = 'Liste des pièces exportées manuellement par ' . $u->getName() . '<br /><br />';
            foreach($exploded_payni as $ref) {
                
                $sql = 'SELECT * from llx_Bimp_ImportPaiementLine WHERE num = "'.$ref.'"';
                
                if($res = $bdd->executeS($sql, 'array')) {
                    if(!$res[0]['exported']) {
                        $data['id']     = $res[0]['id'];
                        $data['amount'] = $res[0]['price'];
                        $data['num']    = $res[0]['num'];
                        $data['date']   = $res[0]['date'];
                        $data['name']   = $res[0]['name'];
                        $ecriture = $tra_constructor->constructTRA($data);
                        $opened_file = fopen($file, 'a+');
                        fwrite($opened_file, $ecriture);
                        fclose($opened_file);
                        
                        $msg .= $ref . '<br />';
                        $msg .= '<pre>' . $ecriture . '</pre>';
                        $msg .= '<br /><br />';
                        
                    }
                }
                $i++;
                        
            }
            echo $msg . '<br /><br />';
            mailSyn2("Export compta MANUEL", BimpCore::getConf('devs_email'), null, $msg);

        }
        
        if($_GET['test'] == 'true') {
            $TRA_Facture = new TRA_facture($bdd, PATH_TMP . "/exportCegid/TEST_TIERS.tra", true);
            if($_GET['facture']) {
                $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', $_GET['facture']);
                $html .= '<pre>';
                $html .= $TRA_Facture->constructTra($facture);
                $html .= '</pre>';
            }
        }
        
        if($_GET['IP'] == 'true') {
            
            $data['infos'] = '';
            
            $export = new TRA_importPaiement($bdd);
            $ipLine = BimpCache::getBimpObjectInstance('bimpfinanc', 'Bimp_ImportPaiementLine', 1);

            echo '<pre>' . $export->constructTRA($ipLine)  ."\n" . print_r($data, 1) . '</pre>';
            
        }
        
        return $html;
    }
    
}