/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

$(document).ready(function () {
    setCommonEvents($('#divCompteur'));
    setInputsEvents($('#divCompteur'));

    $("#divCompteur input").change(function () {
        $("#total2").html(getTotalCaisse());
    });

    $(function () {
        $('input:text:first').focus().select();
        var $inp = $('input:text');
        $inp.bind('keydown', function (e) {
            var key = e.which;
            if (key == 13) {
                e.preventDefault();
                var nextIndex = $inp.index(this) + 2;
                $(":input:text:eq(" + nextIndex + ")").focus().select();
                $("#total2").html(getTotalCaisse());
            }
        });
    });


});


function getTotalCaisse() {
    var total = 0;
    total += $("#compteur_caisse_500").val() * 500;
    total += $("#compteur_caisse_200").val() * 200;
    total += $("#compteur_caisse_100").val() * 100;
    total += $("#compteur_caisse_50").val() * 50;
    total += $("#compteur_caisse_20").val() * 20;
    total += $("#compteur_caisse_10").val() * 10;
    total += $("#compteur_caisse_5").val() * 5;
    total += $("#compteur_caisse_2").val() * 2;
    total += $("#compteur_caisse_1").val() * 1;
    total += $("#compteur_caisse_50c").val() * 0.50;
    total += $("#compteur_caisse_20c").val() * 0.20;
    total += $("#compteur_caisse_10c").val() * 0.10;
    total += $("#compteur_caisse_5c").val() * 0.05;
    total += $("#compteur_caisse_2c").val() * 0.02;
    total += $("#compteur_caisse_1c").val() * 0.01;

    return total;
}

