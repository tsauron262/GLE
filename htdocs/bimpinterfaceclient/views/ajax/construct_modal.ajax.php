<?php

$etat_contrat = Array(
    0 => '<b class="text-danger" ><i class="fa fa-times"></i>Clos</b>',
    1 => '<b class="text-success" ><i class="fa fa-check"></i>En Cours de validité</b>'
);
$periode = array(
    1 => "Mensuel",
    3 => 'Trimestriel',
    6 => 'Semestriel',
    12 => 'Annuel'
);

$modal = '<div class="modal_box" ><div class="modal_content">';


require '../../../main.inc.php';
require DOL_DOCUMENT_ROOT . '/contrat/class/contrat.class.php';

if (isset($_REQUEST['object'])) {

    switch ($_REQUEST['object']) {
        case 'contrat':
            $object = new Contrat($db);
            $object->fetch($_REQUEST['id']);
            $extra = $object->array_options;
            $debut = new DateTime();
            $fin = new DateTime();
            $debut->setTimestamp($extra['options_date_start']);
            $fin->setTimestamp($extra['options_date_start']);
            $fin = $fin->add(new DateInterval("P" . $extra['options_duree_mois'] . "M"));
            $fin = $fin->sub(new DateInterval("P1D"));

            $date_fin = strtotime($fin->format('Y-m-d'));
            $aujourdhui = strtotime(date('Y-m-d'));
            $etat = ($date_fin > $aujourdhui) ? 1 : 0;
            $modal .= '<div class="modal_titre"><h4>Contrat numéro ' . $object->ref . '</h4></div><hr>';
            $modal .= ' <div class="modal_corp">';
            $modal .= ' <div class="row" style="width:100%"><div class="col-md-8 col-md-offset-2"><div class="content table-responsive table-full-width table-upgrade">';
            $modal .= '<table class="table"><thead><th>Informations générale du contrat</th><th class="text-center"></th></thead>';
            $modal .= '<tbody>';

            $modal .= '<tr><td>Status du contrat au ' . date('d/m/Y') . '</td><td>' . $etat_contrat[$etat] . '</td></tr>';
            $modal .= '<tr><td>Date de début </td><td>' . $debut->format('d/m/Y') . '</td></tr>';
            $modal .= '<tr><td>Durée du contrat </td><td>' . $extra['options_duree_mois'] . ' mois</td></tr>';
            $modal .= '<tr><td>Délais d\'intervention </td><td>' . $extra['options_gti'] . ' heures ouvrées</td></tr>';
            $modal .= '<tr><td>Periodicitée de paiement </td><td>' . $periode[$extra['options_periodicity']] . '</td></tr>';
            
            
            $modal .= '</tbody></table></div></div>';
            $modal .= '<div class="col-md-12">
                        <div class="card card-plain">
                            <div class="header">
                                <center><h4 class="title">Contenu du contrat</h4></center>
                            </div>
                            <div class="content table-responsive table-full-width">
                                <table class="table table-hover">
                                    <thead>
                                        <th>Produit</th>
                                    	<th>Qtée</th>
                                    	<th>TVA</th>
                                    	<th>P.U HT</th>
                                    </thead>
                                    <tbody>';
            foreach($object->lines as $line){
                $modal .= '<tr>
                                        	<td>'.$line->description.'</td>
                                        	<td>'.$line->qty.'</td>
                                        	<td>'.number_format($line->tva_tx, 0, '?', '').'%</td>
                                        	<td>'. number_format($line->price_ht,2,',', '').'€</td>
                                        </tr>';
            }
                                        
                                                
            $modal .= '</tbody></table></div></div></div>';
            $modal .= '</div></div>';
            break;
    }
}

$modal .= '</div></div>';

echo $modal;
?>