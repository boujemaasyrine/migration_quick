$(function() {
    deleted_envelopes = initSimpleDataTable("#deleted_envelopes_table", {
        rowId: "id",
        processing: true,
        serverSide: true,
        searching: true,
        order: [
            [1, "desc"]
        ],
        columns: [{
            data: "number"
        }, {
            data: "date"
        }, {
            data: "amount"
        }, {
            data: "source"
        }, {
            data: "reference"
        }, {
            data: "owner"
        }, {
            data: "cashier"
        }, {
            data: "status"
        }, {
            data: "deletedAt"
        }, {
            data: "deletedBy"
        }],
        ajax: {
            url: Routing.generate("deleted_envelope_json_list"),
            data: function(data) {
                data.criteria = serializeArrayToObjectByKey($("#filterDeletedEnvelopeForm").serializeArray(), "name");
            },
            type: "POST"
        }
    });

    $("#reset-filter").on("click", function() {
        resetFilter($(".filter-zone"));
        deleted_envelopes.ajax.reload();
    });

    $("#export-btn").on("click", function() {
        submitExportDocumentFile(".filter-zone", deleted_envelopes, Routing.generate("deleted_envelope_json_list", { download: 1 }));
    });

});
