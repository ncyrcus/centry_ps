$(function () {
    $('.select2').select2();
    $('.category_select2').select2({
  
        data: JSON.parse($("#CENTRY_CATEGORIES").val()),
        minimumInputLength: 3


    });
    var categories_selected = JSON.parse($("#CENTRY_CATEGORIES_SELECTED").val());
    categories_selected.forEach(element => {
        var id_selector= "#categories\\["+element['id_category']+"\\]";
        $(id_selector).val(element['id_centry']); // Select the option with a value of '1'
        $(id_selector).trigger('change'); // Notify any JS components that the value changed
        
        
    });

    $('.manufacturer_select2').select2({
  
        data: JSON.parse($("#CENTRY_MANUFACTURER").val()),
        minimumInputLength: 3


    });
    var manufacturers_selected = JSON.parse($("#CENTRY_MANUFACTURER_SELECTED").val());
    manufacturers_selected.forEach(element => {
        console.log(element);
        var id_selector= "#manufacturers\\["+element['id_manufacturer']+"\\]";
        if(element['id_centry']){
            $(id_selector).val(element['id_centry']); // Select the option with a value of '1'
            $(id_selector).trigger('change'); // Notify any JS components that the value changed
        }
        
    });
    var bien, mal;

    $(".centry_sync_massive").click(function (e) {
        e.preventDefault();
        $(this).prop("disabled", true);
        var ids = $("#CENTRY_SYNC_PRODUCTS_IDS").val().split(",");
        var ajaxURL = $("#CENTRY_AJAX_URL").val();

        bien = [];
        mal = [];
        pb.progressbar("value", 0);
        sincronizar(ajaxURL, 0, ids);
        $(this).prop("disabled", false);
    });

    $("#CENTRY_SYNC_PRODUCTS_IDS").parents(".form-wrapper").append('<div id="centry_sync_progressbar"></div>');

    var pb = $("#centry_sync_progressbar");
    pb.progressbar({
        value: 0
    });

    function sincronizar(url, indice, ids) {
        $.get(url, {dispatcher: "ProductSyncDispatcher", id_product: ids[indice]})
                .done(function () {
                    bien.push(ids[indice]);
                })
                .fail(function () {
                    mal.push(ids[indice]);
                })
                .always(function () {
                    pb.progressbar("value", Math.floor(++indice * 100 / ids.length));
                    if (indice < ids.length) {
                        sincronizar(url, indice, ids);
                    } else {
                        console.log("buenos: ", bien);
                        console.log("malos: ", mal);
                        alert("Sincroniación terminada");
                    }
                });
    }

    $("form.centry_ps input, form.centry_ps select").addClass("no-editado");

    $("form.centry_ps input, form.centry_ps select").on('input', function () {
        $(this).removeClass("no-editado");
    });

    $("form.centry_ps input, form.centry_ps select").on('change', function () {
        $(this).removeClass("no-editado");
    });

    $('form.centry_ps').submit(function (e) {
        if ($('form.centry_ps input[id^="CENTRY_FIELDS_TO_REPAIR_"]:checked').length > 0
                && !confirm("¿Seguro de querer reparar las tablas seleccionadas?\nTen presente que se perderá toda la información que ellas contengan.")) {
            e.preventDefault();
        } else {
            $(".no-editado").remove();
        }
    });
});