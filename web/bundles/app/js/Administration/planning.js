/**
 * Created by hcherif on 04/03/2016.
 */

$(function () {

    var table_body_planning = $('#planning_table_body');
    var add_planning_form = $('#PlanningSupplierForm');
    $('.selectize').selectize({
        plugins: ['remove_button']
    });

    $(document).on('change', ('#supplier_planning_supplier'), function(){
        loader.show();
        var supplierId = $( "#supplier_planning_supplier option:selected" ).val();
        ajaxCall({
                url : Routing.generate('planning',{supplier : supplierId}),
                dataType : 'json'
            },
            function(data){
                if (typeof data.data != 'undefined' && data.data.length != 0){
                    table_body_planning.empty();
                    table_body_planning.attr('data-count', data.data['0']);
                    table_body_planning.append(data.data['1']);
                    $('.selectize').selectize({plugins: ['remove_button']});
                    //$('#supplier_planning_supplier').attr("disabled", "disabled");
                    $('#add_planning_supplier').removeAttr("style");
                    $('#confirm_button').removeAttr("style");
                    $('.body-planning-supplier').attr('data-prototype', data.data['prototype']);
                }else{
                    Notif.alert(Translator.trans('error.general.internal'),500,3000);
                }
                loader.hide();
            });


    });

    $(document).on('click', '#add_planning_supplier', function(){
        var nbr = parseInt(table_body_planning.attr('data-count'));
        var select_categories_id = 'supplier_planning_planning_' + nbr +'_categories';
        table_body_planning.attr('data-count', nbr + 1);
        var prototype = table_body_planning.attr('data-prototype')
            .replace(/_id_/g, nbr)
            .replace(/_rank_/g, nbr + 1);
        $('.body-planning-supplier').append(prototype);
        $('#'+ select_categories_id).selectize({plugins: ['remove_button']});
    });

    $(document).on('click', '#validate_planning_supplier', function(){
        supplierId = $( "#supplier_planning_supplier option:selected" ).val();
        add_planning_form.attr("action",Routing.generate('planning', {supplier : supplierId}));
        add_planning_form.submit();
    });

    $(document).on('click', '#cancel_planning_supplier', function(){
        event.preventDefault();
        showDefaultModal(Translator.trans('provider.planning.cancel'), $( "#modal_cancel_body" ).html(), $( "#modal_cancel_footer" ).html());
    });

    $(document).on('click','#cancel_yes_button',function(){
        table_body_planning.empty();
        $('#add_planning_supplier').attr("style", "display:none");
        $('#confirm_button').attr("style", "display:none");
        $('#supplier_planning_supplier').removeAttr("disabled");
    });

    $(document).on('click', '.btn-delete-planning-line', function() {
        loader.show();

        if ($(this).parentsUntil('tbody','tr').attr('id') == null) {
            $(this).parentsUntil('tbody','tr').remove();
        }
        else {
            lineId = $(this).parentsUntil('tbody','tr').attr('id');
            ajaxCall({
                    url: Routing.generate('delete_line_planning', {line: lineId}),
                    dataType: 'json'
                },
                function (data) {
                    if (typeof data.errors != 'undefined' && data.errors.length > 0) {
                        Notif.alert(Translator.trans('provider.planning.js.error'), 500, 3000);
                    } else {
                        showDefaultModal(Translator.trans('provider.planning.delete'), data.html);
                    }

                });
        }
        loader.hide();
    });

    $(document).on('click', '.form_delete_button', function(){
        loader.show();
        var lineId = $(this).attr('id');
        ajaxCall({
                url : Routing.generate('delete_line_planning',{line : lineId}),
                dataType : 'json',
                method: 'post'
            },
            function(data){
                if (typeof data.errors != 'undefined' && data.errors.length > 0) {
                    Notif.alert(Translator.trans('provider.planning.js.error'),500,3000);
                } else {
                    $('#default-modal-box').modal('toggle');
                    table_body_planning.attr('data-count', data.resForm['0']);
                    table_body_planning.empty();
                    table_body_planning.append(data.resForm['1']);
                    $('.selectize').selectize({plugins: ['remove_button']});
                    $('#' + lineId).remove();
                    Notif.alert(Translator.trans('provider.planning.js.success'),500,3000);
                }

            });
        loader.hide();
    })

});