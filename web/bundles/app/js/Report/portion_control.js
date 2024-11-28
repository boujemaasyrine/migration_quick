/**
 * Created by mchrif on 30/03/2016.
 */

$(function () {
    function importTickets() {
        loader.unblock();
        apiLoader.blockApiLoader();
        $.ajax({
            'url': Routing.generate('import_recent_tickets'),
            'success': function () {
                apiLoader.unblockApiLoader();
            }
        });
    }
    //importTickets();
    $('#portion_control_filter_category').selectize({});
    var productCache = {};
    function initProductAutocomplete() {
        var searchByCode = $('#portion_control_filter_code');
        searchByCode.autocomplete({
            autoFill: true,
            source: function (request, response) {
                var term = request.term;
                if (termKey(term) in productCache) {
                    response(productCache[termKey(term)]);
                    return;
                }
                ajaxCall({
                        url: Routing.generate('find_active_products', {
                            code: term,
                            selectedType: type
                        }),
                        method: GET
                    },
                    function (res) {
                        if (res.errors == undefined) {
                            productCache[termKey(term)] = $.map(res.data[0], function (item) {
                                return {
                                    id: item.id,
                                    name: item.name,
                                    label: item.externalId + '- ' + item.name,
                                    value: item.externalId,
                                    externalId: item.externalId
                                };
                            });
                            response(productCache[termKey(term)]);
                        }
                    }, null, null, true
                );
            },
            select: function (event, ui) {
                searchByProductName.val(ui.item.name);
            }
        });

        var searchByProductName = $('#portion_control_filter_name');
        searchByProductName.autocomplete({
            autoFill: true,
            source: function (request, response) {
                var term = request.term;
                if (termKey(term) in productCache) {
                    response(productCache[termKey(term)]);
                    return;
                }
                ajaxCall({
                        url: Routing.generate('find_active_products', {
                            term: term,
                            selectedType: 'article'
                        }),
                        method: GET
                    },
                    function (res) {
                        if (res.errors == undefined) {
                            productCache[termKey(term)] = $.map(res.data[0], function (item) {
                                return {
                                    id: item.id,
                                    name: item.name,
                                    label: item.name,
                                    value: item.name,
                                    externalId: item.externalId
                                };
                            });
                            response(productCache[termKey(term)]);
                        }
                    }, null, null, true
                );
            },
            select: function (event, ui) {
                searchByCode.val(ui.item.externalId);
            }
        });
    }

    initProductAutocomplete();

    // construct termKey for cache purpose
    function termKey(term) {
        return term;
    }

    function adaptTableHeader() {
        // Change the selector if needed
        var $table = $('#portionControlTable'),
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

    // client search
    var tableId = "#portionControlTable";
    $(document).on('keyup', '#searchOnReport',  function() {
        var value= $(this).val().toString().toUpperCase();
        var rows = $(tableId + " tbody tr");
        rows.each(function(index, elem) {
            if(!$(elem).hasClass('category_name')) {
                var code = $($(elem).children('td')[0]).text().toString().toUpperCase().trim();
                var name = $($(elem).children('td')[1]).text().toString().toUpperCase().trim();
                var regex = new RegExp(".*"+value + ".*", 'g');
                if(code.match(regex) || name.match(regex)) {
                    $(elem).removeClass("hidden");
                } else {
                    $(elem).addClass("hidden");
                }
            }
        });
        adaptTableHeader();
    });

    fixMyHeader("#portionControlTable");

});