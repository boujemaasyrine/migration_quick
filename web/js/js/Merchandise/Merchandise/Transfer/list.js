/**
 * Created by anouira on 03/03/2016.
 */

var listTransfer = null;
var restList = null ;

$(function () {
    listTransfer = initSimpleDataTable('#transfer-table', {
        lengthChange: false,
        rowId: 'id',
        processing: true,
        serverSide: true,
        searching: false,
        lengthMenu: false,
        "order": [[ 3, "desc" ]],
        columnDefs: [
            {
                targets: [5],
                orderable: false
            }
        ],
        "columns": [
            {"data": "num"},
            {
                "data": "type",
                "render": function (data) {
                    return Translator.trans(data);
                }
            },
            {"data": "restaurant"},
            {"data": "date"},
            {
                "data": "val",
                render : function(data){
                    return floatToString(data);
                }

            },

            {
                className: 'actions-btn',
                "render": function (data, type, row) {
                    var btn= "<button type='button' class='btn blue  detail-btn'><span class='glyphicon glyphicon-eye-open'></span> Voir</button>" ;
                    return btn;
                }
            }

        ],
        ajax: {
            url: Routing.generate("transfer_list"),
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
        restList.clear();
    });

    $("#export-btn").on('click',function(){
        submitExportDocumentFile("#filter-form",listTransfer,Routing.generate("download_transfers_file", {"type": 1}));
    });
    $("#export-xls").on('click',function(){
        submitExportDocumentFile("#filter-form",listTransfer,Routing.generate("download_transfers_file",{"type": 2}));
    });
    restList  = $('select[name=restaurant]').selectize();
    restList = restList[0].selectize ;

    showEntityDetailsWhenDocumentReady('transfer_details','transfer',Translator.trans('transfer_details'));

});

$(document).on('click', '.detail-btn', function () {
    var idTransfer = $(this).parentsUntil('tbody', 'tr').attr('id');
    loader.show();
    ajaxCall({
        url: Routing.generate('transfer_details', {'transfer': idTransfer}),
        dataType: 'json'
    }, function (data) {
        showDefaultModal(Translator.trans('transfer_details'), data.data,'','95%','95%');
        loader.hide();
    });
});
