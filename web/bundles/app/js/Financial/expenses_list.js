/**
 * Created by hcherif on 31/03/2016.
 */

$(function () {

    expenses = initSimpleDataTable('#expenses_table', {
        rowId: 'id',
        processing: true,
        serverSide: true,
        searching: false,
        columnDefs: [
            {targets: [0], orderable: false},
            {targets: [1], orderable: false},
            {targets: [2], orderable: false},
            {targets: [3], orderable: false},
            {targets: [4], orderable: false},
            {width: '10%', "aTargets": [4]}
        ],
        "columns": [
            {"data": "reference"},
            {"data": "label"},
            {"data": "owner"},
            {"data": "amount"},
            {
                className: 'actions-btn',
                "render": function (data, type, row) {
                    var btn = "<button type='button' class='btn btn-view btn-icon detail-btn'>" +
                        " " + Translator.trans('btn.view_details') + "</button>";
                    return btn;
                }
            }
        ],
        ajax: {
            url: Routing.generate("expenses_json_list"),
            data: function (d) {
                d.criteria = serializeArrayToObjectByKey($('#filterExpenseForm').serializeArray(), 'name');
                return d;
            },
            type: 'post'
        },
        "rowCallback": function (row, data, index) {
            $(row).attr('data-class', data.dataClass);
            $(row).attr('data-value', data.dataValue);
            $(row).attr('class', 'hidden');
        },
        "fnDrawCallback": function (oSettings) {
            var tableRow = $('#expenses_table > tbody > tr');
            var tableDataClass = [];
            tableRow.each(function () {
                var tr = $(this);
                if (tr.attr('data-class') != undefined && tableDataClass.indexOf(tr.attr('data-class')) == -1) {
                    tableDataClass.push(tr.attr('data-class'));
                    tr.before('<tr style="cursor:pointer; background-color: #E4D9C3 !important;" data-name="' + tr.attr('data-value') + '" class="groupe-date">' +
                        '<td colspan="5""><span class="glyphicon glyphicon-plus"></span><b> ' + tr.attr('data-class') + '</b></td></tr>');
                }
            });
        }
    });

    $(document).on('click', '.detail-btn', function () {
        loader.show();
        var expenseId = $(this).parentsUntil('tbody', 'tr').attr('id');
        ajaxCall({
            url: Routing.generate('expense_detail', {'expense': expenseId}),
            'type': 'json'
        }, function (data) {
            var width;
            if (data.table == true)
                width = "90%";
            else width = '';
            showDefaultModal(Translator.trans('expense.list.expense_details'), data.data, data.dataFooter, width);
        });
        loader.hide();

    });

    $('#reset-filter').on('click', function () {
        resetFilter($('.filter-zone'));
        expenses.ajax.reload();
    });

    $("#export-btn").on('click', function () {
        submitExportDocumentFile(".filter-zone", expenses, Routing.generate("expenses_json_list", {"download": 1}));
    });

    $("#download-btn").on('click', function () {
        submitExportDocumentFile(".filter-zone", expenses, Routing.generate("expenses_json_list", {"download": 2}));
    });

    $(document).on('click', '.groupe-date', function () {
        var name = $(this).attr('data-name');
        if ($('span:first', this).hasClass('glyphicon-plus')) {
            $('span:first', this).removeClass("glyphicon-plus").addClass("glyphicon-minus");
        }
        else {
            $('span:first', this).removeClass("glyphicon-minus").addClass("glyphicon-plus");
        }
        $('#expenses_table > tbody  > tr').each(function () {
            if ($(this).attr('data-value') == name && $(this).hasClass('hidden')) {
                $(this).removeClass('hidden');
            }
            else if ($(this).attr('data-value') == name) {
                $(this).addClass('hidden');
            }
        });
    });

    $(document).on('change', '#expense_search_group', function () {
        var group = $(this).val();
        var select = $('#expense_search_label');
        loader.show();
        ajaxCall({
                url: Routing.generate('expense_group_labels', {group: group}),
                method: POST
            },
            function (res) {
                $('#expense_search_label option:gt(0)').remove();
                $.each(res.labels, function (key, value) {
                    select.append($("<option></option>")
                        .attr("value", key).text(value));
                });
                sortSelect(select);
            });

        loader.hide();
    })

});


