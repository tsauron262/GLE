var signaturePads = [];


function on_rattachement_form_submit($form, extra_data) {
    extra_data['idLineFI_idLineCommande'] = {};
    if(extra_data['id_commande'] > 0) {
            $('.extra_select').each(function(){
            var id_line_commande_selected = $(this).children('option:selected').val();
            if(id_line_commande_selected > 0) {
                extra_data['idLineFI_idLineCommande'][$(this).attr('child_id')] = $(this).children('option:selected').val();
            }
        });
    }
    console.log(extra_data);
    return extra_data;
}

function onSignatureFormSubmit($form) {
    var $typeInput = $form.find('[name="type_signature"]');
    var $input = $form.find('[name="base_64_signature"]');

    if ($typeInput.length && $input.length) {
        var type_sign = parseInt($typeInput.val());
        if (type_sign === 3) {
            var _data = signaturePads[$form.attr('id')]._data;
            var size = 0;
            _data.forEach(element => size += element.length);
            
            
            if(_data.length > 0) {
                if(size > 25){
                    if (typeof (signaturePads[$form.attr('id')]) !== 'undefined') {
                        $input.val(signaturePads[$form.attr('id')].toDataURL('image/png'));
                        return 1;
                    } else {
                        bimp_msg('Erreur: bloc signature non trouvé', 'danger');
                    }
                }else {
                    bimp_msg("Signature client trop petite", "danger");
                }
            } else {
                bimp_msg("Signature client manquante", "danger");
            }
            return 0;
        } else {
            $input.val('');
            return 1;
        }
    }

    return 1;
}

$(document).ready(function () {
    $('body').on('bimp_ready', function () {
        $('.BT_ficheInter_form_signature').each(function () {
            var $form = $(this);

            var $signaturePad = $form.find('#signature-pad');

            if ($signaturePad.length) {
                if ($signaturePad.findParentByClass('inputContainer').width()) {
                    $signaturePad.attr('width', $signaturePad.findParentByClass('inputContainer').width());   
                } else {
                    $signaturePad.attr('width', '750px');   
                }
                $signaturePad.attr('height', '350px');

                var signaturePad = new SignaturePad($signaturePad[0], {
                    backgroundColor: 'rgba(255, 255, 255, 0)',
                    penColor: 'rgb(0, 0, 0)'
                });

                signaturePads[$form.attr('id')] = signaturePad;

                $form.find('.clearSignaturePadBtn').click(function () {
                    signaturePad.clear();
                });
            }
        });
    });
});