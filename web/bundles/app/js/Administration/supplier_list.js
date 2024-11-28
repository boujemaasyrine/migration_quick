/**
 * Created by hcherif on 16/02/2016.
 */

$(function () {
    list_supplier = initSimpleDataTable('#supplier_list_table', {
        searching: false,
        processing: true,
        serverSide: true,
        "order": [[ 1, "asc" ]],
        lengthMenu: false,
        "lengthChange": false,
        "columns": [
            {"data": "code"},
            {"data": "name"},
            {"data": "designation"},
            {"data": "address"},
            {"data": "phone"},
            {"data": "mail"}

        ],
        ajax: {
            url: Routing.generate("supplier_json_list"),
            data: function (d) {
                d.criteria = serializeArrayToObjectByKey($('#supplierFilterForm').serializeArray(), 'name');
                return d;
            },
            type: 'post'
        }

    });


    $(document).on('click', '.detail-btn', function(e){
        var modalId = 'detail-supplier-modal-' + $(this).attr('id');
        showDefaultModal(Translator.trans('provider.list.details'), $('#' + modalId).html());
    });

    $("#export-btn").on('click',function(){
        submitExportDocumentFile(".filter-zone",list_supplier,Routing.generate("supplier_json_list",{"download":1}));
    });

    $('#reset-filter').on('click', function () {
        resetFilter($('.filter-zone'));
        list_supplier.ajax.reload();
    });

});

