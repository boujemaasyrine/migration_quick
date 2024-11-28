/**
 * Created by mchrif on 12/02/2016.
 */
var list = null;
$(function () {
    list = initSimpleDataTable('#pending_commands_table', {
        lengthChange: false,
        rowId: 'id',
        processing: true,
        serverSide: true,
        searching: false,
        lengthMenu: false,
        "order": [[ 2, "asc" ]],
        columnDefs: [
            {
                targets: [6],
                orderable: false
            }
        ],
        "columns": [
            {"data": "numOrder"},
            {"data": "supplier"},
            {"data": "dateOrder"},
            {"data": "dateDelivery"},
            {"data": "responsible"},
            {
                "data": "status",
                className: 'status',
                render: function (data) {
                    return  "<span class='status-badge'>"+Translator.trans(data, [], 'order_status')+"</span>";
                }
            },
            {
                className: 'actions-btn',
                "render": function (data, type, row) {
                    var btn= "<button type='button' class='btn blue  detail-btn'><span class='glyphicon glyphicon-eye-open'></span>"+Translator.trans('see', [], 'messages')+"</button>" ;
                    return btn;
                }
            }

        ],
        ajax: {
            url: Routing.generate("pending_list"),
            data: function (d) {
                d.criteria = {
                    'supplier': $('#supplier').val(),
                    'num_order': $('#num_order').val(),
                    'ca': $('#ca').val(),
                    'date_order': $('#date_order').val(),
                    'date_delivery': $('#date_delivery').val(),
                    'status' : $('#status').val()
                };
                return d;
            }
            ,
            type: 'post'
        },
        createdRow: function (row, data) {
            $(row).addClass(data.status);
        }
    });
});

function cancelOrder(order) {
    loader.show();
    ajaxCall({
        url: Routing.generate('cancel_order', {'order': order}),
        dataType: 'json'
    }, function (data) {
        if (typeof data.errors != 'undefined' && data.errors.length > 0) {
            var errorMsg = "<div class='alert alert-danger'>" + Translator.trans(data.errors[0]) + "</div>"
            showDefaultModal(Translator.trans('delete_order'), errorMsg,'','50%');
        } else {
            showDefaultModal(Translator.trans('delete_order_confirmation',{'num_order':data.orderNum}), data.html,'','50%');
        }
        loader.hide();
    }, null, null, true);
}

$(document).on('click', '.detail-btn', function () {
    var orderID = $(this).parentsUntil('tbody', 'tr').attr('id');
    loader.show();
    ajaxCall({
        url: Routing.generate("details_order", {'order': orderID}),
        dataType: 'json'
    }, function (data) {
        loader.hide();
        showDefaultModal('', data.data,'','95%','95%');
    });
});

$(document).on('click', '.cancel-btn', function () {
    var order = $(this).attr('order-id');
    cancelOrder(order);
});

$(document).on('click', '.mark-as-sended-btn', function () {
    var self = $(this);
    var order = $(this).parentsUntil('tbody', 'tr').attr('id');
    loader.show();
    ajaxCall({
        url : Routing.generate('mark_as_sended',{order:order}),
        dataType : 'json'
    },function(data){
        if (data.data){
            var tr = self.parentsUntil('tbody', 'tr');
            tr.removeClass('unsended');
            tr.addClass('sended');
            tr.find('.status').html(Translator.trans('sended', [], 'order_status'));
            self.remove();
        }
        loader.hide();
    });
});

$(function () {
    $('#reset-filter').on('click', function () {
        resetFilter($('.filter-zone'));
    });

    $("#export-btn").on('click',function(){
        submitExportDocumentFile(".filter-zone",list,Routing.generate("pending_list",{ 'download' : 1 }));
    });

    $("#export-xls").on('click',function(){
        submitExportDocumentFile(".filter-zone",list,Routing.generate("pending_list",{ 'download' : 2 }));
    });

    showEntityDetailsWhenDocumentReady('details_order','order','');

});

