function setInputValue(t, e) {
    $(t).siblings("label[for=" + $(t).attr("id") + "]").addClass("active"), $(t).val(e)
}

function resetInputValue(t) {
    $(t).val(""), $(t).siblings("label[for=" + $(t).attr("id") + "]").removeClass("active")
}

function resetCheckboxValue(t) {
    $(t).prop("checked", !1)
}

function highlightInput(t, e) {
    $(t).addClass(e), $(t).on("change", function(t) {
        var n = t.currentTarget;
        "" != $(n).val() && $(n).removeClass(e)
    })
}

function resetFilter(t) {
    var e = $(t).find("input");
    $.each(e, function(t, e) {
        "checkbox" != $(this).attr("type") ? resetInputValue(e) : resetCheckboxValue(e)
    }), $(t).find("select option").removeAttr("selected")
}

function serializeArrayToObjectByKey(t, e) {
    var n = {};
    return $.each(t, function(t, a) {
        a.hasOwnProperty(e) && a.hasOwnProperty("value") && (n[a[e]] = a.value)
    }), n
}

function createAForm(t, e, n, a) {
    var o = "<form method='post' id='" + a + "' action='" + n + "'>",
        r = $(t).find("input");
    $.each(r, function(t, e) {
        "undefined" != typeof $(e).attr("name") && ("checkbox" != $(e).attr("type") || "checkbox" == $(e).attr("type") && $(e).prop("checked")) && (o = o + "<input name='criteria[" + $(e).attr("name") + "]' value='" + $(e).val() + "' />")
    }), console.log(o);
    var i = $(t).find("select");
    $.each(i, function(t, e) {
        "undefined" != typeof $(e).attr("name") && (o = o + "<input name='criteria[" + $(e).attr("name") + "]' value='" + $(e).val() + "' />")
    });
    var s = e.order()[0];
    o = o + "<input name='order[0][column]' value='" + s[0] + "' />", o = o + "<input name='order[0][dir]' value='" + s[1] + "' />";
    var l = e.search();
    return o = o + "<input name='search[value]' value='" + l + "' />", o += "</form>"
}

function submitExportDocumentFile(t, e, n) {
    var a = createAForm(t, e, n, "export-form");
    $(body).append(a), $("#export-form").submit(), $("#export-form").remove()
}

function searchInArrayByKeyValue(t, e, n) {
    var a;
    for (a in t) {
        var o = t[a];
        if (o.hasOwnProperty(e) && o[e].toString() == n.toString()) return o
    }
    return null
}

function floatToString(t, e) {
    if ("undefined" == typeof e && (e = 2), null == t) return "";
    if (t = parseFloat(t), 0 == e) return Math.trunc(t);
    var n = t.toFixed(2);
    for (n = n.toString().replace(".", ","), n.indexOf(",") == -1 && (n += ",00"); n.substr(n.indexOf(",") + 1).length < e;) n += "0";
    return n.substr(n.indexOf(",") + 1).length > e && (n = n.substr(0, n.indexOf(",") + e + 1)), n
}

function showPopError(t) {
    var e = "<div class='alert alert-danger'><span class='glyphicon glyphicon-warning-sign'></span>  " + t + "</div>";
    $("#error-box-modal").is(":visible") || $("#error-box-modal").modal("show"), $("#error-box-modal .modal-body").append(e)
}

function closeErrorBox() {
    $("#error-box-modal").modal("hide"), $("#error-box-modal .modal-body").html("")
}

function appendErrorMsgTozone(t, e, n) {
    var a = "<div id='__id__' class='alert alert-danger form-error' role='alert'> <span class='glyphicon glyphicon-warning-sign'></span> __msg__ </div>",
        o = a.replace(/__msg__/, e).replace(/__id__/, n);
    t.append(o)
}

function clearErrorMsgToZone(t) {
    t.find(".form-error").remove()
}

function showDetailsInPopUp(t, e) {
    ajaxCall({
        url: t,
        type: "json"
    }, function(t) {
        showDefaultModal(e, t.data, "", "95%", "95%"), loader.hide()
    })
}

function showEntityDetailsWhenDocumentReady(t, e, n) {
    var a = window.location.hash;
    if ("" != a.trim()) {
        var o = a.substr(1);
        if ("" != o.trim() && Number.isInteger(parseFloat(o.trim()))) {
            var r = o.trim();
            loader.show();
            var i = [];
            i[e] = r, showDetailsInPopUp(Routing.generate(t, i), n)
        }
    }
}

function rotateElement(t, e) {
    "undefined" != typeof e ? t.removeClass("rotation") : t.addClass("rotation")
}

function goBack() {
    window.history.back()
}

function sortTabByProp(t, e, n) {
    if (0 == t.length) return 0;
    for (var a = t[0], o = 0; o < t.length; o++)
        if (t[o][e].toString().toUpperCase() == n.toString().toUpperCase()) return t[0] = t[o], void(t[o] = a)
}

function progressBarSuivi(t, e, n, a, o) {
    ajaxCall({
        url: Routing.generate("supervision_progress", {
            progression: t
        }),
        dataType: "json"
    }, function(t) {
        void 0 != t.redirection && (window.location.href = t.redirection), null == t.result ? (clearInterval(e), console.log("clear interval")) : ($(n).find(".progress-bar").css("width", t.result.progress + "%"), "undefined" == typeof a ? $(n).find(".progress-hint .progress-hint-per").html(t.result.progress + "% (" + t.result.proceeded + "/" + t.result.total + " Elements)") : null != a && $(n).find(".progress-hint .progress-hint-per").html(a(t.result)), 100 == parseFloat(t.result.progress) && ($(n).find(".progress-bar").removeClass("active"), $(n).find(".progress-bar").addClass("progress-bar-success"), clearInterval(e), "undefined" != typeof o && null != o && o()))
    })
}

function fixMyHeader(t, e) {
    var n = $(t).find("thead").first();
    $(window).scroll(function() {
        if ($(t).length) {
            var a = $(this).scrollTop(),
                o = $(t).offset().top;
            o - a < 0 ? ($(n).css("position", "fixed"), $(n).css("top", $("#mainNav").height()), void 0 != e && e(t)) : ($(n).css("top", 0), $(n).css("position", "relative"))
        }
    })
}

function fixTableHeaderWidth(t) {
    var e = $(t).find("thead").find(".main-header-tr").first().find("td, th"),
        n = $(t).find("tbody").find(".main-tbody-tr").first().find("td, th");
    $(t).find("thead").css("width", $(t).find("tbody").outerWidth() + "px"), $.each(n, function(t, a) {
        $(e[t]).css("width", $(a).outerWidth() + "px"), $(n[t]).css("width", $(a).outerWidth() + "px")
    })
}

function sortSelect(select) {
    var selected = select.val(),
        options = select.find("option"),
        arr = options.map(function(t, e) {
            return {
                t: $(e).text(),
                v: e.value
            }
        }).get(),
        empty;
    null != selected && (empty = options.map(function(t, e) {
        if ("" == $(e).val()) return {
            t: $(e).text(),
            v: e.value
        }
    }).get()), arr.sort(function(t, e) {
        return t.t > e.t ? 1 : t.t < e.t ? -1 : 0
    });
    var x;
    return x = null != selected ? -1 : 0, options.each(function(i, o) {
        0 == i && null != selected ? empty.length > 0 && (o.value = empty[0].v, $(o).text(empty[0].t)) : (o.value = arr[eval(i + x)].v, $(o).text(arr[eval(i + x)].t)), "" == arr[i].v && (x = 0)
    }), select.val(selected), select
}

function inWorkflow() {
    var t = !1;
    return ajaxCall({
        url: Routing.generate("in_workflow"),
        async: !1
    }, function(e) {
        t = e
    }, null, null, !0), t
}

function inAdministrativeClosing() {
    var t = !1;
    return ajaxCall({
        url: Routing.generate("in_administrative_closing"),
        async: !1
    }, function(e) {
        t = e
    }, null, null, !0), t
}

function inChestCountAdminClosing() {
    var t = !1;
    return ajaxCall({
        url: Routing.generate("in_chest_count"),
        async: !1
    }, function(e) {
        t = e["in"]
    }, null, null, !0), t
}

function nextStep(t, e) {
    var n = {};
    void 0 != t && (n.targetRouteName = t, void 0 != e && (n.outRouteOff = e)), window.location.href = Routing.generate("next_in_workflow", n)
}

function orderMultoSelectWithoutOrderingSelectedOptions(t) {
    var e = [],
        n = [];
    $.each(t.find("option"), function(t, a) {
        $(a).is(":selected") ? e.push(a) : n.push(a)
    }), n.sort(function(t, e) {
        return $(t).text().toLowerCase() < $(e).text().toLowerCase() ? -1 : 1
    }), t.find("option").remove(), $.each(e, function(e, n) {
        t.append(n)
    }), $.each(n, function(e, n) {
        t.append(n)
    })
}

function orderMultoSelect(t) {
    var e = [];
    $.each(t.find("option"), function(t, n) {
        e.push(n)
    }), e.sort(function(t, e) {
        return $(t).text().toLowerCase() < $(e).text().toLowerCase() ? -1 : 1
    }), t.find("option").remove(), $.each(e, function(e, n) {
        t.append(n)
    })
}

function hasTheRightFor(t) {
    var e = !1;
    return ajaxCall({
        url: Routing.generate("has_right", {
            right: t
        }),
        async: !1
    }, function(t) {
        e = t
    }, null, null, !0), e
}

function ShortcutController() {
    var t = this;
    this.add = function(t, e, n, a) {
        $(document).keyup(function(o) {
            e && n ? o.ctrlKey && o.shiftKey && o.keyCode == t && a() : e ? o.ctrlKey && !o.shiftKey && o.keyCode == t && a() : n ? !o.ctrlKey && o.shiftKey && o.keyCode == t && a() : o.ctrlKey || o.shiftKey || o.keyCode != t || a()
        })
    }, this.addCtrl = function(e, n) {
        t.add(e, !0, !1, n)
    }, this.addShift = function(e, n) {
        t.add(e, !1, !0, n)
    }, this.addCtrlShift = function(e, n) {
        t.add(e, !0, !0, n)
    }, this.addSimple = function(e, n) {
        t.add(e, !1, !1, n)
    }
}
var GET = "GET",
    POST = "POST",
    PUT = "PUT",
    DELETE = "DELETE",
    initMultiSelect = function(t) {
        $(t).multiselect({
            sortable: !0,
            searchable: !1,
            searchField: !0,
            filterSelected: !0
        }), $(".multiselect-available-list .ui-icon-arrowthickstop-1-w").toggleClass("ui-icon-arrowthickstop-1-w ui-icon-arrowthickstop-1-e"), $(".multiselect-selected-list .ui-icon-arrowthickstop-1-e").toggleClass("ui-icon-arrowthickstop-1-w ui-icon-arrowthickstop-1-e")
    },
    ajaxCall = function(t, e, n, a, o) {
        var r = Translator.trans("Error.general.internal"),
            i = {
                method: "GET"
            };
        return $.extend(i, t), void 0 == o && (o = !1), e ? i.success = function(t) {
            !o && ("object" != typeof t || "undefined" != typeof t.errors && t.errors.length > 0) && Notif.alert(r), void 0 != t.in_workflow && t.in_workflow ? window.location.href = t.redirect_to : e(t)
        } : o || (i.success = function(t) {
            !o && ("object" != typeof t || "undefined" != typeof t.errors && t.errors.length > 0) && Notif.alert(r)
        }), n ? i.error = function(t, e, a) {
            o || Notif.alert(r), n(t, e, a)
        } : o || (i.error = function(t) {
            !o && "undefined" != typeof t.errors && t.errors.length > 0 && Notif.alert(r)
        }), a && (i.complete = a), $.ajax(i)
    },
    showGeneralModal = function(t, e, n, a, o) {
        var r = $("#general-modal-box");
        return "undefined" != typeof o && r.addClass("modal-" + a), "undefined" != typeof o && r.addClass("modal-" + o), "undefined" != typeof t && r.find(".modal-title").html(t), "undefined" != typeof e && r.find(".modal-body").html(e), "undefined" != typeof n && r.find(".modal-footer").html(n), r.openModal(), r
    },
    showDefaultModal = function(t, e, n, a, o, r) {
        var i = $("#default-modal-box");
        return "undefined" != typeof a && $(i).css("width", a), "undefined" != typeof o && $(i).css("height", o), i.find(".modal-title").html(t), i.find(".modal-body>p").html(e), i.find(".modal-footer").show(), "" == n ? i.find(".modal-footer").hide() : "undefined" != typeof n && null != n ? i.find(".modal-footer").html(n) : i.find(".modal-footer").html("<button type='button' class='btn btn-cancel' data-dismiss='modal'>Fermer</button>"), "undefined" == typeof r ? i.find(".modal-header .close").show() : null != r && i.find(".modal-header .close").hide(), i.modal("show"), i
    },
    showEmtptyModal = function(t) {
        var e = $("#empty-modal-box");
        e.html(t), e.modal("show")
    },
    showHelpBox = function() {
        showDefaultModal("<span class='glyphicon glyphicon-info-sign'></span> Aide", $("#aide-box-content").html())
    },
    openCloseNavBar = function() {
        $("#vertical-nav-bar").is(":visible") ? $(".nav-bar-container").animate({
            width: "toggle"
        }, 350, function() {
            $(".show-nav-bar-btn").show(), $("#body").toggleClass("col-lg-10 col-md-9 col-xs-12")
        }) : ($(".show-nav-bar-btn").hide(), $("#body").toggleClass("col-lg-10 col-md-9 col-xs-12"), $(".nav-bar-container").animate({
            width: "toggle"
        }, 350, function() {}))
    };
$(function() {
    moment.locale(Translator.locale), $(".collapse-link").on("click", function(t) {
        $($(this).attr("data-target")).slideToggle()
    }), $(".modal-trigger").leanModal(), initDatePicker(), $(document).on("mouseenter", ".tooltipped", function(t) {
        $(this).attr("data-tooltip-id") || $(this).tooltip()
    }), $(document).on("mouseenter", ".bootstrap_tooltipped", function(t) {
        $(this).attr("data-tooltip-id") || $(this).tootltipBootstrap()
    }), screen.width <= 991 && openCloseNavBar(), $(document).on("click", ".filter-zone .panel-heading", function() {
        $(this).siblings(".panel-body").slideToggle()
    }), $(document).on("change", ".force-modulo-5", function() {
        var t = $(this).val().replace(",", ".");
        Number(t) == t && $(this).val(t - t % 5)
    }), $(document).on("change", ".parse-float", function() {
        var t = $(this).val().replace(",", ".");
        $(this).val(t)
    })
}), $.urlParam = function(t) {
    var e = new RegExp("[?&]" + t + "=([^&#]*)").exec(window.location.href);
    return null == e ? null : e[1] || 0
}, $.addParamToUrl = function(t, e, n) {
    var a = new RegExp(e + "=([^&]*)", "i").exec();
    return a = a && a[1] || "", "" == a && (t += t.indexOf("?") === -1 ? "?" + e + "=" + n : "&" + e + "=" + n), t
}, $.removeParamToUrl = function(t, e) {
    var n, a = t.split("?")[0],
        o = [],
        r = t.indexOf("?") !== -1 ? t.split("?")[1] : "";
    if ("" !== r) {
        o = r.split("&");
        for (var i = o.length - 1; i >= 0; i -= 1) n = o[i].split("=")[0], n === e && o.splice(i, 1);
        a = a + "?" + o.join("&")
    }
    return a
};
var auxParseFloat = parseFloat;
parseFloat = function(t) {
    return void 0 == t || null == t ? t : (t = t.toString(), t.indexOf(".") > 0 && t.indexOf(",") > 0 && (t = t.replace(".", "")), t = t.replace(",", "."), t = "" === t ? 0 : t, auxParseFloat(t))
};
var auxIsNaN = isNaN;
isNaN = function(t) {
    return "string" == typeof t && (t = t.replace(/\,/g, ".")), auxIsNaN(t)
}, $(document).on("click", ".closeDefaultModal", function() {
    $("#default-modal-box").modal("hide"), $("#default-modal-box p").html("")
}), $(function() {
    $("select.sortable").each(function() {
        sortSelect($(this))
    })
}), $(function() {
    $("form .help-block.has-error").closest(".form-group").addClass("has-error")
}), $(".nav ul").each(function() {
    var t = !0;
    $(this).find("li").each(function() {
        $(this).hasClass("hidden") || (t = !1)
    }), t && $(this).addClass("hidden")
}), $(document).on("click", "*[disabled=disabled]", function() {
    return $(this).blur(), !1
});
var initSimpleDataTable = null;
$(function() {
    var t = {
        processing: Translator.trans("datatable.processing"),
        search: Translator.trans("datatable.search"),
        lengthMenu: Translator.trans("datatable.lengthMenu"),
        info: Translator.trans("datatable.info"),
        infoEmpty: Translator.trans("datatable.infoEmpty"),
        infoFiltered: Translator.trans("datatable.infoFiltered"),
        infoPostFix: Translator.trans("datatable.infoPostFix"),
        loadingRecords: Translator.trans("datatable.loadingRecords"),
        zeroRecords: Translator.trans("datatable.zeroRecords"),
        emptyTable: Translator.trans("datatable.emptyTable"),
        paginate: {
            first: Translator.trans("datatable.paginate.first"),
            previous: Translator.trans("datatable.paginate.previous"),
            next: Translator.trans("datatable.paginate.next"),
            last: Translator.trans("datatable.paginate.last")
        }
    };
    initSimpleDataTable = function(e, n) {
        "undefined" == typeof n && (n = {});
        var a = {
            responsive: !0,
            language: t
        };
        return $.extend(a, n), $(e).DataTable(a)
    }
});
var datepickerDefaultLanguage = {
        labelMonthNext: Translator.trans("datepicker.labelMonthNext"),
        labelMonthPrev: Translator.trans("datepicker.labelMonthPrev"),
        labelMonthSelect: Translator.trans("datepicker.labelMonthSelect"),
        labelYearSelect: Translator.trans("datepicker.labelYearSelect"),
        monthsFull: [Translator.trans("datepicker.monthsFull.1"), Translator.trans("datepicker.monthsFull.2"), Translator.trans("datepicker.monthsFull.3"), Translator.trans("datepicker.monthsFull.4"), Translator.trans("datepicker.monthsFull.5"), Translator.trans("datepicker.monthsFull.6"), Translator.trans("datepicker.monthsFull.7"), Translator.trans("datepicker.monthsFull.8"), Translator.trans("datepicker.monthsFull.9"), Translator.trans("datepicker.monthsFull.10"), Translator.trans("datepicker.monthsFull.11"), Translator.trans("datepicker.monthsFull.12")],
        monthsShort: [Translator.trans("datepicker.monthsShort.1"), Translator.trans("datepicker.monthsShort.2"), Translator.trans("datepicker.monthsShort.3"), Translator.trans("datepicker.monthsShort.4"), Translator.trans("datepicker.monthsShort.5"), Translator.trans("datepicker.monthsShort.6"), Translator.trans("datepicker.monthsShort.7"), Translator.trans("datepicker.monthsShort.8"), Translator.trans("datepicker.monthsShort.9"), Translator.trans("datepicker.monthsShort.10"), Translator.trans("datepicker.monthsShort.11"), Translator.trans("datepicker.monthsShort.12")],
        weekdaysFull: [Translator.trans("datepicker.weekdaysFull.1"), Translator.trans("datepicker.weekdaysFull.2"), Translator.trans("datepicker.weekdaysFull.3"), Translator.trans("datepicker.weekdaysFull.4"), Translator.trans("datepicker.weekdaysFull.5"), Translator.trans("datepicker.weekdaysFull.6"), Translator.trans("datepicker.weekdaysFull.7")],
        weekdaysShort: [Translator.trans("datepicker.weekdaysShort.1"), Translator.trans("datepicker.weekdaysShort.2"), Translator.trans("datepicker.weekdaysShort.3"), Translator.trans("datepicker.weekdaysShort.4"), Translator.trans("datepicker.weekdaysShort.5"), Translator.trans("datepicker.weekdaysShort.6"), Translator.trans("datepicker.weekdaysShort.7")],
        weekdaysLetter: [Translator.trans("datepicker.weekdaysLetter.1"), Translator.trans("datepicker.weekdaysLetter.2"), Translator.trans("datepicker.weekdaysLetter.3"), Translator.trans("datepicker.weekdaysLetter.4"), Translator.trans("datepicker.weekdaysLetter.5"), Translator.trans("datepicker.weekdaysLetter.6"), Translator.trans("datepicker.weekdaysLetter.7")],
        today: Translator.trans("datepicker.today"),
        clear: Translator.trans("datepicker.clear"),
        close: Translator.trans("datepicker.close")
    },
    defaultOptions = {
        selectMonths: !0,
        selectYears: 15,
        closeOnSelect: !0,
        format: "dd/mm/yyyy",
        onClose: function() {
            $(".datepicker").blur(), $(".picker").blur()
        },
        onSet: function(t) {
            "select" in t && this.close()
        },
        firstDay: 1
    };
defaultOptions = $.extend(defaultOptions, datepickerDefaultLanguage);
var initDatePicker = function(t, e) {
        if (void 0 !== e) {
            if (void 0 !== e.onSet) {
                var n = e.onSet;
                defaultOptions.onSet = function(t) {
                    n(t), "select" in t && this.close()
                }
            }
            e = $.extend(e, defaultOptions)
        } else e = defaultOptions;
        if (void 0 === t) {
            var a = $(".datepicker").pickadate(e);
            return $(".datepicker").unbind("focus"), a
        }
        var a = $(t).pickadate(e);
        return $(t).unbind("focus"), a
    },
    loader = null;
$(function() {
    function t() {
        function t() {
            var e = $(".loader_front"),
                a = $(".loader_background"),
                o = e.css("width");
            a.fadeIn(), e.fadeIn(500), a.animate({
                width: o
            }, 1e3, function() {
                n || $(".loader_background").fadeOut(500, function() {
                    $(".loader_background").css("width", 0), $(".loader_front").fadeOut(500), t()
                })
            })
        }
        var e = this,
            n = !0;
        this.show = function() {
            $(".loader").fadeIn(), n = !1, t()
        }, this.block = function(n) {
            n ? ($(n).block({
                message: "",
                css: {
                    border: "3px solid #a00"
                }
            }), t()) : e.show()
        }, this.unblock = function(t) {
            t ? $(t).unblock() : e.hide()
        }, this.hide = function() {
            n = !0, $(".loader_background").animate({
                width: 0
            }, 100, function() {
                $(".loader").fadeOut()
            })
        }
    }
    loader = new t
}), $(document).ready(function() {
    if ("undefined" != typeof currentModule) switch (currentModule) {
        case "financial":
            $("li.financial-item").slideDown();
            break;
        case "supplying":
            $("li.supplying-item").slideDown();
            break;
        case "stock":
            $("li.stock-item").slideDown();
            break;
        case "staff":
            $("li.people-item").slideDown();
            break;
        case "utilities":
            $("li.utility-item").slideDown();
            break;
        case "config":
            $("li.config-item").slideDown();
            break;
        case "report":
            $("li.reporting-item").slideDown()
    }
    $(window).width() > 480 && $("ul.sf-menu").superfish()
});
var Notif = null;
$(function() {
        function t() {
            var t = {
                layout: "bottomRight",
                timeout: n,
                animation: {
                    open: {
                        height: "toggle"
                    },
                    close: {
                        height: "toggle"
                    },
                    easing: "swing",
                    speed: e
                }
            };
            this.alert = function(a, o, r) {
                var i = {
                    text: a,
                    type: "error",
                    timeout: void 0 === r || null === r ? n : r,
                    animation: {
                        speed: void 0 === o || null === o ? e : o
                    }
                };
                return i = $.extend(i, t), noty(i)
            }, this.success = function(a, o, r, i) {
                var s = {
                    text: a,
                    type: "success",
                    timeout: void 0 === r || null === r ? n : r,
                    animation: {
                        speed: void 0 === o || null === o ? e : o
                    }
                };
                return void 0 !== i && (s.buttons = i), s = $.extend(s, t), noty(s)
            }
        }
        var e = 500,
            n = 2500;
        Notif = new t
    }),
    function(t, e, n) {
        var a = {},
            o = {},
            r = function(e) {
                return "string" == t.type(e) && (e = {
                    message: e
                }), arguments[1] && (e = t.extend(e, "string" == t.type(arguments[1]) ? {
                    status: arguments[1]
                } : arguments[1])), new s(e).show()
            },
            i = function(t, e) {
                if (t)
                    for (var n in o) t === o[n].group && o[n].close(e);
                else
                    for (var n in o) o[n].close(e)
            },
            s = function(e) {
                this.options = t.extend({}, s.defaults, e), this.uuid = "ID" + (new Date).getTime() + "RAND" + Math.ceil(1e5 * Math.random()), this.element = t(['<div class="uk-notify-message alert-dismissable">', '<a class="close">&times;</a>', "<div>" + this.options.message + "</div>", "</div>"].join("")).data("notifyMessage", this), this.options.status && (this.element.addClass("alert alert-" + this.options.status), this.currentstatus = this.options.status), this.group = this.options.group, o[this.uuid] = this, a[this.options.pos] || (a[this.options.pos] = t('<div class="uk-notify uk-notify-' + this.options.pos + '"></div>').appendTo("body").on("click", ".uk-notify-message", function() {
                    t(this).data("notifyMessage").close()
                }))
            };
        return t.extend(s.prototype, {
            uuid: !1,
            element: !1,
            timout: !1,
            currentstatus: "",
            group: !1,
            show: function() {
                if (!this.element.is(":visible")) {
                    var t = this;
                    a[this.options.pos].show().prepend(this.element);
                    var e = parseInt(this.element.css("margin-bottom"), 10);
                    return this.element.css({
                        opacity: 0,
                        "margin-top": -1 * this.element.outerHeight(),
                        "margin-bottom": 0
                    }).animate({
                        opacity: 1,
                        "margin-top": 0,
                        "margin-bottom": e
                    }, function() {
                        if (t.options.timeout) {
                            var e = function() {
                                t.close()
                            };
                            t.timeout = setTimeout(e, t.options.timeout), t.element.hover(function() {
                                clearTimeout(t.timeout)
                            }, function() {
                                t.timeout = setTimeout(e, t.options.timeout)
                            })
                        }
                    }), this
                }
            },
            close: function(t) {
                var e = this,
                    n = function() {
                        e.element.remove(), a[e.options.pos].children().length || a[e.options.pos].hide(), delete o[e.uuid]
                    };
                this.timeout && clearTimeout(this.timeout), t ? n() : this.element.animate({
                    opacity: 0,
                    "margin-top": -1 * this.element.outerHeight(),
                    "margin-bottom": 0
                }, function() {
                    n()
                })
            },
            content: function(t) {
                var e = this.element.find(">div");
                return t ? (e.html(t), this) : e.html()
            },
            status: function(t) {
                return t ? (this.element.removeClass("alert alert-" + this.currentstatus).addClass("alert alert-" + t), this.currentstatus = t, this) : this.currentstatus
            }
        }), s.defaults = {
            message: "",
            status: "normal",
            timeout: 5e3,
            group: null,
            pos: "top-center"
        }, t.notify = r, t.notify.message = s, t.notify.closeAll = i, r
    }(jQuery, window, document);
var shortcutController = null,
    KEY_ESCAPE = 27,
    KEY_F8 = 119,
    KEY_F7 = 118,
    KEY_ADD = 107;
$(function() {
    shortcutController = new ShortcutController
}), $(function() {
    $.validator.addMethod("regex", function(t, e, n) {
        var a = new RegExp(n);
        return this.optional(e) || a.test(t)
    }, Translator.trans("general_validation.invalid_format"))
});