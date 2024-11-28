/**
 * Created by hcherif on 27/05/2016.
 */

$(function () {

    $(document).on('click', '#downloadReport', function() {
        var form = $(this).closest('form');
        var url = form.attr('action');
        if(url.indexOf('?') >-1){
            url = url.substring(0, url.indexOf('?'));
        }
        form.attr('action', $.addParamToUrl(url,'download', true));
        $(form).submit();
    });

    $(document).on('click', '#export-btn', function() {
        var form = $(this).closest('form');
        var url = form.attr('action');
        if(url.indexOf('?') >-1){
            url = url.substring(0, url.indexOf('?'));
        }
        form.attr('action', $.addParamToUrl(url,'export', true));
        $(form).submit();
    });

    $(document).on('click', '#export-xls', function() {
        var form = $(this).closest('form');
        var url = form.attr('action');
        if(url.indexOf('?') >-1){
            url = url.substring(0, url.indexOf('?'));
        }
        form.attr('action', $.addParamToUrl(url,'xls', true));
        $(form).submit();
    });
    $(document).on('click', '#generateReport', function() {
        loader.block();
        var form = $(this).closest('form');
        var url = form.attr('action');
        if(url.indexOf('?') >-1){
            url = url.substring(0, url.indexOf('?'));
        }
        form.attr('action', url);
        $(form).submit();
    });

    // Change the selector if needed
    var $table = $('#table'),
        $bodyCells = $table.find('tbody.mainTbody tr.mainRow:first').children(),
        colWidth;

    // Get the tbody columns width array
    colWidth = $bodyCells.map(function() {
        return $(this).innerWidth();
    }).get();

    // Set the width of thead columns
    $table.find('thead tr.mainHeader').children().each(function(i, v) {
        $(v).width(colWidth[i]);
    });

});