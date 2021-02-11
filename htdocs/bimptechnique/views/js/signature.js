$(document).ready(function(){
    var signature = $('#signature-pad');
    var BimpTechniqueSignChoise = $('#BimpTechniqueSignChoise');
    var BimpTechniqueFormName = $('#BimpTechniqueFormName');
    var BimpTechniqueEmailClient = $('#email_client');
    var width = $('.fiche').width();
    var height = $('.fiche').height();    
    signature.attr('width', (70 * width) / 100);
    signature.attr('height', (30 * height) / 100);    
    var signaturePad = new SignaturePad(signature[0], {
        backgroundColor: 'rgba(255, 255, 255, 0)',
        penColor: 'rgb(0, 0, 0)',
    });
    var saveButton = $("#save");
    var cancelButton = $("#clear");
    var expandButton = $("#expand");
    saveButton.click(function(){
        var data = signaturePad.toDataURL('image/png');
        BimpAjax('signFi', {
            controlle: signaturePad._data,
            email: BimpTechniqueEmailClient.val(),
            base64: data,
            nom: BimpTechniqueFormName.val(),
            isChecked: BimpTechniqueSignChoise.is(':checked'),
            
        }, '', {
            success: function (result, bimpAjax) {

            }, error: function(result, bimpAjax) {
                console.log(result);
            }
        });
        console.log(data)
    });
    cancelButton.click(function(){
        signaturePad.clear();
    });
    
    BimpTechniqueSignChoise.click(function(){
        var isChecked = BimpTechniqueSignChoise.is(':checked');
        if(isChecked) {
            signature.fadeOut();
            cancelButton.fadeOut();
        } else {
            signature.fadeIn();
            cancelButton.fadeIn();
        }
    });
    
    expandButton.click(function(){
        
//        var ww = $(window).innerWidth();
        //signature.css('position', 'fixed');
        //signature.css('margin-top', '0');
        //signature.attr('width', (ww));
        //signature.attr('height', (height));
    });
    
});

