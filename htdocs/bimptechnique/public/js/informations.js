
$(document).ready(function(){
    
    var qs  = $('.qs');
    var infos = $("#informations");
    var popover = $("#popover");
    var page = $(".bimp_ldlc_page");
    
    popover.text("Fermer les informations légales");
    
    console.log(infos);
    
    qs.click(function(){
        var etat = infos.css('display');
        if(etat == "none") {
            popover.text("Fermer les informations légales");
            infos.fadeIn();
        } else if(etat == "block") {
            popover.text("Ouvrir les informations légales");
            infos.fadeOut();
        }
    });
    var signature = $('#signature-pad');
    var width = $('.bimp_ldlc_page').width();
    var height = $('.bimp_ldlc_page').height();    
    signature.attr('width', (70 * width) / 100);
    signature.attr('height', (30 * height) / 100);    
    var signaturePad = new SignaturePad(signature[0], {
        backgroundColor: 'rgba(255, 255, 255, 0)',
        penColor: 'rgb(0, 0, 0)',
    });
    var signer = $("#signer");
    var erreurDiv = $("#erreur");
    var refaire = $('#refaire');
    var back = $("#back");
    signer.click(function(){
        var controlle = signaturePad._data;
        var canSign = true;
        var signErreur = "";
        console.log(controlle);
        if(controlle.length == 0) {
            canSign = false;
            signErreur = "Vous devez aposer votre signature avant de valider";
            erreurDiv.text(signErreur);
        }
        
        if(canSign) {
            $.ajax({
            type: "POST",
            url: signer.attr('url'),
            data: {
                key: signer.attr('key'),
                signature: signaturePad.toDataURL('image/png')
            },
            error: function () {
                console.log("Erreur PHP");
            },
            success: function (data) {
                document.location.reload();
            }
        });
        }
        
    });
    
    signature.click(function(){
        erreurDiv.text("");
    });
    
    refaire.click(function(){
        erreurDiv.text("");
        signaturePad.clear();
    });
    
});
