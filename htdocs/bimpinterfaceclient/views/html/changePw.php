<script ype="text/javascript">
    function verif_for_active_button() {
        var cur_pw = document.getElementById('cur_pw').value;
        var new_pw = document.getElementById('new_pw').value;
        var confirm_pw = document.getElementById('confirm_pw').value;
        var btn = document.getElementById('public_form_submit');
        if (cur_pw && new_pw && confirm_pw && new_pw == confirm_pw) {
            btn.disabled = false;
        } else {
            btn.disabled = true;
        }
    }
</script>

<label for="cur_pw">Mot de passe actuel</label><br />
<input id="cur_pw" type="password" name="bic_cur_pw" onkeyup="verif_for_active_button()" placeholder="Mot de passe actuel">

<label for="new_pw">Nouveau mot de passe</label><br />
<input id="new_pw" type="password" name="bic_new_pw" onkeyup="verif_for_active_button()" placeholder="Nouveau mot de passeee"><br />

<label for="confirm_pw">Confirmer votre nouveau mot de passe</label><br />
<input id="confirm_pw" onkeyup="verif_for_active_button()" type="password" name="bic_confirm_new_pw" placeholder="Confirmation">