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
var apiLoader = null;

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
    function ApiLoader()
    {
        var self = this;
        var hidden = true;
        this.showApiLoader = function () {
            $("#api-loader").fadeIn();
            hidden = false;
            animateApiLoader();
        };

        this.blockApiLoader = function (elem) {
            if (elem) {
                $(elem).block({
                    message: '',
                    css: {border: '3px solid #a00'}
                });
                animateApiLoader();
            } else {
                self.showApiLoader();
            }
        };

        this.unblockApiLoader = function (elem) {
            if (elem) {
                $(elem).unblock();
            } else {
                self.hideApiLoader();
            }
        };

        this.hideApiLoader = function () {
            hidden = true;
            $("#api_loader_background").animate({
                width: 0
            }, 100, function () {
                $("#api-loader").fadeOut();
            });
        };

        function animateApiLoader() {
            var loader_front = $("#api_loader_front");
            var loader_background = $("#api_loader_background");
            var width = loader_front.css("width");
            loader_background.fadeIn();
            loader_front.fadeIn(500);
            loader_background.animate({
                width: width
            }, 1000, function () {
                if (!hidden) {
                    $("#api_loader_background").fadeOut(500, function () {
                        $("#api_loader_background").css('width', 0);
                        $("#api_loader_front").fadeOut(500);
                        animateApiLoader();
                    });

                }
            });
        }
    }
    apiLoader = new ApiLoader();
});
