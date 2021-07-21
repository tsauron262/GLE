$(document).ready(function () {

    var signature = $('#signature-pad');
    var BimpTechniqueSignChoise = $('#BimpTechniqueSignChoise'); // Papier
    var BimpTechniqueSignFar = $("#BimpTechniqueSignFar"); // Dist
    var BimpTechniqueSign = $("#BimpTechniqueSign"); // Elec

    var BimpTechniqueFormName = $('#BimpTechniqueFormName');
    var BimpTechniqueEmailClient = $('#email_client');
    var BimpTechniquePreco = $('#note_public');
    var BimpTechniqueAttenteClient = $('#attente_client');
    var BimpTehcniqueNoFinish = $("#inter_no_finish");
    var BimpTechniqueContactCommercial = $("#BimpTechniqueContactCommercial");

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

    saveButton.click(function () {
        saveButton.prop('disabled', true);
        cancelButton.prop('disabled', true);
        var data = signaturePad.toDataURL('image/png');
        BimpAjax('signFi', {
            controlle: signaturePad._data,
            email: BimpTechniqueEmailClient.val(),
            base64: data,
            nom: BimpTechniqueFormName.val(),
            isChecked: BimpTechniqueSignChoise.is(':checked'),
            farSign: BimpTechniqueSignFar.is(':checked'),
            sign: BimpTechniqueSign.is(':checked'),
            preco: BimpTechniquePreco.val(),
            attente: BimpTechniqueAttenteClient.val(),
            noFinish: BimpTehcniqueNoFinish.val(),
            contactCommercial: BimpTechniqueContactCommercial.is(':checked')
        }, '', {display_processing: true}, {
            success: function (result, bimpAjax) {
            }, error: function (result, bimpAjax) {}
        });
    });

    cancelButton.click(function () {
        signaturePad.clear();
    });
    
    BimpTechniqueAttenteClient.change(function () {
        reActiveButtons();
        if (BimpTechniqueAttenteClient.val() == "") {
            BimpTehcniqueNoFinish.prop('disabled', false);
        } else {
            BimpTehcniqueNoFinish.prop('disabled', true);
        }
    });

    signature.click(function () {
        reActiveButtons();
    });
    BimpTechniqueContactCommercial.click(function () {
        reActiveButtons();
    });
    BimpTechniqueSignChoise.change(function () {
        reActiveButtons();
    });
    BimpTechniqueFormName.change(function () {
        reActiveButtons();
    });
    BimpTechniqueEmailClient.change(function () {
        reActiveButtons();
    });
    BimpTechniquePreco.change(function () {
        reActiveButtons();
    });
    BimpTehcniqueNoFinish.change(function () {
        reActiveButtons();
        if (BimpTehcniqueNoFinish.val() == "") {
            BimpTechniqueAttenteClient.prop('disabled', false);
        } else {
            BimpTechniqueAttenteClient.prop('disabled', true);
        }
    });


    function reActiveButtons() {
        saveButton.prop('disabled', false);
        cancelButton.prop('disabled', false);
    }


    BimpTechniqueSignChoise.click(function () { // Papier
        var isChecked = BimpTechniqueSignChoise.is(':checked');
        if (isChecked) {
            BimpTechniqueSignFar.prop("checked", false);
            BimpTechniqueSign.prop('checked', false);
            signature.fadeOut();
            cancelButton.fadeOut();
            BimpTechniqueFormName.fadeIn();
            $("#nomSignataireTitle").fadeIn();
        } else {
            signature.fadeIn();
            cancelButton.fadeIn();
            verifForCheckSignElec();
        }
    });

    BimpTechniqueSign.click(function () { // Elec
        var isChecked = BimpTechniqueSign.is(':checked');
        if (isChecked) {
            BimpTechniqueSignFar.prop("checked", false);
            BimpTechniqueSignChoise.prop('checked', false);
            signature.fadeIn();
            cancelButton.fadeIn();
            BimpTechniqueFormName.fadeIn();
            $("#nomSignataireTitle").fadeIn();
        } else {
            signature.fadeIn();
            cancelButton.fadeIn();
            verifForCheckSignElec();
        }
    });

    BimpTechniqueSignFar.click(function () { // A distance
        var isChecked = BimpTechniqueSignFar.is(':checked');
        if (isChecked) {
            BimpTechniqueSignChoise.prop('checked', false);
            BimpTechniqueSign.prop('checked', false);
            BimpTechniqueFormName.fadeOut();
            $("#nomSignataireTitle").fadeOut();
            signature.fadeOut();
            cancelButton.fadeOut();
        } else {
            signature.fadeIn();
            cancelButton.fadeIn();
            BimpTechniqueFormName.fadeIn();
            $("#nomSignataireTitle").fadeIn();
            verifForCheckSignElec();
        }
    });

    function verifForCheckSignElec() {

        if (!BimpTechniqueSignChoise.prop('checked') && !BimpTechniqueSignFar.prop('checked')) {
            !BimpTechniqueSign.prop('checked', true);
        }

    }

    function showTerminateCheck() {
        return 1;
    }

});

