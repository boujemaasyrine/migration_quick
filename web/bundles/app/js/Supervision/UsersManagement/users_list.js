/**
 * Created by hcherif on 30/05/2016.
 */

$(function () {
    orderMultoSelect($('#add_user_eligibleRestaurants'));
    initMultiSelect('#add_user_eligibleRestaurants');

    if ( $( ".form-error" ).length && !($('#body-edit-user').length) ) {
        $('.filter-zone .panel-heading').siblings('.panel-body').slideToggle();
    }

    list_users = initSimpleDataTable('#users_list_table', {
        searching: true,
        "aoColumnDefs": [
            { "bSortable": false, "aTargets": [ 5 ] },
            { "sClass": "actions-btn", "aTargets": [ 5 ] },
            { width: '10%', "aTargets": [ 5 ] },
        ]

    });

    $(document).on('click', '.detail-btn', function(){
        loader.show();
        var userId = $(this).parentsUntil('tbody','tr').attr('id');
        ajaxCall({
            url : Routing.generate('user_details',{'user' : userId}),
            'type' : 'json'
        },function(data){
            showDefaultModal(Translator.trans('users.list.details', {}, 'supervision'),data.data.bodyModal, data.data.footerModal, '95%');
            initSimpleDataTable('#user_restaurants_table', {"lengthChange": false, searching: false, pageLength: 5});
            loader.hide();
        })
    });

    $(document).on('click', '.delete-user', function(){
        var username = $(this).attr('data-username');
        var userId = $(this).attr('data-user');
        var header = Translator.trans('users.list.delete');
        var content = Translator.trans('users.list.delete_confirm', {username : username});
        var footer = '<div class="row" style="margin-bottom: 0px;">'+
            '<div class="col col-lg-2 col-md-6 col-sm-6 col-xs-6 pull-right">'+
            '<button class="btn btn-primary btn-block  waves-effect waves-light" type="button" data-user="'+ userId +'" id="confirm-delete-user">' +
            '<span>'+ Translator.trans('keyword.yes') +'</span></button>' +
            '</div>' +
            '<div class="col col-lg-2 col-md-6 col-sm-6 col-xs-6 pull-right">' +
            '<button class="btn red btn-block  waves-effect waves-light" type="button" data-dismiss="modal">' +
            '<span>'+ Translator.trans('keyword.no') +'</span></button>' +
            '</div>' +
            '</div>';
        showDefaultModal(header, content, footer);
    });

    $(document).on('click', '#confirm-delete-user', function(){
        loader.block();
        var userId = $(this).attr('data-user');
        ajaxCall({
            url : Routing.generate('delete_user',{'user' : userId}),
            'type' : 'json'
        },function(data){
            if(data.deleted !== undefined && data.deleted == true ){
                location.reload();
            }

        });
        loader.unblock();
    });

    $("#export-btn").on('click', function () {
        var type = $(this).attr('data-type');
        submitExportDocumentFile(".filter-zone", list_users, Routing.generate("users_list_export"));
    });

});
