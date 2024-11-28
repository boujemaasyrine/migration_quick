/**
 * Created by mchrif on 10/02/2016.
 */

$(function() {
    $("div.container.main-content").attr("style", '');
    $(function () {

        var loginObj = {
            successLogin: function (response) {
                if (response.success) {
                    loader.show();
                    setTimeout(function () {
                        window.location = Routing.generate("index");
                    }, 1000);
                } else {
                    $("#alertContent").text(response.message);
                    $("#messages").fadeIn();
                }
            },
            errorLogin: function (response) {
                $("#alertContent").text(loginObj.exceptions.tehcnicalError);
                $("#messages").fadeIn();
            }
        };

        $("#login").click(function () {

            event.preventDefault();

            $.ajax({
                type: 'POST',
                url: $("#loginForm").attr('action'),
                data: {
                    _username: $("#username").val(),
                    _password: $("#password").val(),
                    _remember_me: $("#rememberMe").prop("checked")
                },
                success: loginObj.successLogin,
                error: loginObj.errorLogin,
                complete: function () {

                }
            });
        });
    });
});
