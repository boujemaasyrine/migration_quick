$(document).ready(function () {

    var idleTime = 0;
    var idleInterval = setInterval(timerIncrement, 1000);

    //Zero the idle timer on mouse movement.
    $(this).mousemove(function (e)
    {
        idleTime = 0;
    });
    $(this).keypress(function (e)
    {
        idleTime = 0;
    });

    function timerIncrement()
    {
        if(window.location.href.match(/login/g) === null)
        {
             idleTime++;
            if (idleTime >= window.idle_time ) {
            window.location = Routing.generate("logout");
            }
        }
    }
});