<?php 
    require_once '../../master.inc.php';
    require_once DOL_DOCUMENT_ROOT.'/bimpcore/Bimp_Lib.php';
    $key = "";
    if(isset($_GET['key']) && $_GET['key'] != "")  {
        $key = $_GET['key'];
    }
    
    if($_GET['logout__button__bimp_validator'] == "ok") {
        setcookie("bimp_ldlc_public_signature_bimptechnique", "", time());
        header('Location: ' . DOL_URL_ROOT . '/bimptechnique/public/');
    }
    
    $logo = $conf->mycompany->dir_output . '/logos/' . $mysoc->logo;
?>

<!doctype html>
<html lang="fr">
    <head>
        <meta charset="utf-8">
        <title>Signature d'une fiche d'intervention</title>
        <link rel="stylesheet" href="css/css.css">
        <script type='text/javascript' src="<?= DOL_URL_ROOT ?>/bimptechnique/public/js/public_jquery.js"></script>
        <script type='text/javascript' src="<?= DOL_URL_ROOT ?>/bimptechnique/public/js/informations.js"></script>
    </head>
    <body>
        <div class="bimp_ldlc_page">
            <div class="bimp_ldlc-carre bimp gauche_bas bimp_animation "></div>
            <div class="bimp_ldlc-carre bimp droite_haut "></div>
            <div class="bimp_ldlc-carre ldlc droite_bas "></div>
            <div class='bas_center_gauches' >
                <span class="qs bimp_text">i<span id="popover" class="popover above"></span></span>
            </div>
            <div id="informations">
                <?= $mysoc->name . " - SAS au capital de " . $mysoc->capital . ' - ' . $mysoc->address . ' - ' . $mysoc->zip . ' ' . $mysoc->town . ' - Tél ' . $mysoc->phone . ' - SIRET: ' . $conf->global->MAIN_INFO_SIRET . ' - ' . 'APE : '.$conf->global->MAIN_INFO_APE.' - RCS/RM : '.$conf->global->MAIN_INFO_RCS.' - Num. TVA : FR 34 320387483' ?>
            </div>
            <?php 
      $erreur = false;
      
      if(isset($_POST['code_fi'])) {
          require_once DOL_DOCUMENT_ROOT.'/bimpcore/Bimp_Lib.php';  
          $object = BimpObject::getInstance('bimptechnique', 'BT_ficheInter');
          if($object->find(['public_signature_code' => $_POST['code_fi']], 1)) {
              
              $dateOut = new dateTime($object->getdata('public_signature_date_cloture'));
              $today = new dateTime();
              $strToTimeOut = strtotime($dateOut->format('Y-m-d H:i'));
              $strToTimeIn = strtotime($today->format('Y-m-d H:i'));
              
              if($strToTimeOut >= $strToTimeIn) {
                  setcookie("bimp_ldlc_public_signature_bimptechnique", $object->getData('fk_soc'), time()+3600);  /* Cookie valable 1 heure */
              
                    if($object->getData('signataire') != $_POST['signataire']) {
                        $object->updateField('signataire', $_POST['signataire']);
                    }

                    header('Location: ' . DOL_URL_ROOT . '/bimptechnique/public/?key=' . $object->getData('public_signature_url'));
              
              } else {
                  $erreur = true;
                  ?>
                  <div class="bimp_ldlc_fiche_intervention_login">
                    <img  class="bimp_ldlc-logo" src="../views/images/bimp_ldlc.png" alt="Logo bimp" /> <br />
                    <strong style="color: #706f6f; font-size:30px" >Nous sommes désolé. L'<strong style="color: #EF7D00;" >accès</strong> à ce rapport d'intervention est <strong style="color: #EF7D00;" >révoqué</strong></strong>
                    <br /><br />
                    <a style="text-decoration:none;  color: white" href="<?php echo DOL_URL_ROOT ?>/bimptechnique/public_signature.php">
                        <button id="bimp_ldlc-signer_le_rapport" class="bimp_ldlc-signer_le_rapport ldlc" style="width:20%"> 
                            <span class="gras" >Retour à l'accueil</span>
                        </button>
                    </a>
                </div>
                  <?php
              }
              
               
              ?>
              
              <?php
              
          } else {
              $erreur = true;
              ?>
                <div class="bimp_ldlc_fiche_intervention_login">
                    <img  class="bimp_ldlc-logo" src="../views/images/bimp_ldlc.png" alt="Company Logo" /> <br />
                    <strong style="color: #706f6f; font-size:30px" >Nous sommes désolé. Le <strong style="color: #EF7D00;" >code confidentiel</strong> ne correspond à aucune <strong style="color: #EF7D00;" >fiche d'intervention</strong></strong>
                    <br /><br />
                    <a style="text-decoration:none;  color: white" href="<?php echo DOL_URL_ROOT ?>/bimptechnique/public/">
                        <button id="bimp_ldlc-signer_le_rapport" class="bimp_ldlc-signer_le_rapport ldlc" style="width:20%"> 
                            <span class="gras" >Retour à l'accueil</span>
                        </button>
                    </a>
                </div>
              <?php
          }
      }
      
      if($_COOKIE["bimp_ldlc_public_signature_bimptechnique"]) {
          
          if($key != "") {
              require_once DOL_DOCUMENT_ROOT.'/bimpcore/Bimp_Lib.php';    
            $object = BimpObject::getInstance('bimptechnique', 'BT_ficheInter');
            
            if($object->find(['public_signature_url' => $key], 1)) {
                $client = BimpObject::getInstance('bimpcore', 'Bimp_Societe', $object->getData('fk_soc'));
                ?>
            <div class='haut_centre'>
                <h3><?= $client->getName() ?></h3>
            </div>
                <div class="gauche_haut" >
                    
                    <div class="user-card">
  <div class="user-card__header">
  </div>
                        
  <div class="user-card__body">
    <h3 class="user-card__body__name">
        
    </h3>
    <p class="user-card__body__description">
        Code client: <b class="text_bimp" ><?= $client->getData('code_client') ?></b><br />
        Numéro du rapport: <b class="text_bimp" ><?= $object->getRef() ?></b><br />
        
        <br />
        Plage d'accès à la signature:
        <?php 
            $dateIn = new dateTime($object->getData('public_signature_date_delivrance'));
            $dateOut = new dateTime($object->getdata('public_signature_date_cloture'));
        ?>
        <br />
        Du <b class="text_bimp" ><?= $dateIn->format('d/m/Y') ?></b> à <b class="text_bimp" ><?= $dateIn->format('H:m') ?></b><br />
        Au <b class="text_bimp" ><?= $dateOut->format('d/m/Y') ?></b> à <b class="text_bimp" ><?= $dateOut->format('H:m') ?></b><br />
        <br />
        
        <?php 
         if(!$object->getData('email_signature')) {
             if($client->getData('email')) {
                 $email = $client->getData('email');
             } else {
                 $email = "Pas d'email (Pas de reception du rapport)";
             }
             
         } else {
             $email = $object->getData('email_signature');
         }
        ?>
        Nom du signataire: <br /><b class="text_bimp" ><?= $object->getData('signataire') ?></b><br /><br />
        Email du signataire: <br /><b class="text_bimp" ><?= $email ?></b><br /><br />
        
    </p>
    <p style="cursor:pointer" >Télécharger le rapport d'intervention</p>
    <form method="POST" action="<?= DOL_URL_ROOT ?>/bimptechnique/public/?logout__button__bimp_validator=ok">
        <input name="logout__button__bimp_validator" type="hidden" value="">
        <button name="logout__button__bimp" id="logout__button__bimp" >Déconnexion</button>
    </form>
  </div>
</div>
                </div>
      <?php

      $file =  DOL_URL_ROOT . "/bimptechnique/class/pdf.php?key=" . $_REQUEST['key'] . "&keyId=" . $client->id;
        ?>
            <iframe frameborder="0" importance="hight" src="<?= $file ?>" style="z-index: 9000" type="application/pdf"   height="80%" width="60%"></iframe>
  <?php
                
            } else {
                setcookie("bimp_ldlc_public_signature_bimptechnique", "", time());
                
                ?>
                <div class="bimp_ldlc_fiche_intervention_login">
                    <img  class="bimp_ldlc-logo" src="../views/images/bimp_ldlc.png" alt="Company Logo" /> <br />
                    <strong style="color: #706f6f; font-size:30px" >Nous sommes désolé. Cette <strong style="color: #EF7D00;" >fiche d'intervention </strong> n'existe pas</strong>
                    <br /><br />
                    <a style="text-decoration:none;  color: white" href="<?php echo DOL_URL_ROOT ?>/bimptechnique/public/">
                        <button id="bimp_ldlc-signer_le_rapport" class="bimp_ldlc-signer_le_rapport ldlc" style="width:25%"> 
                            <span class="gras" >Retour à l'accueil</span>
                        </button>
                    </a>
                </div>
                
            <?php
                }
                
            } else {
                setcookie("bimp_ldlc_public_signature_bimptechnique", "", time());
                ?>
                <div class="bimp_ldlc_fiche_intervention_login">
                    <img  class="bimp_ldlc-logo" src="../views/images/bimp_ldlc.png" alt="Company Logo" /> <br />
                    <strong style="color: #706f6f; font-size:30px" >Nous sommes désolé. Cette <strong style="color: #EF7D00;" >fiche d'intervention </strong> n'existe pas</strong>
                    <br /><br />
                    <a style="text-decoration:none;  color: white" href="<?php echo DOL_URL_ROOT ?>/bimptechnique/public/">
                        <button id="bimp_ldlc-signer_le_rapport" class="bimp_ldlc-signer_le_rapport ldlc" style="width:25%"> 
                            <span class="gras" >Retour à l'accueil</span>
                        </button>
                    </a>
                </div>
            <?php
                
            }
        } elseif(!$erreur) {
            ?>
            <form method="POST" action="<?= DOL_URL_ROOT ?>/bimptechnique/public/" >
                <div class="bimp_ldlc_fiche_intervention_login">
                    <img  class="bimp_ldlc-logo" src="../views/images/bimp_ldlc.png" alt="Company Logo" />

                    <div class="bimp__input-groupe">
                      <input type="text" class="bimp_input-groupe" name="signataire" required>
                      <span class="bimp_input-groupe-barre"></span>
                      <label class="bimp_input-groupe-label">Nom du signataire</label>
                    </div>
                    <div class="bimp__input-groupe">
                      <input type="text" class="bimp_input-groupe" name="code_fi" required>
                      <span class="bimp_input-groupe-barre"></span>
                      <label class="bimp_input-groupe-label">Code confidentiel</label>
                    </div>
                    <button class="bimp_ldlc-signer_le_rapport ldlc"> 
                        <span class="gras" >Signer le rapport</span>
                    </button>
                    <br /><br />
                    <div class="bimp_input-groupe-erreur" ><center></center></div>

                </div>
            </form>
          <?php
        }
      ?>
        </div>
    </body>
</html>
