function editDocumentation(idSection, name, serializedMenu){
    var btnValid = '<button onclick="saveDocumentation(\''+ idSection +'\', \''+ name +'\', \''+ serializedMenu +'\');">Valider</button>';
    BimpAjax('loadDocumentation', {
            name: name,
            mode: 'edit'
        }, null, {
            display_success: false,
            display_errors_in_popup_only: true,
            success_msg: 'Enregistrement de la durée totale effectué',
            success: function (result, bimpAjax) {
                 $('#'+idSection).html(btnValid+'<textarea id="edit_'+idSection+'" style="min-height: 501px;width: 100%;">'+result.html+'</textarea>'+btnValid);
            }
        });
}

function saveDocumentation(idSection, name, serializedMenu){
    BimpAjax('saveBimpDocumentation', {
            name: name,
            html: $('#edit_'+idSection).val(),
            idSection: idSection,
            serializedMenu: serializedMenu
        }, null, {
            display_success: false,
            display_errors_in_popup_only: true,
            success_msg: 'Enregistrement de la durée totale effectué',
            success: function (result, bimpAjax) {
                $('#'+idSection).html(result.html);
                $('#'+idSection+"_menu").html(result.htmlMenu);
            }
        });
}