$(document).ready(function () {
    if ($("#check")[0].checked == false) {
        $("#po_degr").val(0);
        $(".degr").hide();
        $("#dure_degr").val(0);
    } else {
        $(".degr").show();
    }
    $("#socid").change(function (e) {//fonction de changement de rapporteur 
        var send = $("#socid").val();
        if (send > 0) {
            $.ajax({
                url: "' . DOL_URL_ROOT . '/synopsischrono/ajax/contactSoc-xml_response.php",
                method: "POST",
                data: {"socid": send},
                dataType: "HTML",
                success: function (data) {
                    $("#contactid").html(data);
                },
                error: function () {
                    alert("Erreur: connexion impossible.");
                }
            });
        } else {
            $("#contactid").html("");
        }
    });
    $("#banque").change(function (e) {
        calculeAfterBanque();
    });
    $("#reCalcBanque").click(function (e) {
        calculeAfterBanque();
    });
    $("#but_deg").click(function (e) {
        e.preventDefault();
        calc_P();
    });
    $("#bouton").click(function (e) {
        e.preventDefault();
        calc();
    });
    $("#check").change(function (e) {//to do
        if ($("#check")[0].checked == false) {
            $("#po_degr, .degr select").val(0);
        }
        $(".degr").toggle(400);
    });
    $("#pretAP").change(function (e) {
        calc();
    });
    $(".rad").change(function (e) {
        init_location(true);
    });
    init_location(false);
});
function init_location(valdef) {
    var aff;
    if ($("#check")[0].checked == false) {
        aff = false;
    } else {
        aff = true;
    }
    var radio = $(".rad:checked");
    if ($(radio).val() == "financier") {
        $(".pr").fadeOut();
        $("#preter").val(0);
        $(".vr").fadeOut();
        $("#VR").val(0);
        if (aff) {
            $(".degr").toggle(400);
            $("#po_degr, .degr select").val(0);
        }
        $("#check").attr("checked", false);
        if (valdef)
            $("#montant").val((parseFloat($("#tot").html().replace(" ", "").replace(",", "."))));
    }
    if ($(radio).val() == "operationnel") {
        $(".pr").fadeOut();
        $("#preter").val(0);
        $(".vr").fadeIn();
        $("#check").attr("checked", false);
        if (aff) {
            $(".degr").toggle(400);
            $("#po_degr, .degr select").val(0);
        }
        if (valdef)
            $("#VR").val(parseFloat($("#matos").html().replace(" ", "").replace(",", ".")) * 0.15);//calc vr matos
        if (valdef)
            $("#montant").val(parseFloat($("#tot").html().replace(" ", "").replace(",", ".")) - $("#VR").val());
    }
    if ($(radio).val() == "evol+") {
        $(".pr").fadeIn();
        $(".vr").fadeOut();
        $("#VR").val(0);
        if (valdef) {
            $("#check").attr("checked", true);
            if (aff == false) {
                $(".degr").toggle(400);
                $(".degr select").val(12);
                $("#po_degr").val((parseFloat($("#matos").html().replace(" ", "").replace(",", ".")) / parseFloat($("#tot").html().replace(" ", "").replace(",", "."))) * 100 / ($("#duree").val() / $(".degr select").val()));
            }
            $("#preter").val(parseFloat($("#matos").html().replace(" ", "").replace(",", ".")));
            $("#montant").val(parseFloat($("#tot").html().replace(" ", "").replace(",", ".")) - $("#preter").val());
        }
    }
}
function calc() {
    var pret = parseFloat($("#preter").val());
    var mois = parseFloat($("#mensuel").val());
    var dure = parseFloat($("#duree").val());
    var cC = parseFloat($("#commC").val());
    var cF = parseFloat($("#commF").val());
    var fric_dispo = parseFloat($("#pretAP").val());
    var pourc_periode2 = parseFloat($("#po_degr").val());//der   
    pourc_periode2 = 1 - (pourc_periode2 / 100);//der   
    if (pret > 0) {
        var loyerpret = pret * pourc_periode2 / dure;
    } else {
        var loyerpret = 0;
    }
    if (fric_dispo > 0) {
        var mensualite = fric_dispo / mois - loyerpret;
        var interet = parseFloat($("#taux").val());
        interet = interet / 100 / 12;
        if (interet == 0) {
            var emprunt = mensualite * dure
        } else {
            var emprunt = mensualite / (interet / (1 - Math.pow(1 + interet, -dure)));
        }
        var res = emprunt / ((100 + cC) / 100 * (100 + cF) / 100);
        res = Math.round(res * 100) / 100;
        res = res / pourc_periode2;//der
        $("#montant").val(res);
    }
}
function calc_P() {
    $("#po_degr").val((parseFloat($("#matos").html().replace(" ", "").replace(",", ".")) / parseFloat($("#tot").html().replace(" ", "").replace(",", "."))) * 100 / ($("#duree").val() / $(".degr select").val()));
}


function calculeAfterBanque(){
    banqueT = $("#banque option:selected").html();
        if ($("#banque").val() != "") {
            var tc = $("#banque").val().split(":");
            tauxT = tc[0];
            coefT = tc[1];

            typePeriode = $("#mensuel").val();
            montant = $("#montant").val();
            duree = $("#duree").val();


            if (banqueT.toUpperCase() == "GRENKE") {
                tauxT = 0;
                if (montant <= 12500 && montant > 500) {
                    if (typePeriode == 3) {
                        if(duree == 36)
                            coefT = 9.21;
                        if(duree == 48)
                            coefT = 7.17;
                        if(duree == 60)
                            coefT = 5.94;
                    }
                    if (typePeriode == 1) {
                        if(duree == 36)
                            coefT = 3.11;
                        if(duree == 48)
                            coefT = 2.42;
                        if(duree == 60)
                            coefT = 2;
                    }
                }
                if (montant <= 25000 && montant > 12500) {
                    if (typePeriode == 3) {
                        if(duree == 36)
                            coefT = 9.09;
                        if(duree == 48)
                            coefT = 7.05;
                        if(duree == 60)
                            coefT = 5.82;
                    }
                    if (typePeriode == 1) {
                        if(duree == 36)
                            coefT = 3.07;
                        if(duree == 48)
                            coefT = 2.38;
                        if(duree == 60)
                            coefT = 1.97;
                    }
                }
                if (montant > 25000) {
                    if (typePeriode == 3) {
                        if(duree == 36)
                            coefT = 9.06;
                        if(duree == 48)
                            coefT = 7.02;
                        if(duree == 60)
                            coefT = 5.79;
                    }
                    if (typePeriode == 1) {
                        if(duree == 36)
                            coefT = 3.06;
                        if(duree == 48)
                            coefT = 2.35;
                        if(duree == 60)
                            coefT = 1.96;
                    }
                }
            }


        }

        $("#taux").val(tauxT);
        $("#coef").val(coefT);

        $("#Bcache").val(banqueT);
}