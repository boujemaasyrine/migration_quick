/**
 * Created by hcherif on 09/03/2016.
 */

$(function () {

    function initializeDatePicker() {
        var today = moment();
        var picker = initDatePicker('#inventory_item_dateSynchro', {
            'disable': [{
                from: -3000, to: [today.year(), today.month(), today.date()]
            }]
        });
    }

    function initializeSubstituteSyncDatePicker() {
        var today = moment();
        var pick = initDatePicker('#substitute_inventory_item_dateSynchro', {
            'disable': [{
                from: -3000, to: [today.year(), today.month(), today.date()]
            }]
        });
    }

    var inventoryItemAddForm = $("#AddInventoryItemForm");
    var modalAdd = $('#modal-inventory-item-added');
    var filterZone = $('#filter-zone-add .panel-heading');
    $('.selectize').selectize({
        plugins: ['remove_button']
    });
    orderMultoSelect($('#inventory_item_restaurants'));
    initMultiSelect('#inventory_item_restaurants');
    initializeDatePicker();

    list_inventory_item = initSimpleDataTable('#inventory_item_list_table', {
        searching: true,
        processing: true,
        serverSide: true,
        rowId: 'id',
        "order": [[0, "asc"]],

        "aoColumnDefs": [
            {"bSortable": false, "aTargets": [6]},
            {width: '15%', "aTargets": [6]}
        ],
        "columns": [
            {"data": "code"},
            {"data": "name"},
            {"data": "buyingCost"},
            {
                "data": "statusKey",
                "render": function (data) {
                    if (data == 'active') {
                        return '<div class="label label-success" style="color:#fff !important;">' + Translator.trans('status.active') + '</div>';
                    }
                    else if (data == 'toInactive') {
                        return '<div class="label label-warning" style="color:#fff !important;">' + Translator.trans('status.toInactive') + '</div>'
                    }
                    else if (data == 'inactive') {
                        return '<div class="label label-danger" style="color:#fff !important;">' + Translator.trans('status.inactive') + '</div>'
                    }
                }
            },
            {"data": "dateSynchro"},
            {"data": "lastDateSynchro"},
            {
                "data": 'id',
                className: 'actions-btn nowrap',
                "render": function (data, type, row) {

                    var restaurants = row.restaurants === undefined ? [] : JSON.parse(row.restaurants);

                    var restOutput = '';

                    restaurants.forEach(function (value, key) {
                        var name = value["name"].replace(/\"/g, "&quot;");
                        name = name.replace(/\'/g, "&quot;");
                        var code = value["code"];
                        restOutput += "<li>- " + code + "- " + name + "</li>";
                    });

                    var btn = "<button type='button' class='btn btn-view btn-icon detail-btn'>" +
                        Translator.trans('btn.view') + "</button> " +
                        "<a data-item-id='{{ item.id }}' class='btn btn-modify btn-icon' style='margin-right: 3px;' " +
                        "href='" + Routing.generate('supervision_inventory_item_list', {productPurchased: data}) + "'>" +
                        Translator.trans('btn.edit') +
                        "</a>" +
                        "<span " +
                        " data-elligible-restaurants='" + restOutput + "' " +
                        " data-product-name='" + row.name + "' " + " class='btn small-btn btn-reload bootstrap_tooltipped reload-inventory-product '" +
                        "title='" + Translator.trans('keywords.force_synchronize') + "' data-item='" + data + "'>" +
                        "<i class='fa fa-cloud-download'></i>" +
                        "</span>" +
                        "<a data-item-id='{{ item.id }}'" +
                        " class='btn small-btn btn-reload bootstrap_tooltipped substitute-btn'" +
                        "title='" + Translator.trans('keywords.substitute') + "' data-item='" + data + "'>" +
                        "<i class='fa fa-exchange'></i>" +
                        "</a>";
                    return btn;
                }
            }

        ],
        ajax: {
            url: Routing.generate("inventory_item_list_export"),
            data: function (d) {
                d.criteria = serializeArrayToObjectByKey($('#itemInventoryFilterForm').serializeArray(), 'name');
                return d;
            },
            type: 'post'
        }

    });

    $(document).on('click', '.btn-edit', function () {
        loader.show();
        var item = $(this).attr('data-item-id');
        ajaxCall({
                url: Routing.generate('supervision_inventory_item_list', {productPurchased: item}),
                dataType: 'json'
            },
            function (res) {
                if (typeof res.data != 'undefined' && res.data.length != 0) {
                    inventoryItemAddForm.html(res.data['0']);
                    $('.datepicker').pickadate({disable: [{from: [0, 0, 0], to: yesterday}]});
                    inventoryItemAddForm.removeAttr('class');
                    inventoryItemAddForm.addClass(item);
                    inventoryItemAddForm.attr('action', Routing.generate('supervision_inventory_item_list', {productPurchased: item}));
                    orderMultoSelect($('#inventory_item_restaurants'));
                    initMultiSelect('#inventory_item_restaurants');
                    $('.heading-add').html('<span class="glyphicon glyphicon-edit"></span> ' + Translator.trans('item.inventory.edit'));
                    if (res.data['1'] !== null) {
                        $('#inventory_item_secondaryItem').parent().remove();
                    }
                    else {
                        $('#inventory_item_secondaryItem').selectize({});
                    }
                    $('#inventory_item_supplier').selectize({});
                    $("input").each(function () {
                        if ($(this).val() != '') {
                            $("label[for='" + $(this).attr('id') + "']").addClass("active");
                        }
                    });
                    if (filterZone.siblings('.panel-body').is(":hidden")) {
                        filterZone.siblings('.panel-body').slideToggle();
                    }
                    $('html,body').animate({
                        scrollTop: 0
                    }, 1200);
                } else {
                    Notif.alert(Translator.trans('error.general.internal'), 500, 3000);
                }
                loader.hide();
            });

    });

    $(document).on('click', '.detail-btn', function (e) {
        var inventoryItemId = $(this).parentsUntil('tbody', 'tr').attr('id');
        loader.show();
        ajaxCall({
            url: Routing.generate('supervision_inventory_item_detail', {'inventoryItem': inventoryItemId}),
            'type': 'json'
        }, function (data) {
            showDefaultModal(Translator.trans('item.inventory.details'), data.data, data.footerBtn, '95%');
            initSimpleDataTable('#item_restaurants_table', {"lengthChange": false, searching: false, pageLength: 5});
            initSimpleDataTable('#supplier_resto_list_table', {"lengthChange": false, searching: false, pageLength: 5});
            loader.hide();
        })
    });

    $(document).on('click', '#validate-substitution', function (e) {
        var form = $('#form-substitute');
        var inventoryItemId = $('#substitute_inventory_item_mainProduct').val();
        loader.show();
        ajaxCall({
            url: Routing.generate('inventory_item_substitute', {'inventoryItem': inventoryItemId}),
            method: 'POST',
            data: form.serialize()
        }, function (data) {
            showDefaultModal(Translator.trans('item.inventory.substitute'), data.data, data.footerBtn, '95%');
            initSimpleDataTable('#recipe_lines_table', {});
            initializeSubstituteSyncDatePicker();
            $('#substitute_inventory_item_productPurchased').selectize({plugins: ['remove_button']});
            loader.hide();
        })
    });
    $(document).on('click', '.substitute-btn', function (e) {
        var inventoryItemId = $(this).parentsUntil('tbody', 'tr').attr('id');
        loader.show();
        ajaxCall({
            url: Routing.generate('inventory_item_substitute', {'inventoryItem': inventoryItemId}),
            'type': 'json'
        }, function (data) {
            showDefaultModal(Translator.trans('item.inventory.substitute'), data.data, data.footerBtn, '95%');
            initSimpleDataTable('#recipe_lines_table', {});
            initializeSubstituteSyncDatePicker();
            $('#substitute_inventory_item_productPurchased').selectize({plugins: ['remove_button']});
            loader.hide();
        })
    });

    $(document).on('click', '.btn-remove', function () {
        var item = $(this).attr('data-item-id');
        loader.show();
        ajaxCall({
            url: Routing.generate('activate_inventory_item', {productPurchased: item, activate: 'false'}),
            dataType: 'json'
        }, function (data) {
            if (typeof data.errors != 'undefined' && data.errors.length > 0) {
                var errorMsg = "<div class='alert alert-danger'>" + Translator.trans(data.errors[0]) + "</div>"
                showDefaultModal(Translator.trans('item.inventory.delete'), errorMsg);
            } else {
                showDefaultModal(Translator.trans('item.inventory.delete'), data.html);
            }
            loader.hide();
        });

    });

    $(document).on('click', '.btn-activate', function () {
        var item = $(this).attr('data-item-id');
        loader.show();
        ajaxCall({
            url: Routing.generate('activate_inventory_item', {productPurchased: item, activate: 'true'}),
            dataType: 'json'
        }, function (data) {
            if (typeof data.errors != 'undefined' && data.errors.length > 0) {
                var errorMsg = "<div class='alert alert-danger'>" + Translator.trans(data.errors[0]) + "</div>"
                showDefaultModal(Translator.trans('item.inventory.delete'), errorMsg);
            } else {
                showDefaultModal(Translator.trans('item.inventory.delete'), data.html);
            }
            loader.hide();
        });

    });

    $(document).on('change', '#inventory_item_status', function () {
        var status = $(this).val();
        var deactivateDateInput = $('#inventory_item_deactivationDate');
        if (status == 'toInactive') {
            (deactivateDateInput).closest('.col').removeAttr('style');
            highlightInput("#inventory_item_deactivationDate", 'shadow-danger');
        }
        else {
            (deactivateDateInput).closest('col').attr('style', 'display:none;');
        }
    });

    $(document).on('click', '#btn-cancel-edit', function () {
        var content = "<h2>" + Translator.trans('cancel.confirmation.content') + "</h2>";
        var footer = "<div class='row'>" +
            "<div class='col col-lg-12 col-md-12 col-xs-12 col-sm-12' style='text-align: right'>" +
            "<button type='button' id='btn-yes-cancel-edit' class='btn waves-effect waves-light blue'>" + Translator.trans('yes_confirm') + "</button>" +
            "<button type='button' class='btn btn-cancel' data-dismiss='modal' style='margin-right: 10px'>" + Translator.trans('btn.close') + "</button>" +
            "</div>";
        showDefaultModal(Translator.trans('cancel.confirmation.title'), content, footer);
    });

    $(document).on('click', '#btn-yes-cancel-edit', function () {
        $(location).attr("href", Routing.generate('supervision_inventory_item_list'));
        //loader.show();
        //ajaxCall({
        //        url: Routing.generate('inventory_item_list')
        //    },
        //    function (res) {
        //        if (res.errors === undefined) {
        //            inventoryItemAddForm.html(res.data['0']);
        //            $('#inventory_item_secondaryItem').selectize({});
        //            $('#inventory_item_supplier').selectize({});
        //            initDatePicker();
        //            filterZone.siblings('.panel-body').slideToggle();
        //            $('.heading-add').html('<span class="glyphicon glyphicon-plus"></span> ' + Translator.trans('item.inventory.add'));
        //        }
        //        else {
        //            Notif.alert(Translator.trans('error.general.internal'),500,3000);
        //        }
        //        loader.hide();
        //    });

    });

    $("#export-btn").on('click', function () {
        submitExportDocumentFile(".filter-zone", list_inventory_item, Routing.generate("inventory_item_list_export", {"download": 1}));
    });

    $('#reset-filter').on('click', function () {
        resetFilter($('#filter-zone-search'));
        $select = $('#inventory_item_search_supplierSearch').selectize({});
        var control = $select[0].selectize;
        control.clear();
        list_inventory_item.ajax.reload();
    });

    $(document).on('change', '#inventory_item_inventoryQty', function () {
        var expedUnit = $('#inventory_item_labelUnitExped');
        var inventoryUnit = $('#inventory_item_labelUnitInventory');
        var conversion = $('#conversion_exped_invent');
        //if( (expedUnit.val()) && (inventoryUnit.val())  && ($(this).val()) ){
        conversion.html(expedUnit.find("option:selected").text() + ' = ' + $(this).val() + ' ' + inventoryUnit.find("option:selected").text());
        //}
        //else{
        //    conversion.html('');
        //}
    });

    $(document).on('change', '#inventory_item_labelUnitExped', function () {
        var inventoryUnit = $('#inventory_item_labelUnitInventory');
        var inventoryQty = $('#inventory_item_inventoryQty');
        var conversion = $('#conversion_exped_invent');
        //if( (inventoryQty.val()) && (inventoryUnit.val()) && ($(this).val()) ){
        conversion.html($(this).find("option:selected").text() + ' = ' + inventoryQty.val() + ' ' + inventoryUnit.find("option:selected").text());
        //}
        //else{
        //    conversion.html('');
        //}
    });

    $(document).on('change', '#inventory_item_labelUnitInventory', function () {
        var expedUnit = $('#inventory_item_labelUnitExped');
        var usageUnit = $('#inventory_item_labelUnitUsage');
        var inventoryQty = $('#inventory_item_inventoryQty');
        var usageQty = $('#inventory_item_usageQty');
        var conversionExpInv = $('#conversion_exped_invent');
        var conversionInvUsg = $('#conversion_invent_usage');
        //if( (inventoryQty.val()) && (expedUnit.val()) && ($(this).val()) ){
        conversionExpInv.html(expedUnit.find("option:selected").text() + ' = ' + inventoryQty.val() + ' ' + $(this).find("option:selected").text());
        //}
        //else{
        //    conversionExpInv.html('');
        //}
        //if( (usageQty.val()) && (usageUnit.val()) && ($(this).val()) ){
        conversionInvUsg.html($(this).find("option:selected").text() + ' = ' + usageQty.val() + ' ' + usageUnit.find("option:selected").text());
        //}
        //else{
        //    conversionInvUsg.html('');
        //}
    });

    $(document).on('change', '#inventory_item_labelUnitUsage', function () {
        var inventoryUnit = $('#inventory_item_labelUnitInventory');
        var usageQty = $('#inventory_item_usageQty');
        var conversion = $('#conversion_invent_usage');
        //if( (inventoryUnit.val()) && (usageQty.val()) && ($(this).val()) ){
        conversion.html(inventoryUnit.find("option:selected").text() + ' = ' + usageQty.val() + ' ' + $(this).find("option:selected").text());
        //}
        //else{
        //    conversion.html('');
        //}
    });

    $(document).on('change', '#inventory_item_usageQty', function () {
        var inventoryUnit = $('#inventory_item_labelUnitInventory');
        var usageUnit = $('#inventory_item_labelUnitUsage');
        var conversion = $('#conversion_invent_usage');
        //if( (usageUnit.val()) && (inventoryUnit.val()) && ($(this).val()) ){
        conversion.html(inventoryUnit.find("option:selected").text() + ' = ' + $(this).val() + ' ' + usageUnit.find("option:selected").text());
        //}
        //else{
        //    conversion.html('');
        //}
    });

    $(document).on('click', '#submit-inventory-item-form', function (event) {
        event.preventDefault();
        $('#AddInventoryItemForm').submit();
    });

    $(document).on('click', '#submit-inventory-item-form-synchronize', function (event) {
        event.preventDefault();
        var form = $('#AddInventoryItemForm');
        var url = form.attr('action');
        form.attr('action', $.addParamToUrl(url, 'synchronize', true));
        $(form).submit();
    });

    function addItemSuccess(res) {
        modalAdd.html(res.data['0']);
        inventoryItemAddForm.html(res.data['2']);
        filterZone.siblings('.panel-body').slideToggle();
        showDefaultModal(Translator.trans('item.inventory.add_success'), modalAdd.html(), res.data['footerBtn']);
        list_inventory_item.ajax.reload();
    }


    $(document).on('click', '.reload-inventory-product', function () {
        var inventoryItemId = $(this).attr('data-item');
        var restaurants = $(this).attr('data-elligible-restaurants');
        var productName = $(this).attr('data-product-name');
        bootbox.confirm({
            title: Translator.trans('product.synchronization_title'),
            message: Translator.trans('product.synchronization_message',
                {
                    product: productName,
                    restaurants: restaurants
                }),
            closeButton: false,
            buttons: {

                'cancel': {
                    label: Translator.trans('keyword.no'),
                    className: 'btn btn-cancel btn-icon margin-right-left'
                },
                'confirm': {
                    label: Translator.trans('keyword.yes'),
                    className: 'btn btn-validate btn-icon margin-right-left pull-right'
                }
            },
            callback: function (result) {
                if (result) {
                    loader.show();
                    ajaxCall({
                        url: Routing.generate('force_synchronize_purchased_product', {'productPurchased': inventoryItemId}),
                        'type': 'json'
                    }, function (data) {
                        if (data.data.sucess !== undefined) {
                            location.reload();
                        }
                        loader.hide();
                    })
                } else {
                }
            }
        });
    })
});