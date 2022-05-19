function onSearchFormSubmit($form) {
    var $inputAuxiliaire = $form.find('[name="auxiliare"]');
    var $inputFacture = $form.find('[name="facture"]');
    var $inputSearchBy = $form.find('[name="serachBy"]');    
    var continueSearching = true;
    
    $inputAuxiliaire.parent().removeClass('value_required');
    $inputFacture.parent().removeClass('value_required');
    
    /**
     * $inputSearchBy values
     * 0 -> Par compte auxiliaire
     * 1 -> Par facture
     */
    
    if($inputSearchBy.children('option:selected').val() == 0 && $inputAuxiliaire.val().length == 0) {
        continueSearching = false;
        bimp_msg("Il doit y avoir un code auxiliaire", "danger");
        $inputAuxiliaire.parent().addClass('value_required');
    }

    if($inputSearchBy.children('option:selected').val() == 1 && $inputFacture.val().length == 0) {
        continueSearching = false;
        bimp_msg("Il doit y avoir une facture", "danger");
        $inputFacture.parent().addClass('value_required');
    }
    
    if(continueSearching) {
        BimpAjax('search', {
            aux: $inputAuxiliaire.val(),
            facture: $inputFacture.val(),
            searchBy: $inputSearchBy.children('option:selected').val()
        }, '', {display_processing: true}, {
            success: function (result, bimpAjax) {
            }, error: function (result, bimpAjax) {}
        });
    }

//    if($inputAuxiliaire.val().length > 0) {
//        console.log($inputAuxiliaire.val().length);
//        console.log($inputSearchBy.children('option:selected').val());
//        $inputAuxiliaire.parent().removeClass('value_required');
//
//        BimpAjax('search', {
//            aux: $inputAuxiliaire.val(),
//            searchBy: $inputSearchBy.children('option:selected').val()
//        }, '', {display_processing: true}, {
//            success: function (result, bimpAjax) {
//            }, error: function (result, bimpAjax) {}
//        });
//
//    } else {
//        bimp_msg("Il doit y avoir un code auxiliaire", "danger");
//        $inputAuxiliaire.parent().addClass('value_required');
//    }

}
