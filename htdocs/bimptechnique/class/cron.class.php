<?php

require_once __DIR__ . '/../../bimpcore/Bimp_Lib.php';
require_once DOL_DOCUMENT_ROOT . '/synopsistools/SynDiversFunction.php';

class Cron
{

    public $output = "";
    
    public $idTechForVanina = [157,632];
    public $sendEmailDefault = 'v.gilbert@bimp.fr';
    
    public function start()
    {
        $this->relanceTechBrouillonJplus1etPlus();
    }

    public function relanceTechBrouillonJplus1etPlus()
    {

        $fiche = BimpCache::getBimpObjectInstance("bimptechnique", "BT_ficheInter");
        $list = $fiche->getList(["fk_statut" => 0, "new_fi" => 1]);

        $relance_array = Array();

        $now = new DateTime(date('Y-m-d'));

        foreach ($list as $obj) {
            $fiche->fetch($obj['rowid']);
            if ($fiche->getData('datei')) {
                $datei = new DateTime($fiche->getData('datei'));
                $diff = $datei->diff($now);
                if ($diff->invert == 0 && $diff->days > 1) {
                    $relance_array[$fiche->getData('fk_user_tech')][$fiche->id] = ["days" => $diff->days, "id" => $fiche->id];
                }
            }
        }

        $tech = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User');
        $fi = BimpCache::getBimpObjectInstance('bimptechnique', 'BT_ficheInter');

        foreach ($relance_array as $id_tech => $information) {
            $mail = "Bonjour, <br />Voici la liste de vos fiches d’interventions en brouillon dont la date d’intervention est dépassée<br /><br />";
            $tech->fetch($id_tech);
            foreach ($information as $id_fi => $i) {
                $fi->fetch($id_fi);
                $this->output .= $fi->getData('ref') . " retard de " . $i['days'] . " jours " . $tech->getData('email') . "<br />";
                $mail .= str_replace('bimpinv01072020', 'bimp8', $fi->getLink()) . " retard de " . $i['days'] . " jours<br />";
            }
            $mail .= "<br />Si la régularisation a été faite entre temps, merci de ne pas tenir compte de cet email.<br />Cordialement.";
            
            $emailSendTo = BimpTools::cleanEmailsStr($tech->getData('email'));
            $sujet = 'Fiches d\'intervention en brouillon';
            
            if(in_array($id_tech, $this->idTechForVanina)) {
                $sujet = 'Fiche d\'intervention en brouillon de ' . $tech->getName();
                $emailSendTo = $this->sendEmailDefault;
                $email .= '<br /><br />Ceci est un mail de redirection de ' . $tech->getData('email') . ' vers ' . $this->sendEmailDefault;
            }
            
            global $langs;
            echo '<h1>'.$tech->dol_object->getFullName($langs).'</h1><br/>'.$mail;
            mailSyn2($sujet, $emailSendTo, null, $mail);
        }
    }
}
