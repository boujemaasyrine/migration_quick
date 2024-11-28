/**
 * Created by hcherif on 19/05/2016.
 */

$(function () {

    notifications = initSimpleDataTable('#notifications_table', {
        rowId: 'id',
        processing: true,
        serverSide: true,
        searching: false,
        "columns": [
            /*{
             "data": "type",
             render: function (data, type, row) {
             return ' <span class="label label-info pull-right" style="color: #ffffff !important;">4 minutes ago</span>' +
             '<em class="fa fa-fw fa-comment mr"></em>' + data
             }
             },*/
            {
                "data": "message",
                className: 'actions-btn',
                "render": function (data, type, row) {
                    var message = row.message;

                    message += "<a href='" + Routing.generate('delete_notification', {'instance': row.id}) +
                        "' class='btn btn-delete btn-icon btn-xs pull-right' style='margin-left:7px;'>" +
                        " " + Translator.trans('btn.delete') + "</a>";

                    if (row.type_ != 'NONEXISTENT_PLU_CODE_NOTIFICATION')
                        message += "<a href='" + Routing.generate('see_notification', {'instance': row.id}) +
                            "' class='btn btn-view btn-icon detail-btn btn-xs pull-right'>" +
                            " " + Translator.trans('btn.view') + "</a>";

                    return message;
                }
            }
        ],
        ajax: {
            url: Routing.generate("notifications_json_list"),
            data: function (d) {
                d.criteria = serializeArrayToObjectByKey($('#filterNotificationForm').serializeArray(), 'name');
                return d;
            },
            type: 'post'
        },
        "rowCallback": function (row, data, index) {
            if (data.seen == false) {
                $(row).attr('class', 'not-seen');
            }

            $(row).attr('data-class', data.type);
            $(row).attr('data-value', data.message);
            $(row).attr('data-seen', data.seen);
            $(row).attr('data-id', data.id);
            $(row).attr('data-icon', data.icon);
            $(row).attr('data-created', data.created);
            if (!(instance != null && instance == data.id))
                $(row).attr('class', 'hidden');
            else {
                $(row).attr('class', 'open');
            }
        },
        "fnDrawCallback": function (oSettings) {
            var tableRow = $('#notifications_table > tbody > tr');
            var tableDataClass = [];
            tableRow.each(function () {
                var tr = $(this);
                if (tr.attr('data-id') != undefined && tableDataClass.indexOf(tr.attr('data-id')) == -1) {
                    tableDataClass.push(tr.attr('data-id'));
                    var row = '<tr style="cursor:pointer; !important;" data-id="' + tr.attr('data-id') + '" data-name="' + tr.attr('data-value') + '" class="details ';
                    if (tr.attr('data-seen') == "false") {
                        row += 'not-seen';
                    }
                    row += '">' +
                        '<td colspan="5" style="font-size: 16px">' +
                        '<span class="label label-info pull-right" style="color: #ffffff !important; margin: 0; font-size: 85%;">' + tr.attr('data-created') + '</span>' +
                        '<em class="fa '+ tr.attr('data-icon') +' fa-comment mr"></em>' + tr.attr('data-class') +
                        '</td></tr>';
                    tr.before(row);
                }
            });
        }
    });

    $(document).on('click', '.details', function () {
        var row = $(this);
        var id = row.attr('data-id');
        $('#notifications_table > tbody  > tr').each(function () {
            if (!$(this).hasClass('details') && $(this).attr('data-id') == id && $(this).hasClass('hidden')) {
                $(this).removeClass('hidden');
                $(this).addClass('open');
                if (row.hasClass('not-seen')) {
                    row.removeClass('not-seen');
                    ajaxCall({
                        url: Routing.generate('see_notification', {'instance': row.attr('data-id')}),
                        dataType: 'json',
                        method: 'get'
                    });
                }
            }
            else if (!$(this).hasClass('details') && $(this).attr('data-id') == id) {
                $(this).addClass('hidden');
                $(this).removeClass('open');
            }
        });
    });


});