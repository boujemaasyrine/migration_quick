function ShortcutController(){var t=this;this.add=function(t,o,d,e,n){n=n?"."+n:"",$(document).bind("keyup"+n,function(n){o&&d?n.ctrlKey&&n.shiftKey&&n.keyCode==t&&e(n):o?n.ctrlKey&&!n.shiftKey&&n.keyCode==t&&e(n):d?!n.ctrlKey&&n.shiftKey&&n.keyCode==t&&e(n):n.ctrlKey||n.shiftKey||n.keyCode!=t||e(n)})},this.addCtrl=function(o,d){t.add(o,!0,!1,d)},this.addShift=function(o,d){t.add(o,!1,!0,d)},this.addCtrlShift=function(o,d){t.add(o,!0,!0,d)},this.addSimple=function(o,d){t.add(o,!1,!1,d)}}var shortcutController=null,KEY_ESCAPE=27,KEY_F8=119,KEY_F7=118,KEY_ADD=107,KEY_ENTER=13;$(function(){shortcutController=new ShortcutController});