let hookEditing = false;
let originalFile = "";

function editingClick() {
    hookEditing = true;
    originalFile = $('#fileEdit').html();
    
    setStateButton();
}

function cancelClick() {
    hookEditing = false;
    setStateButton();
}

function savingClick() {
    
    BimpAjax('saveFile', {
        originalTRA: originalFile,
        newTRA: $('#fileEdit').html(),
        fichier: $('#fileEdit').attr('fichier'),
    }, '', {display_processing: true}, {
        success: function (result, bimpAjax) {
            hookEditing = false;
            setStateButton();
        }, error: function (result, bimpAjax) {
            console.log(result);
        }
    });
}

function setStateButton() {

    var $pre = $('#file');
    var $preEditing = $('#fileEdit');
    var $buttonEditing = $('#button_edit');
    var $buttonCancel = $('#button_cancel');
    
    $preEditing.text(originalFile);
    
    // Etat quand la fenetre s'ouvre
    var cssDisplayEditingButton     = 'block';
    var cssDisplayPre               = 'block';
    var cssDisplayEditingPre        = 'none';
    var contenteditable             = false;
    var cssDisplayCancelButton      = 'none';
    
    if(hookEditing) {
        var cssDisplayEditingButton     = 'none';
        var cssDisplayPre               = 'none';
        var cssDisplayEditingPre        = 'block';
        var contenteditable             = true;
        var cssDisplayCancelButton      = 'block';
    }
    
    $buttonEditing.css('display', cssDisplayEditingButton);
    $buttonCancel.css('display', cssDisplayCancelButton);
    $pre.css('display', cssDisplayPre);
    $preEditing.css('display', cssDisplayEditingPre);
    $preEditing.attr('contenteditable', contenteditable);
    
}
