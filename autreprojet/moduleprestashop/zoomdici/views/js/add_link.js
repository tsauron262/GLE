$(document).ready(function () {
    $('div#order-infos  ul')
            .append('<li style="text-align: center;"><a class="btn btn-primary"' +
                    'href="' + base_url + 'index.php?fc=module&module=zoomdici&controller=validateorder&id_order=' + id_order + '">Obtenir les tickets</a></li>');
});