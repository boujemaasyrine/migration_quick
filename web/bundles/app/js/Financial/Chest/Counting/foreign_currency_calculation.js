/**
 * Created by mchrif on 07/04/2016.
 */

var calculateForeignCurencyTotals = null;

$(function () {
    $(document).on('focus', '.exchange_rate_select', function() {
        $(this).attr('data-selected', true);

        var selects = $('.exchange_rate_select');
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

    calculateForeignCurencyTotals = function () {
        if(! countingIsValid){
            return;
        }
        var total = 0;
        var rows = $('#foreignCurrencyContainer').children('.row');

        rows.each(function(index, elem) {
            var amount = $(elem).find('.foreign_currency_amount').first().val();
            var exchangeRate = $(elem).find('.exchange_rate_select').first().val();
            var totalSpan = $(elem).find('.total_span').first();
            var subTotal = parseFloat(amount) * parseFloat(exchangeRate);
            total += subTotal;
            totalSpan.text(floatToString(subTotal));
        });

        $('.total_foreign_currency').text(floatToString(total));
        processSummaryCalculation();
    };

    $(document).on('keyup', '.foreign_currency_amount', function() {
        calculateForeignCurencyTotals();
    });

    $(document).on('focusout', '.exchange_rate_select', function() {
        $(this).attr('data-selected', false);
    });

    $(document).on('change', '.exchange_rate_select', function() {
        $(this).attr('data-selected', false);
        var select = $(this).find('option:selected').text();
        select = select.replace(/EUR => /g, '');
        var foreignLabel = select.split(' ')[0];
        $(this).siblings('.foreign_currency_label_input').first().val(foreignLabel);
        calculateForeignCurencyTotals();
    });

    $(document).on('click', '#addForeignCurrency', function() {
        var maxInputNumber = $('.exchange_rate_select').first().find('option').length;
        var container = $('#foreignCurrencyContainer');
        var nbr = parseInt($(this).attr('data-count'));
        if(maxInputNumber === 0 ||  $('#foreignCurrencyContainer .row').length +1 <= maxInputNumber) {
            $(this).attr('data-count', nbr + 1);
            var prototype = $('#addForeignCurrencyExchangeRate').data('prototype')
                .replace(/__name__/g, nbr)
                .replace(/__total__/g, '0,00');
            container.append($(prototype));
        }
    });

    var keyUpNamespace = 'foreign_currency_amount';
    $(document).on('focus', '.foreign_currency_amount', function () {
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

    var foreignCurrencyModal = null;
    $(document).on('click', '#addForeignCurrencyExchangeRate', function(e) {
        var title = Translator.trans('chest.foreign_currency.add_new_foreign_currency');
        var sourceBody   = $("#foreignCurrencyModalBody").html();
        var templateBody = Handlebars.compile(sourceBody);
        var contextBody = {};
        var body    = templateBody(contextBody);

        var sourceFooter   = $("#foreignCurrencyModalFooter").html();
        var templateFooter = Handlebars.compile(sourceFooter);
        var contextFooter = {};
        var footer    = templateFooter(contextFooter);

        foreignCurrencyModal = showDefaultModal(title, body, footer);

        $('#foreignCurrencyForm').validate({
            rules: {
                label: {
                    required: true
                },
                rate: {
                    required: true,
                    number: true
                }
            }
        });
    });

    $(document).on('click', '#saveForeignCurrency', function() {
        if($('#foreignCurrencyForm').valid()) {
            loader.block();
            ajaxCall({
                method: 'POST',
                url: Routing.generate('foreign_currency', {}),
                data: $('#foreignCurrencyForm').serialize()
            }, function (res) {
                if (res.errors === undefined) {
                    $('.exchange_rate_select').append('<option value="'+ $('#foreignCurrencyRate').val() +'" >' + $('#foreignCurrencyLabel').val() +
                        ' => EUR  ('+ $('#foreignCurrencyRate').val()+ ')</option>');
                    foreignCurrencyModal.modal('hide');
                } else {
                }
            }, null, function () {
                loader.unblock();
            });
        }
    });

});