$(document).ready(function(){loader.show(),ajaxCall({url:Routing.generate("default_password"),type:"json"},function(e){showDefaultModal(e.header,e.formBody,e.footer),$("#btn-cancel").css("display","none"),loader.hide()}),$(document).on("click","#btn-validate-password",function(e){var o=$(this).parent().attr("data-user"),r=$("#defaultPasswordForm");loader.show(),e.preventDefault(),ajaxCall({url:Routing.generate("default_password",{staff:o,firstConnexion:"true"}),method:POST,data:r.serialize()},function(e){void 0===e.errors&&void 0===e.formError?e.isFromSupervision?window.location.href=Routing.generate("restaurant_list_super"):window.location.href=Routing.generate("index"):void 0===e.errors&&r.html(e.formError[0]),loader.hide()})})});