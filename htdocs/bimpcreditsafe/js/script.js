$(window).on("load", function () {
    if ($("#idprof1").length > 0) {
        $("#idprof1").change(function () {
            datas = "siren=" + $("#idprof1").val();
            jQuery.ajax({
                url: DOL_URL_ROOT + "/bimpcreditsafe/test.php",
                data: datas,
                datatype: "json",
                type: "POST",
                cache: false,
                success: function (msg) {
                    var result = JSON.parse(msg);
                    if (typeof result.Erreur !== "undefined")
                        alert("SIREN non valide");
                    else {
                        $("#name").val(result.Nom);
                        $("#address").val(result.Adresse);
                        $("#capital").val(result.Capital);
                        $("#name_alias_input").val("Note : " + result.Note);
                    }
                }
            });
        });
    }
});