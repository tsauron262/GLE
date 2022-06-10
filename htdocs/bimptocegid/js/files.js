let hookEditing = false;

function editingClick() {
    hookEditing = true;
    setStateButton();
}

function cancelClick() {
    hookEditing = false;
    setStateButton();
    
}

function setStateButton() {

    var $pre = $('#file');
    var $preEditing = $('#fileEdit');
    var $buttonEditing = $('#button_edit');
    var $buttonCancel = $('#button_cancel');
    
    $preEditing.text($pre.attr('original-tra'));
    
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
