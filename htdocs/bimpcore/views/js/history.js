

function send_detail(id) {
    var data = {};
    data['typeofobject'] = id;
    BimpAjax('displayDetails', data ,null, {
        display_processing: true,
        success: function (result, bimpAjax) {
            $('div#selected_object > div.panel-body').empty();
            $('div#selected_object > div.panel-body').html(result.html);
        }
    });
}

$(document).ready(function () {
    $('td[name="display_details"]').click(function(){
        send_detail($(this).attr('id'));
    });
});
