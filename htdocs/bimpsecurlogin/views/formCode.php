<!doctype html>
<html>
    <head>
        <meta charset="UTF-8">		
        <title>Vérification du code</title>
        <link rel="shortcut icon" type="image/x-icon" href="/bimp8/theme/eldy/img/favicon.ico"/>
        <link rel="stylesheet" href="<?= DOL_URL_ROOT . '/bimpsecurlogin/views/css/codeForm.css' ?>">
        <script type="text/javascript" src=" <?= DOL_URL_ROOT . '/includes/jquery/js/jquery.min.js?layout=classic&version=8.0.3' ?>"></script>
        <script type="text/javascript" src="<?= DOL_URL_ROOT . '/bimpsecurlogin/views/js/codeForm.js'?>" ></script>
    </head>
    <body>
	<section id="content">
            <h1>Saisir le code reçu par SMS</h1>
            <h2><?= $message ?></h2>
            <center>
		<form method="POST" action="">
		    <input type="number" name="sms_code_1" maxLength="1" size="1" min="0" max="9" pattern="[0-9]{1}" />
                    <input type="number" name="sms_code_2" maxLength="1" size="1" min="0" max="9" pattern="[0-9]{1}" />
                    <input type="number" name="sms_code_3" maxLength="1" size="1" min="0" max="9" pattern="[0-9]{1}"/>
                    <input type="number" name="sms_code_4" maxLength="1" size="1" min="0" max="9" pattern="[0-9]{1}" />
                    <br /><br />
                        <center id="error_js"></center>
		    <button class='btn'>
			<span>Valider</span>
                    </button>
		</form>
                <form>
                    <button id="return_button">Code non reçus ? renvoyer le code</button>
		</form>   
            </center>    
        </section>
	<center>
            <img src="<?php global $mysoc; echo DOL_URL_ROOT . '/viewimage.php?cache=1&modulepart=mycompany&file=' . $mysoc->logo ?>"> 
	</center>
    </body>
</html>