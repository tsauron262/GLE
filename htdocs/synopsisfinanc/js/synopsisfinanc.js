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
    $("#banque, #montant, #pretAP, #mensuel, #duree, #preter, #VR").change(function (e) {
        calculeAfterBanque();
    });
    $("#reCalcBanque, .rad").click(function (e) {
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
    if ($(radio).val() == "evolPlus") {
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


function calculeAfterBanque() {
    banqueT = $("#banque option:selected").html();
    if ($("#banque").val() != "") {
        var tc = $("#banque").val().split(":");
        tauxT = tc[0];
        coefT = tc[1];

        typePeriode = $("#mensuel").val();
        montant = parseFloat($("#montant").val()) + parseFloat($("#VR").val()) + parseFloat($("#preter").val());
        duree = $("#duree").val();


        tauxT = 0;
        coefT = 0;


        if (banqueT.toUpperCase() == "GRENKE") {
            if (montant <= 12500 && montant > 500) {
                if (typePeriode == 3) {
                    if (duree == 36)
                        coefT = 9.21;
                    if (duree == 48)
                        coefT = 7.17;
                    if (duree == 60)
                        coefT = 5.94;
                }
                if (typePeriode == 1) {
                    if (duree == 36)
                        coefT = 3.11;
                    if (duree == 48)
                        coefT = 2.42;
                    if (duree == 60)
                        coefT = 2;
                }
            }
            if (montant <= 25000 && montant > 12500) {
                if (typePeriode == 3) {
                    if (duree == 36)
                        coefT = 9.09;
                    if (duree == 48)
                        coefT = 7.05;
                    if (duree == 60)
                        coefT = 5.82;
                }
                if (typePeriode == 1) {
                    if (duree == 36)
                        coefT = 3.07;
                    if (duree == 48)
                        coefT = 2.38;
                    if (duree == 60)
                        coefT = 1.97;
                }
            }
            if (montant > 25000) {
                if (typePeriode == 3) {
                    if (duree == 36)
                        coefT = 9.06;
                    if (duree == 48)
                        coefT = 7.02;
                    if (duree == 60)
                        coefT = 5.79;
                }
                if (typePeriode == 1) {
                    if (duree == 36)
                        coefT = 3.06;
                    if (duree == 48)
                        coefT = 2.35;
                    if (duree == 60)
                        coefT = 1.96;
                }
            }
        }


        else if (banqueT.toUpperCase() == "GE CAPITAL") {
            if ($("#radevolPlus").attr('checked')) {//Credit bail
                if (montant <= 15000 && montant > 1000) {
                    if (typePeriode == 3) {
                        if (duree == 36)
                            coefT = 9.063;
                        if (duree == 39)
                            coefT = 8.444;
                        if (duree == 42)
                            coefT = 7.913;
                        if (duree == 48)
                            coefT = 7.052;
                        if (duree == 51)
                            coefT = 6.698;
                        if (duree == 60)
                            coefT = 5.849;
                    }
                    if (typePeriode == 1) {
                        if (duree == 36)
                            coefT = 3.042;
                        if (duree == 39)
                            coefT = 2.834;
                        if (duree == 42)
                            coefT = 2.656;
                        if (duree == 48)
                            coefT = 2.367;
                        if (duree == 51)
                            coefT = 2.248;
                        if (duree == 60)
                            coefT = 1.964;
                    }
                }
                if (montant <= 30000 && montant > 15000) {
                    if (typePeriode == 3) {
                        if (duree == 36)
                            coefT = 8.966;
                        if (duree == 39)
                            coefT = 8.345;
                        if (duree == 42)
                            coefT = 7.814;
                        if (duree == 48)
                            coefT = 6.950;
                        if (duree == 51)
                            coefT = 6.595;
                        if (duree == 60)
                            coefT = 5.744;
                    }
                    if (typePeriode == 1) {
                        if (duree == 36)
                            coefT = 3.007;
                        if (duree == 39)
                            coefT = 2.799;
                        if (duree == 42)
                            coefT = 2.621;
                        if (duree == 48)
                            coefT = 2.331;
                        if (duree == 51)
                            coefT = 2.212;
                        if (duree == 60)
                            coefT = 1.927;
                    }
                }
                if (montant > 30000) {
                    if (typePeriode == 3) {
                        if (duree == 36)
                            coefT = 8.942;
                        if (duree == 39)
                            coefT = 8.321;
                        if (duree == 42)
                            coefT = 7.789;
                        if (duree == 48)
                            coefT = 6.925;
                        if (duree == 51)
                            coefT = 6.570;
                        if (duree == 60)
                            coefT = 5.718;
                    }
                    if (typePeriode == 1) {
                        if (duree == 36)
                            coefT = 2.998;
                        if (duree == 39)
                            coefT = 2.790;
                        if (duree == 42)
                            coefT = 2.612;
                        if (duree == 48)
                            coefT = 2.322;
                        if (duree == 51)
                            coefT = 2.203;
                        if (duree == 60)
                            coefT = 1.918;
                    }
                }
            }
            else {
                if (montant <= 15000 && montant > 800) {
                    if (typePeriode == 3) {
                        if (duree == 24)
                            coefT = 13.328;
                        if (duree == 36)
                            coefT = 9.211;
                        if (duree == 39)
                            coefT = 8.579;
                        if (duree == 42)
                            coefT = 8.037;
                        if (duree == 48)
                            coefT = 7.158;
                        if (duree == 51)
                            coefT = 6.797;
                        if (duree == 60)
                            coefT = 5.931;
                    }
                    if (typePeriode == 1) {
                        if (duree == 24)
                            coefT = 4.472;
                        if (duree == 36)
                            coefT = 3.091;
                        if (duree == 39)
                            coefT = 2.879;
                        if (duree == 42)
                            coefT = 2.698;
                        if (duree == 48)
                            coefT = 2.403;
                        if (duree == 51)
                            coefT = 2.282;
                        if (duree == 60)
                            coefT = 1.991;
                    }
                }
                if (montant <= 30000 && montant > 15000) {
                    if (typePeriode == 3) {
                        if (duree == 24)
                            coefT = 13.239;
                        if (duree == 36)
                            coefT = 9.116;
                        if (duree == 39)
                            coefT = 8.482;
                        if (duree == 42)
                            coefT = 7.939;
                        if (duree == 48)
                            coefT = 7.059;
                        if (duree == 51)
                            coefT = 6.696;
                        if (duree == 60)
                            coefT = 5.828;
                    }
                    if (typePeriode == 1) {
                        if (duree == 24)
                            coefT = 4.439;
                        if (duree == 36)
                            coefT = 3.057;
                        if (duree == 39)
                            coefT = 2.845;
                        if (duree == 42)
                            coefT = 2.663;
                        if (duree == 48)
                            coefT = 2.368;
                        if (duree == 51)
                            coefT = 2.246;
                        if (duree == 60)
                            coefT = 1.955;
                    }
                }
                if (montant > 30000) {
                    if (typePeriode == 3) {
                        if (duree == 24)
                            coefT = 13.216;
                        if (duree == 36)
                            coefT = 9.092;
                        if (duree == 39)
                            coefT = 8.458;
                        if (duree == 42)
                            coefT = 7.915;
                        if (duree == 48)
                            coefT = 7.034;
                        if (duree == 51)
                            coefT = 6.671;
                        if (duree == 60)
                            coefT = 5.802;
                    }
                    if (typePeriode == 1) {
                        if (duree == 24)
                            coefT = 4.431;
                        if (duree == 36)
                            coefT = 3.048;
                        if (duree == 39)
                            coefT = 2.836;
                        if (duree == 42)
                            coefT = 2.654;
                        if (duree == 48)
                            coefT = 2.359;
                        if (duree == 51)
                            coefT = 2.237;
                        if (duree == 60)
                            coefT = 1.946;
                    }
                }
            }
        }

        else if (banqueT.toUpperCase() == "LOCAM") {
            if (montant <= 1500 && montant > 300) {
                if (typePeriode == 3) {
                    if (duree == 24)
                        coefT = 14,451;
                    if (duree == 36)
                        coefT = 10.432;
                    if (duree == 48)
                        coefT = 8.451;
                    if (duree == 60)
                        coefT = 7.286;
                    if (duree == 63)
                        coefT = 7.067;
                    if (duree == 72)
                        coefT = 6.527;
                }
                if (typePeriode == 1) {
                    if (duree == 13)
                        coefT = 8,398;
                    if (duree == 24)
                        coefT = 4.897;
                    if (duree == 36)
                        coefT = 3.539;
                    if (duree == 48)
                        coefT = 2.870;
                    if (duree == 60)
                        coefT = 2.476;
                    if (duree == 63)
                        coefT = 2.402;
                    if (duree == 72)
                        coefT = 2.220;
                }
            }
            if (montant <= 3000 && montant > 1501) {
                if (typePeriode == 3) {
                    if (duree == 24)
                        coefT = 14.112;
                    if (duree == 36)
                        coefT = 10.060;
                    if (duree == 48)
                        coefT = 8.054;
                    if (duree == 60)
                        coefT = 6.867;
                    if (duree == 63)
                        coefT = 6.643;
                    if (duree == 72)
                        coefT = 6.089;
                }
                if (typePeriode == 1) {
                    if (duree == 13)
                        coefT = 8,279;
                    if (duree == 24)
                        coefT = 4.867;
                    if (duree == 36)
                        coefT = 3.401;
                    if (duree == 48)
                        coefT = 2.725;
                    if (duree == 60)
                        coefT = 2.325;
                    if (duree == 63)
                        coefT = 2.249;
                    if (duree == 72)
                        coefT = 2.063;
                }
            }
            if (montant <= 7500 && montant > 3000) {
                if (typePeriode == 3) {
                    if (duree == 24)
                        coefT = 13.887;
                    if (duree == 36)
                        coefT = 9.815;
                    if (duree == 48)
                        coefT = 7.794;
                    if (duree == 60)
                        coefT = 6.593;
                    if (duree == 63)
                        coefT = 6.367;
                    if (duree == 72)
                        coefT = 5.803;
                }
                if (typePeriode == 1) {
                    if (duree == 13)
                        coefT = 8,199;
                    if (duree == 24)
                        coefT = 4.682;
                    if (duree == 36)
                        coefT = 3.311;
                    if (duree == 48)
                        coefT = 2.631;
                    if (duree == 60)
                        coefT = 2.227;
                    if (duree == 63)
                        coefT = 2.150;
                    if (duree == 72)
                        coefT = 1.961;
                }
            }
            if (montant <= 15000 && montant > 7500) {
                if (typePeriode == 3) {
                    if (duree == 24)
                        coefT = 13.719;
                    if (duree == 36)
                        coefT = 9.632
                    if (duree == 48)
                        coefT = 7.601;
                    if (duree == 60)
                        coefT = 6.391;
                    if (duree == 63)
                        coefT = 6.162;
                    if (duree == 72)
                        coefT = 5.593;
                }
                if (typePeriode == 1) {
                    if (duree == 13)
                        coefT = 8,140;
                    if (duree == 24)
                        coefT = 4.618;
                    if (duree == 36)
                        coefT = 3.244;
                    if (duree == 48)
                        coefT = 2.561;
                    if (duree == 60)
                        coefT = 2.154;
                    if (duree == 63)
                        coefT = 2.078;
                    if (duree == 72)
                        coefT = 1.886;
                }
            }
            if (montant <= 300000 && montant > 15000) {
                if (typePeriode == 3) {
                    if (duree == 24)
                        coefT = 13.551;
                    if (duree == 36)
                        coefT = 9.451;
                    if (duree == 48)
                        coefT = 7.410;
                    if (duree == 60)
                        coefT = 6.192;
                    if (duree == 63)
                        coefT = 5.961;
                    if (duree == 72)
                        coefT = 5.386;
                }
                if (typePeriode == 1) {
                    if (duree == 13)
                        coefT = 8,079;
                    if (duree == 24)
                        coefT = 4.555;
                    if (duree == 36)
                        coefT = 3.178;
                    if (duree == 48)
                        coefT = 2.493;
                    if (duree == 60)
                        coefT = 2.084;
                    if (duree == 63)
                        coefT = 2.006;
                    if (duree == 72)
                        coefT = 1.813;
                }
            }
        }




    }

    $("#taux").val(tauxT);
    $("#coef").val(coefT);

    $("#Bcache").val(banqueT);
}