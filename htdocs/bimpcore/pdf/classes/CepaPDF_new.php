<?php

require_once __DIR__ . '/BimpModelPDF.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/pdf.lib.php';

ini_set('display_errors', 1);

class CepaPDF extends BimpModelPDF
{

    public static $type = 'societe';
    public static $include_logo = true;
    public $maxLogoHeight = 30; // px
    public $client = null;
    

    protected function initData()
    {
        $this->pdf->topMargin = 10;
    }

    protected function renderContent()
    {
        global $mysoc;

        if (!BimpObject::objectLoaded($this->client) && BimpObject::objectLoaded($this->object)) {
            $this->client = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Client', $this->object->id);
        }

        $html = '';

        $html .= $this->getLogoHtml();
        $html .= '<div style="text-align: center; color: #' . $this->primary . '; font-size: 12px; font-weight: bold; line-height: 8px">MANDAT DE PRELEVEMENT SEPA</div>';

        $html .= '<div style="text-align: center;font-style: italic; font-size: 9px;font-weight: normal">';
        $html .= 'A compléter, dater, signer, accompagner d\'un RIB et envoyer par e-mail';

        $email = BimpCore::getConf('email_facturation', null, 'bimpcore');
        if ($email) {
            $html .= ' à <br/>';
            $html .= '<a href="mailto: ' . $email . '">' . $email . '</a>';
        }
        $html .= '</div>';

        $html .= '<br/><br/>';

        $html .= '<table style="width: 100%">';
        $html .= '<tr>';
        $html .= '<td width="8%"></td>';
        $html .= '<td width="84%" style="font-size: 8px">';
        if (BimpObject::objectLoaded($this->client)) {
            $html .= 'Compte client : <b>' . $this->client->getRef() . '</b><br/><br/>';
        }

        $date_prelevement = BimpCore::getConf('sepa_date_prelevement', null, 'bimpcommercial');
        if ($date_prelevement) {
            $html .= 'Date de prélèvement : <b>' . $date_prelevement . '</b><br/><br/>';
        }

        $html .= 'En signant ce formulaire de mandat, vous autorisez la société ' . $mysoc->name . ' à envoyer des instructions à votre banque pour débiter votre compte, et votre banque à débiter votre compte conformément aux instructions de la société ' . $mysoc->name;
        $html .= '<br/>Vous bénéficiez du droit d\'être remboursé par votre banque selon les conditions décrites dans la convention que vous avez signée avec elle.';
        $html .= '<br/>Une demande de remboursement doit être présentée :';
        $html .= '<ul>';
        $html .= '<li>Dans les 8 semaines suivant la date de débit de votre compte pour un prélèvement autorisé</li>';
        $html .= '<li>Sans tarder et au plus tard dans les 13 mois en cas de prélèvement non autorisé</li>';
        $html .= '</ul>';
        $html .= 'Vos droits concernant le présent mandat sont expliqués dans un document que vous pouvez obtenir auprès de votre banque.';

        $html .= '<br/><br/><b>Référence Unique Mandat (RUM)</b> <span style="font-style: italic; font-size: 6px">(réservée au créancier)</span> : <span style="color: #CCCACA">_______________________________________________</span>';
        $html .= '</td>';
        $html .= '<td width = "8%"></td>';
        $html .= '</tr>';
        $html .= '</table>';

        $html .= '<table style="width: 100%" cellpadding="10" cellspacing="10">';
        $html .= '<tr>';
        $html .= '<td width="50%" style="border: solid 1px #000000;">';
        $html .= '<span style="line-height: 8px; text-align: center; color: #' . $this->primary . '">Titulaire du compte à débiter</span>';
        $html .= '<div style="font-size: 8px; line-height: 20px">';
        $html .= '<b>Nom et Prénom ou Société <span style="color: #' . $this->primary . '">*</span> : </b><span style="color: #CCCACA">_______________________</span>';
        $html .= '<br/><span style="color: #CCCACA">_________________________________________________</span><br/>';
        $html .= '<b>Adresse <span style="color: #' . $this->primary . '">*</span> : </b><span style="color: #CCCACA">_______________________________________</span>';
        $html .= '<br/><span style="color: #CCCACA">_________________________________________________</span><br/>';
        $html .= '<b>Code postal <span style="color: #' . $this->primary . '">*</span> : </b><span style="color: #CCCACA">___________________________________</span><br/>';
        $html .= '<b>Ville <span style="color: #' . $this->primary . '">*</span> : </b><span style="color: #CCCACA">__________________________________________</span><br/>';
        $html .= '<b>Pays <span style="color: #' . $this->primary . '">*</span> : </b><span style="color: #CCCACA">_________________________________________</span>';
        $html .= '</div>';
        $html .= '</td>';

        $html .= '<td width="50%" style="border: solid 1px #000000">';
        $html .= '<span style="color: #' . $this->primary . '">Identiifiant Créancier SEPA : </span>';

        $code_ics = BimpCore::getConf('code_ics', null, 'bimpcore');
        if ($code_ics) {
            $html .= $code_ics;
        }

        $html .= '<div style="font-size: 8px; line-height: 20px">';
        $html .= '<b>Société : </b>' . $mysoc->name . '<br/>';
        $html .= '<b>Adresse : </b>' . $mysoc->address . '<br/>';
        $html .= '<b>Code postal : </b>' . $mysoc->zip . '<br/>';
        $html .= '<b>Ville : </b>' . $mysoc->town . '<br/>';
        $html .= '<b>Pays : </b>' . ($mysoc->pays ? $mysoc->pays : 'France');

        $html .= '</div>';
        $html .= '</td>';
        $html .= '</tr>';

        $html .= '<tr>';
        $html .= '<td colspan="2" style="border: solid 1px #000000">';
        $html .= '<span style="text-align: center; color: #' . $this->primary . ';">Compte à débiter</span><br/><br/>';

        $html .= '<table cellpadding="2" style="width: 100%">';
        $html .= '<tr>';
        $html .= '<td width="40px">';
        $html .= '<b>BIC <span style="color: #' . $this->primary . '">*</span></b>';
        $html .= '</td>';
        $html .= '<td colspan="7">';
        $html .= $this->getBoxesHtml(11);
        $html .= '</td>';
        $html .= '</tr>';

        $html .= '<tr>';
        $html .= '<td colspan="99" line-height="6px"></td>';
        $html .= '</tr>';
        $html .= '<tr>';
        $html .= '<td width="40px">';
        $html .= '<b>IBAN <span style="color: #' . $this->primary . '">*</span></b>';
        $html .= '</td>';

        for ($i = 1; $i <= 6; $i++) {
            $html .= '<td width="65px">';
            $html .= $this->getBoxesHtml(4);
            $html .= '</td>';
        }

        $html .= '<td>';
        $html .= $this->getBoxesHtml(3);
        $html .= '</td>';

        $html .= '</tr>';
        $html .= '</table>';

        $html .= '</td>';
        $html .= '</tr>';
        $html .= '</table>';

        $html .= '<div style="color: #' . $this->primary . '; line-height: 8px; font-size: 7px; text-align: center">';
        $html .= '<b>Veuillez compléter tous les champs (*) du mandat, joindre un RIB ou RICE, puis adresser l\'ensemble au créancier.</b>';
        $html .= '</div>';

        $html .= '<br/>';

        $html .= '<table style="width: 100%" cellpadding="3" cellspacing="15">';
        $html .= '<tr>';
        $html .= '<td width="50%">';

        $html .= '<table cellpadding="2" style="width: 100%">';

        $html .= '<tr>';
        $html .= '<td width="30px">Le <span style="color: #' . $this->primary . '">*</span> : </td>';
        $html .= '<td>';
        $html .= $this->getBoxesHtml(2);
        $html .= '</td>';
        $html .= '<td>';
        $html .= $this->getBoxesHtml(2);
        $html .= '</td>';
        $html .= '<td>';
        $html .= $this->getBoxesHtml(4);
        $html .= '</td>';
        $html .= '<td width="20px"></td>';
        $html .= '</tr>';

        $html .= '<tr>';
        $html .= '<td colspan="99" style="line-height: 30px">&nbsp;</td>';
        $html .= '</tr>';

        $html .= '<tr>';
        $html .= '<td width="30px">A <span style="color: #' . $this->primary . '">*</span> : </td>';
        $html .= '<td colspan="4" style="color: #CCCACA">_______________________________</td>';
        $html .= '</tr>';
        $html .= '</table>';
        $html .= '</td>';

        $html .= '<td width="50%" style="border: solid 1px #000000">';
        $html .= '<span style="text-align: center; font-size: 7px; line-height: 8px">';
        $html .= 'Signature du titulaire du compte à débiter <span style="color: #' . $this->primary . '">*</span>';
        $html .= '</span>';
        $html .= '</td>';
        $html .= '</tr>';
        $html .= '</table>';

        $html .= '<div style="font-size: 7px; font-style: italic">';
        $html .= 'Les informations contenues dans le présent mandat, qui doit être complété, sont destinées à n\'être utilisées par le groupe ' . $mysoc->name . ',';
        $html .= 'en sa qualité de responsable du traitement, que pour la gestion de sa relation avec son client. Les informations collectées sont ';
        $html .= 'indispensables à cette gestion. Elles pourront donner lieu à l\'exercice par le client de ses droits d\'opposition pour des motifs ';
        $html .= 'légitimes, d\'interrogation, d\'accès et de rectification relativement à l\'ensemble des données qui le concernent et qui s\'exercent ';
        $html .= 'auprès du groupe ' . $mysoc->name . ', par courrier électronique';

        $email = BimpCore::getConf('email_dpo', null, 'bimpcore');
        if ($email) {
            $html .= ' à l\'adresse <a href="mailto: ' . $email . '">' . $email . '</a>';
        }

        $html .= ', accompagné d\'une copie d\'un titre d\'identité.';
        $html .= '</div>';

        $this->writeContent($html);
    }

    public function getBoxesHtml($n = 1, $size = 15)
    {
        $html = '';

        $html .= '<table>';
        $html .= '<tr>';
        for ($i = 1; $i <= $n; $i++) {
            $html .= '<td width="' . $size . 'px" height="' . $size . 'px" style="border: solid 1px #000000;, line-height: ' . $size . 'px"></td>';
        }
        $html .= '</tr>';
        $html .= '</table>';

        return $html;
    }
}
