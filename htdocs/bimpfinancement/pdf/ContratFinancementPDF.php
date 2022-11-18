<?php

require_once DOL_DOCUMENT_ROOT . '/bimpfinancement/pdf/DocFinancementPDF.php';

class ContratFinancementPDF extends DocFinancementPDF
{

    public static $doc_type = 'contrat';
    public $data;

    public function __construct($db, $demande, $data = array())
    {
        $this->data = $data;
        parent::__construct($db, $demande);
        $this->doc_name = 'Contrat de location';
    }

    public function renderTop()
    {
        $html = '';

        $html .= '<div style="font-size: 9px">';
        $html .= '<p style="font-size: 10px; font-weight: bold; color: #' . $this->primary . '">Le locataire</p>';

        $html .= '<p style="font-size: 10px; font-weight: bold; color: #' . $this->primary . '">Le loueur</p>';
        $html .= '<p>';
        $html .= 'La société LDLC PRO LEASE, SAS au capital de 100 000,00 € dont le siège social est situé à LIMONEST (69760), 2 rue des érables, ';
        $html .= 'enregistrée sous le numéro siren 838 651 594 auprès du RCS de Lyon, représentée par M. Olivier VILLEMONTE de la CLERGERIE, ';
        $html .= 'intervenant en qualité de Président.';
        $html .= '</p>';

        $html .= '<p>';
        $html .= 'Le loueur donne en location, l’équipement désigné ci-dessous (ci-après « équipement »), au locataire qui l’accepte, ';
        $html .= 'aux Conditions Particulières et aux Conditions Générales composées de deux pages recto :';
        $html .= '</p>';
        $html .= '</div>';

        $this->writeContent($html);
    }

    public function renderDocInfos()
    {
        
    }

    public function renderAfterLines()
    {
        $html = '';

        $html .= '<div style="font-size: 9px">';
        $html .= '<p style="font-size: 10px; font-weight: bold; color: #' . $this->primary . '">Durées et loyers</p>';

        $html .= '<p>';
        $html .= 'Le loyer est ferme et non révisable en cours de contrat, payable par terme à échoir, par prélèvements automatiques.';
        $html .= '</p>';

        switch ($this->demande->getData('formule')) {
            case 'evo':
                $nb_loyers = $this->demande->getNbLoyers();
                $periodicity = (int) $this->demande->getData('periodicity');
                $periodicity_label = $this->demande->displayData('periodicity', 'default', 0, 1);
                $loyer_ht = (float) $this->demande->getData('loyer_mensuel_evo_ht') * $periodicity;
                $loyer_ttc = $loyer_ht * 1.2;

                $html .= '<table cellpadding="3px" style="text-align: center">';
                $html .= '<tr>';
                $html .= '<th style="width: 120px; background-color: #' . $this->primary . '; color: #fff;">Nombre de loyers</th>';
                $html .= '<th style="width: 120px; background-color: #' . $this->primary . '; color: #fff;">Montant HT</th>';
                $html .= '<th style="width: 120px; background-color: #' . $this->primary . '; color: #fff;">Périodicité</th>';
                $html .= '<th style="width: 120px; background-color: #' . $this->primary . '; color: #fff;">Montant TTC</th>';
                $html .= '</tr>';
                $html .= '<tr>';
                $html .= '<td style="background-color: #F2F2F2"><b>' . $nb_loyers . '</b></td>';
                $html .= '<td style="background-color: #F2F2F2"><b>' . BimpTools::displayMoneyValue($loyer_ht) . '</b></td>';
                $html .= '<td style="background-color: #F2F2F2"><b>' . $periodicity_label . '</b></td>';
                $html .= '<td style="background-color: #F2F2F2"><b>' . BimpTools::displayMoneyValue($loyer_ttc) . '</b></td>';
                $html .= '</tr>';
                $html .= '</table>';
                break;

            case 'dyn':
                $nb_loyers = $this->demande->getNbLoyers();
                $periodicity = (int) $this->demande->getData('periodicity');
                $periodicity_label = $this->demande->displayData('periodicity', 'default', 0, 1);
                $loyer_ht = (float) $this->demande->getData('loyer_mensuel_dyn_ht') * $periodicity;
                $loyer_ttc = $loyer_ht * 1.2;
                $loyer_suppl_ht = (float) $this->demande->getData('loyer_mensuel_suppl_ht') * $periodicity;
                $loyer_suppl_ttc = $loyer_suppl_ht * 1.2;

                $html .= '<table cellpadding="3px" style="text-align: center">';
                $html .= '<tr>';
                $html .= '<th style="width: 120px; background-color: #' . $this->primary . '; color: #fff;">Nombre de loyers</th>';
                $html .= '<th style="width: 120px; background-color: #' . $this->primary . '; color: #fff;">Montant HT</th>';
                $html .= '<th style="width: 120px; background-color: #' . $this->primary . '; color: #fff;">Périodicité</th>';
                $html .= '<th style="width: 120px; background-color: #' . $this->primary . '; color: #fff;">Montant TTC</th>';
                $html .= '</tr>';
                $html .= '<tr>';
                $html .= '<td style="background-color: #F2F2F2"><b>' . $nb_loyers . '</b></td>';
                $html .= '<td style="background-color: #F2F2F2"><b>' . BimpTools::displayMoneyValue($loyer_ht) . '</b></td>';
                $html .= '<td style="background-color: #F2F2F2"><b>' . $periodicity_label . '</b></td>';
                $html .= '<td style="background-color: #F2F2F2"><b>' . BimpTools::displayMoneyValue($loyer_ttc) . '</b></td>';
                $html .= '</tr>';
                $html .= '</table>';

                $html .= '<p>Suivi de : </p>';
                $html .= '<table cellpadding="3px" style="text-align: center">';
                $html .= '<tr>';
                $html .= '<th style="width: 120px; background-color: #' . $this->primary . '; color: #fff;">Nombre de loyers</th>';
                $html .= '<th style="width: 120px; background-color: #' . $this->primary . '; color: #fff;">Montant HT</th>';
                $html .= '<th style="width: 120px; background-color: #' . $this->primary . '; color: #fff;">Périodicité</th>';
                $html .= '<th style="width: 120px; background-color: #' . $this->primary . '; color: #fff;">Montant TTC</th>';
                $html .= '</tr>';
                $html .= '<tr>';
                $html .= '<td style="background-color: #F2F2F2"><b>' . (12 / $periodicity) . '</b></td>';
                $html .= '<td style="background-color: #F2F2F2"><b>' . BimpTools::displayMoneyValue($loyer_suppl_ht) . '</b></td>';
                $html .= '<td style="background-color: #F2F2F2"><b>' . $periodicity_label . '</b></td>';
                $html .= '<td style="background-color: #F2F2F2"><b>' . BimpTools::displayMoneyValue($loyer_suppl_ttc) . '</b></td>';
                $html .= '</tr>';
                $html .= '</table>';
                break;
        }
        
        $html .= '<p>';
        $html .= 'Le locataire déclare avoir été parfaitement informé de l’opération lors de la phase précontractuelle, avoir pris connaissance, reçu et accepter toutes les conditions particulières et générales. Il atteste que le contrat est en rapport direct avec son activité professionnelle et souscrit pour les besoins de cette dernière. Le signataire atteste être habilité à l’effet d’engager le locataire au titre du présent contrat. Le locataire reconnait avoir une copie des Conditions Générales, les avoir acceptées sans réserve y compris les clauses attribution de compétence et CNIL.';
        $html .= '</p>';
        
        $html .= '<p>';
        $html .= 'Fait en autant d’exemplaires que de parties, un pour chacune des parties';
        $html .= '</p>';
        
        $html .= '<p>';
        $html .= '<b>ANNEXES : </b>';
        $html .= '<ul>';
        $html .= '<li>Conditions générales composées de deux pages recto</li>';
        $html .= '</ul>';
        $html .= '</p>';

        $html .= '<p>Fait à Limonest, le : </p>';
        $html .= '</div>';

        $this->writeContent($html);
    }
}
