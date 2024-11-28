<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 27/06/2016
 * Time: 10:13
 */

function escape_yaml_char($string)
{
    if (strlen($string) > 0) {
        $specialChars = ['@'];
        if (in_array($string[0], $specialChars)) {
            $string = $string[0] . $string;
        }
    }
    if (!is_null($string)) {
        return trim($string);
    }
    return $string;
}

$dbName = 'prd_quick_bo';
$templateParamFile = __DIR__ . '/app/config/parameters.template.yml';
$paramFile = __DIR__ . '/app/config/parameters.yml';

echo "===> Configuration du Quick <===\n\n";

$again = true;
while ($again) {
    echo "Veuillez saisir le nom du Quick : ";
    $quickName = fgets(STDIN);
    $quickName = str_replace(array("\r", "\n"), '', $quickName);
    if (trim($quickName) != '') {
        $again = false;
    }
}

$again = true;
while ($again) {
    echo "Veuillez saisir le Code du Quick : ";
    $quickCode = fgets(STDIN);
    $quickCode = str_replace(array("\r", "\n"), '', $quickCode);
    if (trim($quickCode) != '') {
        $again = false;
    }
}

$again = true;
while ($again) {
    echo "Veuillez saisir l'adresse du Quick : ";
    $quickAdr = fgets(STDIN);
    $quickAdr = str_replace(array("\r", "\n"), '', $quickAdr);
    if (trim($quickAdr) != '') {
        $again = false;
    }
}

echo "\n\n===> Configuration de le FTP du portail fournisseur <===\n\n";
$again = true;
while ($again) {
    echo "Veuillez saisir l'adresse du serveur FTP du portail fournisseur : ";
    $ftpHost = fgets(STDIN);
    $ftpHost = str_replace(array("\r", "\n"), '', $ftpHost);
    if (trim($ftpHost) != '') {
        $again = false;
    }
}

$again = true;
while ($again) {
    echo "Veuillez saisir le nom d'utilisateur pour le serveur FTP du portail fournisseur : ";
    $ftpUser = fgets(STDIN);
    $ftpUser = str_replace(array("\r", "\n"), '', $ftpUser);
    if (trim($ftpUser) != '') {
        $again = false;
    }
}

$again = true;
while ($again) {
    echo "Veuillez saisir le mot de passe pour le serveur FTP du portail fournisseur : ";
    $ftpPW = fgets(STDIN);
    $ftpPW = str_replace(array("\r", "\n"), '', $ftpPW);
    if (trim($ftpPW) != '') {
        $again = false;
    }
}

echo "Veuillez saisir le port pour le serveur FTP [21] : ";
$ftpPort = fgets(STDIN);
$ftpPort = str_replace(array("\r", "\n"), '', $ftpPort);
if (trim($ftpPort) == '') {
    $ftpPort = '21';
}

echo "\n\n===> Configuration du Wynd <===\n\n";
$again = true;
while ($again) {
    echo "Veuillez saisir l'url de Wynd pour la récupération des tickets : ";
    $wyndUrl = fgets(STDIN);
    $wyndUrl = str_replace(array("\r", "\n"), '', $wyndUrl);
    if (trim($wyndUrl) != '') {
        $again = false;
    }
}

$again = true;
while ($again) {
    echo "Veuillez saisir l'url de Wynd pour la récupération des utilisateurs : ";
    $wyndUrlUsers = fgets(STDIN);
    $wyndUrlUsers = str_replace(array("\r", "\n"), '', $wyndUrlUsers);
    if (trim($wyndUrlUsers) != '') {
        $again = false;
    }
}

$again = true;
while ($again) {
    echo "Veuillez saisir l'url du Rapport Z : ";
    $rapportZUrl = fgets(STDIN);
    $rapportZUrl = str_replace(array("\r", "\n"), '', $rapportZUrl);
    if (trim($rapportZUrl) != '') {
        $again = false;
    }
}

$again = true;
while ($again) {
    echo "Veuillez saisir le nom d'utilisateur de Wynd : ";
    $wyndUser = fgets(STDIN);
    $wyndUser = str_replace(array("\r", "\n"), '', $wyndUser);
    if (trim($wyndUser) != '') {
        $again = false;
    }
}

$again = true;
while ($again) {
    echo "Veuillez saisir le mot de passe de Wynd : ";
    $wyndPw = fgets(STDIN);
    $wyndPw = str_replace(array("\r", "\n"), '', $wyndPw);
    if (trim($wyndPw) != '') {
        $again = false;
    }
}

echo "\n\n===> Configuration de l'interfacage avec le Central <===\n\n";
$again = true;
while ($again) {
    echo "Veuillez saisir l'url de la supervision : ";
    $superUrl = fgets(STDIN);
    $superUrl = str_replace(array("\r", "\n"), '', $superUrl);
    if (trim($superUrl) != '') {
        $again = false;
    }
}

echo "Veuillez saisir l'alias: [] ";
$superAlias = fgets(STDIN);
$superAlias = str_replace(array("\r", "\n"), '', $superAlias);
if (trim($superAlias) == '') {
    $superAlias = '';
}


$again = true;
while ($again) {
    echo "Veuillez saisir le token pour la supervision : ";
    $superToken = fgets(STDIN);
    $superToken = str_replace(array("\r", "\n"), '', $superToken);
    if (trim($superToken) != '') {
        $again = false;
    }
}
echo "*** Transférer via un canal sécurisé ce code à l'équipe technique du central afin d'initialiser le restaurant *** \n";

echo "\n\n===> Configuration de l'accès à la base de données $dbName <===\n\n";

$again = true;
while ($again) {
    echo "Veuillez saisir le nom d'utilisateur de la base de données '$dbName' : ";
    $username = fgets(STDIN);
    $username = str_replace(array("\r", "\n"), '', $username);
    if (trim($username) != '') {
        $again = false;
    }
}

$again = true;
while ($again) {
    echo "Veuillez saisir le mot de passe de l'utilisateur $username pour la base de données 'prd_quick_user' : ";
    $pw = fgets(STDIN);
    $pw = str_replace(array("\r", "\n"), '', $pw);
    if (trim($pw) != '') {
        $again = false;
    }
}

echo "Veuillez saisir l'adresse du serveur de la base de données 'prd_quick_user' [localhost] : ";
$host = fgets(STDIN);
$host = str_replace(array("\r", "\n"), '', $host);
if (trim($host) == '') {
    $host = 'localhost';
}

echo "Veuillez saisir le port du serveur de la base de données 'prd_quick_user' [5432] : ";
$port = fgets(STDIN);
$port = str_replace(array("\r", "\n"), '', $port);
if (trim($port) == '') {
    $port = '5432';
}

echo "\n\n===> Configuration de la boite e-mail <===\n\n";


echo "Veuillez saisir l'adresse du serveur mail [smtp.gmail.com] : ";
$emailHost = fgets(STDIN);
$emailHost = str_replace(array("\r", "\n"), '', $emailHost);
if (trim($emailHost) == '') {
    $emailHost = 'smtp.gmail.com';
}


echo "Veuillez saisir le mode du transport pour le serveur mail [gmail] : ";
$emailTransport = fgets(STDIN);
$emailTransport = str_replace(array("\r", "\n"), '', $emailTransport);
if (trim($emailTransport) == '') {
    $emailTransport = 'gmail';
}

$again = true;
while ($again) {
    echo "Veuillez saisir l'adresse email que vous allez utiliser pour l'envoi des emails : ";
    $email = fgets(STDIN);
    $email = str_replace(array("\r", "\n"), '', $email);
    if (trim($email) != '') {
        $again = false;
    }
}

$again = true;
while ($again) {
    echo "Veuillez saisir le mot de passe pour le compte mail: ";
    $emailPw = fgets(STDIN);
    $emailPw = str_replace(array("\r", "\n"), '', $emailPw);
    if (trim($emailPw) != '') {
        $again = false;
    }
}

echo "\n\n===> Autres parametres <===\n\n";
echo "Veuillez saisir le chemin pour l'exécutable de WKHtmlToPDF [/usr/bin/wkhtmltopdf] : ";
$wkhtml = fgets(STDIN);
$wkhtml = str_replace(array("\r", "\n"), '', $wkhtml);
if (trim($wkhtml) == '') {
    $wkhtml = '/usr/bin/wkhtmltopdf';
}

echo "Veuillez saisir le protocol utilisé http/https [http] : ";
$http = fgets(STDIN);
$http = str_replace(array("\r", "\n"), '', $http);
if (trim($http) == '') {
    $http = 'http';
}

echo "Veuillez saisir l'url publique de l'application (si c'est accessible publiquement) : ";
$publicUrl = fgets(STDIN);
$publicUrl = str_replace(array("\r", "\n"), '', $publicUrl);
if (trim($publicUrl) == '') {
    $publicUrl = '';
}

$again = true;
while ($again) {
    echo "Veuillez saisir le chemin pour les exports Optikitchen: ";
    $optikitchenURL = fgets(STDIN);
    $optikitchenURL = str_replace(array("\r", "\n"), '', $optikitchenURL);
    if (trim($optikitchenURL) != '') {
        $again = false;
    }
}


$fileContent = file_get_contents($templateParamFile);

$fileContent = str_replace('__DB_HOST__', escape_yaml_char($host), $fileContent);
$fileContent = str_replace('__DB_PORT__', escape_yaml_char($port), $fileContent);
$fileContent = str_replace('__DB_NAME__', escape_yaml_char($dbName), $fileContent);
$fileContent = str_replace('__DB_USER__', escape_yaml_char($username), $fileContent);
$fileContent = str_replace('__DB_PW__', escape_yaml_char($pw), $fileContent);

$fileContent = str_replace('__MAIL_TRANSPORT__', escape_yaml_char($emailTransport), $fileContent);
$fileContent = str_replace('__MAIL_HOST__', escape_yaml_char($emailHost), $fileContent);
$fileContent = str_replace('__MAIL_USER__', escape_yaml_char($email), $fileContent);
$fileContent = str_replace('__MAIL_PW__', escape_yaml_char($emailPw), $fileContent);

$fileContent = str_replace('__QUICK_NAME__', escape_yaml_char($quickName), $fileContent);
$fileContent = str_replace('__QUICK_CODE__', escape_yaml_char($quickCode), $fileContent);
$fileContent = str_replace('__QUICK_ADRESSE__', escape_yaml_char($quickAdr), $fileContent);

$fileContent = str_replace('__FTP_HOST__', escape_yaml_char($ftpHost), $fileContent);
$fileContent = str_replace('__FTP_USER__', escape_yaml_char($ftpUser), $fileContent);
$fileContent = str_replace('__FTP_PW__', escape_yaml_char($ftpPW), $fileContent);
$fileContent = str_replace('__FTP_PORT__', escape_yaml_char($ftpPort), $fileContent);

$fileContent = str_replace('__WYND_URL__', escape_yaml_char($wyndUrl), $fileContent);
$fileContent = str_replace('__WYND_API_REST_URL__', escape_yaml_char($wyndUrlUsers), $fileContent);
$fileContent = str_replace('__WYND_USER__', escape_yaml_char($wyndUser), $fileContent);
$fileContent = str_replace('__RAPPORT_Z_URL__', escape_yaml_char($rapportZUrl), $fileContent);
$fileContent = str_replace('__WYND_SECRET_KEY__', escape_yaml_char($wyndPw), $fileContent);

$fileContent = str_replace('__CENTRAL_URL__', escape_yaml_char($superUrl), $fileContent);
$fileContent = str_replace('__CENTRAL_ALIAS__', escape_yaml_char($superAlias), $fileContent);
$fileContent = str_replace('__CENTRAL_KEY__', escape_yaml_char($superToken), $fileContent);

$fileContent = str_replace('__WKHTMLTOPDF_PATH__', escape_yaml_char($wkhtml) . " ", $fileContent);
$fileContent = str_replace('__HTTP_PROTOCOL__', escape_yaml_char($http), $fileContent);
$fileContent = str_replace('__APP_PUBLIC_BASE_URL__', escape_yaml_char($publicUrl), $fileContent);
$fileContent = str_replace('__EXPORT_OPTI_PATH__', escape_yaml_char($optikitchenURL), $fileContent);


if (file_exists($paramFile)) {
    $oldParams = file_get_contents($paramFile);
    file_put_contents($paramFile . date('YmdHis'), $fileContent);
}

file_put_contents($paramFile, $fileContent);