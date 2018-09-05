function toggleAllUserCheck() {
    var $checkboxes = $('#usersCheckboxes').find('input[type=checkbox]');
    if ($('#checkAllUsers').prop('checked')) {
        $('#checkAllUsersLabel').text('Tout décocher');
        $checkboxes.each(function () {
            $(this).attr('checked', '');
        });
    } else {
        $('#checkAllUsersLabel').text('Tout cocher');
        $checkboxes.each(function () {
            $(this).removeAttr('checked');
        });
    }
}

    var exept = false;
$(document).ready(function () {
    $('input[name=usersGroup]').change(function () {
        if ($(this).val() == 1) {
            $('#singleUserBlock').slideUp(250);
            $('#userGroupBlock').slideDown(250);
        } else {
            $('#userGroupBlock').slideUp(250);
            $('#singleUserBlock').slideDown(250);
        }
    });
    $('#group_id').change(function () {
        $('#groupUsersCheckboxes').hide();
        $('#groupUsersList').html('').hide();
        $('div.loading').hide();
        var groupId = $(this).val();
        if (groupId < 0) {
            $('#showGroupUsers').hide();
        } else {
            $('#showGroupUsers').show();
            $('div.loading').show();
            $.ajax({
                url: "./groupUsers.php",
                dataType: "html",
                data: {'groupId': groupId},
                success: function (html) {
                    $('div.loading').hide();
                    $('#groupUsersList').html(html).show();
                },
                error: function () {
                    $('div.loading').hide();
                    $('#groupUsersList').html('<p style="color: #A00000">Erreur: la liste des utilisateurs n\'a pas pu être chargée.</p>').show();
                }
            });
        }
    });
    $('#showGroupUsers').click(function () {
        $(this).hide();
        $('#groupUsersCheckboxes').show();
    });
    $('#closeGroupUsers').click(function () {
        $('#showGroupUsers').show();
        $('#groupUsersCheckboxes').hide();
    });
    $('#showUsersList').click(function () {
        $(this).hide();
        $('#usersListContainer').show();
    });
    $('#closeUsersList').click(function () {
        $('#showUsersList').show();
        $('#usersListContainer').hide();
    });




    inputCentre = $('textarea[name="description"]');
    inputChoix = $("select[name='centreRapide']");
    inputChoix.change(function () {
        inputCentre.val(inputCentre.val() + " " + $(this).find(' option:selected').text());
    });
    inputException = $('#is_exception');
    
    function changeCheck(){
        if (inputException.prop('checked') == true) {
            inputChoix.show();
            exept = true;

        } else {
            inputChoix.hide();
            exept = false;
        }
    }

    inputException.change(function () {
        if ($(this).prop('checked') == true && $('#is_rtt').prop('checked') == true)
            $('#is_rtt').prop('checked', false);
        changeCheck();
    });
    $('#is_rtt').change(function () {
        if ($(this).prop('checked') && $('#is_exception').prop('checked'))
            inputException.prop('checked', false);
        changeCheck();
    });



});







    function valider()
    {
        if(exept  && $('textarea[name="description"]').val() == ""){
            alert("Choisir la raison");
            return false;
        }
        
        
        if (document.demandeCP.date_debut_.value != "")
        {
            if (document.demandeCP.date_fin_.value == "") {
                alert("Vous devez choisir une date de fin.");
                return false;
            }
        } else
        {
            alert("Vous devez choisir une date de début.");
            return false;
        }
    }