/**
 * Created by bbarhoumi on 07/04/2016.
 */
$(function () {
    envelopes = initSimpleDataTable('#envelopes_table', {
        rowId: 'id',
        processing: true,
        serverSide: true,
        searching: true,
        "order": [[1, "desc"]],
        "columns": [
            {"data": "number"},
            {"data": "date"},
            {"data": "amount"},
            {"data": "sousType"},
            {"data": "reference"},
            {"data": "owner"},
            {"data": "status"}
        ],
        ajax: {
            url: Routing.generate("envelope_ticket_json_list"),
            data: function (d) {
                d.criteria = serializeArrayToObjectByKey($('#filterEnvelopeForm').serializeArray(), 'name');
                return d;
            },
            type: 'post'
        }
    });
    $('#reset-filter').on('click', function () {
        resetFilter($('.filter-zone'));
        envelopes.ajax.reload();
    });
    $("#export-btn").on('click',function(){
        submitExportDocumentFile(".filter-zone",envelopes,Routing.generate("envelope_ticket_json_list",{"download":1}));
    });
});
