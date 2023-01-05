


function editDocumentation(idSection, name, serializedMenu){
    var btnValid = '<button onclick="saveDocumentation(\''+ idSection +'\', \''+ name +'\', \''+ serializedMenu +'\');">Valider</button>';
    BimpAjax('loadDocumentation', {
            name: name,
            mode: 'edit'
        }, null, {
            display_success: false,
            display_errors_in_popup_only: true,
            success_msg: '',
            success: function (result, bimpAjax) {
                var html = btnValid;
                html += '<textarea id="edit_'+idSection+'" style="min-height: 501px;width: 100%;">'+result.html+'</textarea>';
                html += btnValid;
                html += '<div class="container"><form method="post" action="" enctype="multipart/form-data" id="myform">Nouveau nom :<input type="text" id="new_name"/><br/>Fichier :<input type="file" id="file" name="file" /><input type="button" onclick="uploadFile($(this).parent().parent());" class="button" value="Upload" id="but_upload"></form></div>';
                 $('#'+idSection).html(html);
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
            display_success: true,
            display_errors_in_popup_only: true,
            success_msg: 'Enregistrement de la doc OK',
            success: function (result, bimpAjax) {
                $('#'+idSection).html(result.html);
                $('#'+idSection+"_menu").html(result.htmlMenu);
            }
        });
}

function uploadFile(form){
    var fd = new FormData();
    var files = $(form).find('#file')[0].files;
    var new_name = $(form).find('#new_name').val();

    // Check file selected or not
    if(files.length > 0 ){
       fd.append('file',files[0]);
       fd.append('new_name',new_name);
       
       BimpAjax('uploadBimpDocumentationFile', fd, null, {
            display_success: true,
            display_errors_in_popup_only: true,
            success_msg: 'Fichier Uploadé',
            success: function (result, bimpAjax) {
                $(form).find('#file').val('');
                $(form).find('#new_name').val('');
            }
        });
    }else{
       alert("Please select a file.");
    }
}


