$(window).load(function() {
    if (document.URL.search("comm/propal.php") > 0) {
        $("a.butAction").each(function() {
            if ($(this).html() == "Modifier")
                $(this).hide();
        });
    }



    if ($(".apple_sn").size() && $(".zonePlus").size()) {
        var serial = $(".apple_sn").html();
        var resultZone = $(".zonePlus");
        if (!serial || !resultZone.size()) {
            resultZone.html('<p class="error">Veuillez entrer un numéro de série</p>');
//        } else if (!/^[0-9a-zA-Z]+$/.test(serial)) {
//            resultZone.html('<p class="error">Le format du numéro de série est incorrect</p>');
        } else {
            resultZone.html('<div id="serialResult"></div><div id="productInfos"></div>');
            $.getScript(DOL_URL_ROOT + "/apple/appleGsxScripts.js", function() {
                $("head").append($(document.createElement("link")).attr({rel: "stylesheet", type: "text/css", href: DOL_URL_ROOT + "/apple/appleGSX.css"}));
                setGetRequest('newSerial', '&serial=' + serial);
            });
        }
    }
});

