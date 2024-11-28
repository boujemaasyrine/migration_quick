/**
 * Created by bbarhoumi on 09/05/2016.
 */
$(function () {
    chestCounts = initSimpleDataTable('#chest_counts_table', {
        rowId: 'id',
        processing: true,
        serverSide: true,
        searching: true,
        pageLength: 10,
        "order": [[0, "desc"]],
        "columns": [
            {"data": "date"},
            {"data": "owner"},
            {"data": "realCounted"},
            {"data": "gap"},
            {
                "data": "closured",
                "render": function (value) {
                    if (value) {
                        return Translator.trans('keyword.yes');
                    } else {
                        return Translator.trans('keyword.no');
                    }
                }
            },
            {"data": "closureDate"},
            {
                className: 'actions-btn',
                "render": function (data, type, row) {
                    var btn = "<button type='button' class='btn btn-view btn-icon detail-btn'>" +
                        " " + Translator.trans('btn.view_details') + "</button>" +
                        '<a class="strech_btn text-black" href="' + Routing.generate('chest_count_detail_print', {'chestCount' : row.id}) +'" id="downloadReport"> ' +
                        '<img src="' + pdfIcon + '" style="height: 25px" alt="' + Translator.trans('btn.download') + Translator.trans('btn.download_pdf') + '"/>' +
                        '<span style="color: #000;">' + Translator.trans('btn.download_pdf') + '</span> </a>' +
                        '<a class="strech_btn text-black" href="'+ Routing.generate('chest_count_detail_print', {'chestCount' : row.id, xls: true}) +'" id="export-btn"> ' +
                        '<img src="' + xlsIcon + '" style="height: 25px" alt="' + Translator.trans('btn.download') + Translator.trans('btn.download_xls') + '"/> ' +
                        '<span style="color: #000;">' + Translator.trans('btn.download_xls') + '</span> </a>';
                    return btn;
                }
            }
        ],
        ajax: {
            url: Routing.generate("chest_list_json"),
            data: function (d) {
                d.criteria = serializeArrayToObjectByKey($('#filterChestCountsForm').serializeArray(), 'name');
                return d;
            },
            type: 'post'
        }
    });
    $('#reset-filter').on('click', function () {
        resetFilter($('.filter-zone'));
        chestCounts.ajax.reload();
    });
    $("#export-btn").on('click', function () {
        submitExportDocumentFile(".filter-zone", chestCounts, Routing.generate("chest_list_json", {"download": 1}));
    });

    $("#export-xls").on('click', function () {
        submitExportDocumentFile(".filter-zone", chestCounts, Routing.generate("chest_list_json", {"download": 2}));
    });

    $(document).on('click', '.detail-btn', function () {
        loader.block();
        var chestCountId = $(this).parentsUntil('tbody', 'tr').attr('id');
        ajaxCall({
            url: Routing.generate('chest_count_detail', {'chestCount': chestCountId}),
            'type': 'json'
        }, function (data) {
            showDefaultModal(Translator.trans('chest.listing.details'), data.data, data.dataFooter, '90%');
            loader.unblock();
        }, function (data) {
            loader.unblock();
        });
    });
});