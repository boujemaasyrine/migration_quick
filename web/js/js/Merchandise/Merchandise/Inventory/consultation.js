/**
 * Created by mchrif on 15/02/2016.
 */

$(function() {
    var d=new Date();
    d.setDate(1);
    d.setMonth(d.getMonth()-1);
    var picker1=initDatePicker('#inventory_search_startDate',{
        disable: [
            true,
            { from: d, to: new Date() }
        ]
    });

    var picker2=initDatePicker('#inventory_search_endDate', {
        disable: [
            true,
            {from: d, to: new Date()}
        ]
    });



    picker1.pickadate('picker').on({
            close: function() {
                var min=picker1.pickadate('picker').get('select');
                picker2.pickadate('picker').set('min',min);
            }
        }
    );

    picker2.pickadate('picker').on({
            close: function() {
                var max=picker2.pickadate('picker').get('select');
                picker1.pickadate('picker').set('max',max);
            }
        }
    );




    iventoryListDatatable = initSimpleDataTable('#inventory-table', {

        searching: true,
        processing: true,
        serverSide: true,
        "order": [[1, "desc"]],
        ajax: {
            "url": Routing.generate('inventory_list'),
            data: function (d) {
                d.criteria = serializeArrayToObjectByKey($('#filterInventoryForm').serializeArray(), 'name');
                return d;
            },
            "type": "GET"
        },
        "columns": [
            {"data": "id"},
            {
                "data": "createdAt.timestamp",
                "render": function (d) {
                    return moment.unix(d).format('DD/MM/YYYY HH:mm:ss');
                }
            },
            {
                "data": "fiscalDate.timestamp",
                "render": function (d) {
                    return moment.unix(d).format('DD/MM/YYYY');
                }
            },
            {
                "data": "sheetModelLabel"
            },
            {
                "data": null,
                "orderable": false,
                render: function (row) {
                    var today = moment();
                    var date = moment.unix(row.createdAt.timestamp);

                    var action = $('#inventory-table').attr('data-template');
                    action = action.replace(/_id_/g, row.id);
                    return action;

                },
                "targets": 0
            }
        ],
        "drawCallback": function () {
            $('[data-toggle="tooltip"]').tooltip();
        }
    });


    $('#reset-filter').on('click', function () {
        resetFilter($('.filter-zone'));
        iventoryListDatatable.ajax.reload();
    });

    $(document).on('click', '.btnDetailsInventory', function (e) {
        var self = $(this);
        var id = $(this).attr('data-id');
        loader.block(self);
        ajaxCall({
            method: 'GET',
            url: Routing.generate('inventory_sheet_details', {
                'inventorySheet': id
            })
        }, function (res) {
            if (res.errors === undefined) {

                showDefaultModal(Translator.trans('inventory.title.consult'), res.data[0], '', 1000, null);
                init();
            }
        }, null, function () {
            loader.unblock(self);
        })
    });

});
