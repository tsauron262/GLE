<?php 

    class indexController extends BimpController {
        
        public function renderHtml() {
            
            global $user;
            
//            if(!$user->admin)
//            die('Momentanément indisponible, contactez Tommy');
            
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
            
            $html .= "<select onchange=\"window.location.href ='".DOL_URL_ROOT."/bimptocegid/?element='+this.value\">"
                    . "<option value=''>Aucun élément selectionné</option>"
                    . "<option value='facture'>Facture client</option>"
                    . "<option value='facture_fourn'>Facture fournisseur</option>"
                    . "<option value='paiement'>Paiement client</option>"
                    . "</select>";
            
            $msgs = "Il y à plusieur méthodes pour obtenir des fichiers TRA (Il est obligatoire d'indiquer l'élément souhaité)<br />";
            $msgs.= "<ul>";
            $msgs.= "<li>Choisir une date (Ce qui va générer un fichier daté avec la date demandée) - Les pièces traités par le module seront alors marqués comme exportés</li>";
            $msgs.= "<li>Choisie une référence (Ce qui va générer un fichier avec la référence de la pièce dans le nom du fichier avec la référence demandée) - <b class='danger' >La pièce demandée ne sera alors pas marquée comme exportée</b></li>";
            $msgs.= "<li>Ne choisir ni de date, ni de références (Ce qui va générer des fichiers daté avec la date des pièces traitées par le module) - Les pièces s'exporteront à partir de la date de début du trimestre</li>";
            $msgs.= "</ul>";
            
            $html .= BimpRender::renderAlerts($msgs, 'info', false);
            if(isset($_REQUEST['element'])) {
                $html .= "<h3>Export des <b class='warning'>".ucfirst($_REQUEST['element'])."</b></h3>";
                $html .= "Le fichier tiers correspondant ce crée automatiquement<br />";
            }
            if(isset($_REQUEST['element'])) {
                $html .= "<form method='POST' action='".DOL_URL_ROOT."/bimptocegid/".$add."'><input type='date' name='date' ><input type='submit' class='btn btn-primary' value='Exporter' onclick=''></form>";
                $html .= "<form method='POST' action='".DOL_URL_ROOT."/bimptocegid/".$add."'><input placeholder='Référence' type='text' name='ref' ><input type='submit' class='btn btn-primary' value='Exporter' onclick=''></form>";
                $html .= "<form method='POST' action='".DOL_URL_ROOT."/bimptocegid/".$add."'><input placeholder='Depuis le ".$export->getStartTrimestreComptable()."' type='text' disabled><input type='submit' class='btn btn-primary' value='Exporter' onclick='' name='since'></form>";

            }
            
            $html .= '<br /><br /><br />';
            
            $dir = DIR_SYNCH . 'exportCegid/BY_DATE';
            $scanned_directory_by_date = array_diff(scandir($dir), array('..', '.', 'imported', 'imported_auto'));
            
            $html .= '<h3>Liste des fichiers TRA par date</h3>';
            $html .= '<small>'.$dir.'</small>';
            $html .= '<table class="bimp_list_table">';

            $html .= '<thead>';
            $html .= '<tr>';
            $html .= '<th>Fichier</th>';
            $html .= '<th>Taille</th>';
            $html .= '<th>Date</th>';
            $html .= '<th></th>';
            $html .= '</tr>';
            $html .= '</thead>';

            $html .= '<tbody>';
            if($scanned_directory_by_date) {
                foreach ($scanned_directory_by_date as $file => $name) {
                    $html .= '<tr>';

                    $onclick = "window.open('".DOL_URL_ROOT."/bimptocegid/doc.php?folder=BY_DATE&nom=".$name."')";
                    $onclick_imported = $export->getJsActionOnclick('imported', array('nom' => $name, 'folder' => "BY_DATE/"), array(
                            'confirm_msg'      => "Cette action est irrévessible, voullez vous continuer ?",
                            'success_callback' => 'function() {bimp_reloadPage();}'
                    ));
                        $html .= '<td><a class="btn btn-default" onclick = "'.$onclick.'">';



                        $html .= '<i class="' . BimpRender::renderIconClass(BimpTools::getFileIcon($name)) . ' iconLeft" ></i>';
                        $html .= $name . '</a></td>';

                        $html .= '<td>';
                        if (is_file($dir."/". $name) && filesize($dir."/" . $name)) {
                            $html .= filesize($dir."/" . $name).' b';
                        } else {
                            $html .= 'taille inconnue';
                        }
                        $html .= '</td>';

                        $html .= '<td>';
                        if(filemtime($dir."/".$name))
                            $html .= date('d / m / Y H:i:s', filemtime($dir."/".$name));
                        $html .= '</td>';


                        $html .= '<td class="buttons">';

                        $html .= BimpRender::renderRowButton('Marquer comme importé dans Cégid', 'check', $onclick_imported);
                        $html .= '</td>';
                        $html .= '</tr>';
                }
            }
            else {
                $html .= '<tr>';
                $html .= '<td colspan="4">';
                $html .= BimpRender::renderAlerts('Aucun fichier TRA par date', 'info', false);
                $html .= '</td>';
                $html .= '</tr>';
            }
            $html .= '</tbody></table>';
            
            
            $dir = DIR_SYNCH . 'exportCegid/BY_REF';
            
            $scanned_directory_by_ref = array_diff(scandir($dir), array('..', '.', 'imported', 'imported_auto'));
            
            $html .= '<h3>Liste des fichiers TRA par REF</h3>';
            $html .= '<table class="bimp_list_table">';

            $html .= '<thead>';
            $html .= '<tr>';
            $html .= '<th>Fichier</th>';
            $html .= '<th>Taille</th>';
            $html .= '<th>Date</th>';
            $html .= '<th></th>';
            $html .= '</tr>';
            $html .= '</thead>';

            $html .= '<tbody>';
            if($scanned_directory_by_ref) {
                foreach ($scanned_directory_by_ref as $file => $name) {
                $html .= '<tr>';
                $onclick = "window.open('".DOL_URL_ROOT."/bimptocegid/doc.php?folder=BY_REF&nom=".$name."')";
                $onclick_delete = $export->getJsActionOnclick('deleteTra', array('nom' => $name, 'folder' => "BY_REF/"), array(
                        'confirm_msg'      => "SUPPRESSION DU FICHIER, Cette action est irrévessible, voullez vous continuer ?",
                        'success_callback' => 'function() {bimp_reloadPage();}'
                ));
                
                 
                
                    $html .= '<td><a class="btn btn-default" onclick = "'.$onclick.'">';
                    
                    
                    
                    $html .= '<i class="' . BimpRender::renderIconClass(BimpTools::getFileIcon($name)) . ' iconLeft" ></i>';
                    $html .= $name . '</a></td>';

                    $html .= '<td>';
                    if (is_file($dir."/". $name) && filesize($dir."/" . $name)) {
                        $html .= filesize($dir."/" . $name).' b';
                    } else {
                        $html .= 'taille inconnue';
                    }
                    $html .= '</td>';

                    $html .= '<td>';
                    if(filemtime($dir."/".$name))
                        $html .= date('d / m / Y H:i:s', filemtime($dir."/".$name));
                    
                    
                    $html .= '</td>';


                    $html .= '<td class="buttons">';
                  
//                    $onclick = $this->getJsActionOnclick('deleteFile', array('file' => htmlentities($file['fullname'])), array(
//                        'confirm_msg'      => 'Veuillez confirmer la suppression de ce fichier',
//                        'success_callback' => 'function() {bimp_reloadPage();}'
//                    ));
                    $html .= BimpRender::renderRowButton('Supprimer', 'trash', $onclick_delete);
                    $html .= '</td>';
                    $html .= '</tr>';
                }
            } else {
                $html .= '<tr>';
                $html .= '<td colspan="4">';
                $html .= BimpRender::renderAlerts('Aucun fichier TRA par ref', 'info', false);
                $html .= '</td>';
                $html .= '</tr>';
            }
            $html .= '</tbody></table>';
            
            
            
            if(GETPOST('detail') == 'true'){
                $dir = DIR_SYNCH . 'exportCegid/BY_REF/imported';

                $scanned_directory_by_ref = array_diff(scandir($dir), array('..', '.', 'imported'));

                $html .= '<h3>Liste des fichiers TRA importée</h3>';
                $html .= '<table class="bimp_list_table">';

                $html .= '<thead>';
                $html .= '<tr>';
                $html .= '<th>Fichier</th>';
                $html .= '<th>Taille</th>';
                $html .= '<th>Date</th>';
                $html .= '<th></th>';
                $html .= '</tr>';
                $html .= '</thead>';

                $html .= '<tbody>';
                if($scanned_directory_by_ref) {
                    foreach ($scanned_directory_by_ref as $file => $name) {
                    $html .= '<tr>';
                    $onclick = "window.open('".DOL_URL_ROOT."/bimptocegid/doc.php?folder=BY_REF&nom=imported/".$name."')";



                        $html .= '<td><a class="btn btn-default" onclick = "'.$onclick.'">';



                        $html .= '<i class="' . BimpRender::renderIconClass(BimpTools::getFileIcon($name)) . ' iconLeft" ></i>';
                        $html .= $name . '</a></td>';

                        $html .= '<td>';
                        if (is_file($dir."/". $name) && filesize($dir."/" . $name)) {
                            $html .= filesize($dir."/" . $name).' b';
                        } else {
                            $html .= 'taille inconnue';
                        }
                        $html .= '</td>';

                        $html .= '<td>';
                        if(filemtime($dir."/".$name))
                            $html .= date('d / m / Y H:i:s', filemtime($dir."/".$name));


                        $html .= '</td>';


                        $html .= '<td class="buttons">';

                        $html .= '</td>';
                        $html .= '</tr>';
                    }
                } else {
                    $html .= '<tr>';
                    $html .= '<td colspan="4">';
                    $html .= BimpRender::renderAlerts('Aucun fichier TRA par ref', 'info', false);
                    $html .= '</td>';
                    $html .= '</tr>';
                }
                
                $dir = DIR_SYNCH . 'exportCegid/BY_DATE/imported';
                $scanned_directory_by_ref = array_diff(scandir($dir), array('..', '.', 'imported'));
                if($scanned_directory_by_ref) {
                    foreach ($scanned_directory_by_ref as $file => $name) {
                    $html .= '<tr>';
                    $onclick = "window.open('".DOL_URL_ROOT."/bimptocegid/doc.php?folder=BY_DATE&nom=imported/".$name."')";



                        $html .= '<td><a class="btn btn-default" onclick = "'.$onclick.'">';



                        $html .= '<i class="' . BimpRender::renderIconClass(BimpTools::getFileIcon($name)) . ' iconLeft" ></i>';
                        $html .= $name . '</a></td>';

                        $html .= '<td>';
                        if (is_file($dir."/". $name) && filesize($dir."/" . $name)) {
                            $html .= filesize($dir."/" . $name).' b';
                        } else {
                            $html .= 'taille inconnue';
                        }
                        $html .= '</td>';

                        $html .= '<td>';
                        if(filemtime($dir."/".$name))
                            $html .= date('d / m / Y H:i:s', filemtime($dir."/".$name));


                        $html .= '</td>';


                        $html .= '<td class="buttons">';

                        $html .= '</td>';
                        $html .= '</tr>';
                    }
                } else {
                    $html .= '<tr>';
                    $html .= '<td colspan="4">';
                    $html .= BimpRender::renderAlerts('Aucun fichier TRA par date', 'info', false);
                    $html .= '</td>';
                    $html .= '</tr>';
                }
                $html .= '</tbody></table>';
            }
            
            return $html;
            
        }
        
        
        
    }