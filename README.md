Quick
=====

* FRONTEND Installation :

- need to install Node and npm
- npm install -g gulp
- run npm install
- run gulp watch (or gulp) => there is an option on gulpfile.js so the assets willbe rendered without minification => dev purpose.

A Symfony project created on November 6, 2015, 2:37 pm.

Project Technical Requirements:

- need to install  wkhtmltopdf then update parameters with wkhtmltopdf_path


PHPSTORM Configuration:

- Ensure to add the custom twig namespaces in the Symfony plugin (autocomplete twig path)

        "%kernel.root_dir%/../src/AppBundle/Administration/Resources/views": Administration
        "%kernel.root_dir%/../src/AppBundle/Api/Resources/views": Api
        "%kernel.root_dir%/../src/AppBundle/General/Resources/views": General
        "%kernel.root_dir%/../src/AppBundle/Merchandise/Resources/views": Merchandise
        "%kernel.root_dir%/../src/AppBundle/Report/Resources/views": Report
        "%kernel.root_dir%/../src/AppBundle/Security/Resources/views": Security
        "%kernel.root_dir%/../src/AppBundle/Staff/Resources/views": Staff

- Need to launch jsTranslationCommand to dump all translations

	php app/console bazinga:js-translation:dump  --env=prod
	
	
// USEFULL LINUX COMMAND

// Killing process cron job
ps -o pid,sess,cmd afx | egrep "( |/)cron( -f)?$"  => will show all the runnning cron jobs

### Tips
1. Dump data base: 
* Binary: pg_dump -U user -h host -Fc -d db > name.dump
* Sql: pg_dump -U user -h host -d db > name.dump

2. Restore data base: 
* New DB: pg_restore -h host -U user -n public --no-owner --role=role  -d db name.dump
* Existing DB: pg_restore -h host -U user -n public -c -1 --no-owner --role=role  -d db name.dump
Test
