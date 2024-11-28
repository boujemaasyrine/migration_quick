/**
 * Created by hcherif on 16/02/2016.
 */

$(function () {
    var categoryAddForm = $("#AddCategoryForm");
    var modalAdd = $('#modal-category-added');
    var filterZone = $('.filter-zone .panel-heading');

    list_categories = initSimpleDataTable('#categories_list_table', {
        rowId: 'category',
        "aoColumnDefs": [
            { "bSortable": false, "aTargets": [ 5 ] },
            { "sClass": "actions-btn", "aTargets": [ 5 ] }
        ]
    });

    $(document).on('click', '#plus-category-button', function (e) {
        loader.show();
        ajaxCall({
                url: Routing.generate('categories_list'),
                method: POST,
                data: categoryAddForm.serialize()
            },
            function (res) {
                if (res.errors === undefined && res.formError === undefined) {
                    modalAdd.html(res.data['0']);
                    showDefaultModal(Translator.trans('category.list.add_success'), modalAdd.html());
                    addCategorySuccess(res);
                }else{
                    categoryAddForm.html(res.formError['0']);
                    $( "input" ).each(function() {
                        if ( $( this ).val() != '' && $( this).attr('name') != 'category[helpCmd]' )
                        {
                            $( "label[for='"+$(this).attr('id')+"']" ).addClass( "active" );
                        }
                    });
                }
                loader.hide();
                });

    });

    $(document).on('click', '#edit-category-button', function (e) {
        loader.show();
        category = categoryAddForm.attr('class');
        ajaxCall({
                url: Routing.generate('categories_list', {category: category}),
                method: POST,
                data: categoryAddForm.serialize()
            },
            function (res) {
                if (res.errors === undefined && res.formError === undefined) {
                    list_categories.row( $('#' + category) ).remove().draw();
                    modalAdd.html(res.data['0']);
                    showDefaultModal(Translator.trans('category.list.edit_success'), modalAdd.html());
                    addCategorySuccess(res);
                    $('.panel-heading').html('<span class="glyphicon glyphicon-plus"></span> ' + Translator.trans('category.list.add'));
                categoryAddForm.removeAttr('class');
                }else{
                    categoryAddForm.html(res.formError['0']);
                    $( "input" ).each(function() {
                        if ( $( this ).val() != '' && $( this).attr('name') != 'category[eligible]' )
                        {
                            $( "label[for='"+$(this).attr('id')+"']" ).addClass( "active" );
                        }
                    });
                }
                loader.hide();
            });
        loader.hide();
    });

    $(document).on('click', '.glyphicon-edit', function(){
        loader.show();
        var category = $(this).parentsUntil('tbody','tr').attr('id');
        ajaxCall({
                url: Routing.generate('categories_list', {category: category}),
                dataType: 'json'
            },
            function (res) {
                if (typeof res.data != 'undefined' && res.data.length != 0) {
                    $('.panel-heading').html('<span class="glyphicon glyphicon-edit"></span> ' + Translator.trans('category.list.edit'));
                    categoryAddForm.html(res.data['0']);
                    categoryAddForm.removeAttr('class');
                    categoryAddForm.addClass(category);
                    $( "input" ).each(function() {
                        if ( $( this ).val() != '' && $( this).attr('name') != 'category[helpCmd]' )
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
        var categoryId = $(this).parentsUntil('tbody','tr').attr('id');
        loader.show();
        ajaxCall({
            url : Routing.generate('category_detail',{'category' : categoryId}),
            'type' : 'json'
        },function(data){
            showDefaultModal(Translator.trans('category.list.details'),data.data);
            loader.hide();
        })
    });

    $(document).on('click', '.glyphicon-remove', function(){
        var category = $(this).parentsUntil('tbody','tr').attr('id');
        loader.show();
        ajaxCall({
            url: Routing.generate('delete_category', {'category': category}),
            dataType: 'json'
        }, function (data) {
            if (typeof data.errors != 'undefined' && data.errors.length > 0) {
                var errorMsg = "<div class='alert alert-danger'>" + Translator.trans(data.errors[0]) + "</div>";
                showDefaultModal(Translator.trans('category.list.delete'), errorMsg);
            } else {
                showDefaultModal(Translator.trans('category.list.delete'), data.html);
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
                url: Routing.generate('categories_list')
            },
            function (res) {
                if (res.errors === undefined) {
                    categoryAddForm.html(res.data['0']);
                    filterZone.siblings('.panel-body').slideToggle();
                    $('.panel-heading').html('<span class="glyphicon glyphicon-plus"></span> ' + Translator.trans('category.list.add'));
                }
                else {
                    Notif.alert(Translator.trans('error.general.internal'),500,3000);
                }
            });

        loader.hide();
    });

    $("#export-btn").on('click',function(){
        submitExportDocumentFile(".filter-zone",list_categories,Routing.generate("categories_list_export"));
    });

    function addCategorySuccess(res){
        categoryAddForm.html(res.data['2']);
        filterZone.siblings('.panel-body').slideToggle();
        var newRow = list_categories.row.add([
            res.data['1'].order,
            res.data['1'].name,
            res.data['1'].groupCategory,
            res.data['1'].taxBe,
            res.data['1'].taxLux,
            res.data['1'].eligible,
            res.data['1'].btn
        ]).draw().node();
        $( newRow )
            .attr( 'id', res.data['1'].id );
    }
});

