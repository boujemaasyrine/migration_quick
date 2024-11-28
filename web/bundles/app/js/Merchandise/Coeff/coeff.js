/**
 * Created by anouira on 09/05/2016.
 */

/* Create an array with the values of all the input boxes in a column */
$.fn.dataTable.ext.order['dom-text-numeric'] = function (settings, col) {
    return this.api().column(col, {order: 'index'}).nodes().map(function (td, i) {
        return parseFloat($('input', td).val());
    });
};

$.fn.dataTable.ext.order['t-r-f'] = function (settings, col) {
    return this.api().column(col, {order: 'index'}).nodes().map(function (td, i) {
        if ($(td).find("input[value='real']").is(':checked')) {
            return 'r';
        } else {
            return 't';
        }
    });
};

$.fn.dataTable.ext.order['fixed'] = function (settings, col) {
    return this.api().column(col, {order: 'index'}).nodes().map(function (td, i) {
        if ($(td).find("input").is(':checked')) {
            return true;
        } else {
            return false;
        }
    });
};

$(document).on('keyup', '#code-product', function () {
    productsTable.column(0).search($(this).val(), true, false, true).draw();
});

$(document).on('keyup', '#name-product', function () {
    productsTable.column(1).search($(this).val(), true, false, true).draw();
});

function refreshDataTable() {
    var suppId = $('#supplier').val();
    var catId = $('#categories').val();

    $.fn.dataTable.ext.search.pop();

    if (suppId != null && catId != null) {
        $.fn.dataTable.ext.search.push(
            function (settings, data, dataIndex) {
                return  ( $.inArray($(productsTable.row(dataIndex).node()).attr('supplier-id'),suppId)>=0 ) &&
                    ( $.inArray($(productsTable.row(dataIndex).node()).attr('cat-id'),catId)>=0 ) ;
            }
        );
    } else if (suppId != null) {
        $.fn.dataTable.ext.search.push(
            function (settings, data, dataIndex) {
                return ( $.inArray($(productsTable.row(dataIndex).node()).attr('supplier-id'),suppId)>=0 );
            }
        );
    } else if (catId != null) {
        $.fn.dataTable.ext.search.push(
            function (settings, data, dataIndex) {
                return ( $.inArray($(productsTable.row(dataIndex).node()).attr('cat-id'),catId)>=0 );
            }
        );
    }
    productsTable.draw();
}

function initDataTable() {
    $.fn.DataTable.ext.pager.numbers_length = 6;
    productsTable = initSimpleDataTable("#products", {
            "order": [[ 1, "asc" ]],
            columns: [
                null,
                null,
                {
                    orderable : false
                },
                {
                    orderable : false
                },
                {"orderDataType": "t-r-f"},
                null,
                {"orderDataType": "dom-text-numeric"},
                {"orderDataType": "fixed"}
            ],
            initComplete: function (settings, json) {
                $('#products_filter').parent().hide();
                $('#code-product, #name-product').on('click', function (event) {
                    event.stopPropagation();
                    return false;
                });
            }
        }
    );

}

function initDataTableCoef() {
    $('#supplier').selectize({
        plugins: ['remove_button']
    });
    $('#categories').selectize({
        plugins: ['remove_button']
    });

    ca = parseFloat($('#products').attr('ca'));

    initDataTable();

    $('#supplier, #categories').on('change', function () {
        refreshDataTable();
    });

    $(document).on('change', '.nature_radio', function () {
        var tdCons = $("td[p-id='" + $(this).attr('target') + "']");
        var coef_input = $("input[p-id='" + $(this).attr('target') + "']");
        var real = parseFloat(tdCons.attr('real-qty'));
        var theo = parseFloat(tdCons.attr('theo-qty'));
        var coef = null;
        if ($(this).val() == 'real') {
            if (real == 0) {
                coef = 0;
            } else {
                coef = ca / real;
            }
            tdCons.html(real);
        } else {
            if (theo == 0) {
                coef = 0;
            } else {
                coef = ca / theo;
            }
            tdCons.html(theo);
        }
        coef = Math.round(coef);
        coef_input.val(coef);
    });
}

$(document).on('click','#save_coef',function(){
    loader.show();
    productsTable.destroy();
    $('#coef_form').find('input').removeAttr('disabled');
    $('#coef_form').submit();
});

var progressTimeInterval ;
$(function(){
    var progressContainer = $('.progress-container');
    if (progressContainer.length != 0) {
        progressTimeInterval = window.setInterval(function () {
            progressBarSuivi(
                $('#progress-bars-container').attr('progress-id'),
                progressTimeInterval,
                '#coeff-product-progress-bar',
                function (result) {
                    return result.progress + '% (' + result.proceeded + "/" + result.total + " "+Translator.trans('product_label')+")";
                }, function () {
                    window.location.href = Routing.generate('show_coeff_pp',{'base':$('#progress-bars-container').attr('base-id')});
                }
            );
        }, 1000);
    }else{
        initDataTableCoef();
        var today = moment();
    }
});

/*** BASE DE CALCUL ***/

$(document).on('change', '#coef_base_startDate, #coef_base_endDate', function () {

    ajaxCall({
        url: Routing.generate('coef_calculate_base'),
        data: $("form[name='coef_base']").serialize(),
        method: 'POST'
    }, function (receivedData) {
        if (receivedData.error != undefined) {
            $("#form-zone").html(receivedData.html);
            $('#ca-zone').html(" - ");
            initDatePicker();
        } else {
            $('#ca-zone').html(floatToString(receivedData.ca,0) + " &euro; ");
            $('.form-error').hide();
        }
    })


});