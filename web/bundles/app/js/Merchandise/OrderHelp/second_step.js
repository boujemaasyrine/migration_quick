/**
 * Created by anouira on 01/04/2016.
 */

var progressTimeInterval = null;
var productsTable = null;
var ca = null;


function getCoefTable(idTmp) {
    ajaxCall({
        url: Routing.generate('get_coef_table', {tmp: idTmp}),
        dataType: 'json'
    }, function (data) {
        $('#data-table').html(data.data);
        $('.no-inventory-exist-alert').tootltipBootstrap();
        initDataTableForSecondStep();
        loader.hide();
    });
}

function launchCoefCalculation() {
    var idTmp = $('#init-product-progress-bar').attr('order-Tmp');
    ajaxCall({
        url: Routing.generate('launch_coef_calculation', {tmp: idTmp}),
        dataType: 'json'
    }, function (data) {
        if (data.data.ProgressId != null) {
            progressTimeInterval = window.setInterval(function () {
                progressBarSuivi(data.data.ProgressId,
                    progressTimeInterval,
                    "#coeff-product-progress-bar",
                    function (result) {
                        return result.progress + '% (' + result.proceeded + "/" + result.total + " "+Translator.trans('product_label')+")";
                    }, function () {
                        loader.show();
                        $('#progress-bars-container').hide();
                        getCoefTable(idTmp);
                    }
                )
            }, 1000);
        }
    });

}

function initDataTable() {
    $.fn.DataTable.ext.pager.numbers_length = 6;
    productsTable = initSimpleDataTable("#products", {
            columns: [
                null,
                null,
                null,
                null,
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

function initDataTableForSecondStep() {
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

    $('#goToStep3Btn').on('click', function () {
        productsTable.destroy();
        $('#second_step_form').submit();
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

$(document).on('change', '.fixed-checkbox', function () {
    var btnsRadio = $(this).parent().siblings('.btn-radio-td').find('input');
    var consumedQty = $(this).parent().siblings('.consumed-qty-class');
    if ($(this).is(':checked')) {
        btnsRadio.prop('disabled', 'disabled');
    } else {
        if (consumedQty.attr('stock-final-exist') != undefined && consumedQty.attr('stock-final-exist') == '1'){
            btnsRadio.removeAttr('disabled');
        }
    }
});

$(document).on('submit', '#second_step_form', function () {
    $('.btn-radio-td').find('input').removeAttr('disabled');
});

$(document).on('keyup', '#code-product', function () {
    productsTable.column(0).search($(this).val(), true, false, true).draw();
});

$(document).on('keyup', '#name-product', function () {
    productsTable.column(1).search($(this).val(), true, false, true).draw();
});

$(function () {

    var progressContainer = $('.progress-container');
    if (progressContainer.length != 0) {
        progressTimeInterval = window.setInterval(function () {
            progressBarSuivi(
                $('#init-product-progress-bar').attr('progress-id'),
                progressTimeInterval,
                '#init-product-progress-bar',
                function (result) {
                    return result.progress + '% (' + result.proceeded + "/" + result.total + " "+Translator.trans('product_label')+")";
                }, function () {
                    launchCoefCalculation();
                }
            );
        }, 1000);
    }

    initDataTableForSecondStep();

});