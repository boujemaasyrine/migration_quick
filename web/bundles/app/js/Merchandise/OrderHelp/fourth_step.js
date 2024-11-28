/**
 * Created by anouira on 05/04/2016.
 */
var progressTimeInterval = null;
var productDataTmp = null;
var dayNumberTmp = null;
var supplierIdTmp = null;
var idOrderTmp = null;

$.fn.DataTable.ext.pager.numbers_length = 6;

function initProductTables() {
    $.each($('.products'), function (key, value) {
        initProductTable('#' + $(value).attr('id'));
    });
}

function initProductTable(selector) {
    $.fn.dataTable.ext.order['dom-text-numeric'] = function (settings, col) {
        return this.api().column(col, {order: 'index'}).nodes().map(function (td, i) {
            return $('input', td).val();
        });
    };
    initSimpleDataTable(selector, {
        columns: [
            null,
            null,
            null,
            null,
            null,
            null,
            {"orderDataType": "dom-text-numeric"},
        ]
    });
}

function initSelectFilters(){
    $('#supplier-select').selectize({
        plugins: ['remove_button']
    });
    $('#day-select').selectize({
        plugins: ['remove_button']
    });
    $('#supplier-select, #day-select').on('change',function(){

        var supplierID = $('#supplier-select').val();
        var dayID = $('#day-select').val();

        var forms  = $('.form-products');
        forms.hide();
        $.each(forms,function(key,value){
            var fsID = $(value).attr('id').split('-')[1];
            var fdID = $(value).attr('id').split('-')[2];

            if (supplierID  != null && dayID != null){
                if ($.inArray(fsID,supplierID)>=0 && $.inArray(fdID,dayID)>=0 ){
                    $(value).show();
                }
            }else if (supplierID  == null && dayID != null){
                if ($.inArray(fdID,dayID)>=0){
                    $(value).show();
                }
            }else if (supplierID  != null && dayID == null) {
                if ($.inArray(fsID,supplierID)>=0){
                    $(value).show();
                }
            }else{
                $(value).show();
            }
        });

    });
}

function getResults() {
    loader.show();
    ajaxCall({
        url: Routing.generate('load_results_help_order',{displayAll: displayAll}),
        dataType: 'json'
    }, function (data) {
        $('#result-zone').html(data.data.html);
        initProductTables();
        initSelectFilters();
        loader.hide();
    });
}

function orderWithNoModification(id) {
    var html = $('#pop_up_not_free_with_no_modification').html();
    html = html.replace(/__order_details_link__/, Routing.generate('list_pendings_commands') + "#" + id);
    showDefaultModal('', html,'');
}

function orderWithModification() {
    var html = $('#pop_up_not_free_with_modification').html();
    showDefaultModal('', html,'');
}

function orderFree(productData, dayNumber, supplierId) {
    createOrder(productData, dayNumber, supplierId);
}

function createOrder(productData, dayNumber, supplierId,idOrder,complete) {
    var url ;
    if (typeof  idOrder != 'undefined'){
        url = Routing.generate('create_order_from_help') + "?day=" + dayNumber + "&supplier=" + supplierId+"&orderId="+idOrder ;
    }else{
        url = Routing.generate('create_order_from_help') + "?day=" + dayNumber + "&supplier=" + supplierId ;
    }
    ajaxCall({
        url: url ,
        method: 'POST',
        data: productData
    }, function (data) {
        if (data.data == null){
            showDefaultModal('', '<h3><span class="glyphicon glyphicon-remove"></span>'+Translator.trans('order_not_created')+'</h3>','');
        }else{
            var html = $('#order_created_pop_up_content').html();
            html = html.replace(/__order_details_link__/, Routing.generate('list_pendings_commands') + "#" + data.data);
            showDefaultModal('', html,'');
            var btn = $(".prepare-cmd-btn[target-id='table-"+supplierId+"-"+dayNumber+"']");
            btn.attr('disabled','disabled')
            btn.siblings('.confirmation-msg').show();
        }
        if (typeof complete != 'undefined'){
            complete();
        }
    });
}

$(document).on('click', '#override-btn', function () {
    createOrder(productDataTmp, dayNumberTmp, supplierIdTmp,idOrderTmp, function () {
        productDataTmp = null;
        dayNumberTmp = null;
        supplierIdTmp = null;
        idOrderTmp = null;
    });
});

$(document).on('click', '.prepare-cmd-btn', function () {
    var targetId = '#' + $(this).attr('target-id');
    var table = $(targetId).DataTable();
    table.destroy();
    var form = $(targetId.replace(/table/, 'form'));
    var productData = form.serialize();

    var supplierId = targetId.split('-')[1];
    var dayNumber = targetId.split('-')[2];
    initProductTable(targetId);

    ajaxCall(
        {
            url: Routing.generate('verify_availability') + "?day=" + dayNumber + "&supplier=" + supplierId
        }, function (data) {
            if (data.data.code == 'free') {
                orderFree(productData, dayNumber, supplierId);
            } else if (data.data.code == 'not_free_with_modification') {
                productDataTmp = productData;
                dayNumberTmp = dayNumber;
                supplierIdTmp = supplierId;
                idOrderTmp = data.data.id;
                orderWithModification();
            } else {
                orderWithNoModification(data.data.id);
            }
        }
    );
});

$(function () {
    var progressContainer = $('.progress-container');
    if (progressContainer.length != 0) {
        progressTimeInterval = window.setInterval(function () {
            progressBarSuivi(
                $('#calculate-result-progress').attr('progress-id'),
                progressTimeInterval,
                '#calculate-result-progress',
                function (result) {
                    return result.progress + '% (' + result.proceeded + "/" + result.total + " "+Translator.trans('product_label')+")";
                }, function () {
                    $('#progress-bars-container').remove();
                    getResults();
                }
            );
        }, 1000);
    } else {
        getResults();
    }
});