/**
 * Created by mchrif on 14/04/2016.
 */

$(function () {
    recipeTickets = initSimpleDataTable('#recipe_tickets', {
        rowId: 'id',
        processing: true,
        serverSide: true,
        searching: true,
        columnDefs: [
            {
                targets: [5],
                orderable: false
            }
        ],
        "order": [[2, "desc"]],
        "columns": [
            {"data": "id"},
            {
                "data": "label",
                "render": function (value) {
                    return Translator.trans(value);
                }
            },
            {"data": "date"},
            {"data": "amount"},
            {"data": "owner"},
            {
                "className": 'actions-btn',
                "render": function (data, type, row) {
                    var btn = "<a href='" +
                        Routing.generate('print_recipe_tickets', {'recipeTicket': row.id}) +
                        "' class='btn btn-print btn-icon'> &nbsp;" +
                        Translator.trans('shortcut.labels.print') +
                        "</a>";
                    return btn;
                }
            }
        ],
        ajax: {
            url: Routing.generate("list_recipe_tickets"),
            data: function (d) {
                d.criteria = serializeArrayToObjectByKey($('#filterRecipeTicketForm').serializeArray(), 'name');
                return d;
            },
            type: 'POST'
        }
    });

    $('#reset-filter').on('click', function () {
        resetFilter($('.filter-zone'));
        recipeTickets.ajax.reload();
    });
    $("#export-btn").on('click', function () {
        submitExportDocumentFile(".filter-zone",
            recipeTickets,
            Routing.generate("list_recipe_tickets",
                {"download": 1})
        );
    });
    $("#download-btn").on('click',function(){
        submitExportDocumentFile(".filter-zone",recipeTickets,Routing.generate("list_recipe_tickets",{"download":2}));
    });

});