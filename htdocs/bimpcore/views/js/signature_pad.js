function BimpSignaturePad() {
    var ptr = this;
    this.$container = null;
    this.$input = null;
    this.$resultContainer = null;
    this.$button = null;

    this.init = function () {
        ptr.$container = $('#signature_form');
        ptr.$input = ptr.$container.find('input[name="id_signature"]');
        ptr.$resultContainer = ptr.$container.find('.ajaxResultContainer');
        ptr.$button = ptr.find('#idSignatureSubmit');
    };

    this.loadSignatureForm = function () {
        ptr.$resultContainer.stop().slideUp(250, function () {
            ptr.$resultContainer.html('');
        });

        var id_signature = parseInt(ptr.$input.val());

        if (!id_signature) {
            ptr.msg('Veuillez saisir l\'ID de la signature', 'danger');
        } else if (isNaN(id_signature)) {
            ptr.msg('Format de l\'ID signature invalide', 'danger');
        } else {
            ptr.$input.val('');

            setObjectAction(ptr.$button, {
                module: 'bimpcore',
                object_name: 'BimpSignature',
                id_object: id_signature
            }, 'signElec', {}, 'sign_elec', ptr.$resultContainer);
        }
    };

    this.msg = function (msg, type) {
        if (typeof (type) === 'undefined') {
            type = 'info';
        }

        ptr.$resultContainer.stop().fadeOut(100, function () {
            ptr.$resultContainer.html('');
            bimp_msg(msg, type, ptr.$resultContainer);
        });
    };
}

var BimpSignaturePad = new BimpSignaturePad();

$(document).ready(function () {
    BimpSignaturePad.init();
    ajaxRequestsUrl = dol_url_root + '/bimpcore/signature.php';
});