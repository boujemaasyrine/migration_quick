/**
 * Created by mchrif on 11/03/2016.
 */

var productSoldDatatable = null;

$(function () {

    productSoldDatatable = initSimpleDataTable('#product_sold_table', {
        searching: true,
        processing: true,
        serverSide: true,
        pageLength: 100,
        ajax: {
            "url": Routing.generate('product_sold_list_export'),
            data: function (d) {
                d.criteria = serializeArrayToObjectByKey($('#productSoldFilterForm').serializeArray(), 'name');
                return d;
            },
            "type": "post"
        },
        "columns": [
            {"data": "codePlu"},
            {"data": "name"},
            {
                "data": "type",
                render: function (row) {
                    return Translator.trans(row);
                }
            },
            {
                "data": "active",
                render: function(row){
                    if(row == true)
                        return '<div class="label label-success" style="color:#fff !important; margin: 0">'  + Translator.trans('status.active')+'</div>';
                    return '<div class="label label-danger" style="color:#fff !important; margin: 0">'  +Translator.trans('status.inactive')+'</div>';
                }
            },
            {
                "data": null,
                "orderable": false,
                render: function (row) {
                    var action = "<button data-id='" + row.id +"' type='button' class='btn btn-view btn-icon detail-btn btn-xs'>" + Translator.trans('btn.view')+ "</button>";
                    return action;
                },
                "targets": 0
            }
        ],
        "drawCallback": function () {
            $('[data-toggle="tooltip"]').tooltip();
        }
    });

    $(document).on('click','.detail-btn',function(){

        var productSoldId = $(this).attr('data-id');
        loader.show();
        ajaxCall({
            url : Routing.generate('product_sold_detail',{'productSold' : productSoldId}),
            'type' : 'json'
        },function(data){
            showDefaultModal(Translator.trans('product_sold.detail_title'),data.data[0]);

            initSimpleDataTable('.recipes', {
                "lengthChange": false,
                processing: false,
                serverSide: false,
                searching: true,
                lengthMenu: true,
                ordering: false
            });
            loader.hide();
        })

    });

    $('#reset-filter').on('click', function () {
        resetFilter($('#filter-zone-search'));
        productSoldDatatable.ajax.reload();
    });

    $("#export-btn").on('click',function(){
        submitExportDocumentFile(".filter-zone",productSoldDatatable,Routing.generate("product_sold_list_export",{"download":1}));
    });

});