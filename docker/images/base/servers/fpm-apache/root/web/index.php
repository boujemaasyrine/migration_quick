<?php http_response_code(404); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>You are using php:fpm-apache</title>
    <style>*,:after,:before{box-sizing:border-box}body{padding:2em;margin:0;min-height:100vh;scroll-behavior:smooth;text-rendering:optimizeSpeed;line-height:1.5}a{color:inherit;cursor:pointer}</style>
</head>
<body>
    <h1>Hello world!</h1>
    <hr>
    <?php phpinfo(); ?>
</body>
</html>