/**
 * Created by anouira on 04/03/2016.
 */


var listTransfer = null;
$(function () {
    listTransfer = initSimpleDataTable('#return-table', {
        lengthChange: false,
        rowId: 'id',
        processing: true,
        serverSide: true,
        searching: false,
        lengthMenu: false,
        "order": [[1, "desc"]],
        columnDefs: [
            {
                targets: [4],
                orderable: false
            }
        ],
        "columns": [
            {"data": "supplier"},
            {"data": "date"},
            {"data": "responsible"},
            {
                "data": "valorization",
                render: function (data) {
                    return floatToString(data);
                }
            },
            {
                className: 'actions-btn',
                "render": function (data, type, row) {
                    var btn = "<button type='button' class='btn blue  detail-btn'><span class='glyphicon glyphicon-eye-open'></span> Voir</button>";
                    return btn;
                }
            }

        ],
        ajax: {
            url: Routing.generate("json_return_list"),
            data: function (d) {
                d.criteria = serializeArrayToObjectByKey($('#filter-form').serializeArray(), 'name');
                return d;
            }
            ,
            type: 'post'
        }
    });

    $('#reset-filter').on('click', function () {
        resetFilter($('.filter-zone'));
    });

    $("#export-btn").on('click', function () {
        submitExportDocumentFile(".filter-zone", listTransfer, Routing.generate("json_return_list", {"download": 1}));
    });
    $("#export-xls").on('click', function () {
        submitExportDocumentFile(".filter-zone", listTransfer, Routing.generate("json_return_list", {"download": 2}));
    });

    showEntityDetailsWhenDocumentReady('details_return','return',Translator.trans('return_details_title'));

});

$(document).on('click', '.detail-btn', function () {
    var idReturn = $(this).parentsUntil('tbody', 'tr').attr('id');
    loader.show();
    ajaxCall({
        url: Routing.generate('details_return', {'return': idReturn}),
        dataType: 'json'
    }, function (data) {
        showDefaultModal(Translator.trans('return_details_title'), data.data,'','95%','95%');
        loader.hide();
    });
});
