$(function(){function a(){list_roles=initSimpleDataTable("#staff_roles_list_table",{searching:!1,lengthMenu:!1,bPaginate:!1,columnDefs:[{width:"20%",aTargets:[0]},{width:"15%",aTargets:[2]},{targets:[2],orderable:!1}]})}$("#AttributeRoleForm");staff=initSimpleDataTable("#staff_table",{rowId:"id",processing:!0,serverSide:!0,searching:!0,order:[[2,"desc"]],columnDefs:[{targets:[5],orderable:!1},{width:"10%",aTargets:[5]}],columns:[{data:"socialSecurity"},{data:"firstName"},{data:"username"},{data:"email"},{data:"role"},{className:"actions-btn",render:function(a,t,e){var r="<button type='button' class='btn btn-view btn-icon btn-xs  detail-btn'> "+Translator.trans("btn.view")+"</button>";return r}}],ajax:{url:Routing.generate("staff_json_list"),data:function(a){return a.criteria=serializeArrayToObjectByKey($("#filterStaffForm").serializeArray(),"name"),a},type:"post"}}),$(document).on("click",".detail-btn",function(){loader.show();var t=$(this).parentsUntil("tbody","tr").attr("id");ajaxCall({url:Routing.generate("staff_detail",{staff:t}),type:"json"},function(t){showDefaultModal(Translator.trans("staff.list.details"),t.data,t.footer),a(),loader.hide()})}),$("#reset-filter").on("click",function(){resetFilter($(".filter-zone")),staff.ajax.reload()}),$("#export-btn").on("click",function(){submitExportDocumentFile(".filter-zone",staff,Routing.generate("staff_json_list",{download:1}))}),$("#export-xls").on("click",function(){submitExportDocumentFile(".filter-zone",staff,Routing.generate("staff_json_list",{download:2}))}),$(document).on("click","#attribute-role",function(){loader.block();var a=$(this).parent().attr("data-user");ajaxCall({url:Routing.generate("attribute_role",{staff:a}),type:"json"},function(a){showDefaultModal(Translator.trans("staff.list.attribute_role"),a.formBody,a.footer)}),loader.unblock()}),$(document).on("click","#default-password",function(){loader.show();var a=$(this).parent().attr("data-user");ajaxCall({url:Routing.generate("default_password",{staff:a}),type:"json"},function(a){showDefaultModal(Translator.trans("staff.list.default_password_title"),a.formBody,a.footer),loader.hide()})}),$(document).on("click","#change-email",function(){loader.show();var a=$(this).parent().attr("data-user");ajaxCall({url:Routing.generate("change_email",{staff:a}),type:"json"},function(a){if(void 0!==a.errors){var t='<div class="alert alert-danger"><span class="glyphicon glyphicon-remove"></span> '+Translator.trans("staff.list.email.right_error")+"</div>";showDefaultModal(Translator.trans("staff.list.change_email"),t,a.footer)}else showDefaultModal(Translator.trans("staff.list.change_email"),a.formBody,a.footer);loader.hide()})}),$(document).on("click","#btn-validate-email",function(){var t=$(this).parent().attr("data-user"),e=$("#changeEmailForm");loader.show(),ajaxCall({url:Routing.generate("change_email",{staff:t}),method:POST,data:e.serialize()},function(r){void 0===r.errors&&void 0===r.formError?ajaxCall({url:Routing.generate("staff_detail",{staff:t}),type:"json"},function(t){var e='<div class="alert alert-success"><span class="glyphicon glyphicon-ok"></span> '+Translator.trans("staff.list.email.success")+"</div>"+t.data;showDefaultModal(Translator.trans("staff.list.details"),e,t.footer),a(),staff.ajax.reload()}):void 0===r.errors&&e.html(r.formError[0]),loader.hide()})}),$(document).on("click","#btn-validate-role",function(){var t=$(this).parent().attr("data-user"),e=$("#AttributeRoleForm");loader.show(),ajaxCall({url:Routing.generate("attribute_role",{staff:t}),method:POST,data:e.serialize()},function(r){void 0===r.errors&&void 0===r.formError?ajaxCall({url:Routing.generate("staff_detail",{staff:t}),type:"json"},function(t){var e='<div class="alert alert-success"><span class="glyphicon glyphicon-ok"></span> '+Translator.trans("staff.list.roles.success")+"</div>"+t.data;showDefaultModal(Translator.trans("staff.list.details"),e,t.footer),a()}):void 0===r.errors&&e.html(r.formError[0]),loader.hide()})}),$(document).on("click","#btn-validate-password",function(){var t=$(this).parent().attr("data-user"),e=$("#defaultPasswordForm");loader.show(),ajaxCall({url:Routing.generate("default_password",{staff:t}),method:POST,data:e.serialize()},function(r){void 0===r.errors&&void 0===r.formError?ajaxCall({url:Routing.generate("staff_detail",{staff:t}),type:"json"},function(t){var e='<div class="alert alert-success"><span class="glyphicon glyphicon-ok"></span> '+Translator.trans("staff.list.password.success")+"</div>"+t.data;showDefaultModal(Translator.trans("staff.list.details"),e,t.footer),a()}):void 0===r.errors&&e.html(r.formError[0]),loader.hide()})}),$(document).on("click","#btn-cancel",function(){loader.show();var t=$(this).parent().attr("data-user");ajaxCall({url:Routing.generate("staff_detail",{staff:t}),type:"json"},function(t){showDefaultModal(Translator.trans("staff.list.details"),t.data,t.footer),a()}),loader.hide()}),$(document).on("click","#synchronise-btn",function(){loader.block(),ajaxCall({method:"GET",url:Routing.generate("synchronize_users")},function(a){if(void 0===a.errors&&void 0===a.errors){var t=Translator.trans("synchronize.users_added",{addedUsers:a.countNewUsers,deletedUsers:a.countDeletedUsers});showDefaultModal(Translator.trans("staff.list.synchronize.success"),t),staff.ajax.reload()}loader.unblock()},null,function(){})}),$(document).on("click",".btn-delete-role",function(){loader.block();var t=$(this).parents("tr").attr("data-user"),e=$(this).parents("tr").attr("data-role");ajaxCall({url:Routing.generate("delete_staff_role",{staff:t,role:e}),type:"json"},function(e){ajaxCall({url:Routing.generate("staff_detail",{staff:t}),type:"json"},function(t){var e='<div class="alert alert-success"><span class="glyphicon glyphicon-ok"></span> '+Translator.trans("staff.list.roles.deleted")+"</div>"+t.data;showDefaultModal(Translator.trans("staff.list.details"),e,t.footer),a(),staff.ajax.reload()})}),loader.unblock()})});