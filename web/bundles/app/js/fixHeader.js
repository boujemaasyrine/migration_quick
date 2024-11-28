$(function () {
    function adaptTableHeader() {
        // Change the selector if needed
        var $table = $('.table'),
            $bodyCells = $table.find('tbody.mainTbody tr.mainRow:not(.hidden):first').children(),
            colWidth;

        // Get the tbody columns width array
        colWidth = $bodyCells.map(function() {
            return $(this).innerWidth();
        }).get();

        // Set the width of thead columns
        $table.find('thead tr.mainHeader').children().each(function(i, v) {
            $(v).width(colWidth[i]);
        });
    }

    adaptTableHeader();

    $(window).scroll(function () {
        adaptTableHeader();
    });

    fixMyHeader(".table");
});