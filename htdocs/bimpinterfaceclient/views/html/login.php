<label for="email">Email</label>
<br />
<input id="email" type="text" name="bic_login_email" placeholder="Email" value="<?php (isset($_REQUEST['email']) ? $_REQUEST['email'] : '') ?>">
<br /><br />
<label for="password">Mot de passe</label>
<br />
<input id="password" type="password" name="bic_login_pw" placeholder="Mot de passe">

<br/>
<p style="text-align: center"><a href="javascript: var email = $(\'input[name=bic_login_email]\').val(); window.location = \'./client.php?display_public_form=1&public_form=reinitPw\' + (email ? \'&email=\' + email : \'\') + &success_url= + document.location.href;');">Mot de passe oublié</a></p>