/**
 * Created by hcherif on 09/05/2016.
 */

$(function () {
    $(document).on('click', '.addTicketRestaurantValue', function(){
        var nbr = parseInt($(this).attr('data-count'));
        var prototype = $(this).attr('data-prototype')
            .replace(/__index__/g, nbr);
        $(this).closest('.row').before(prototype);
        $(this).attr('data-count', nbr + 1);
    });
    $(document).on('click', '.btnRemoveValue', function () {
        $(this).closest('.row').remove();
    });

    $(document).on('click', '.addCheckQuickValue', function(){
        var nbr = parseInt($(this).attr('data-count'));
        var prototype = $(this).attr('data-prototype')
            .replace(/__index__/g, nbr);
        $(this).closest('div').before(prototype);
        $(this).attr('data-count', nbr + 1);
    });
    $(document).on('click', '.removeCheckQuickValue', function () {
        $(this).closest('.checkQuickRow').remove();
    });

    $(document).on('click', '.addForeignCurrencyValue', function(){
        var nbr = parseInt($(this).attr('data-count'));
        var prototype = $(this).attr('data-prototype')
            .replace(/__index__/g, nbr)
            .replace(/__value__/g, '');
        $(this).closest('div').before(prototype);
        $(this).attr('data-count', nbr + 1);
    });
    $(document).on('click', '.removeForeignCurrencyValue', function () {
        $(this).closest('.foreignCurrencyRow').remove();
    });

    list_labels = initSimpleDataTable('#labels_list_table', {
        "aoColumnDefs": [
            {"bSortable": false, "aTargets": [2]}
        ]
    });

    $(document).on('click', '.btn-delete-label', function(){
        loader.block();
        var labelId = $(this).parentsUntil('tbody','tr').attr('id');
        var type = $(this).parentsUntil('tbody','tr').attr('data-type');
        ajaxCall({
            url : Routing.generate('delete_label',{'label' : labelId}),
            'type' : 'json'
        },function(data){
            if(data.deleted !== undefined && data.deleted == true ){
                window.location.href = Routing.generate('labels_config', {'type': type});
            }
        });
        loader.unblock();
    });

    $(document).on('click', '#save-parameters', function(){
        var header = Translator.trans('parameters.save');
        var content = Translator.trans('parameters.confirm_save');
        var footer = '<div class="row" style="margin-bottom: 0px;">'+
            '<div class="col col-lg-12 col-md-12 col-sm-12 col-xs-12" style="text-align: right">'+
            '<button class="btn waves-effect waves-light blue" type="button" id="confirm-save-parameters" style="margin-left: 10px;">' +
            '<span>'+ Translator.trans('keyword.yes') +'</span></button>' +
            '<button class="btn btn-cancel" type="button" data-dismiss="modal">' +
            '<span>'+ Translator.trans('keyword.close') +'</span></button>' +
            '</div>' +
            '</div>';
        showDefaultModal(header, content, footer);
    });
    $(document).on('click', '#confirm-save-parameters', function(){
        $('#cashBoxParametersForm').submit();
    });

    $(document).on('change', '#cashbox_parameter_foreignCurrencyContainer_allCurrencies', function(){
        var currency = $(this).val();
        var button = $('.addForeignCurrencyValue');
        var nbr = parseInt(button.attr('data-count'));
        var prototype = button.attr('data-prototype')
            .replace(/__index__/g, nbr)
            .replace(/__value__/g, currency);
        button.closest('div').before(prototype);
        button.attr('data-count', nbr + 1);
    });

    $(document).on('click', '.addAdditionalMailValue', function(){
        var nbr = parseInt($(this).attr('data-count'));
        var prototype = $(this).attr('data-prototype')
            .replace(/__index__/g, nbr);
        $('.additional-emails').append(prototype);
        $(this).attr('data-count', nbr + 1);
    });

    $(document).on('click', '.btnRemoveMailValue', function(){
        $(this).closest('.mail-container').remove();
    })


});