<?php 
    
    //define('NOLOGIN', 1);
    require_once '../main.inc.php';

    $key = "";
    if(isset($_GET['key']) && $_GET['key'] != "")  {
        $key = $_GET['key'];
    }
    
    $logo = $conf->mycompany->dir_output . '/logos/' . $mysoc->logo;
    
?>

  <div class="bimp_ldlc_page"> 
    <div class="bimp_ldlc-carre bimp gauche_bas bimp_animation ">
    </div>
    <div class="bimp_ldlc-carre bimp droite_haut ">
    </div>
      <div class="bimp_ldlc-carre ldlc droite_bas ">
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

                    header('Location: ' . DOL_URL_ROOT . '/bimptechnique/public_signature.php?key=' . $object->getData('public_signature_url'));
              
              } else {
                  $erreur = true;
                  ?>
                  <div class="bimp_ldlc_fiche_intervention_login">
                    <img  class="bimp_ldlc-logo" src="views/images/bimp_ldlc.png" alt="Company Logo" /> <br />
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
                    <img  class="bimp_ldlc-logo" src="views/images/bimp_ldlc.png" alt="Company Logo" /> <br />
                    <strong style="color: #706f6f; font-size:30px" >Nous sommes désolé. Le <strong style="color: #EF7D00;" >code confidentiel</strong> ne correspond à aucune <strong style="color: #EF7D00;" >fiche d'intervention</strong></strong>
                    <br /><br />
                    <a style="text-decoration:none;  color: white" href="<?php echo DOL_URL_ROOT ?>/bimptechnique/public_signature.php">
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
                <div class="gauche_haut" >
                    <div class="user-card">
  <div class="user-card__header">

  </div>
  <div class="user-card__body">
    <h3 class="user-card__body__name">
        <?= $client->getName() ?>
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
    <ul class="user-card__body__links">
      <li><a href="#">Déconnexion</a></li>
    </ul>
  </div>
</div>
                </div>
      <?php
      $user->id = 1;
        $file =  DOL_URL_ROOT . "/document.php?modulepart=fichinter&file=" . $object->getRef() . "/" . $object->getRef() . '.pdf&entity=0';
      
        //$file = DOL_DATA_ROOT . '/ficheinter/' . $object->getRef() . '/' . $object->getRef() . '.pdf';
        ?>
     <embed src="<?= $file ?>" style="" type="application/pdf"   height="80%" width="60%">
      
  <?php
                
            } else {
                setcookie("bimp_ldlc_public_signature_bimptechnique", "", time());
                
                ?>
                <div class="bimp_ldlc_fiche_intervention_login">
                    <img  class="bimp_ldlc-logo" src="views/images/bimp_ldlc.png" alt="Company Logo" /> <br />
                    <strong style="color: #706f6f; font-size:30px" >Nous sommes désolé. Cette <strong style="color: #EF7D00;" >fiche d'intervention </strong> n'existe pas</strong>
                    <br /><br />
                    <a style="text-decoration:none;  color: white" href="<?php echo DOL_URL_ROOT ?>/bimptechnique/public_signature.php">
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
                    <img  class="bimp_ldlc-logo" src="views/images/bimp_ldlc.png" alt="Company Logo" /> <br />
                    <strong style="color: #706f6f; font-size:30px" >Nous sommes désolé. Cette <strong style="color: #EF7D00;" >fiche d'intervention </strong> n'existe pas</strong>
                    <br /><br />
                    <a style="text-decoration:none;  color: white" href="<?php echo DOL_URL_ROOT ?>/bimptechnique/public_signature.php">
                        <button id="bimp_ldlc-signer_le_rapport" class="bimp_ldlc-signer_le_rapport ldlc" style="width:25%"> 
                            <span class="gras" >Retour à l'accueil</span>
                        </button>
                    </a>
                </div>
            <?php
                
            }
        } elseif(!$erreur) {
            ?>
            <form method="POST" action="<?= DOL_URL_ROOT ?>/bimptechnique/public_signature.php" >
                <div class="bimp_ldlc_fiche_intervention_login">
                    <img  class="bimp_ldlc-logo" src="views/images/bimp_ldlc.png" alt="Company Logo" />

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


<style>
body { background-color: #FFF; }
.gras { font-weight: bold; }
.bimp_ldlc_page {
  transition: opacity .8s ease-in-out;
  position: fixed;
  height: 100%;
  width: 100%;
  top: 0;
  left: 0;
  background: #FFF;
  opacity: 1;
  z-index: 100;
  text-align: center;
  vertical-align: middle;
  display: flex;
  justify-content: center;
  align-items: center;
}
.bimp_ldlc-logo { width: 300px; margin-bottom: 45px; }
.bimp_ldlc-signer_le_rapport {
  max-height: 50px;
  background-color: #01579B;
  color: #FFFFFF;
  padding: 13px 10px;
  border-radius: 30px;
  font-size: 18px;
  width: 90%;
  margin: 0 auto;
  border: none;
  outline: none;
  fill: transparent;
  transition: all .5s;
  box-shadow: 0px 1px 11px 1px rgba(110, 106, 106, 0.8);
  cursor: pointer;
}
.bimp_ldlc-signer_le_rapport:hover {
  box-shadow: 0px 3px 13px 3px rgba(110, 106, 106, 0.8);
}
.bimp_ldlc_page .bimp_ldlc-carre {
  width: 250px;
  height: 250px;
  position: absolute;
  border-radius: 30px;
  -ms-transform: rotate(20deg);
  -webkit-transform: rotate(20deg);
  transform: rotate(20deg);
}
.bimp_ldlc_page .bimp_ldlc-carre.blue-dark {
  background-color: #01579B;
}
.bimp_ldlc_page .bimp_ldlc-carre.red {
  background-color: #d75a4a;
}

.text_bimp {
    color: #EF7D00;
}

.bimp_ldlc_page .bimp_ldlc-carre.bimp {
  background-color: #EF7D00;
}

.bimp_ldlc_page .ldlc {
    background-color: #706f6f;
}

.bimp_ldlc_page .bimp_ldlc-carre.gauche_bas {
  bottom: -40px;
  left: -100px;
  box-shadow: 1px 4px 25px rgba(0, 0, 0, 0.33);
}
.bimp_ldlc_page .bimp_ldlc-carre.droite_haut {
  top: -40px;
  right: -100px;
  box-shadow: -5px 4px 9px rgba(0, 0, 0, 0.31);
}

.bimp_ldlc_page .gauche_haut {
    top: 25px;
    left: 25px;
    position: absolute;
}

.bimp_ldlc_page .bimp_ldlc-carre.droite_bas {
  bottom: -40px;
  right: -100px;
  box-shadow: -5px 4px 9px rgba(0, 0, 0, 0.31);
  -ms-transform: rotate(30deg);
  -webkit-transform: rotate(30deg);
  transform: rotate(30deg);
}

/* ------- MATERIAL-INPUT ------- */
.bimp__input-groupe {
  position: relative;
  margin-bottom: 25px;
}
.bimp__input-groupe i {
  position: absolute;
  top: 6px;
  left: 5px;
  color: #ee3b33;
  font-size: 23px;
}
.bimp_input-groupe-erreur {
  margin-top: 4px;
  margin-left: 4px;
  color: #D75A4A;
  display: block;
  text-align: left;
}
.bimp_input-groupe-label {
  font-size: 18px;
  font-weight: normal;
  position: absolute;
  pointer-events: none;
  left: 5px;
  top: 10px;
  transition: 0.2s ease all;
  -moz-transition: 0.2s ease all;
  -webkit-transition: 0.2s ease all;
  color: #999;
}

.bimp_input-groupe {
  font-family: 'Ubuntu', sans-serif;
  font-size: 18px;
  padding: 10px 10px 10px 5px;
  display: block;
  width: 100%;
  border: none;
  border-bottom: 1px solid #757575;
  background: transparent;
  transition: all .7s;
  -webkit-transition: all .5s;
  color: #000;
}
.bimp_input-groupe:focus {
  outline: none;
  border-bottom: 1px solid #d75a4a;
}
.bimp_input-groupe:focus ~ label {
  top: -20px;
  font-size: 14px;
}
.bimp_input-groupe:focus ~ .bimp_input-groupe-barre:before {
  width: 50%;
}
.bimp_input-groupe:focus ~ .bimp_input-groupe-barre:after {
  width: 50%;
}
.bimp_input-groupe-barre {
  position: relative;
  display: block;
}
.bimp_input-groupe-barre:before {
  content: '';
  height: 2px;
  width: 0;
  bottom: 1px;
  position: absolute;
  transition: 0.2s ease all;
  -moz-transition: 0.2s ease all;
  -webkit-transition: 0.2s ease all;
  background: #EF7D00;
  left: 50%;
}
.bimp_input-groupe-barre:after {
  content: '';
  height: 2px;
  width: 0;
  bottom: 1px;
  position: absolute;
  transition: 0.2s ease all;
  -moz-transition: 0.2s ease all;
  -webkit-transition: 0.2s ease all;
  background: #EF7D00;
  right: 50%;
}

@media screen and (max-width: 650px) {
  .wind-turbine-parent {
    visibility: hidden;
  }
}
@media screen and (max-height: 800px) {
  .bimp_ldlc-carre {
    width: 175px;
    height: 175px;
  }
}
@media screen and (max-height: 580px) {
  .bimp_ldlc-carre.droite_haut {
    top: -80px;
  }

  .bimp_ldlc-carre.gauche_bas {
    bottom: -80px;
  }
}


@-webkit-keyframes bimp_ldlc_carre_animation {
  0% {
    left: -100px;
  }
  50% {
    left: -80px;
  }
  100% {
    left: -100px;
  }
}
@-moz-keyframes bimp_ldlc_carre_animation {
  0% {
    left: -100px;
  }
  50% {
    left: -80px;
  }
  100% {
    left: -100px;
  }
}
@keyframes bimp_ldlc_carre_animation {
  0% {
    left: -100px;
  }
  50% {
    left: -80px;
  }
  100% {
    left: -100px;
  }
}
.bimp_animation {
  -webkit-animation-name: bimp_ldlc_carre_animation;
  -webkit-animation-delay: 0;
  -webkit-animation-duration: 2.5s;
  -webkit-animation-fill-mode: forwards;
  -webkit-animation-iteration-count: infinite;
  -webkit-animation-timing-function: ease-in-out;
  -moz-animation-name: bimp_ldlc_carre_animation;
  -moz-animation-delay: 0;
  -moz-animation-duration: 2.5s;
  -moz-animation-fill-mode: forwards;
  -moz-animation-iteration-count: infinite;
  -moz-animation-timing-function: ease-in-out;
  animation-name: bimp_ldlc_carre_animation;
  animation-delay: 0;
  animation-duration: 2.5s;
  animation-fill-mode: forwards;
  animation-iteration-count: infinite;
  animation-timing-function: ease-in-out;
}


.user-card {
  min-height: 60%;
  width: 100%;
  box-shadow: 0 2px 5px 0 rgba(0, 0, 0, 0.16), 0 2px 10px 0 rgba(0, 0, 0, 0.12);
}
.user-card__body {
  text-align: center;
  color: #706f6f;
}
.user-card__body__name {
  padding-top:20px;
  padding-left:20px;
  padding-right:20px;
  font-size: 1.4em;
}
.user-card__body__description {
  font-size: 1em;
}
.user-card__body__links {
  list-style: none;
  padding-left:0;
  padding-right: 0;
  padding-bottom: 20px;
}
.user-card__body__links li {
  display: inline-block;
}
.user-card__body__links a {
  padding: 5px;
  color: #706f6f;
  text-decoration: none;
  transition: color .3s ease-in-out;
}
.user-card__body__links a:hover {
  color: #EF7D00;
}
.user-card__body__links .fa {
  font-size: 1.4em;
}
    </style>
