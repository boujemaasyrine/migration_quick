/**
 * Created by hcherif on 12/02/2016.
 */

$(function () {
    $select = $('.selectize').selectize({});
    list_inventory_item = initSimpleDataTable('#inventory_item_list_table', {
        searching: true,
        processing: true,
        serverSide: true,
        rowId: 'id',
        "order": [[ 0, "asc" ]],
        "aoColumnDefs": [
            { "bSortable": false, "aTargets": [ 5 ] }
        ],
        "columns": [
            {"data": "code"},
            {"data": "name"},
            {"data": "buyingCost"},
            {"data": "supplier"},
            {
                "data": "statusKey",
                "render": function(data){
                    if (data == 'active'){
                        return '<div class="label label-success" style="color:#fff !important;">'  + Translator.trans('status.active')+'</div>';
                    }
                    else if (data == 'toInactive') {
                        return '<div class="label label-warning" style="color:#fff !important;">'  + Translator.trans('status.toInactive')+'</div>'
                    }
                }
            },
            {
                className: 'actions-btn',
                "render": function (data, type, row) {
                    var btn= "<button type='button' class='btn btn-view btn-icon btn-xs  detail-btn'>" +
                        " " + Translator.trans('btn.view') + "</button>" ;
                    return btn;
                }
            }

        ],
        ajax: {
            url: Routing.generate("inventory_items_json_list"),
            data: function (d) {
                d.criteria = serializeArrayToObjectByKey($('#itemInventoryFilterForm').serializeArray(), 'name');
                return d;
            },
            type: 'post'
        }

    });

    $("#export-btn").on('click',function(){
        submitExportDocumentFile(".filter-zone",list_inventory_item,Routing.generate("inventory_items_json_list",{"download":1}));
    });

    $('#reset-filter').on('click', function () {
        resetFilter($('.filter-zone'));
        var control = $select[0].selectize;
        control.clear();
        list_inventory_item.ajax.reload();
    });

    $(document).on('click','.detail-btn',function(){

        var inventoryItemId = $(this).parentsUntil('tbody','tr').attr('id');
        loader.show();
        ajaxCall({
            url : Routing.generate('inventory_item_detail',{'inventoryItem' : inventoryItemId}),
            'type' : 'json'
        },function(data){
            showDefaultModal(Translator.trans('item.inventory.details'),data.data, null, '95%');
            loader.hide();
        })

    });
});
