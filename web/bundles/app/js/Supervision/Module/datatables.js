/**
 * Created by mchrif on 10/02/2016.
 */

var initSimpleDataTable = null;

$(function() {
    var dataTableDefaultLanguage = {
        processing: Translator.trans('datatable.processing'),
        search: Translator.trans('datatable.search'),
        lengthMenu: Translator.trans('datatable.lengthMenu'),
        info: Translator.trans('datatable.info'),
        infoEmpty: Translator.trans('datatable.infoEmpty'),
        infoFiltered: Translator.trans('datatable.infoFiltered'),
        infoPostFix: Translator.trans('datatable.infoPostFix'),
        loadingRecords: Translator.trans('datatable.loadingRecords'),
        zeroRecords: Translator.trans('datatable.zeroRecords'),
        emptyTable: Translator.trans('datatable.emptyTable'),
        paginate: {
            first: Translator.trans('datatable.paginate.first'),
            previous: Translator.trans('datatable.paginate.previous'),
            next: Translator.trans('datatable.paginate.next'),
            last: Translator.trans('datatable.paginate.last')
        }
    };

    initSimpleDataTable = function (selector, options) {
        if (typeof options == 'undefined') {
            options = {};
        }
        var defaultOptions = {
            responsive: true,
            language: dataTableDefaultLanguage
        };
        $.extend(defaultOptions, options);
        return $(selector).DataTable(defaultOptions);
    }
});

