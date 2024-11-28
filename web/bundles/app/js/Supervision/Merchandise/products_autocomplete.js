/**
 * Created by mchrif on 12/03/2016.
 */

/**
 *
 * @returns {Function}
 */
function autocompleteProduct(productIdSelector, codeSupplierSelector, productNameSelector, quantitySelector, usageUnitLabel, selectCallback) {
    var cache = {};
    // construct termKey for cache purpose
    function termKey(term) {
        return term;
    }

    var productIdInput = $(productIdSelector);
    var productNameInput = $(productNameSelector);
    var codeSupplierInput = $(codeSupplierSelector);
    var quantitySelector = $(quantitySelector);
    var usageUnitLabel = $(usageUnitLabel);

    return function () {

        codeSupplierInput.autocomplete({
            autoFill: true,
            source: function (request, response) {
                var term = request.term;
                if (termKey(term) in cache) {
                    response(cache[termKey(term)]);
                    return;
                }
                ajaxCall({
                        url: Routing.generate('find_products', {
                            code: term,
                            selectedType: "article"
                        }),
                        method: GET
                    },
                    function (res) {
                        if (res.errors == undefined) {
                            cache[termKey(term)] = $.map(res.data[0], function (item) {
                                return {
                                    id: item.id,
                                    name: item.name,
                                    label: item.externalId,
                                    value: item.externalId,
                                    externalId: item.externalId,
                                    labelUnitUsage: item.labelUnitUsage,
                                    buyingCost: parseFloat(item.buyingCost),
                                    inventoryQty: parseFloat(item.inventoryQty),
                                    usageQty: parseFloat(item.usageQty)
                                };
                            });
                            response(cache[termKey(term)]);
                        }
                    }, null, null, true
                );
            },
            select: function (event, ui) {
                productNameInput.val(ui.item.name);
                productIdInput.val(ui.item.id);
                if (usageUnitLabel !== undefined) {
                    usageUnitLabel.text(Translator.trans(ui.item.labelUnitUsage));
                }
                quantitySelector.attr('data-usage-unit-price', ui.item.buyingCost / ( ui.item.inventoryQty * ui.item.usageQty ));
            }
        });
        productNameInput.autocomplete({
            autoFill: true,
            source: function (request, response) {
                var term = request.term;
                if (termKey(term) in cache) {
                    response(cache[termKey(term)]);
                    return;
                }
                ajaxCall({
                        url: Routing.generate('find_products', {
                            term: term,
                            selectedType: "article"
                        }),
                        method: GET
                    },
                    function (res) {
                        if (res.errors == undefined) {
                            cache[termKey(term)] = $.map(res.data[0], function (item) {
                                return {
                                    id: item.id,
                                    name: item.name,
                                    label: item.externalId + '- ' + item.name,
                                    externalId: item.externalId,
                                    labelUnitUsage: item.labelUnitUsage,
                                    buyingCost: parseFloat(item.buyingCost),
                                    inventoryQty: parseFloat(item.inventoryQty),
                                    usageQty: parseFloat(item.usageQty)
                                };
                            });
                            response(cache[termKey(term)]);
                        }
                    }, null, null, true
                );
            },
            select: function (event, ui) {
                console.log(ui, ui.item.buyingCost , ui.item.inventoryQty , ui.item.usageQty );
                if (selectCallback != null) {
                    selectCallback(event, ui);
                }
                productIdInput.val(ui.item.id);
                codeSupplierInput.val(ui.item.externalId);
                if (usageUnitLabel !== undefined) {
                    usageUnitLabel.text(Translator.trans(ui.item.labelUnitUsage));
                }
                quantitySelector.attr('data-usage-unit-price', ui.item.buyingCost / ( ui.item.inventoryQty * ui.item.usageQty ));
            }
        });
    };
}