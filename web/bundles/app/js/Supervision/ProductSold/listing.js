/**
 * Created by mchrif on 11/03/2016.
 */

var productSoldDatatable = null;

$(function () {

    productSoldDatatable = initSimpleDataTable('#product_sold_table', {
        searching: true,
        processing: true,
        serverSide: true,
        ajax: {
            url: Routing.generate("supervision_product_sold_list_export"),
            data: function (d) {
                d.criteria = serializeArrayToObjectByKey($('#productSoldFilterForm').serializeArray(), 'name');
                return d;
            },
            type: 'post'
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
            {"data": "dateSynchro"},
            {"data": "lastDateSynchro"},
            {
                "data": null,
                "orderable": false,
                render: function (row) {
                    var action = $('#product_sold_table').attr('data-template');
                    var restaurants = row.restaurants === undefined ? [] : JSON.parse(row.restaurants);

                    var restOutput = '';

                    restaurants.forEach(function(value, key) {
                        var name = value["name"].replace(/\"/g,"&quot;");
                        name = name.replace(/\'/g,"&quot;");
                        var code = value["code"];
                        restOutput += "<li>- " + code + "- " + name  + "</li>";
                    });

                    action = action.replace(/_id_/g, row.id);
                    action = action.replace(/_product_name_/g, row.name);
                    action = action.replace(/_restaurants_/g, restOutput);
                    return action;
                },
                "targets": 0
            }
        ],
        "drawCallback": function () {
            $('[data-toggle="tooltip"]').tooltip();
        }
    });

    $(document).on('click', '.reload-sold-item', function(){
        var soldItemId = $(this).attr('data-item');
        var restaurants = $(this).attr('data-elligible-restaurans');
        var productName = $(this).attr('data-product-name');
        bootbox.confirm({
            title: Translator.trans('product.synchronization_title'),
            message: Translator.trans('product.synchronization_message',
                {
                    product: productName,
                    restaurants: restaurants
                }),
            closeButton: false,
            buttons: {

                'cancel': {
                    label: Translator.trans('keyword.no'),
                    className: 'btn btn-cancel btn-icon margin-right-left'
                },
                'confirm': {
                    label: Translator.trans('keyword.yes'),
                    className: 'btn btn-validate btn-icon margin-right-left pull-right'
                }
            },
            callback: function (result) {
                if (result) {
                    loader.show();
                    ajaxCall({
                        url : Routing.generate('force_synchronize_sold_product',{'productSold' : soldItemId}),
                        'type' : 'json'
                    },function(data){
                        if(data.data.sucess !== undefined) {
                            location.reload();
                        }
                        loader.hide();
                    })
                } else {
                }
            }
        });
    });

    $('#reset-filter').on('click', function () {
        resetFilter($('#filter-zone-search'));
        productSoldDatatable.ajax.reload();
    });

    $("#export-btn").on('click',function(){
        submitExportDocumentFile(".filter-zone",productSoldDatatable,Routing.generate("supervision_product_sold_list_export",{"download":1}));
    });

});