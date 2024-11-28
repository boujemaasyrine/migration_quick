/**
 * Created by hcherif on 08/03/2016.
 */

$(function () {
    var groupAddForm = $("#AddGroupForm");
    var modalAdd = $('#modal-group-added');
    var filterZone = $('.filter-zone .panel-heading');

    list_groups = initSimpleDataTable('#groups_list_table', {
        searching: true,
        pageLength: 10,
        "lengthChange": true,
        "aoColumnDefs": [
            { "bSortable": false, "aTargets": [ 2 ] },
            { width: '15%', "aTargets": [ 2 ] },
            { width: '20%', "aTargets": [ 0 ] },
            { "sClass": "actions-btn", "aTargets": [ 2 ] }
        ]
    });

    $(document).on('click', '#plus-group-button', function (e) {
        loader.show();
        ajaxCall({
                url: Routing.generate('groups_list'),
                method: POST,
                data: groupAddForm.serialize()
            },
            function (res) {
                if (res.errors === undefined && res.formError === undefined) {
                    addGroupSuccess(res);
                    showDefaultModal(Translator.trans('group.list.add_success'),
                        Translator.trans('list.group.message_add_success', {'name' : res.data['1'].name}));

                }else{
                    groupAddForm.html(res.formError['0']);
                }
                loader.hide();
            });

    });

    $(document).on('click', '#edit-group-button', function (e) {
        loader.show();
        group = groupAddForm.attr('class');
        var buttonEdit = $('#edit_restaurant_button_' + group );
        ajaxCall({
                url: Routing.generate('groups_list', {group: group}),
                method: POST,
                data: groupAddForm.serialize()
            },
            function (res) {
                if (res.errors === undefined && res.formError === undefined) {
                    list_groups.row( $('#' + group) ).remove().draw();
                    addGroupSuccess(res);
                    showDefaultModal(Translator.trans('group.list.edit_success'),
                        Translator.trans('list.group.message_edit_success', {'name' : res.data['1'].name}));
                    $('.panel-heading').html('<span class="glyphicon glyphicon-plus"></span> ' + Translator.trans('group.list.add'));

                }else{
                    groupAddForm.html(res.formError['0']);
                }
                loader.hide();
            });
    });

    $(document).on('click', '.glyphicon-edit', function(){
        loader.show();
        var group = $(this).parentsUntil('tbody','tr').attr('id');
        ajaxCall({
                url: Routing.generate('groups_list', {group: group}),
                dataType: 'json'
            },
            function (res) {
                if (typeof res.data != 'undefined' && res.data.length != 0) {
                    groupAddForm.html(res.data['0']);
                    groupAddForm.removeAttr('class');
                    groupAddForm.addClass(group);
                    $('.panel-heading').html('<span class="glyphicon glyphicon-edit"></span> ' + Translator.trans('group.list.edit'));
                    $( "label" ).addClass( "active" );
                    if (filterZone.siblings('.panel-body').is(":hidden")){
                        filterZone.siblings('.panel-body').slideToggle();
                    }
                    $('html,body').animate({
                        scrollTop: 0
                    }, 1200);
                    return false;
                }else{
                    Notif.alert(Translator.trans('error.general.internal'),500,3000);
                }
            });

        loader.hide();
    });

    $(document).on('click', '.detail-btn', function(e){
        var groupId = $(this).parentsUntil('tbody','tr').attr('id');
        loader.show();
        ajaxCall({
            url : Routing.generate('group_detail',{'group' : groupId}),
            'type' : 'json'
        },function(data){
            showDefaultModal(Translator.trans('group.list.details'),data.data);
            loader.hide();
        })
    });

    $(document).on('click', '.glyphicon-remove', function(){
        var group = $(this).parentsUntil('tbody','tr').attr('id');
        loader.show();
        ajaxCall({
            url: Routing.generate('delete_group', {'group': group}),
            dataType: 'json'
        }, function (data) {
            if (typeof data.errors != 'undefined' && data.errors.length > 0) {
                var errorMsg = "<div class='alert alert-danger'>" + Translator.trans(data.errors[0]) + "</div>"
                showDefaultModal(Translator.trans('group.list.delete'), errorMsg);
            } else {
                showDefaultModal(Translator.trans('group.list.delete'), data.html);
            }
            loader.hide();
        });

    });

    $(document).on('click', '#btn-cancel-edit', function(){
        var content = "<h2>" + Translator.trans('cancel.confirmation.content')+ "</h2>";
        var footer = "<div class='row'>" +
            "<div class='col col-lg-12 col-md-12 col-xs-12 col-sm-12' style='text-align: right'>" +
            "<button type='button' id='btn-yes-cancel-edit' class='btn waves-effect waves-light blue' data-dismiss='modal'>" + Translator.trans('yes_confirm') + "</button>" +
            "<button type='button' class='btn btn-cancel' data-dismiss='modal' style='margin-right: 10px'>" + Translator.trans('btn.close') + "</button>" +
            "</div>";
        showDefaultModal(Translator.trans('cancel.confirmation.title'), content, footer);
    });

    $(document).on('click', '#btn-yes-cancel-edit', function(){
        loader.show();
        ajaxCall({
                url: Routing.generate('groups_list')
            },
            function (res) {
                if (res.errors === undefined) {
                    groupAddForm.html(res.data['0']);
                    filterZone.siblings('.panel-body').slideToggle();
                    $('.panel-heading').html('<span class="glyphicon glyphicon-plus"></span> ' + Translator.trans('group.list.add'));
                }
                else {
                    Notif.alert(Translator.trans('error.general.internal'),500,3000);
                }
            });

        loader.hide();
    });

    $("#export-btn").on('click',function(){
        submitExportDocumentFile(".filter-zone",list_groups,Routing.generate("groups_list_export"));
    });

    function addGroupSuccess(res){
        modalAdd.html(res.data['0']);
        groupAddForm.html(res.data['2']);
        filterZone.siblings('.panel-body').slideToggle();
        var newRow = list_groups.row.add([
            res.data['1'].id,
            res.data['1'].nameFR,
            res.data['1'].nameNL,
            res.data['1'].btn
        ]).draw().node();
        $( newRow )
            .attr( 'id', res.data['1'].id );


    }
});
