<?php

require_once DOL_DOCUMENT_ROOT . '/bimpfinancement/pdf/DocFinancementPDF.php';

class ConsignesContratFinancementPDF extends DocFinancementPDF
{

    public static $doc_type = 'consignes';
    public $signature_bloc = false;

    public function __construct($db, $demande)
    {
        parent::__construct($db, $demande);
        $this->doc_name = 'Contrat de location';
    }

    public function renderTop()
    {
        $html = '';

        $html .= '<div style="font-size: 9px">';

        $html .= '<p>Bonjour,</p>';

        $html .= '<p>Vous venez de recevoir le dossier qui va vous permettre de mettre en place un contrat de location longue- durée, pour le financement de votre nouvel équipement informatique.</p>';
        $html .= '<p>Afin de compléter au mieux ce document, nous vous serions reconnaissants de suivre toutes les indications qui suivent.</p>';
        $html .= '<p>1 – Imprimez l’ensemble de ce document en recto-verso (8 feuilles). Dans le cas où vous rencontreriez un problème d’impression, en mode page / page.</p>';

        $html .= '<p>';
        $html .= '2 – Relisez soigneusement le contenu de ce contrat, en vérifiant qu’il n’y ait pas d’erreur sur :';
        $html .= '</p>';
        $html .= '<ul>';
        $html .= '<li>l\'identité de l’entreprise ou des personnes</li>';
        $html .= '<li>les configurations proposées</li>';
        $html .= '<li>les chaînes de loyers</li>';
        $html .= '</ul>';

        $html .= '<p>3 – Signez (ou paraphez) chacune des pages aux emplacements prévus, en apposant le cachet de la société.<br/>';
        $html .= '<span style="color: #B30000; font-weight: bold">Seuls les documents originaux (signature au stylo !) sont considérés comme valables par nos partenaires.</span></p>';

        $html .= '<p>';
        $html .= '4 – Complétez ce dossier avec les documents suivants :';
        $html .= '</p>';
        $html .= '<ul>';
        $html .= '<li>extrait K-Bis de moins de 3 mois pour les sociétés</li>';
        $html .= '<li>photocopie d’une pièce d’identité (recto-verso) de la personne signataire du contrat</li>';
        $html .= '<li>un relevé d’identité bancaire du compte sur lequel les loyers seront prélevés</li>';
        $html .= '</ul>';

        $html .= '<p>5 - Mettre le tout (documents originaux) sous enveloppe, pour expédition à :</p>';

        $html .= '<table style="font-weight: bold">';
        $html .= '<tr>';
        $html .= '<td style="width: 100px"></td>';
        $html .= '<td>';
        $html .= 'LDLC.PRO LEASE <br/>';
        $html .= 'Pascale ARDUIN / Alain GAILLARD <br/>';
        $html .= '2 rue des érables <br/>';
        $html .= '69760 LIMONEST';
        $html .= '</td>';
        $html .= '</tr>';
        $html .= '</table>';

        $html .= '<p>6 – Dès réception de ce dossier complet, les équipes du Groupe LDLC : <br/>';
        $html .= '<b> - LDLC.PRO</b><br/>';
        $html .= '<b> - BIMP .PRO</b><br/>';
        $html .= '<b> - Boutiques LDLC</b><br/>';
        $html .= 'mettront tout en œuvre pour gérer votre commande dans les meilleures conditions.</p>';

        $html .= '<p>Nous vous remercions pour votre confiance, et vous souhaitons une bonne utilisation de vos nouveaux équipements.</p>';

        $html .= '<table>';
        $html .= '<tr>';
        $html .= '<td><b>Pascale ARDUIN</b><br/><a href="mailto: p.arduin@ldlc.pro">p.arduin@ldlc.pro</a><br/>Tél : 06 88 03 37 74</td>';
        $html .= '<td><b>Alain GAILLARD</b><br/><a href="mailto: a.gaillard@ldlc.pro">a.gaillard@ldlc.pro</a><br/>Tél : 06 22 47 00 09</td>';
        $html .= '</tr>';
        $html .= '</table>';
        $html .= '</div>';

        $this->writeContent($html);
    }
    
    public function renderLines()
    {
        
    }
}
