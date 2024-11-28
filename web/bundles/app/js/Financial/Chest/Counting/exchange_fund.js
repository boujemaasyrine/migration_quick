/**
 * Created by mchrif on 07/04/2016.
 */

var calculateExchangeFundTotal = null;

$(function () {
    $(document).on('focus', '.exchange_fund_rate_select', function() {
        $(this).attr('data-selected', true);

        var selects = $('.exchange_fund_rate_select');
        var myOptions = $(this).find('option');
        var self = $(this);

        var mapObj = {};
        // first step
        selects.each(function(index, elem) {
            if($(elem).attr('data-selected') !== "true") {
                mapObj[$(this).find('option:selected').text()] = true;
            }
        });

        //second step
        myOptions.each(function(index, elem) {
            if($(elem).text() !== '') {
                if (mapObj[$(elem).text()] !== undefined) {
                    $(elem).attr('disabled', 'disabled');
                } else {
                    $(elem).removeAttr('disabled');
                }
            }
        });
    });

    calculateExchangeFundTotal = function () {
        if(! countingIsValid){
            return;
        }
        var total = 0;
        var rows = $('#exchangeContainer').find('.exchange_row');
        var regExp = /\(([^)]+)\)/;

        rows.each(function(index, elem) {
            var qty = $(elem).find('.exchange_quantity').first().val();
            var exchangeRate = 0;
            var select = $(elem).find('.exchange_fund_rate_select').first();
            if(select.val() != "") {
                exchangeRate = regExp.exec($(select).find('option:selected').text())[1];
            }
            var totalSpan = $(elem).find('.total_span').first();
            var subTotal = parseFloat(qty) * parseFloat(exchangeRate);
            total += subTotal;
            totalSpan.text(floatToString(subTotal));
        });

        $('.total_exchange_fund').text(floatToString(total));
        processSummaryCalculation();
    };

    $(document).on('keyup', '.exchange_quantity', function() {
        calculateExchangeFundTotal();
    });

    $(document).on('focusout', '.exchange_fund_rate_select', function() {
        $(this).attr('data-selected', false);
    });

    $(document).on('change', '.exchange_fund_rate_select', function() {
        $(this).attr('data-selected', false);
        var unitValue = $($(this).closest('.exchange_row')).find('.unit_value').first();
        var select = $(this).find('option:selected').text();
        var regExp = /\(([^)]+)\)/;
        var exchangeRate = 0;
        exchangeRate = parseFloat(regExp.exec(select)[1]);
        $(unitValue).val(floatToString(exchangeRate));
        calculateExchangeFundTotal();
    });

    $(document).on('click', '#addExchange', function() {
        //var maxInputNumber = $('.exchange_fund_rate_select').first().find('option').length;
        var container = $('#exchangeContainer');
        var nbr = parseInt($(this).attr('data-count'));
        //if(maxInputNumber === 0 ||  $('#foreignCurrencyContainer .row').length +1 <= maxInputNumber) {
            $(this).attr('data-count', nbr + 1);
            var prototype = $(this).data('prototype')
                .replace(/__name__/g, nbr)
                .replace(/__total__/g, '0,00');
            container.append($(prototype));
        //}
    });

    $(document).on('focus', '.foreign_currency_amount', function () {
        var keyUpNamespace = 'foreign_currency_amount';
        var self = $(this);
        var e = shortcutController.add(KEY_ENTER, null, null, function () {
            var nextCell = $($($(self).closest('.row').next('.row')[0]).find('.foreign_currency_amount')[0]);
            nextCell.focus();
            nextCell.select();
        }, keyUpNamespace);
        $(self).focusout(function () {
            $(document).unbind('keyup' + '.' + keyUpNamespace);
        });
    });

    $(document).on('focus', '.exchange_quantity', function () {
        var keyUpNamespace = 'exchange_fund_next_cell';
        var self = $(this);
        var e = shortcutController.add(KEY_ENTER, null, null, function () {
            var nextCell = $($($(self).closest('tr').next('tr')[0]).find('.exchange_quantity')[0]);
            nextCell.focus();
            nextCell.select();
        }, keyUpNamespace);
        $(self).focusout(function () {
            $(document).unbind('keyup' + '.' + keyUpNamespace);
        });
    });

});