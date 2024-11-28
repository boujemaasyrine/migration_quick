/**
 * Created by mchrif on 19/11/2015.
 */

/**
 * Loader Description:
 * - loader.show: show a loader on full screen
 * - loader.hide: hide the full screen loader
 * - loader.block(elem): block an element/ if elem is null it will show full screen loader
 * - loader.unblock(elem): unblock an element/ if elem is null it will hide the full screen loader
 * */

var loader = null;

$(function() {
    function Loader() {
        var self = this;
        var hidden = true;
        this.show = function () {
            $(".loader").fadeIn();
            hidden = false;
            animate();
        };

        this.block = function (elem) {
            // TODO Marwen: need to create a gif and insert it as message.
            //var template = "<div id='loader' style='display:none ;width: 100%; height: 100%; " +
            //    "background: -webkit - linear - gradient(left top, rgba(242, 234, 217, 1), rgba(255, 253, 255, 1), rgba(228, 217, 195, 1));/* For Safari 5.1 to 6.0 */" +
            //    "background: -o - linear - gradient(rgba(242, 234, 217, 1), rgba(255, 253, 255, 1), rgba(228, 217, 195, 1));/* For Opera 11.1 to 12.0 */" +
            //    "background: -moz - linear - gradient(rgba(242, 234, 217, 1), rgba(255, 253, 255, 1), rgba(228, 217, 195, 1));/* For Firefox 3.6 to 15 */" +
            //    "background: linear - gradient(tobottomleft, rgba(242, 234, 217, 1), rgba(255, 253, 255, 1), rgba(228, 217, 195, 1));/* Standard syntax */" +
            //    "position: fixed;top: 0;left: 0;z - index:9999;'>" +
            //    "< div id = 'loader_background' style = 'position: absolute;height: 17em;left: 44%; overflow: hidden; width: 0;top: 35 %;'>< img src = '{{ asset('bundles/app/images/background_loader.png') }}' style = ' height:17em;'>" +
            //    "</div>" +
            //    "<img id='loader_front' src = '/bundles/app/images/loader.png' style='position: absolute;height: 17em;left: 44%;top: 35 %;'>" +
            //    "</div>";
            if (elem) {
                $(elem).block({
                    message: '',
                    css: {border: '3px solid #a00'}
                });
                animate();
            } else {
                self.show();
            }

        };

        this.unblock = function (elem) {
            if (elem) {
                $(elem).unblock();
            } else {
                self.hide();
            }
        };

        this.hide = function () {
            hidden = true;
            $(".loader_background").animate({
                width: 0
            }, 100, function () {
                $(".loader").fadeOut();
            });
        };

        function animate() {
            var loader_front = $(".loader_front");
            var loader_background = $(".loader_background");
            var width = loader_front.css("width");
            loader_background.fadeIn();
            loader_front.fadeIn(500);
            loader_background.animate({
                width: width
            }, 1000, function () {
                if (!hidden) {
                    $(".loader_background").fadeOut(500, function () {
                        $(".loader_background").css('width', 0);
                        $(".loader_front").fadeOut(500);
                        animate();
                    });

                }
            });
        }
    }
    loader = new Loader();
});
