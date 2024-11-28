/**
 * Created by hcherif on 31/03/2016.
 */
$(function () {
    function initializeDatePicker() {
        var from = moment(startDate, 'DD/MM/YYYY');
        var to = new Date(new Date().setDate(new Date().getDate() +1));
        initDatePicker('#expense_dateExpense', {
            disable: [
                true,
                {
                    from: new Date(from.format('YYYY/MM/DD')),
                    to: to
                }
            ],
            onOpen: function () {
                $('#expense_dateExpense_root .picker__today').remove();
            }
        });
    }

    initializeDatePicker();

    $("form[name=expense]").on('submit', function () {
        loader.block();
    });
});