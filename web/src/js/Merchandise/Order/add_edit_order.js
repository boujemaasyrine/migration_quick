function checkProductOrderable(t) {
    var productId = t.item.product.id;
    var orderDate = $('#order_dateOrder').val();
    var errorElement = $('#code-product-error');
    // Réinitialise le message d'erreur
    errorElement.text('');
    // Vérifiez si la date est définie
    if (orderDate !== '') {
        var dateParts = orderDate.split('/');
        var formattedDate = dateParts[0] + '/' + dateParts[1] + '/' + dateParts[2];
        if (productId !== '') {
            $.ajax({
                url: Routing.generate('check_product_orderable'),
                method: 'GET',
                data: {
                    productId: productId,
                    orderDate: formattedDate
                },
                success: function (response) {
                    if (!response.orderable) {
                        errorElement.text(response.message); // Affiche le message d'erreur
                        $('#code-product').val(''); // Vide le champ de saisie
                        $('#label-product').val(''); // Vide le champ de saisie
                    }
                },
                error: function () {
                    errorElement.text('Une erreur s\'est produite lors de la vérification du produit.');
                }
            });
        }
    } else {
        errorElement.text('Veuillez sélectionner une date de commande.');
    }
}
function selectSupplier(e) {
    $(supplierSelector).find("option").removeAttr("selected"), $(supplierSelector).find("option[value=" + e + "]").attr("selected", "selected")
}

function getProductBy(e, t) {
    var r;
    for (r in products) {
        var a = products[r];
        if (a.hasOwnProperty(e) && a[e].toString() == t.toString()) return a
    }
    return null
}

function updateTotalValorization() {
    var e = 0;
    $.each($(".cmd-line"), function(t, r) {
        var a = parseInt($(r).find(".product-qty-input").val()) * parseFloat($(r).find(".product-unit-price-input").html());
        $(r).find(".val_line").html(floatToString(a)), e += a
    }), $("#order-val-total").html(floatToString(e))
}

function addNewLine(e) {
    if ($("#code-product").removeClass("shadow-danger"), $("#qty-cmd").removeClass("shadow-danger"), "undefined" != typeof e && "" == $("#code-product").val().trim()) return !0;
    if ("" == $("#code-product").val().trim()) return $("#code-product").focus(), $("#code-product").addClass("shadow-danger"), !1;
    if ("" == $("#qty-cmd").val().trim() || parseInt($("#qty-cmd").val().trim()) < 0) return $("#qty-cmd").focus(), $("#qty-cmd").addClass("shadow-danger"), !1;
    var t = $("#label-product").val(),
        r = $("#expd-unit").html(),
        a = $("#code-product").val(),
        n = $("#qty-cmd").val(),
        l = getProductBy("code", a);
    if (null == l) return $("#code-product").focus(), $("#code-product").addClass("shadow-danger"), !1;
    var o = parseInt($("#products-table").attr("line-count"));
    $("#products-table").attr("line-count", o + 1);
    var i = $("#products-table").attr("data-prototype").replace(/_line_number_/g, o).replace(/__name_product__/g, t).replace(/__unit__/g, Translator.trans(r)).replace(/__unit_price__/g, floatToString(l.unit_price)).replace(/__val_line__/g, floatToString(l.unit_price * n)).replace(/__rapport_unit__/g, rapportUnits(l)).replace(/__ref_product__/g, a);
    $("#new-line").before(i);
    var d = $(".cmd-line:last .stock_qty");
    d.html("<span class='min-loader'></span>"), getProductQty(l, function(e, t) {
        "real" == e.type ? t.html(e.qty + " " + Translator.trans(e.inv_unit_label) + " (R)") : t.html(e.qty + " " + Translator.trans(e.inv_unit_label) + " (T)")
    }, d), $("#order_lines_" + o + "_product").val(l.id), $("#order_lines_" + o + "_qty").val(n), updateTotalValorization(), resetNewLine(), $("#code-product").focus()
}

function setInputDate(e, t) {
    var r = $(e).pickadate("picker");
    r.set("select", t, {
        format: "dd/mm/yyyy"
    }), null == t || "" == t.trim() ? resetInputValue(e) : setInputValue(e, t)
}

function getOrderDate(e) {
    var t = "orderDateZoneError";
    $("#" + t).remove(), resetInputValue(orderDateSelector), resetInputValue(order_dateDelivery), $("#order_dateOrder_error_zone").hide(), ajaxCall({
        url: Routing.generate("get_next_planning", {
            supplier: e
        }),
        dataType: "json"
    }, function(e) {
        if (null != e.data) {
            setInputDate(orderDateSelector, e.data.order), setInputDate(deliveryDateSelector, e.data.delivery), highlightDeliveryDate(e.data.delivery), deliveryDate = e.data.delivery;
            var r = testPendingOrderExist();
            null != r && showEditOrderPopUp(r)
        } else appendErrorMsgTozone($(".orderDateInputFieldZone"), Translator.trans("command.new.js.date_not_found"), t)
    })
}

function getProductsBySupplier(e) {
    products = [], ajaxCall({
        url: Routing.generate("get_product_by_supplier", {
            supplier: e
        }),
        dataType: "json"
    }, function(e) {
        "undefined" != typeof e.data && e.data.length > 0 ? products = e.data : showPopError(Translator.trans("command.new.js.product_not_found"))
    })
}

function resetOrderForm() {
    resetInputValue(caPrevSelector), resetInputValue(orderDateSelector), resetInputValue(deliveryDateSelector), $(".cmd-line").remove(), resetNewLine(), $(".form-error").remove()
}

function resetNewLine() {
    $("#code-product").val(""), $("#label-product").val(""), $("#expd-unit").html(""), $("#stock-qty").html("-"), $("#qty-cmd").val(""), $("#new-unit-price").html(""), $("#rapport_expd_inv").html("")
}

function saveAsDraft(e) {
    $("form[name=order]").attr("action", Routing.generate("save_as_draft", {
        order: e
    })), $("form[name=order]").submit()
}

function refreshDatepicker() {
    if (orderPicker.set("disable", !0), null != supplierPlanning && 0 != supplierPlanning.length) {
        var e = [],
            t = [];
        $.each(supplierPlanning, function(e, r) {
            t.push(r.order + 1)
        });
        for (var r = 1; r < 8; r++) $.inArray(r, t) == -1 && e.push(r);
        orderPicker.set("disable", !1), orderPicker.set("disable", e)
    }
}

function getOrderDays() {
    var e = [];
    return "undefined" == typeof supplierPlanning || null == supplierPlanning || 0 == supplierPlanning.length ? [] : ($.each(supplierPlanning, function(t, r) {
        e.push(r.order)
    }), e)
}

function getSuppliersPlanning(e) {
    supplierPlanning = null, ajaxCall({
        url: Routing.generate("supplier_planning_json", {
            supplier: e
        }),
        dataType: "json"
    }, function(e) {
        supplierPlanning = e.data, loader.hide()
    })
}

function testPendingOrderExist() {
    var e = $("#order_dateOrder").val(),
        t = searchInArrayByKeyValue(pendingsOrders, "dateOrder", e);
    return t
}

function getPendingsOrder(e, t) {
    pendingsOrders = null, ajaxCall({
        url: Routing.generate("pendings_orders_by_supplier", {
            supplier: e
        }),
        dataType: "json"
    }, function(e) {
        if (pendingsOrders = e.data, t = "undefined" != typeof t && t, !t) {
            var r = testPendingOrderExist();
            null != r && showEditOrderPopUp(r)
        }
    })
}

function showEditOrderPopUp(e) {
    var t = $("#confirmation-edit-modal-box .modal-body").html(),
        r = $("#confirmation-edit-modal-box .modal-title").html();
    t = t.replace(/__link_to_edit__/, Routing.generate("edit_order", {
        order: e.id
    })), showDefaultModal(r, t, " ", null, null, !1)
}

function resetInputDates() {
    $("#default-modal-box").modal("hide")
}

function addClassToDate(e, t, r) {
    var a = $("#" + e + "_table.picker__table").find(".picker__day.picker__day--infocus[aria-label='" + t + "']");
    0 != a.length && null != a && a.addClass(r)
}

function addClassToCol(e, t, r) {
    if (!(t < 0 || t > 6)) {
        var a = $("#" + e + "_table.picker__table").find(".picker__day.picker__day--infocus");
        $.each(a, function(e, a) {
            var n = $(a).attr("aria-label"),
                l = moment(n, "DD/MM/YYYY");
            l.day() == t && $(a).addClass(r)
        })
    }
}

function highlightCols(e, t, r) {
    $("#" + e + "_table .selectable-date").removeClass("selectable-date"), "undefined" != typeof t && 0 != t.length && $.each(t, function(t, a) {
        addClassToCol(e, a, r)
    })
}

function showConfirmationPopUp() {
    showDefaultModal("Confirmation", $("#confirmation-modal-box").html(), "")
}

function confirmationYes() {
    $("#default-modal-box").modal("hide"), deliveryDate = null, setInputDate(deliveryDateSelector, ""), $(deliveryDateSelector + "_table .selectable-date").removeClass("selectable-date"), horsPlanning = !0
}

function confirmationNo() {
    horsPlanning = !1, $("#default-modal-box").modal("hide"), resetInputValue(orderDateSelector), resetInputValue(order_dateDelivery)
}

function isProductSelectable(e, t, r) {
    return !($.inArray(e.id, t) != -1 || !horsPlanning && 0 != r.length && null == searchInArrayByKeyValue(r, "id", e.category_id))
}

function highlightDeliveryDate(e) {
    $(deliveryDateSelector + "_table .selectable-date").removeClass("selectable-date"), addClassToDate("order_dateDelivery", e, "selectable-date")
}

function showConfirmationDeliveryDate() {
    showDefaultModal("Confirmation", $("#confirmation-delivery-modal-box").html(), "")
}

function confirmationDeliveryNo() {
    $("#default-modal-box").modal("hide"), setInputDate(deliveryDateSelector, deliveryDate)
}

function confirmationDeliveryYes() {
    $("#default-modal-box").modal("hide")
}

function disableEnableInputs() {
    var e = $("form[name=order]").find("input, select, button[type=submit]").not("#order_supplier ").not(".numOrder").not(".selectize-control .selectize-input input").not("#expd-unit");
    "" == $("#order_supplier").val() ? e.attr("disabled", "disabled") : e.removeAttr("disabled")
}

function getProductQty(e, t, r) {
    ajaxCall({
        url: Routing.generate("last_product_qty", {
            product: e.id
        }),
        dataType: "json"
    }, function(e) {
        "undefined" != typeof e.data && t(e.data, r)
    })
}

function initQtyProducts() {
    var e = $(".cmd-line");
    $.each(e, function(e, t) {
        var r = {
                id: $(t).find(".hidden input").val()
            },
            a = $(t).find(".stock_qty");
        a.html("<span class='min-loader'></span>"), getProductQty(r, function(e, t) {
            "real" == e.type ? t.html(e.qty + " " + Translator.trans(e.inv_unit_label) + " (R)") : t.html(e.qty + " " + Translator.trans(e.inv_unit_label) + " (T)")
        }, a)
    })
}

function rapportUnits(e) {
    var t = "1 " + Translator.trans(e.unitExp) + " = " + e.inv_ratio + " " + Translator.trans(e.unitInv),
        r = "1 " + Translator.trans(e.unitInv) + " = " + e.use_ratio + " " + Translator.trans(e.unitUse);
    return t + "<br>" + r
}

function isProductAlreadyOrdred(e) {
    var t = $("#order_dateOrder").val(),
        r = $("#order_dateDelivery").val(),
        a = [];
    if (!pendingsOrders) return !1;
    for (var n = 0; n < pendingsOrders.length; n++) t == pendingsOrders[n].dateOrder && a.push(pendingsOrders[n]);
    for (var n = 0; n < a.length; n++)
        if (null != a[n] && a[n].dateDelivery != r)
            for (var l = 0; l < a[n].lines.length; l++)
                if (e == a[n].lines[l]) return !0;
    return !1
}
var suppliers = null,
    products = [],
    supplierPlanning = null,
    orderPicker = null,
    pendingsOrders = null,
    supplierSelector = "#order_supplier",
    orderDateSelector = "#order_dateOrder",
    deliveryDateSelector = "#order_dateDelivery",
    caPrevSelector = "#order_caPrev",
    horsPlanning = !1,
    consultingTableInit = !1,
    deliveryDate = null;
ajaxCall({
    url: Routing.generate("find_suppliers"),
    dataType: "json"
}, function(e) {
    suppliers = e.data
}), $(document).on("change", supplierSelector, function() {
    disableEnableInputs(), horsPlanning = !1, resetOrderForm(), "" != $(this).val() && (loader.show(), resetInputValue(caPrevSelector), getProductsBySupplier($(this).val()), getOrderDate($(this).val()), getSuppliersPlanning($(this).val()), getPendingsOrder($(this).val()))
}), $(document).on("click", ".add-line", function() {
    addNewLine()
}), $(document).on("click", ".remove-line", function() {
    $(this).parentsUntil("tbody", "tr").remove(), updateTotalValorization()
}), $(document).on("submit", "form[name=order]", function(e) {
    $("#order_dateDelivery").removeAttr("disabled");
    var t = addNewLine(!0);
    if (t) return loader.show(), !0
}), $(document).on("click", "#submit_order_btn", function(e) {
    $("#order_dateOrder").val(), $("#order_dateDelivery").val();
    addNewLine();
    var t = [];
    if ($(".cmd-line").each(function(e, r) {
        var a = $(r).children("td").first().text().trim();
        isProductAlreadyOrdred(a) && t.push($(r).children("td").eq(1).text().trim())
    }), t.length > 0) {
        for (var r = "", a = 0; a < t.length; a++) r += "- <b>" + t[a] + "</b><br>";
        bootbox.confirm({
            title: Translator.trans("command.modal.title_submit"),
            message: "<p style='font-size: 16px;'>" + Translator.trans("command.modal.message_confirm_submit", {
                list: r
            }) + "</p>",
            closeButton: !1,
            size: "small",
            buttons: {
                confirm: {
                    label: Translator.trans("keyword.yes"),
                    className: "btn-validate margin-right-left"
                },
                cancel: {
                    label: Translator.trans("keyword.no"),
                    className: "btn-default margin-right-left"
                }
            },
            callback: function(e) {
                e && $("form[name=order]").submit()
            }
        })
    } else $("form[name=order]").submit()
}), $(document).on("blur", "#label-product", function() {
    var e = getProductBy("name", $(this).val());
    null == e && ($("#code-product").val(""), $("#expd-unit").html(""), $("#qty-cmd").val(""), $("#label-product").val(""), $("#stock-qty").html("-"))
}), $(function() {
    initQtyProducts(), orderPicker = $("#order_dateOrder").pickadate("picker"), "" != $(supplierSelector).val() && (loader.show(), getPendingsOrder($(supplierSelector).val(), !0), getProductsBySupplier($(supplierSelector).val()), getSuppliersPlanning($(supplierSelector).val())),
        $("#code-product").autocomplete({
        source: function(e, t) {
            var r = [],
                a = [],
                n = [],
                l = $("#order_dateOrder"),
                o = moment(l.val(), "DD/MM/Y"),
                i = searchInArrayByKeyValue(supplierPlanning, "order", o.day());
            i = null != i ? i.categories : [], $.each(products, function(t, l) {
                n = $(".product input"), $.each(n, function(e, t) {
                    a.push(parseInt($(t).val()))
                }), isProductSelectable(l, a, i) && l.code.toString().toUpperCase().indexOf(e.term.toUpperCase()) >= 0 && r.push({
                    value: l.code,
                    label: l.code + " (" + l.name + ")",
                    product: l
                })
            }), sortTabByProp(r, "value", e.term), t(r)
        },
        select: function(e, t) {
            isProductAlreadyOrdred(t.item.product.code) && bootbox.confirm({
                title: Translator.trans("command.modal.title"),
                message: "<p style='font-size: 16px;'>" + Translator.trans("command.modal.message", {
                    label: t.item.product.name
                }) + "</p>",
                closeButton: !1,
                size: "small",
                buttons: {
                    confirm: {
                        label: Translator.trans("keyword.yes"),
                        className: "btn-validate margin-right-left"
                    },
                    cancel: {
                        label: Translator.trans("keyword.no"),
                        className: "btn-default margin-right-left"
                    }
                },
                callback: function(e) {
                    if (!e) return void resetNewLine()
                }
            }), $("#stock-qty").html("<span class='min-loader'></span>"), getProductQty(t.item.product, function(e, t) {
                "real" == e.type ? t.html(e.qty + " " + Translator.trans(e.inv_unit_label) + " (R)") : t.html(e.qty + " " + Translator.trans(e.inv_unit_label) + " (T)")
            }, $("#stock-qty")), $("#expd-unit").html(Translator.trans(t.item.product.unitExp)), $("#label-product").val(t.item.product.name), $("#new-unit-price").html(floatToString(t.item.product.unit_price)), $("#rapport_expd_inv").html(rapportUnits(t.item.product)), $("#qty-cmd").focus();
            checkProductOrderable(t);
        },
        autoFocus: !0
    }), $("#label-product").autocomplete({
        source: function(e, t) {
            var r = [],
                a = [],
                n = [],
                l = $("#order_dateOrder"),
                o = moment(l.val(), "DD/MM/Y"),
                i = searchInArrayByKeyValue(supplierPlanning, "order", o.day());
            i = null != i ? i.categories : [], $.each(products, function(t, l) {
                n = $(".product input"), $.each(n, function(e, t) {
                    a.push(parseInt($(t).val()))
                }), isProductSelectable(l, a, i) && l.name.toUpperCase().indexOf(e.term.toUpperCase()) >= 0 && r.push({
                    value: l.name,
                    label: l.name + " (" + l.code + ")",
                    product: l
                })
            }), sortTabByProp(r, "value", e.term), t(r)
        },
        select: function(e, t) {
            isProductAlreadyOrdred(t.item.product.code) && bootbox.confirm({
                title: Translator.trans("command.modal.title"),
                message: "<p style='font-size: 16px;'>" + Translator.trans("command.modal.message", {
                    label: t.item.product.name
                }) + "</p>",
                closeButton: !1,
                size: "small",
                buttons: {
                    confirm: {
                        label: Translator.trans("keyword.yes"),
                        className: "btn-validate margin-right-left"
                    },
                    cancel: {
                        label: Translator.trans("keyword.no"),
                        className: "btn-default margin-right-left"
                    }
                },
                callback: function(e) {
                    if (!e) return void resetNewLine()
                }
            }), $("#qty-cmd").focus(), $("#stock-qty").html("<span class='min-loader'></span>"), getProductQty(t.item.product, function(e, t) {
                "real" == e.type ? t.html(e.qty + " " + Translator.trans(e.inv_unit_label) + " (R)") : t.html(e.qty + " " + Translator.trans(e.inv_unit_label) + " (T)")
            }, $("#stock-qty")), $("#expd-unit").html(Translator.trans(t.item.product.unitExp)), $("#code-product").val(t.item.product.code), $("#new-unit-price").html(floatToString(t.item.product.unit_price)), $("#rapport_expd_inv").html(rapportUnits(t.item.product)), e.stopImmediatePropagation();
             checkProductOrderable(t);
        },
        autoFocus: !0
    }), shortcutController.addCtrlShift(107, function() {
        addNewLine()
    }), orderPicker.on("close", function() {
        var e = "orderDateZoneError";
        $("#" + e).remove();
        var t = $("#order_dateOrder");
        if (null != t.val() && "" != t.val().trim()) {
            var r = moment(t.val(), "DD/MM/Y");
            if (null == supplierPlanning) resetInputValue("#order_dateDelivery");
            else {
                for (var a = 0; a < supplierPlanning.length; a++)
                    if (supplierPlanning[a].order == r.day()) {
                        var n = supplierPlanning[a].delivery - supplierPlanning[a].order;
                        n < 0 && (n = 7 + n);
                        var l = r.add(n, "d").format("DD/MM/Y");
                        setInputDate(deliveryDateSelector, l), highlightDeliveryDate(l), deliveryDate = l, horsPlanning = !1;
                        break
                    } var o = testPendingOrderExist();
                null != o && showEditOrderPopUp(o)
            }
            t = $("#order_dateOrder"), r = moment(t.val(), "DD/MM/Y");
            var i = getOrderDays(),
                d = !1;
            $.each(i, function(e, t) {
                t == r.day() && (d = !0)
            }), d || showConfirmationPopUp()
        }
    }), orderPicker.on("render", function() {
        highlightCols("order_dateOrder", getOrderDays(), "selectable-date")
    }), orderPicker.on("open", function() {
        var e = moment(),
            t = e.subtract(31, "days");
        orderPicker.set("disable", [{
            from: [2e3, 1, 1],
            to: [t.year(), t.month(), t.date()]
        }]), highlightCols("order_dateOrder", getOrderDays(), "selectable-date"), $(".picker__day--selected").removeClass("picker__day--selected"), $("#order_dateOrder_table.picker__table").find(".picker__day.picker__day--infocus[aria-label='" + $("#order_dateOrder").val() + "']").addClass("picker__day--selected")
    }), initPlanningConsulting();
    var e = $.Event("keyup");
    e.which = 13, shortcutController.addSimple(KEY_F7, function() {
        if (planningTable.column(0).search("", !0, !1, !0).draw(), $("#planning_command_modal").is(":visible")) $("#planning_command_modal").modal("hide");
        else if ($("#planning_command_modal").modal("show"), "" != $("#order_supplier").val()) {
            var e = $("#order_supplier").find("option[selected=selected]").html();
            planningTable.column(0).search("^" + e + "$", !0, !1, !0).draw(), $("#planning-table_filter input[type=search]").val(e)
        }
    }), $(document).on("keyup", "#planning-table_filter input[type=search]", function() {
        planningTable.column(0).search($(this).val(), !0, !1, !0).draw()
    });
    var t = $(deliveryDateSelector).pickadate("picker");
    t.on("open", function() {
        null != deliveryDate && highlightDeliveryDate(deliveryDate)
    }), t.on("set", function() {
        null != deliveryDate && highlightDeliveryDate(deliveryDate)
    }), t.on("close", function() {
        if (!horsPlanning) {
            var e = $(deliveryDateSelector).val();
            e != deliveryDate && showConfirmationDeliveryDate()
        }
    }), $("#qty-cmd").keypress(function(e) {
        13 == e.which && addNewLine()
    }), $("form[name=order]").keypress(function(e) {
        if (13 == e.which) return e.stopPropagation(), !1
    }), $("#order_supplier").selectize(), "" != $("#order_supplier").val() && $(supplierSelector).siblings(".selectize-control").find(".selectize-input input").css("position", "absolute"), disableEnableInputs(), updateTotalValorization()
}), $(document).unbind("keydown").bind("keydown", function(e) {
    var t = !1;
    if (8 === e.keyCode) {
        var r = e.srcElement || e.target;
        t = ("INPUT" !== r.tagName.toUpperCase() || "TEXT" !== r.type.toUpperCase() && "PASSWORD" !== r.type.toUpperCase() && "FILE" !== r.type.toUpperCase() && "SEARCH" !== r.type.toUpperCase() && "EMAIL" !== r.type.toUpperCase() && "NUMBER" !== r.type.toUpperCase() && "DATE" !== r.type.toUpperCase()) && "TEXTAREA" !== r.tagName.toUpperCase() || (r.readOnly || r.disabled)
    }
    t && e.preventDefault()
}), $(document).on("change", ".product-qty-input", function() {
    updateTotalValorization()
});