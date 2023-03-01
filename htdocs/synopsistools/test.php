<?php
//setcookie('PHPSESSIDZZZZ', 'ZZZZZZ')   ;  
	session_start();
        
        
    
if(!count($_COOKIE))
    $_SESSION['sessioninit'] = '1';

if(isset($_POST['public_form']))
    $_SESSION['form_sended'] = true;    
        
//setcookie('fdffdffdfd', 'ffdfdfdf')   ;
//setcookie('PHPSESSIDZZZZ', 'ffdfdfdf')   ; 
		$cookieparams = array(
			'maxlifetime' => 0,
		);
		setcookie('ZZZAddd', 'inutile22S', 6000); 
		$cookieparams = array(
//			'lifetime' => 0,
		);
		setcookie('DDDF', 'inutile2', $cookieparams); 
        
        
echo '<pre>';
echo '<h1>Liste des cookies</h1>';
        print_r($_COOKIE);
echo '<h1>Infos session (au minimum sessioninit=1)</h1>';
        print_r($_SESSION);
echo '<h1>Session Id</h1>';
echo session_id();
        
        



session_write_close();
        
        
        
//        echo '<form method="POST"><h2>Espace client</h2><h3 style="text-align: center">Votre identifiant et mot de passe sont différents de votre compte client LDLC</h3><div id="erp_bimp"><input type="hidden" name="public_form_submit" value="1"><input type="hidden" name="public_form" value="login"><label for="bic_login_email">Email</label><br><input id="bic_login_email" type="text" name="bic_login_email" placeholder="Email" value=""><br><br><label for="bic_login_pw">Mot de passe</label><br><input id="bic_login_pw" type="password" name="bic_login_pw" placeholder="Mot de passe"><br><p style="text-align: center"><a href="javascript: var email = document.getElementById(\'bic_login_email\').value; window.location = \'https://erp.bimp.fr/b/display_public_form=1&amp;public_form=reinitPw\' + (email ? \'&amp;email=\' + email : \'\');">Mot de passe oublié</a></p><p style="text-align: center">Si vous souhaitez prendre un rendez-vous en ligne dans un de nos centres SAV pour la réparation de votre matériel et que vous ne disposez pas de compte client LDLC Apple, veuillez <a href="https://erp.bimp.fr/b/fc=savForm" style="color: #00BEE5">cliquer ici</a></p><br><br><input id="public_form_submit" class="button submit" type="submit" value="Valider"><div data-lastpass-icon-root="true" style="position: relative !important; height: 0px !important; width: 0px !important; float: left !important;"></div><div data-lastpass-icon-root="true" style="position: relative !important; height: 0px !important; width: 0px !important; float: left !important;"></div></div></form>';die;
        echo '<br/><br/><form method="POST"><input type="hidden" name="public_form" value="sended">'
        . '<input id="public_form_submit" class="button submit" type="submit" value="Valider">'
                . '</div></form>';die;
