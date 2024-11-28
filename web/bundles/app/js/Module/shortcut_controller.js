/**
 * Created by mchrif on 10/02/2016.
 */

var shortcutController = null;

// Keys
var KEY_ESCAPE = 27;
var KEY_F8 = 119;
var KEY_F7 = 118;
var KEY_ADD = 107;
var KEY_ENTER = 13;

$(function () {
    shortcutController = new ShortcutController();
});

function ShortcutController() {
    var self = this;
    this.add = function (codeKey, isCtrl, isShift, callback, namespace) {
        if(namespace) {
            namespace = '.' + namespace;
        } else {
            namespace = ''
        }
        $(document).bind('keyup' + namespace , function (event) {
            if (isCtrl && isShift) {
                if (event.ctrlKey && event.shiftKey && event.keyCode == codeKey) {
                    callback(event);
                }
            } else if (isCtrl) {
                if (event.ctrlKey && !event.shiftKey && event.keyCode == codeKey) {
                    callback(event);
                }
            } else if (isShift) {
                if (!event.ctrlKey && event.shiftKey && event.keyCode == codeKey) {
                    callback(event);
                }
            } else {
                if (!event.ctrlKey && !event.shiftKey && event.keyCode == codeKey) {
                    callback(event);
                }
            }
        });
    };

    this.addCtrl = function (codeKey, callback) {
        self.add(codeKey, true, false, callback);
    };

    this.addShift = function (codeKey, callback) {
        self.add(codeKey, false, true, callback);
    };

    this.addCtrlShift = function (codeKey, callback) {
        self.add(codeKey, true, true, callback);
    };

    this.addSimple = function (codeKey, callback) {
        self.add(codeKey, false, false, callback);
    };
}
