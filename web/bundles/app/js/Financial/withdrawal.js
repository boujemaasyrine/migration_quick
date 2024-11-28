/**
 * Created by hcherif on 28/03/2016.
 */

$(function() {

    function importTickets() {
        loader.unblock();
        apiLoader.blockApiLoader();
        $.ajax({
            'url': Routing.generate('import_recent_tickets'),
            'success': function () {
                apiLoader.unblockApiLoader();
            }
        });
    }
    importTickets();

    var withdrawalAddForm = $("#AddWithdrawalForm");
    var envelopeAddForm = $('#AddEnvelopeForm');
    init();

    function init() {
        list_withdrawals = initSimpleDataTable('#withdrawals_table', {
            searching: false,
            lengthMenu: false,
            "order": [[0, "desc"]],
            "lengthChange": false,
            "paging": false,
            "info": false
        });
    }

    $(document).on('click', '#btn-validate', function(){

        loader.block();
        ajaxCall({
                url: Routing.generate('withdrawal_entry'),
                method: POST,
                data: withdrawalAddForm.serialize()
            },
            function (res) {
                if (res.errors === undefined && res.formError === undefined) {
                    list_withdrawals.clear().draw();
                    var title = Translator.trans('fund_management.withdrawal.entry.modal_withdrawal_set_title');
                    var content = '<h3>' + Translator.trans('fund_management.withdrawal.entry.new_envelope') + '</h3>';
                    var footer = res.data['footer_btn'];
                    $('#AddWithdrawalForm').html(res.data['newForm']);
                    init();
                    showDefaultModal(title, content, footer, null, null, false);
                }else if ( res.errors === undefined ){
                    withdrawalAddForm.html(res.formError['0']);
                    init();
                    if ($('#withdrawal_amountWithdrawal').val() != ''){
                        $("label[for='withdrawal_amountWithdrawal']").addClass('active');
                    }
                }
                loader.unblock();
            });

    });

    $(document).on('click', '#btn-edit', function(){
        var withdrawalId = $(this).attr('data-withdrawal-id');
        loader.show();
        ajaxCall({
                url: Routing.generate('withdrawal_entry',{'withdrawal' : withdrawalId}),
                method: POST,
                data: withdrawalAddForm.serialize()
            },
            function (res) {
                if (res.errors === undefined && res.formError === undefined) {
                    if (res.data['envelope'] == 'true') {
                        window.location.href = Routing.generate('withdrawal_list');
                    }
                else{
                        var title = Translator.trans('fund_management.withdrawal.entry.modal_withdrawal_edit_title');
                        var content = Translator.trans('fund_management.withdrawal.entry.new_envelope');
                        var footer = res.data['footer_btn'];
                        $('#AddWithdrawalForm').html(res.data['newForm']);
                        init();
                        showDefaultModal(title, content, footer);
                }
                }else if ( res.errors === undefined ){
                    withdrawalAddForm.html(res.formError['0']);
                    init();
                    if ($('#withdrawal_amountWithdrawal').val() != ''){
                        $("label[for='withdrawal_amountWithdrawal']").addClass('active');
                    }

                }
            });

        loader.hide();
    });

    $(document).on('click', '#envelope-validate', function(){
        loader.show();
        var amount = $(this).attr('data-withdrawal-amount');
        var withdrawal = $(this).attr('data-withdrawal-id');
        ajaxCall({
                url: Routing.generate('envelope_entry', {withdrawal: withdrawal}),
                dataType: 'json'
            },

            function (res) {
                if (typeof res.data != 'undefined' && res.data.length != 0) {
                    var title = Translator.trans('fund_management.withdrawal.entry.title_envelope');
                    showDefaultModal(title, res.data['0'], res.data['1'], null, null, false);
                    $("label[for='envelope_amount']").addClass('active');
                    $('#envelope_amount').attr('disabled', 'disabled').val(amount);
                }else{
                    Notif.alert(Translator.trans('error.general.internal'),500,3000);
                }
            });
        loader.hide();
    });

    $(document).on('click', '#envelope-create', function(){
        loader.show();
        var withdrawal = $(this).attr('data-withdrawal-id');
        $('#envelope_amount').removeAttr('disabled');
        ajaxCall({
                url: Routing.generate('envelope_entry', {withdrawal: withdrawal}),
                method: POST,
                data: $('#AddEnvelopeForm').serialize()
            },
            function (res) {
                if (res.errors === undefined && res.formError === undefined) {
                    window.location.href = Routing.generate('withdrawal_list');
                }else if ( res.errors === undefined ){
                    $('#AddEnvelopeForm').html(res.formError['0']);
                    $('#envelope_amount').attr('disabled', 'disabled');
                    $("label[for='envelope_amount']").addClass('active');
                }
                loader.hide();
            });
    });

    $(document).on('click', '#envelope-cancel', function(){
        loader.show();
        ajaxCall({
                url: Routing.generate('cancel_envelope'),
                method: POST
            },
            function (res) {
                if (res.errors === undefined && res.formError === undefined) {
                    window.location.href = Routing.generate('withdrawal_list');
                }else{

                }
                loader.hide();
            });
    });

    //$(document).on('change', '#withdrawal_member', function(){
    //    var memberId = $('#withdrawal_memberId');
    //    var memberIdLabel = $("label[for='withdrawal_memberId']");
    //    if ($(this).val()){
    //        memberId.val($(this).val());
    //        memberIdLabel.addClass('active');
    //    }
    //    else{
    //        memberId.val('');
    //        memberIdLabel.removeClass('active');
    //    }
    //});

    $(document).on('change', '#withdrawal_member', function(){
        var member = $(this).val();
    if (member != ''){
        var withdrawal = $(this).attr('data-withdrawal');
        if (typeof withdrawal === 'undefined'){
            withdrawal = null;
        }
        loader.show();
        ajaxCall({
                url: Routing.generate('previous_withdrawals', {member: member, withdrawal: withdrawal}),
                method: POST
            },
            function (res) {
                list_withdrawals.clear();
                list_withdrawals.draw();
                $.each( res.data, function( key, value ) {
                    var newRow = list_withdrawals.row.add([
                        value['createdAt'],
                        value['amount']
                    ]).draw().node();
                });
            });

        loader.hide();
    }
        else{
        list_withdrawals.clear();
        list_withdrawals.draw();
    }
    });

    $(document).on('keyup', '#withdrawal_amountWithdrawal', function(){
        var container = $('.amount-withdrawal');
        if ($(this).val() % 5 != 0){
            container.children('.alert-danger').remove();
            $('.modulo-5-error').remove();
            container.append('<div class="alert alert-danger form-error modulo-5-error" role="alert"><span class="glyphicon glyphicon-warning-sign"></span>' +
            '' + Translator.trans('modulo_five') + '<br></div>');
        }
        else{
            $('.modulo-5-error').remove();
        }
    });

    $(document).on('change', '#withdrawal_amountWithdrawal', function() {
        var container = $('.amount-withdrawal');
            container.children('.modulo-5-error').remove();
    });

    $(document).on('click', '.refresh-button', function(){
        apiLoader.blockApiLoader();
        ajaxCall({
            method: 'GET',
            url: Routing.generate('retrieve_recent_tickets', {})
        }, function (res) {
            if (res.errors === undefined) {
                    ajaxCall({
                            url: Routing.generate('withdrawal_entry', {validate: 'noValidate'}),
                            method: POST,
                            data: withdrawalAddForm.serialize()
                        },
                        function (res) {
                            if ( res.errors === undefined ){
                                withdrawalAddForm.html(res.formError['0']);
                                init();
                                if ($('#withdrawal_amountWithdrawal').val() != ''){
                                    $("label[for='withdrawal_amountWithdrawal']").addClass('active');
                                }
                            }
                        });
                apiLoader.unblockApiLoader();
            }
        }, null, function () {

        });
    })

});
