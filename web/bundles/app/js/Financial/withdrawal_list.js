/**
 * Created by hcherif on 28/03/2016.
 */

$(function () {

    withdrawals = initSimpleDataTable('#withdrawals_table', {
        "lengthChange": true,
        rowId: 'id',
        processing: true,
        serverSide: true,
        searching: false,
        "order": [[2, "desc"]],
        columnDefs: [
            {
                targets: [5],
                orderable: false
            },
            //{
            //    targets: [6],
            //    orderable: false
            //},
            { width: '13%', "aTargets": [ 5 ] }
            //{ width: '8%', "aTargets": [ 6 ] }
        ],
        "columns": [
            {"data": "responsible"},
            {"data": "member"},
            {"data": "date"},
            {"data": "amount"},
            {"data": "status"},
            {
                "data": "envelope",
                className: 'actions-btn',
                "render": function (data, type, row) {
                        if (data == 'true') {
                            var btn = "<button type='button' class='btn blue  detail-btn'><span class='glyphicon glyphicon-eye-open'></span> " +
                                Translator.trans('btn.view_envelope') + "</button>";
                            return btn;
                        }
                    else { return Translator.trans('fund_management.withdrawal.list.without_envelope') }
                }
            }
            //{
            //    "data": "edit",
            //    className: 'actions-btn',
            //    "render": function (data, type, row) {
            //        if (data == 'editable') {
            //            var btn="<a data-tooltip=\"" + Translator.trans('btn.edit') + "\" data-position=\"top\"" +
            //                "class=\"tooltipped glyphicon glyphicon-edit\"></a>";
            //            return btn;
            //        }
            //        else if ( data == 'closing' ) { return Translator.trans('status.counted'); }
            //        else{
            //            return Translator.trans('fund_management.withdrawal.list.notOwner');
            //        }
            //    }
            //}

        ],
        ajax: {
            url: Routing.generate("withdrawals_json_list"),
            data: function (d) {
                d.criteria = serializeArrayToObjectByKey($('#filterWithdrawalForm').serializeArray(), 'name');
                return d;
            },
            type: 'post'
        }
    });

    $(document).on('click', '.detail-btn', function(){
        loader.show();
        var withdrawalId = $(this).parentsUntil('tbody','tr').attr('id');
        ajaxCall({
            url : Routing.generate('withdrawal_detail',{'withdrawal' : withdrawalId}),
            'type' : 'json'
        },function(data){
            showDefaultModal(Translator.trans('fund_management.withdrawal.list.envelope_details'),data.data);
        });
        loader.hide();
    });

    $('#reset-filter').on('click', function () {
        resetFilter($('.filter-zone'));
        withdrawals.ajax.reload();
    });

    $(document).on('click', '#export-btn', function() {
        submitExportDocumentFile(".filter-zone",withdrawals,Routing.generate("withdrawals_json_list",{"download":1}));
    });

    $(document).on('click', '#print-btn', function() {
        submitExportDocumentFile(".filter-zone",withdrawals,Routing.generate("withdrawals_json_list",{"pdf":1}));
    });

    $(document).on('click', '.glyphicon-edit', function(){
        var withdrawalId = $(this).parentsUntil('tbody','tr').attr('id');
        window.location.href = Routing.generate('withdrawal_entry',{'withdrawal' : withdrawalId});
    });

    $(document).on('change', '#withdrawal_search_endDate', function(){
        var startDate = $('#withdrawal_search_startDate').val();
        var endDate = $(this).val();
        if(startDate != '' && startDate != null && endDate != '' && endDate != null ){
            compareDate(startDate, endDate);
        }
        else{
            $('.date-compare').remove();
        }
    });
    $(document).on('change', '#withdrawal_search_startDate', function(){
        var startDate = $(this).val();
        var endDate = $('#withdrawal_search_endDate').val();
        if(( startDate != '' && startDate != null && endDate != '' && endDate != null ) ){
            compareDate(startDate, endDate);
        }
        else{
            $('.date-compare').remove();
        }
    });

    function compareDate(startDate, endDate){
        var start = new Date();
        start.setFullYear(startDate.substr(6,4));
        start.setMonth(startDate.substr(3,2));
        start.setDate(startDate.substr(0,2));
        start.setHours(0);
        start.setMinutes(0);
        start.setSeconds(0);
        start.setMilliseconds(0);
        var d1 = start.getTime();

        var end = new Date();
        end.setFullYear(endDate.substr(6,4));
        end.setMonth(endDate.substr(3,2));
        end.setDate(endDate.substr(0,2));
        end.setHours(0);
        end.setMinutes(0);
        end.setSeconds(0);
        end.setMilliseconds(0);
        var d2=end.getTime();

        if ( d1 > d2 ){
            $('.date-compare').remove();
            $('.endDate').append('<div class="alert alert-danger form-error date-compare" role="alert"><span class="glyphicon glyphicon-warning-sign"></span>' +
                ' ' + Translator.trans('portion_control.validation.start_date_must_be_before_end_dat') + '<br></div>')
        }
        else{
            $('.date-compare').remove();
        }
    }

    $(document).on('click', '.btn-refresh', function(){
        location.reload();
    })
});
