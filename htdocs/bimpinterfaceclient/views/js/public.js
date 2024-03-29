
function verif_pw() {
    var cur_pw = document.getElementById('cur_pw').value;
    var new_pw = document.getElementById('new_pw').value;
    var confirm_pw = document.getElementById('confirm_pw').value;
    var btn = document.getElementById('public_form_submit');
    
    var check = true;
    
    if (cur_pw && new_pw && confirm_pw && new_pw == confirm_pw) {
        check = false;
    }
    
    if (!new_pw || new_pw.length < 8) {
        $('#min_chars_alert').stop().slideDown(250);
        check = false;
    } else {
        $('#min_chars_alert').stop().slideUp(250);
    }
    
    if (!new_pw || !/[^A-Za-z0-9]/.test(new_pw)) {
        $('#special_chars_alert').stop().slideDown(250);
        check = false;
    } else {
        $('#special_chars_alert').stop().slideUp(250);
    }
    
    if (!new_pw || !/[A-Z]/.test(new_pw)) {
        $('#maj_chars_alert').stop().slideDown(250);
        check = false;
    } else {
        $('#maj_chars_alert').stop().slideUp(250);
    }
    
    if (!new_pw || !/[0-9]/.test(new_pw)) {
        $('#num_chars_alert').stop().slideDown(250);
        check = false;
    } else {
        $('#num_chars_alert').stop().slideUp(250);
    }
    
    if (!check) {
        btn.disabled = false;
    } else {
        btn.disabled = true;
    }
}