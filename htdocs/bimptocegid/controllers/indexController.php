<?php 

    class indexController extends BimpController {
        
        public function renderHtml() {
            
            $export = BimpObject::getInstance('bimptocegid', 'BTC_export');

            $html = '<h3><b>BIMP</b><b class="warning" >to</b><b>CEGID</b></h3>';
            $html .= "<b>Date de début de l'export: </b>" . $export->getStartTrimestreComptable() . ' (Début du trimestre) <br :>';
            if(isset($_POST['date']) || isset($_POST['ref']) || isset($_POST['since']) ) {
                $erreur = "";
                if(!isset($_REQUEST['element']) && empty($_REQUEST['element'])) {
                    $erreur = 'Aucun élément choisi';
                } elseif(isset($_POST['date']) && empty($_POST['date'])) {
                    $erreur = "La <b>date</b> ne peut pas être vide";
                } elseif(isset($_POST['ref']) && empty($_POST['ref'])) {
                    $erreur = "La <b>référence</b> ne peut pas être vide";
                }
                
                if(isset($_REQUEST['element'])) {
                    $element = ['facture', 'facture_fourn', 'paiement'];
                    if(!in_array($_REQUEST['element'], $element)) {
                        $erreur = "L'élément " . $_REQUEST['element'] . " n'existe pas"; 
                    }
                }
                
                if(!empty($erreur)) {
                    $html .= BimpRender::renderAlerts($erreur, 'danger', false);
                } else {
                    // C'est tous bon
                    $date = null;
                    $ref = null;
                    $all = false;
                    if(isset($_POST['date'])) { $date = $_POST['date'];}
                    if(isset($_POST['ref'])) { $ref = $_POST['ref'];}
                    if(isset($_POST['since'])) { $all = true;}
                    
                    $export->exportFromIndex($date, $ref, $all, $_REQUEST['element']);
                    
                }
                
            }
            $html .= '<br /><hr><br />';
            
            $add = "";
            if(isset($_REQUEST['element'])) {
                $add = "?element=" . $_REQUEST['element'];
            }
            
            $html .= "<select>"
                    . "<option onclick='window.location.href = \"".DOL_URL_ROOT."/bimptocegid/\"'>Aucun élément selectionné</option>"
                    . "<option onclick='window.location.href = \"".DOL_URL_ROOT."/bimptocegid/?element=facture\"'>Facture client</option>"
                    . "<option onclick='window.location.href = \"".DOL_URL_ROOT."/bimptocegid/?element=facture_fourn\"'>Facture fournisseur</option>"
                    . "<option onclick='window.location.href = \"".DOL_URL_ROOT."/bimptocegid/?element=paiement\"'>Paiement client</option>"
                    . "</select>";
            
            $msgs = "Il y à plusieur méthodes pour obtenir des fichiers TRA (Il est obligatoire d'indiquer l'élément souhaité)<br />";
            $msgs.= "<ul>";
            $msgs.= "<li>Choisir une date (Ce qui va générer un fichier daté avec la date demandée) - Les pièces traités par le module seront alors marqués comme exportés</li>";
            $msgs.= "<li>Choisie une référence (Ce qui va générer un fichier avec la référence de la pièce dans le nom du fichier avec la référence demandée) - La pièce demandée ne sera alors pas marquée comme exportée</li>";
            $msgs.= "<li>Ne choisir ni de date, ni de références (Ce qui va générer des fichiers daté avec la date des pièces traitées par le module) - Les pièces s'exporteront à partir de la date de début du trimestre</li>";
            $msgs.= "</ul>";
            
            $html .= BimpRender::renderAlerts($msgs, 'info', false);
            if(isset($_REQUEST['element'])) {
                $html .= "<h3>Export des <b class='warning'>".ucfirst($_REQUEST['element'])."</b></h3>";
                $html .= "Le fichier tiers correspondant ce crée automatiquement<br />";
            }
            $html .= "<form method='POST' action='".DOL_URL_ROOT."/bimptocegid/".$add."'><input type='date' name='date' ><input type='submit' class='btn btn-primary' value='Exporter' onclick=''></form>";
            $html .= "<form method='POST' action='".DOL_URL_ROOT."/bimptocegid/".$add."'><input placeholder='Référence' type='text' name='ref' ><input type='submit' class='btn btn-primary' value='Exporter' onclick=''></form>";
            $html .= "<form method='POST' action='".DOL_URL_ROOT."/bimptocegid/".$add."'><input placeholder='Depuis le ".$export->getStartTrimestreComptable()."' type='text' disabled><input type='submit' class='btn btn-primary' value='Exporter' onclick='' name='since'></form>";

            return $html;
            
        }
        
    }