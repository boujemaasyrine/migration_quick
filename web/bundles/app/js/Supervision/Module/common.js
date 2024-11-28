/**
 * Created by mchrif on 10/02/2016.
 * This file will contain all common functions that is used in all modules
 * The other controllers such as loaderContrller and shortcutController must be included as needed
 */

// Global constant
var GET = "GET";
var POST = "POST";
var PUT = "PUT";
var DELETE = "DELETE";

// define here the commmon globhal object
var initMultiSelect = function (obj) {
    $(obj).multiselect({
        sortable: true,
        searchable: false,
        searchField: true,
        filterSelected: true
    });

    $('.multiselect-available-list .ui-icon-arrowthickstop-1-w').toggleClass('ui-icon-arrowthickstop-1-w ui-icon-arrowthickstop-1-e');
    $('.multiselect-selected-list .ui-icon-arrowthickstop-1-e').toggleClass('ui-icon-arrowthickstop-1-w ui-icon-arrowthickstop-1-e');
};

var ajaxCall = function (options, success, error, complete, disableNotification) {
    var internalErrorMessage = Translator.trans('Error.general.internal');
    var defaultOptions = {
        method: 'GET'
    };
    $.extend(defaultOptions, options);

    if (disableNotification == undefined) {
        disableNotification = false;
    }

    if (success) {
        defaultOptions.success = function (res) {
            if (!disableNotification && (typeof res !== 'object' || (typeof res.errors != 'undefined' && res.errors.length > 0))) {
                Notif.alert(internalErrorMessage);
            }

            if (res.in_workflow != undefined && res.in_workflow) {
                window.location.href = res.redirect_to;
            } else {
                success(res);
            }
        }
    } else if (!disableNotification) {
        defaultOptions.success = function (res) {
            if (!disableNotification && (typeof res !== 'object' || (typeof res.errors != 'undefined' && res.errors.length > 0))) {
                Notif.alert(internalErrorMessage);
            }
        }
    }

    if (error) {
        defaultOptions.error = function (jqXHR, textStatus, errorThrown) {
            if (!disableNotification) {
                Notif.alert(internalErrorMessage);
            }
            error(jqXHR, textStatus, errorThrown)
        }
    } else if (!disableNotification) {
        defaultOptions.error = function (res) {
            if (!disableNotification && (typeof res.errors != 'undefined' && res.errors.length > 0)) {
                Notif.alert(internalErrorMessage);
            }
        }
    }

    if (complete) {
        defaultOptions.complete = complete
    } else if (!disableNotification) {

    }

    return $.ajax(defaultOptions);
};


/**
 *
 * @param title
 * @param body
 * @param footer
 * @param size
 * @param color
 * @returns {*|jQuery|HTMLElement}
 *
 * title: text
 * body/footer: html
 * size: sm/md/lg
 * color: default/success/info/danger/warning/primary
 */
var showGeneralModal = function (title, body, footer, size, color) {

    var modalBox = $('#general-modal-box');

    if (typeof color != 'undefined') modalBox.addClass('modal-' + size);
    if (typeof color != 'undefined') modalBox.addClass('modal-' + color);

    if (typeof title != 'undefined') modalBox.find('.modal-title').html(title);
    if (typeof body != 'undefined') modalBox.find('.modal-body').html(body);
    if (typeof footer != 'undefined') modalBox.find('.modal-footer').html(footer);

    modalBox.openModal();

    return modalBox;
};

var showDefaultModal = function (title, body, footer, width, height, closable) {
    var modalBox = $('#default-modal-box');

    if (typeof width != 'undefined') {
        $(modalBox).css('width', width);
    }

    if (typeof height != 'undefined') {
        $(modalBox).css('height', height);
    }

    modalBox.find('.modal-title').html(title);
    modalBox.find('.modal-body>p').html(body);

    modalBox.find('.modal-footer').show();
    if (footer == '') {
        modalBox.find('.modal-footer').hide();
    } else if (typeof footer != 'undefined' && footer != null) {
        modalBox.find('.modal-footer').html(footer);
    } else {
        modalBox.find('.modal-footer').html("<button type='button' class='btn btn-cancel' data-dismiss='modal'>Fermer</button>");
    }

    if (typeof closable == 'undefined') {
        modalBox.find('.modal-header .close').show();
    } else {
        if (closable != null) {
            modalBox.find('.modal-header .close').hide();
        }
    }

    modalBox.modal('show');

    return modalBox;
};

var showEmtptyModal = function (content) {
    var modalBox = $('#empty-modal-box');
    modalBox.html(content);
    modalBox.modal('show');
};

var showHelpBox = function () {
    showDefaultModal("<span class='glyphicon glyphicon-info-sign'></span> Aide", $('#aide-box-content').html());
};

var openCloseNavBar = function () {
    if ($('#vertical-nav-bar').is(':visible')) {//We gonna hide
        $('.nav-bar-container').animate({width: 'toggle'}, 350, function () {
            $('.show-nav-bar-btn').show();
            $('#body').toggleClass("col-lg-10 col-md-9 col-xs-12");
        });
    } else {//We gonna show
        $('.show-nav-bar-btn').hide();
        $('#body').toggleClass("col-lg-10 col-md-9 col-xs-12");
        $('.nav-bar-container').animate({width: 'toggle'}, 350, function () {
        });
    }
};

// define here the implementation
$(function () {
    moment.locale(Translator.locale);
    $('.collapse-link').on('click', function (event) {
        $($(this).attr('data-target')).slideToggle();
    });
    $('.modal-trigger').leanModal();
    initDatePicker();
    $(document).on('mouseenter', '.tooltipped', function (event) {
        if (!$(this).attr('data-tooltip-id')) {
            $(this).tooltip();
        }
    });

    $(document).on('mouseenter', '.bootstrap_tooltipped', function (event) {
        if (!$(this).attr('data-tooltip-id')) {
            $(this).tootltipBootstrap();
        }
    });

    // Responsive block
    if (screen.width <= 991) {
        openCloseNavBar();
    }

    $(document).on('click', '.filter-zone .panel-heading', function () {
        $(this).siblings('.panel-body').slideToggle();
    });

    $(document).on('change', '.force-modulo-5', function () {
        var amount = $(this).val().replace(',', '.');
        if (Number(amount) == amount) {
            $(this).val(amount - ( amount % 5));
        }
    });

    $(document).on('change', '.parse-float', function () {
        var input = $(this).val().replace(',', '.');
        $(this).val(input);
    });

});

/**
 *
 * @param name
 * @returns {*}
 */
$.urlParam = function (name) {
    var results = new RegExp('[\?&]' + name + '=([^&#]*)').exec(window.location.href);
    if (results == null) {
        return null;
    }
    else {
        return results[1] || 0;
    }
};

/**
 *
 * @param url
 * @param key
 * @param value
 * @returns {*}
 */
$.addParamToUrl = function (url, key, value) {
    //check if param exists
    var result = new RegExp(key + "=([^&]*)", "i").exec();
    result = result && result[1] || "";

    //param doesn't exist in url, add it
    if (result == '') {
        //doesn't have any params
        if (url.indexOf('?') === -1) {
            url += "?" + key + '=' + value;
        }
        else {
            url += "&" + key + '=' + value;
        }
    }

    //return the finished url
    return url;
};

/**
 *
 * @param uri
 * @param key
 * @returns {*}
 */
$.removeParamToUrl = function (uri, key) {
    var rtn = uri.split("?")[0],
        param,
        params_arr = [],
        queryString = (uri.indexOf("?") !== -1) ? uri.split("?")[1] : "";
    if (queryString !== "") {
        params_arr = queryString.split("&");
        for (var i = params_arr.length - 1; i >= 0; i -= 1) {
            param = params_arr[i].split("=")[0];
            if (param === key) {
                params_arr.splice(i, 1);
            }
        }
        rtn = rtn + "?" + params_arr.join("&");
    }

    return rtn;
};

function setInputValue(selector, value) {
    $(selector).siblings("label[for=" + $(selector).attr('id') + "]").addClass("active");
    $(selector).val(value);
}

function resetInputValue(selector) {
    $(selector).val('');
    $(selector).siblings("label[for=" + $(selector).attr('id') + "]").removeClass("active");
}

function resetCheckboxValue(selector) {
    $(selector).prop('checked', false);
}

function highlightInput(selector, highlight_class) {
    $(selector).addClass(highlight_class);
    $(selector).on('change', function (e) {
        var target = e.currentTarget;
        if ($(target).val() != '') {
            $(target).removeClass(highlight_class);
        }
    });
}

function resetFilter(container) {
    var inputs = $(container).find('input');
    $.each(inputs, function (key, value) {
        if ($(this).attr('type') != 'checkbox')
            resetInputValue(value);
        else
            resetCheckboxValue(value);
    });
    $(container).find('select option').removeAttr('selected');
}

function serializeArrayToObjectByKey(tab, key) {
    var result = {};
    $.each(tab, function (indice, value) {
        if (value.hasOwnProperty(key) && value.hasOwnProperty('value')) {
            result[value[key]] = value.value;
        }
    });
    return result;
}

function createAForm(form, dataTable, action, id) {

    var newForm = "<form method='post' id='" + id + "' action='" + action + "'>";

    var inputs = $(form).find('input');

    $.each(inputs, function (key, value) {
        if (typeof $(value).attr('name') != 'undefined') {
            if (($(value).attr('type') != 'checkbox') || ($(value).attr('type') == 'checkbox' && $(value).prop('checked'))) {
                newForm = newForm + "<input name='criteria[" + $(value).attr('name') + "]' value='" + $(value).val() + "' />";
            }
        }

    });

    console.log(newForm);

    var selects = $(form).find('select');

    $.each(selects, function (key, value) {
        if (typeof $(value).attr('name') != 'undefined') {
            newForm = newForm + "<input name='criteria[" + $(value).attr('name') + "]' value='" + $(value).val() + "' />";
        }
    });

    var orderBy = dataTable.order()[0];

    newForm = newForm + "<input name='order[0][column]' value='" + orderBy[0] + "' />";
    newForm = newForm + "<input name='order[0][dir]' value='" + orderBy[1] + "' />";

    var search = dataTable.search();
    newForm = newForm + "<input name='search[value]' value='" + search + "' />";

    newForm = newForm + "</form>";

    return newForm;

}

function submitExportDocumentFile(formSelector, dataTable, url) {
    var formHTML = createAForm(formSelector, dataTable, url, 'export-form');
    $(body).append(formHTML);
    $('#export-form').submit();
    $('#export-form').remove();
}

function searchInArrayByKeyValue(tab, key, value) {
    var i;
    for (i in tab) {
        var p = tab[i];
        if (p.hasOwnProperty(key)) {
            if (p[key].toString() == value.toString()) {
                return p;
            }
        }
    }
    return null;
}

var auxParseFloat = parseFloat;
parseFloat = function (chaine) {
    if (chaine == undefined || chaine == null) return chaine;

    chaine = chaine.toString();
    if (chaine.indexOf('.') > 0 && chaine.indexOf(',') > 0) {
        chaine = chaine.replace('.', '');
    }
    chaine = chaine.replace(',', '.');
    chaine = chaine === '' ? 0 : chaine;
    return auxParseFloat(chaine);
};

var auxIsNaN = isNaN;
isNaN = function (value) {
    if (typeof value == 'string') {
        value = value.replace(/\,/g, '.')
    }
    return auxIsNaN(value);
};

function floatToString(value, n) {

    if (typeof n == 'undefined') {
        n = 2;
    }

    if (value == null) return '';

    value = parseFloat(value);

    if (n == 0) {
        return Math.trunc(value);
    }

    var str = value.toFixed(2);
    str = str.toString().replace('.', ',');

    if (str.indexOf(',') == -1) {
        str = str + ',00';
    }

    while (str.substr(str.indexOf(',') + 1).length < n) {
        str = str + '0';
    }


    if (str.substr(str.indexOf(',') + 1).length > n) {
        str = str.substr(0, str.indexOf(',') + n + 1)
    }

    return str;
}

function showPopError(error) {
    var errorHtml = "<div class='alert alert-danger'><span class='glyphicon glyphicon-warning-sign'></span>  " + error + "</div>";

    if (!$('#error-box-modal').is(':visible')) {
        $('#error-box-modal').modal('show');
    }

    $('#error-box-modal .modal-body').append(errorHtml);
}

function closeErrorBox() {
    $('#error-box-modal').modal('hide');
    $('#error-box-modal .modal-body').html('');
}

function appendErrorMsgTozone(dom, msg, id) {
    var tmp = "<div id='__id__' class='alert alert-danger form-error' role='alert'>"
        + " <span class='glyphicon glyphicon-warning-sign'></span> __msg__"
        + " </div>";
    var zoneError = tmp.replace(/__msg__/, msg).replace(/__id__/, id);
    dom.append(zoneError)
}

function clearErrorMsgToZone(dom) {
    dom.find(".form-error").remove();
}

function showDetailsInPopUp(url, title) {
    ajaxCall({
        url: url,
        'type': 'json'
    }, function (data) {
        showDefaultModal(title, data.data, '', '95%', '95%');
        loader.hide();
    })
}

function showEntityDetailsWhenDocumentReady(routeName, paramName, title) {
    var hashUrl = window.location.hash;
    if (hashUrl.trim() != '') {
        var hash = hashUrl.substr(1);
        if (hash.trim() != '') {
            if (Number.isInteger(parseFloat(hash.trim()))) {
                var idEntity = hash.trim();
                loader.show();
                var options = [];
                options[paramName] = idEntity;
                showDetailsInPopUp(Routing.generate(routeName, options), title);
            }
        }
    }
}

function rotateElement(elm, stop) {
    if (typeof  stop != 'undefined') {
        elm.removeClass('rotation')
    } else {
        elm.addClass('rotation')
    }
}

function goBack() {
    window.history.back();
}

function sortTabByProp(tab, key, value) {
    if (tab.length == 0) {
        return 0;
    }
    var aux = tab[0];
    for (var i = 0; i < tab.length; i++) {
        if (tab[i][key].toString().toUpperCase() == value.toString().toUpperCase()) {
            tab[0] = tab[i];
            tab[i] = aux;
            return;
        }
    }
}

$(document).on('click', '.closeDefaultModal', function () {
    $('#default-modal-box').modal('hide');
    $('#default-modal-box p').html('');
});


function progressBarSuivi(progressId, intervalTimer, progressContainer, hintRendering, completed) {
    ajaxCall({
            url: Routing.generate('supervision_progress', {'progression': progressId}),
            dataType: 'json'
        }, function (data) {

            if (data.redirection != undefined) {
                window.location.href = data.redirection;
            }

            if (data.result == null) {
                clearInterval(intervalTimer);
                console.log('clear interval');
            } else {
                $(progressContainer).find('.progress-bar').css('width', data.result.progress + '%');

                if (typeof hintRendering == 'undefined') {
                    $(progressContainer).find('.progress-hint .progress-hint-per')
                        .html(data.result.progress + '% (' + data.result.proceeded + "/" + data.result.total + " Elements)");
                } else if (hintRendering != null) {
                    $(progressContainer).find('.progress-hint .progress-hint-per')
                        .html(hintRendering(data.result));
                }

                if (parseFloat(data.result.progress) == 100) {
                    $(progressContainer).find('.progress-bar').removeClass('active');
                    $(progressContainer).find('.progress-bar').addClass('progress-bar-success');
                    clearInterval(intervalTimer);

                    if (typeof  completed != 'undefined' && completed != null) {
                        completed();
                    }

                }
            }
        }
    )
}

function fixMyHeader(tableSelector, callback) {
    var headerSelector = $(tableSelector).find('thead').first();
    $(window).scroll(function () {
        var iCurScrollPos = $(this).scrollTop();
        var tableOffset = $(tableSelector).offset().top;
        if ((tableOffset - iCurScrollPos) < 0) {
            $(headerSelector).css('position', 'fixed');
            $(headerSelector).css('top', $('#mainNav').height());
            if (callback != undefined) {
                callback(tableSelector);
            }
        } else {
            $(headerSelector).css('top', 0);
            $(headerSelector).css('position', 'relative');
        }
    });
}

function fixTableHeaderWidth(table) {
    var mainHeaderTd = $(table).find('thead').find('.main-header-tr').first().find('td, th');
    var mainBodyTd = $(table).find('tbody').find('.main-tbody-tr').first().find('td, th');

    $(table).find('thead').css('width', $(table).find('tbody').outerWidth() + 'px');

    $.each(mainBodyTd, function (key, value) {
        $(mainHeaderTd[key]).css('width', $(value).outerWidth() + "px");
        $(mainBodyTd[key]).css('width', $(value).outerWidth() + "px");
    });
}

$(function () {
    $('select.sortable').each(function () {
        sortSelect($(this));
    });
});
function sortSelect(select) {
    var selected = select.val(),
        options = select.find('option'),
        arr = options.map(function (_, o) {
            return {
                t: $(o).text(),
                v: o.value
            };
        }).get(), empty;
    if (selected != null) {
        empty = options.map(function (_, o) {
            if ($(o).val() == '')
                return {
                    t: $(o).text(),
                    v: o.value
                };
        }).get();
    }
    arr.sort(function (o1, o2) {
        return o1.t > o2.t ? 1 : o1.t < o2.t ? -1 : 0;
    });
    var x;
    if (selected != null)
        x = -1;
    else  x = 0;
    options.each(function (i, o) {
        if (i == 0 && selected != null) {
            if (empty.length > 0) {
                o.value = empty[0].v;
                $(o).text(empty[0].t);
            }
        } else {
            o.value = arr[eval(i + x)].v;
            $(o).text(arr[eval(i + x)].t);
        }
        if (arr[i].v == '')
            x = 0;
    });
    select.val(selected);

    return select;
}

$(function () {
    $('form .help-block.has-error').closest('.form-group').addClass('has-error');
});

function inWorkflow() {
    var inW = false;
    ajaxCall({
        url: Routing.generate('in_workflow'),
        async: false
    }, function (x) {
        inW = x;
    }, null, null, true);

    return inW;
}

function inAdministrativeClosing() {
    var inW = false;
    ajaxCall({
        url: Routing.generate('in_administrative_closing'),
        async: false
    }, function (x) {
        inW = x;
    }, null, null, true);

    return inW;
}

function inChestCountAdminClosing() {
    var inW = false;
    ajaxCall({
        url: Routing.generate('in_chest_count'),
        async: false
    }, function (x) {
        inW = x.in;
    }, null, null, true);

    return inW;
}

function nextStep(targetRouteName,outRouteOf) {
    var obj = {};
    if (targetRouteName != undefined){
        obj.targetRouteName = targetRouteName;
        if (outRouteOf != undefined){
            obj.outRouteOff = outRouteOf;
        }
    }
    window.location.href = Routing.generate('next_in_workflow',obj);
}

function orderMultoSelectWithoutOrderingSelectedOptions(selectToBeOrdered){
    var rawSelectedOptions = [];
    var rawNotSelectedOptions = [];
    $.each(selectToBeOrdered.find('option'),function(key,value){
        if ($(value).is(':selected')){
            rawSelectedOptions.push(value);
        }else{
            rawNotSelectedOptions.push(value);
        }
    });

    //Order Not selected Option
    rawNotSelectedOptions.sort(function(x,y){
        if ( $(x).text().toLowerCase() < $(y).text().toLowerCase()){
            return -1;
        }
        return 1 ;
    });

    selectToBeOrdered.find('option').remove();
    $.each(rawSelectedOptions,function(key,value){
        selectToBeOrdered.append(value);
    });
    $.each(rawNotSelectedOptions,function(key,value){
        selectToBeOrdered.append(value);
    });
}

function orderMultoSelect(selectToBeOrdered){
    var rawOptions = [];
    $.each(selectToBeOrdered.find('option'),function(key,value){
        rawOptions.push(value);

    });

    //Order Not selected Option
    rawOptions.sort(function(x,y){
        if ( $(x).text().toLowerCase() < $(y).text().toLowerCase()){
            return -1;
        }
        return 1 ;
    });

    selectToBeOrdered.find('option').remove();
    $.each(rawOptions,function(key,value){
        selectToBeOrdered.append(value);
    });
}

$('.nav ul').each(function () {
    var ok = true;
    $(this).find('li').each(function () {
        if(!$(this).hasClass('hidden'))
            ok = false;
    });
    if(ok) $(this).addClass('hidden');
});

$(document).on('click', '*[disabled=disabled]', function () {
    $(this).blur();
    return false;
});

function hasTheRightFor(right) {
    var inW = false;
    ajaxCall({
        url: Routing.generate('has_right', {right: right}),
        async: false
    }, function (x) {
        inW = x;
    }, null, null, true);

    return inW;
}