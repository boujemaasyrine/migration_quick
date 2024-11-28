/**
 * Created by mchrif on 11/03/2016.
 */

$(function () {
    var transformed_product_block = $('#transformed_product_block');
    var non_transformed_product_block = $('#non_transformed_product_block');
    var product_sold_recipes = $('#product_sold_recipes');
    var selectedCanals = {};
    orderMultoSelect($('#product_sold_restaurants'));
    initMultiSelect('#product_sold_restaurants');

    function initProductAutocomplete(recipeNumber, recipeLineNumber) {
        var productIdSelector = $("#" + recipeNumber + "_recipeLines_" + recipeLineNumber + "_productPurchased");
        var productNameSelector = $("#" + recipeNumber + "_recipeLines_" + recipeLineNumber + "_productPurchasedName");
        var codeSupplierSelector = $("#" + recipeNumber + "_recipeLines_" + recipeLineNumber + "_supplierCode");
        var quantitySelector = $("#" + recipeNumber + "_recipeLines_" + recipeLineNumber + "_qty");
        var siblingUnitLabel = $($("#" + recipeNumber + "_recipeLines_" + recipeLineNumber + "_qty").siblings('span')[0]);
        autocompleteProduct(productIdSelector, codeSupplierSelector, productNameSelector, quantitySelector, siblingUnitLabel)();
    }

    $(document).on('change', '#product_sold_type input', function (e) {
        if ($(e.target).val() == TRANSFORMED_PRODUCT) {
            transformed_product_block.fadeIn(200);

            // TODO : must reset the form inside
            non_transformed_product_block.fadeOut(200);

        } else {
            // TODO : must reset the form inside
            transformed_product_block.fadeOut(200);

            non_transformed_product_block.fadeIn(200);
        }
    });

    // Recipe
    $(document).on('click', '#addNewRecipe', function () {
        var nbr = parseInt($(this).attr('data-count'));
        $(this).attr('data-count', nbr + 1);
        var prototype = $(this).attr('data-prototype')
            .replace(/__name__label__/g, '')
            .replace(/__name__/g, nbr);
        prototype = $(prototype);
        product_sold_recipes.append(prototype);
    });
    $(document).on('click', '.deleteRecipe', function () {
        var row = $(this).closest('.row').parent().parent();
        row.fadeOut(200);
        row.remove();
    });

    // Recipe line
    $(".recipe_line_row, .non_transformed_product_row").each(function(index, elem) {
        elem = $(elem);
        var productIdInput = $(elem.find('.productIdInput')[0]);
        var productNameInput = $(elem.find('.productNameInput')[0]);
        var codeSupplierInput = $(elem.find('.codeSupplierInput')[0]);
        var qtySelector = $(elem.find('.qty-input')[0]);
        var usageUnitLabel = $(elem.find('.usageUnitLabel')[0]);
        if($(elem).hasClass('non_transformed_product_row')) {
            autocompleteProduct(productIdInput, codeSupplierInput, productNameInput, qtySelector, usageUnitLabel, function(event, ui) {
                console.debug(ui.item);
                $('#purchasedProductPrice').text(floatToString(ui.item.buyingCost / (ui.item.inventoryQty * ui.item.usageQty))+ ' €');
            })();
        } else {
            autocompleteProduct(productIdInput, codeSupplierInput, productNameInput, qtySelector, usageUnitLabel)();
        }
    });
    $(document).on('click', '.addNewRecipeLine', function () {
        var recipeNumber = $(this).attr('data-recipe-number');
        var recipeContainer = $('#' + recipeNumber + '_recipeLines');
        var nbr = parseInt(recipeContainer.attr('data-count'));
        recipeContainer.attr('data-count', nbr + 1);
        var prototype = $(this).attr('data-prototype')
            .replace(/__recipe_line__label__/g, '')
            .replace(/_unit_label_/g, '')
            .replace(/__recipe_line__/g, nbr)
            .replace(/_usage_unit_price_/g, 0);
        prototype = prototype.replace(/^<div>/,'');
        prototype = prototype.replace(/$<\/div>/,'');
        prototype = prototype.replace(/<label><\/label>/,'');
        recipeContainer.append(prototype);
        // call product autocomplete
        initProductAutocomplete(recipeNumber, nbr);
    });
    $(document).on('click', '.deleteRecipeLine', function () {
        var row = $(this).closest('.recipe_line_row');
        row.fadeOut(200);
        row.remove();
    });

    $(document).on('click', '.panel-heading a', function() {
        $(this).find('.glyphicon');
        $(this).find('.glyphicon').toggleClass('glyphicon-collapse-up');
        $(this).find('.glyphicon').toggleClass('glyphicon-collapse-down');
    });

    // Solding Canal management
    $(document).on('focus', '.canal_selector', function (e) {
        refreshCanalsSelect();
    });
    function refreshCanalsSelect() {
        selectedCanals = {};
        $('.canal_selector').each(function (index, elem) {
            var selectedCanalId = $(elem).val();
            selectedCanals[selectedCanalId.toString()] = true;
        }).each(function (index, elem) {
            $(elem).find('option').each(function (index, elem1) {
                if (index >= 0) {
                    // test if this value is not selected
                    var selectedCanalId = $($(elem1).closest('select')[0]).val().toString();

                    var selected = selectedCanalId === $(elem1).attr('value').toString();
                    if (!selected) {
                        if (selectedCanals[$(elem1).attr('value')] !== undefined) {
                            $(elem1).attr('disabled', 'disabled');
                        } else {
                            $(elem1).removeAttr('disabled');
                        }
                    }
                }
            });
        });
    }

    $(document).on('keyup', '.qty-input', function() {
        var recipeNumber = $($(this).closest('.recipeBlock')[0]).attr('data-recipe-number');
        refreshRecipeRevenu(recipeNumber);
    });

    function refreshRecipeRevenu(recipeNumber) {
        var recipeContainer = $('#' + recipeNumber + '_recipeLines');
        var revenuLabel = $('#recipe_revenu_' + recipeNumber);
        var total = 0;

        recipeContainer.find('.qty-input').each(function(index, elem) {
            var temp = parseFloat($(elem).val()) * parseFloat($(elem).attr('data-usage-unit-price'));

            if($(elem).val() != '' && !isNaN(temp)) {
                total += temp;
                $(elem).closest('tr').find('.total-amount').html(temp.toFixed('2') + ' €');
            }
        });

        revenuLabel.text(floatToString(total) + '€');
    }

    $(document).on('click', '#validateForm', function(event){
        event.preventDefault();
        $('#sheet_model_form').submit();
    });

    $(document).on('click', '#validateFormWithSynchro', function(event){
        event.preventDefault();
        var form = $('#sheet_model_form');
        var url = form.attr('action');
        form.attr('action', $.addParamToUrl(url,'synchronize', true));
        $(form).submit();
    });

});