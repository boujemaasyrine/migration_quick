/**
 * Created by mchrif on 25/02/2016.
 */
/**
 * Created by mchrif on 19/11/2015.
 */

var Notif = null;

$(function () {
    var SPEED = 500;
    var TIMEOUT = 2500;

    /**
     * @constructor
     */
    function Notificator() {
        var self = this;
        var defaultOptions = {
            layout: 'bottomRight',
            timeout: TIMEOUT,
            animation: {
                open: {height: 'toggle'}, // jQuery animate function property object
                close: {height: 'toggle'}, // jQuery animate function property object
                easing: 'swing', // easing
                speed: SPEED // opening & closing animation speed
            }
        };

        /**
         *
         * @param message
         * @param speed
         * @param timeout
         */
        this.alert = function (message, speed, timeout) {
            var myOptions = {
                text: message,
                type: 'error',
                timeout: timeout === undefined || timeout === null ? TIMEOUT : timeout,
                animation: {
                    speed: speed === undefined || speed === null ? SPEED : speed
                }
            };
            myOptions = $.extend(myOptions, defaultOptions);

            return noty(myOptions);
        };

        /**
         * @param message
         * @param speed
         * @param timeout
         * @param buttons
         */
        this.success = function (message, speed, timeout, buttons) {
            var myOptions = {
                text: message,
                type: 'success',
                timeout: timeout === undefined || timeout === null ? TIMEOUT : timeout,
                animation: {
                    speed: speed === undefined || speed === null ? SPEED : speed
                }
            };
            if (buttons !== undefined) {
                myOptions.buttons = buttons;
            }
            myOptions = $.extend(myOptions, defaultOptions);

            return noty(myOptions);
        };
    }

    Notif = new Notificator();
});
