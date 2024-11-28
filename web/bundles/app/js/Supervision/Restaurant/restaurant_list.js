/**
 * Created by hcherif on 16/02/2016.
 */

$(function () {

    function initializeDatePicker() {
       var openingDate = $('.opening_date');
        if(openingDate.val() !== '')
        {
            openingDate.attr('readonly', true);
        }
        else
        {
            var today = moment();
            fiscalDatepicker = initDatePicker('.opening_date', {
                min: today
            });
        }
    }

    var restaurantAddForm = $("#AddRestaurantForm");
    $('.selectize').selectize({
        plugins: ['remove_button']
    });
    var modalAdd = $('#modal-restaurant-added');
    var filterZone = $('.filter-zone .panel-heading');

    var list_restaurant = initSimpleDataTable('#restaurant_list_table', {
        searching: true,
        "aoColumnDefs": [
            { "bSortable": false, "aTargets": [ 8 ] },
            { "sClass": "actions-btn nowrap", "aTargets": [ 8 ] }
        ],
        order: [[ 7, "asc" ]]

    });

    //$(document).on('click', '#plus-restaurant-button', function (e) {
    //    loader.show();
    //    ajaxCall({
    //            url: Routing.generate('restaurants_list'),
    //            method: POST,
    //            data: restaurantAddForm.serialize()
    //        },
    //        function (res) {
    //            if (res.errors === undefined && res.formError === undefined) {
    //                addRestaurantSuccess(res);
    //                showDefaultModal(Translator.trans('restaurant.list.add_success'), modalAdd.html());
    //                $('#edit_new_button_' + res.data['1'].id).closest('td').attr('class', 'actions-btn');
    //            }
    //            else {
    //                restaurantAddForm.html(res.formError['0']);
    //                $('.selectize').selectize({});
    //                $( "input" ).each(function() {
    //                    if ( $( this ).val() != '' )
    //                    {
    //                        $( "label[for='"+$(this).attr('id')+"']" ).addClass( "active" );
    //                    }
    //                });
    //            }
    //            });
    //
    //    loader.hide();
    //});

    //$(document).on('click', '#edit-restaurant-button', function (e) {
    //    loader.show();
    //    restaurant = restaurantAddForm.attr('class');
    //    ajaxCall({
    //            url: Routing.generate('restaurants_list', {restaurant: restaurant}),
    //            method: POST,
    //            data: restaurantAddForm.serialize()
    //        },
    //        function (res) {
    //            if (res.errors === undefined && res.formError === undefined) {
    //                $('.panel-heading').html('<span class="glyphicon glyphicon-plus"></span> ' + Translator.trans('restaurant.list.add'));
    //                list_restaurant.row( $('#' + restaurant) ).remove().draw();
    //                addRestaurantSuccess(res);
    //                showDefaultModal(Translator.trans('restaurant.list.edit_success'), modalAdd.html());
    //                $('.selectize').selectize({});
    //                $('#edit_new_button_' + restaurant).closest('td').attr('class', 'actions-btn');
    //                restaurantAddForm.removeAttr('class');
    //            }
    //            else {
    //                restaurantAddForm.html(res.formError['0']);
    //                $('.selectize').selectize({});
    //                $( "input" ).each(function() {
    //                    if ( $( this ).val() != '' )
    //                    {
    //                        $( "label[for='"+$(this).attr('id')+"']" ).addClass( "active" );
    //                    }
    //                });
    //            }
    //            loader.hide();
    //        });
    //});

    //$(document).on('click', '.btn-edit-restaurant', function(){
    //    loader.show();
    //    var restaurant = $(this).attr('data-restaurant-id');
    //    ajaxCall({
    //            url: Routing.generate('restaurants_list', {restaurant: restaurant}),
    //            dataType: 'json'
    //        },
    //        function (res) {
    //            if (typeof res.data != 'undefined' && res.data.length != 0) {
    //                $('.panel-heading').html('<span class="glyphicon glyphicon-edit"></span> ' + Translator.trans('restaurant.list.edit'));
    //                restaurantAddForm.html(res.data['0']);
    //                $('.selectize').selectize({});
    //                restaurantAddForm.removeAttr('class');
    //                restaurantAddForm.addClass(restaurant);
    //                $("input").each(function () {
    //                    if ($(this).val() != '') {
    //                        $("label[for='" + $(this).attr('id') + "']").addClass("active");
    //                    }
    //                });
    //                if (filterZone.siblings('.panel-body').is(":hidden")){
    //                    filterZone.siblings('.panel-body').slideToggle();
    //                  }
    //                $('html,body').animate({
    //                    scrollTop: 0
    //                }, 1200);
    //            }else{
    //                Notif.alert(Translator.trans('error.general.internal'),500,3000);
    //            }
    //            loader.hide();
    //        });
    //
    //
    //});

    $(document).on('click', '.detail-btn', function(e){
        var restaurantId = $(this).parentsUntil('tbody','tr').attr('id');
        loader.show();
        ajaxCall({
            url : Routing.generate('restaurant_detail',{'restaurant' : restaurantId}),
            'type' : 'json'
        },function(data){
            showDefaultModal(Translator.trans('restaurant.list.details'),data.data, data.footerBtn, '95%');
            initSimpleDataTable('#supplier_resto_list_table', {"lengthChange": false, searching: false, pageLength: 5});
            loader.hide();
        })
    });

    $(document).on('click', '.btn-remove-restaurant', function(){

        var restaurant = $(this).attr('data-restaurant-id');
        loader.show();
        ajaxCall({
            url: Routing.generate('delete_restaurant', {'restaurant': restaurant}),
            dataType: 'json'
        }, function (data) {
            if (typeof data.errors != 'undefined' && data.errors.length > 0) {
                var errorMsg = "<div class='alert alert-danger'>" + Translator.trans(data.errors[0]) + "</div>"
                showDefaultModal(Translator.trans('restaurant.list.delete'), errorMsg);
            } else {
                showDefaultModal(Translator.trans('restaurant.list.delete'), data.html);
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
        $(location).attr("href", Routing.generate('restaurants_list'));
        //loader.show();
        //ajaxCall({
        //        url: Routing.generate('restaurants_list')
        //    },
        //    function (res) {
        //        if (res.errors === undefined) {
        //            restaurantAddForm.html(res.data['0']);
        //            $('.selectize').selectize({});
        //            filterZone.siblings('.panel-body').slideToggle();
        //            $('.panel-heading').html('<span class="glyphicon glyphicon-plus"></span> ' + Translator.trans('restaurant.list.add'));
        //        }
        //        else {
        //            Notif.alert(Translator.trans('error.general.internal'),500,3000);
        //        }
        //    });
        //
        //loader.hide();
    });


    $("#export-restaurant-btn").on('click',function(){
        submitExportDocumentFile(".filter-zone",list_restaurant,Routing.generate("supervision_restaurant_list_export"));
    });


    function addRestaurantSuccess(res){
        modalAdd.html(res.data['0']);
        restaurantAddForm.html(res.data['2']);
        filterZone.siblings('.panel-body').slideToggle();
        var newRow = list_restaurant.row.add([
            res.data['1'].code,
            res.data['1'].name,
            res.data['1'].email,
            res.data['1'].manager,
            res.data['1'].address,
            res.data['1'].phone,
            res.data['1'].type,
            res.data['1'].btn
        ]).draw().node();
        $( newRow )
            .attr( 'id', res.data['1'].id );
    }

    $(document).on('click','.parameters-btn', function(){
        var restaurant =  $(this).parentsUntil('tbody','tr').attr('id');
        loader.show();
        ajaxCall({
            url: Routing.generate('restaurant_parameters', {'restaurant': restaurant})
        },
        function (res) {
            if (res.errors === undefined) {
                var header = Translator.trans('restaurant_parameter.title', {'restaurant': res.restaurantName});
                showDefaultModal(header, res.formView, res.btn, '95%')
            }
            else {
                Notif.alert(Translator.trans('error.general.internal'),500,3000);
            }
            loader.hide();
        })
    });

    $(document).on('click', '#save-parameters', function(){
        loader.show();
        var restaurant = $(this).attr('data-restaurant');
        var parameterForm = $('#parameter-restaurant-form');
        var header;
        ajaxCall({
                url: Routing.generate('restaurant_parameters', {'restaurant': restaurant}),
                method: POST,
                data: parameterForm.serialize()
            },
            function (res) {
                if (res.errors === undefined && res.formError === undefined) {
                    header = Translator.trans('success');
                    var content = Translator.trans('restaurant_parameter.update_success', {'restaurant': res.restaurantName});
                    showDefaultModal(header, content, null, '50%')
                }
                else {
                    header = Translator.trans('restaurant_parameter.title', {'restaurant': res.restaurantName});
                    showDefaultModal(header, res.formView, res.btn, '95%')
                }
                loader.hide();
            });
    });


    $(document).on('change', '#parameters_restaurant_paymentMethod_1', function(){
        $('.check-quick-children').slideToggle();
        if ($(this).prop('checked') == true){
            $(".check-quick-children :input").each(function() {
                $(this).prop('checked', true);
            });
        }else{
            $(".check-quick-children :input").each(function() {
                $(this).prop('checked', false);
            });
        }
    });

    $(document).on('change', '#parameters_restaurant_paymentMethod_3', function(){
        $('.bank-card-children').slideToggle();
        if ($(this).prop('checked') == true){
            $(".bank-card-children :input").each(function() {
                $(this).prop('checked', true);
            });
        }else{
            $(".bank-card-children :input").each(function() {
                $(this).prop('checked', false);
            });
        }
    });

    $(document).on('change', '#parameters_restaurant_paymentMethod_4', function(){
        $('.ticket-paper-children').slideToggle();
        if ($(this).prop('checked') == true){
            $(".ticket-paper-children :input").each(function() {
                $(this).prop('checked', true);
            });
        }else{
            $(".ticket-paper-children :input").each(function() {
                $(this).prop('checked', false);
            });
        }
    });

    $(document).on('change', '#parameters_restaurant_paymentMethod_5', function(){
        $('.ticket-electronic-children').slideToggle();
        if ($(this).prop('checked') == true){
            $(".ticket-electronic-children :input").each(function() {
                $(this).prop('checked', true);
            });
        }else{
            $(".ticket-electronic-children :input").each(function() {
                $(this).prop('checked', false);
            });
        }
    });

    var radioActive = $('[name="restaurant[active]"]');
    radioActive.change(function () {
        var form = $(this).closest("form");
        var data = {};
        var section = $("#section-for-active-restaurant");
        data[$(this).attr("name")] = $('[name="restaurant[active]"]:checked').val();
        data['validation'] = false;
        loader.show();
        $.ajax({
            url: form.attr('action'),
            type: 'post',
            data : data,
            success: function (html) {
                console.log(html);
                section.replaceWith(
                    $(html).find('#section-for-active-restaurant')
                );
                initializeDatePicker();
                loader.hide();
            },
            error: function () {

            }
        });
    });

});