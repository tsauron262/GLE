<!DOCTYPE html>
<html lang="fr">
    <head>
        <title>BIMP ERP Connexion client</title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <style type="text/css" >
            html {
                background:#FFFFFF;
                -webkit-background-size: cover;
                -moz-background-size: cover;
                -o-background-size: cover;
                background-size: cover;
                font-family: 'Montserrat', sans-serif;
            }
            a {
                display:block;
                text-decoration:none;
                color:#A2A5A6;
                text-align:center;
                padding:20px 0 0;
            }

            form {
                margin:5% auto;
                width:400px;
                min-height:400px;
                background:white;
                padding:2.5% 5% 2.5%;
                border-radius:2.5%;
            }

            form img {
                text-align:center;
                width:35%;
                margin:0 auto;
                display:block;
                padding:0;
            }

            h2 {
                font-size:2em;
                padding:0;
                margin:0;
                text-align:center;
            }

            input {
                margin:5% 0;
                border:none;
                width:100%;
                font-size:1.5em;
                padding:0 0 2%;
                background:none;
            }

            textarea:focus, input:focus{
                outline: 0;
            }

            #name {
                border-bottom:2px solid #FBD75C;
            }

            #confirme_password {
                border-bottom:2px solid #F2B07E;
            }

            #password {
                border-bottom:2px solid #BDC3C7;
            }

            label {
                color:#BDC3C7;
                text-transform:uppercase;
                font-size:0.8em;
                letter-spacing:4px;
            }

            #button {
                background:none;
                border:none;
                width:100%;
                min-height:50px;
                margin:10px 0 10px;
                border-radius:2.5%;
                color:white;
                padding:0.5% 0 0;
                font-size:1.75em;

                -webkit-transition: background 1s ease-out;  
                -moz-transition: background 1s ease-out;  
                -o-transition: background 1s ease-out;  
                transition: background 1s ease-out; 


                /* Permalink - use to edit and share this gradient: http://colorzilla.com/gradient-editor/#f2b07e+0,fbd75c+100 */
                background: #f2b07e; /* Old browsers */
                background: -moz-linear-gradient(left,  #f2b07e 0%, #fbd75c 100%); /* FF3.6+ */
                background: -webkit-gradient(linear, left top, right top, color-stop(0%,#f2b07e), color-stop(100%,#fbd75c)); /* Chrome,Safari4+ */
                background: -webkit-linear-gradient(left,  #f2b07e 0%,#fbd75c 100%); /* Chrome10+,Safari5.1+ */
                background: -o-linear-gradient(left,  #f2b07e 0%,#fbd75c 100%); /* Opera 11.10+ */
                background: -ms-linear-gradient(left,  #f2b07e 0%,#fbd75c 100%); /* IE10+ */
                background: linear-gradient(to right,  #f2b07e 0%,#fbd75c 100%); /* W3C */
                filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#f2b07e', endColorstr='#fbd75c',GradientType=1 ); /* IE6-9 */
            }
            
            #button:disabled {
                background:lightgrey;
                border:none;
                width:100%;
                min-height:50px;
                margin:10px 0 10px;
                border-radius:2.5%;
                color:white;
                padding:0.5% 0 0;
                font-size:1.75em;
            }


            #button:hover {
                background-position:-400px;  
            } 

            #erp_bimp {
                margin-top:25%;
            }

        </style>
        
        <script ype="text/javascript">
            //document.getElementById('button').prop('disabled', 'disabled');
            function verif_for_active_button() {
                var password = document.getElementById('password').value;
                var confirme = document.getElementById('confirme_password').value;
                var btn = document.getElementById('button');
                if(confirme == password && password != '' && confirme != '') {
                    btn.disabled = false;
                } else {
                    btn.disabled = true;
                }
            }
        </script>
        
    </head>
    <body>

        <form action="<?= DOL_URL_ROOT . '/bimpinterfaceclient/' ?>" method="POST">
            <img src="<?php
            global $mysoc;
            echo DOL_URL_ROOT . '/viewimage.php?cache=1&modulepart=mycompany&file=' . $mysoc->logo
            ?>" style="width: 70%"> 
            <span>
                <h4>Le changement de votre mot de passe est requis</h4>
                <div id="erp_bimp">
                    <label for="password">Nouveau mot de passe</label>
                    <br />
                    <input id="password" type="password" name="new_password" onkeyup="verif_for_active_button()"  placeholder="Nouveau mot de passe">
                    <br />
                    <input id="confirme_password" onkeyup="verif_for_active_button()" type="password" name="confirme_password"  placeholder="Confirmer votre mot de passe">
                    <br /><br /><br />
                    <input id="button" type="submit" value="Changer mon mot de passe" disabled>
                </div>
        </form>
    </body>
</html>