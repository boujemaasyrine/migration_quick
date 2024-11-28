/**
 * Created by hcherif on 16/02/2016.
 */

$(function () {

    var supplierAddForm = $("#AddSupplierForm");
    var modalAdd = $( "#modal-supplier-added" );
    var filterZone = $('#filter-zone-add .panel-heading');

    list_supplier = initSimpleDataTable('#supplier_list_table', {
        searching: false,
        processing: true,
        serverSide: true,
        rowId: 'id',
        pageLength: 10,
        "order": [[ 1, "asc" ]],
        "lengthChange": true,
        "aoColumnDefs": [
            { "bSortable": false, "aTargets": [ 5 ] },

        ],
        "columns": [
            {"data": "code"},
            {"data": "name"},
            {"data": "designation"},
            {"data": "address"},
            {"data": "phone"},
            {"data": "mail"},
            {
                className: 'actions-btn nowrap',
                "render": function (data, type, row) {
                    var btn="<a data-tooltip=\"" + Translator.trans('btn.edit') + "\" data-position=\"top\"" +
                    "class=\"tooltipped btn btn-modify btn-icon btn-xs modify-supplier\"  style='margin-right: 5px;'></a>" +
                        "<a data-tooltip=\"" + Translator.trans('btn.delete') + "\" data-position=\"top\"" +
                    "class=\"tooltipped  btn btn-delete btn-icon btn-xs remove-supplier\" ></a>";
                    return btn;
                }
            }

        ],
        ajax: {
            url: Routing.generate("supplier_list_export"),
            data: function (d) {
                d.criteria = serializeArrayToObjectByKey($('#supplierFilterForm').serializeArray(), 'name');
                return d;
            },
            type: 'post'
        }
    });

    $(document).on('click', '#plus-supplier-button', function (e) {
        loader.show();
        ajaxCall({
                url: Routing.generate('supervision_suppliers_list'),
                method: POST,
                data: supplierAddForm.serialize()
            },
            function (res) {
                if (res.errors === undefined && res.formError === undefined) {
                    addSupplierSuccess(res);
                    showDefaultModal(Translator.trans('provider.list.add_success'), modalAdd.html());
                    $('#edit_new_button_' + res.data['1'].id).parentsUntil('tbody', 'tr').attr('id', res.data['1'].id);
                    $('#edit_new_button_' + res.data['1'].id).closest('td').attr('class', 'actions-btn');
                }else{
                    supplierAddForm.html(res.formError['0']);
                    $( "input" ).each(function() {
                        if ( $( this ).val() != '' )
                        {
                            $( "label[for='"+$(this).attr('id')+"']" ).addClass( "active" );
                        }
                    });
                }
                loader.hide();
                });

    });

    $(document).on('click', '#edit-supplier-button', function (e) {
        loader.show();
        supplier = supplierAddForm.attr('class');
        ajaxCall({
                url: Routing.generate('supervision_suppliers_list', {supplier: supplier}),
                method: POST,
                data: supplierAddForm.serialize()
            },
            function (res) {
                if (res.errors === undefined && res.formError === undefined) {
                    list_supplier.row( $('#' + supplier) ).remove().draw();
                    addSupplierSuccess(res);
                    showDefaultModal(Translator.trans('provider.list.edit_success'), modalAdd.html());
                    $('.heading-add').html('<span class="glyphicon glyphicon-plus"></span> ' + Translator.trans('provider.list.add'));
                    $('#edit_new_button_' + supplier).parentsUntil('tbody', 'tr').attr('id', supplier);
                    $('#edit_new_button_' + supplier).closest('td').attr('class', 'actions-btn');
                    supplierAddForm.removeAttr('class');
                }else{
                    supplierAddForm.html(res.formError['0']);
                    $( "input" ).each(function() {
                        if ( $( this ).val() != '' )
                        {
                            $( "label[for='"+$(this).attr('id')+"']" ).addClass( "active" );
                        }
                    });
                }
                loader.hide();
            });
    });

    $(document).on('click', '.modify-supplier', function(){
        loader.show();
        var supplier = $(this).parentsUntil('tbody','tr').attr('id');
        ajaxCall({
                url: Routing.generate('supervision_suppliers_list', {supplier: supplier}),
                dataType: 'json'
            },
            function (res) {
                if (typeof res.data != 'undefined' && res.data.length != 0) {
                    $('.heading-add').html('<span class="glyphicon glyphicon-edit"></span> ' + Translator.trans('provider.list.edit'));
                    supplierAddForm.html(res.data['0']);
                    supplierAddForm.removeAttr('class');
                    supplierAddForm.addClass(supplier);
                    $( "input" ).each(function() {
                        if ( $( this ).val() != '' )
                        {
                            $( "label[for='"+$(this).attr('id')+"']" ).addClass( "active" );
                        }
                    });
                    if (filterZone.siblings('.panel-body').is(":hidden")){
                        filterZone.siblings('.panel-body').slideToggle();
                    }
                    $('html,body').animate({
                        scrollTop: 0
                    }, 1200);
                }else{
                    Notif.alert(Translator.trans('error.general.internal'),500,3000);
                }
                loader.hide();
            });

    });

    $(document).on('click', '.detail-btn', function(e){
        var supplierId = $(this).parentsUntil('tbody','tr').attr('id');
        loader.show();
        ajaxCall({
            url : Routing.generate('supplier_detail',{'supplier' : supplierId}),
            'type' : 'json'
        },function(data){
            showDefaultModal(Translator.trans('provider.list.details'),data.data);
            loader.hide();
        })
    });

    $(document).on('click', '.remove-supplier', function(){
        var supplier = $(this).parentsUntil('tbody','tr').attr('id');
        loader.show();
        ajaxCall({
            url: Routing.generate('delete_supplier', {'supplier': supplier}),
            dataType: 'json'
        }, function (data) {
            if (typeof data.errors != 'undefined' && data.errors.length > 0) {
                var errorMsg = "<div class='alert alert-danger'>" + Translator.trans(data.errors[0]) + "</div>"
                showDefaultModal(Translator.trans('provider.list.popUp_delete'), errorMsg);
            } else {
                showDefaultModal(Translator.trans('provider.list.popUp_delete'), data.html);
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
                url: Routing.generate('supervision_suppliers_list')
            },
            function (res) {
                if (res.errors === undefined) {
                    supplierAddForm.html(res.data['0']);
                    filterZone.siblings('.panel-body').slideToggle();
                    $('.heading-add').html('<span class="glyphicon glyphicon-plus"></span> ' + Translator.trans('provider.list.add'));
                }
                else {
                    Notif.alert(Translator.trans('error.general.internal'),500,3000);
                }
                loader.hide();
            });

    });

    $("#export-supplier-btn").on('click',function(){
        submitExportDocumentFile(".filter-zone",list_supplier,Routing.generate("supplier_list_export",{"download":1}));
    });

    $('#reset-filter').on('click', function () {
        resetFilter($('#filter-zone-search'));
        list_supplier.ajax.reload();
    });

     function addSupplierSuccess(res){
        modalAdd.html(res.data['0']);
        supplierAddForm.html(res.data['2']);
        filterZone.siblings('.panel-body').slideToggle();
         list_supplier.ajax.reload();
    }
});

