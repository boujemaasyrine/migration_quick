/**
 * Created by hcherif on 02/05/2016.
 */


$(function () {

    list_roles = initSimpleDataTable('#roles_list_table', {
        searching: false,
        lengthMenu: false,
        "lengthChange": false,
        "aoColumnDefs": [
            {"bSortable": false, "aTargets": [1]}
        ]
    });

    $(document).on('click', '.btn-delete-role', function(){
        var role = $(this).parent().siblings(":first").text();
        var roleId = $(this).parentsUntil('tbody','tr').attr('id');
        var header = Translator.trans('staff.role.delete');
        var content = Translator.trans('staff.role.delete_confirm', {role : role});
        var footer = '<div class="row" style="margin-bottom: 0px;">'+
            '<div class="col col-lg-2 col-md-6 col-sm-6 col-xs-6 pull-right">'+
            '<button class="btn btn-primary btn-block  waves-effect waves-light" type="button" data-role="'+ roleId +'" id="confirm-delete-role">' +
            '<span>'+ Translator.trans('keyword.yes') +'</span></button>' +
            '</div>' +
            '<div class="col col-lg-2 col-md-6 col-sm-6 col-xs-6 pull-right">' +
            '<button class="btn red btn-block  waves-effect waves-light" type="button" data-dismiss="modal">' +
            '<span>'+ Translator.trans('keyword.no') +'</span></button>' +
            '</div>' +
            '</div>';
        showDefaultModal(header, content, footer);
    });

    $(document).on('click', '#confirm-delete-role', function(){
        loader.block();
        var roleId = $(this).attr('data-role');
        ajaxCall({
            url : Routing.generate('delete_role',{'role' : roleId}),
            'type' : 'json'
        },function(data){
            if(data.deleted !== undefined && data.deleted == true ){
                location.reload();
            }
            else if(data.deleted !== undefined){
                showDefaultModal(Translator.trans('staff.role.delete_failed_title'), Translator.trans('staff.role.delete_failed_body'));
            }

        });
        loader.unblock();
    })

});
