var deliveries = null;
var listSupp = null;

$(function () {

    deliveries = initSimpleDataTable('#deliveries_table', {
        rowId: 'id',
        processing: true,
        serverSide: true,
        searching: false,
        "order": [[3, "desc"]],
        columnDefs: [
            {
                targets: [6],
                orderable: false
            }
        ],
        "columns": [
            {"data": "num_delivery"},
            {"data": "order.supplier"},
            {"data": "order.dateOrder"},
            {"data": "date"},
            {
                "data": "valorization",
                render: function (data) {
                    return floatToString(data);
                }
            },
            {"data": "responsible"},
            {
                className: 'actions-btn',
                "render": function (data, type, row) {
                    var btn = "<button type='button' class='btn blue  detail-btn'><span class='glyphicon glyphicon-eye-open'></span> Voir</button>";
                    return btn;
                }
            }

        ],
        ajax: {
            url: Routing.generate("deliveries_list"),
            data: function (d) {
                d.criteria = serializeArrayToObjectByKey($('#delivery-filter-form').serializeArray(), 'name');
                return d;
            }
            ,
            type: 'post'
        }
    });
    listSupp = $('#supplier').selectize();
    listSupp = listSupp[0].selectize ;
});

$(document).on('click', '.detail-btn', function () {
    var deliveryId = $(this).parentsUntil('tbody', 'tr').attr('id');
    loader.show();
    showDetailsInPopUp(Routing.generate('delivery_details', {'delivery': deliveryId}));
});

$(function () {
    $('#reset-filter').on('click', function () {
        resetFilter($('.filter-zone'));
        listSupp.clear();
    });

    $("#export-btn").on('click', function () {
        submitExportDocumentFile(".filter-zone", deliveries, Routing.generate("deliveries_list", {"download": 1}));
    });
    $("#export-xls").on('click', function () {
        submitExportDocumentFile(".filter-zone", deliveries, Routing.generate("deliveries_list", {"download": 2}));
    });

    showEntityDetailsWhenDocumentReady('delivery_details','delivery','');

});