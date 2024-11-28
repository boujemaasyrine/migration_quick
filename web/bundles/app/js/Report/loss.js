/**
 * Created by hcherif on 30/03/2016.
 */

$(function () {
    $('.selectize').selectize({});
    var productCache = {};
    function initProductAutocomplete() {
        var searchByCode = $('#product_code');
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
                            if (type == 'article'){
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
                            else {
                                productCache[termKey(term)] = $.map(res.data[0], function (item) {
                                    return {
                                        id: item.id,
                                        name: item.name,
                                        label: item.codePlu + '- ' + item.name,
                                        value: item.codePlu,
                                        externalId: item.codePlu
                                    };
                                });
                                response(productCache[termKey(term)]);
                            }
                        }
                    }, null, null, true
                );
            },
            select: function (event, ui) {

            }
        });

        var searchByProductName = $('#product_name');
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
                            selectedType: type
                        }),
                        method: GET
                    },
                    function (res) {
                        if (res.errors == undefined) {
                            if (type == 'article'){
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
                            else {
                                productCache[termKey(term)] = $.map(res.data[0], function (item) {
                                    return {
                                        id: item.id,
                                        name: item.name,
                                        label: item.name,
                                        value: item.name,
                                        externalId: item.codePlu
                                    };
                                });
                                response(productCache[termKey(term)]);
                            }
                        }
                    }, null, null, true
                );
            },
            select: function (event, ui) {

            }
        });
    }

    initProductAutocomplete();

    // construct termKey for cache purpose
    function termKey(term) {
        return term;
    }

    // client search
    var tableId = "#table";
    $(document).on('keyup', '#searchOnReport',  function() {
        var value= $(this).val().toString().toUpperCase();
        var rows = $(tableId + " tbody tr");
        rows.each(function(index, elem) {
            if(!$(elem).hasClass('tabHeader') && !$(elem).hasClass('tabFooter')) {
                var name = $($(elem).children('td')[0]).text().toString().toUpperCase().trim();
                var regex = new RegExp(".*"+value + ".*", 'g');
                if(name.match(regex)) {
                    $(elem).fadeIn();

                } else {
                    $(elem).fadeOut();
                }
            }
        });

    });

});
