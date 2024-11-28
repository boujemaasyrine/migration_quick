* Process of install new BO :

- Add these lines to CRONTAB : => 'crontab -e'

0 21 * * * php /var/www/html/prod/quick_bo/app/console order:prepared:notify --env=prod
30 23 * * * php /var/www/html/prod/quick_bo/app/console order:sending:launch --env=prod
*/5 * * * * php /var/www/html/prod/quick_bo/app/console quick:wynd:rest:import --env=prod
* * * * * php /var/www/html/prod/quick_bo/app/console quick:financial:revenue:import  --env=prod
0 1 * * 1 php /var/www/html/prod/quick_bo/app/console report:synthetic:foodcost --env=prod

# Integration Crons
0 5 * * * php /var/www/html/prod/quick_bo/app/console quick:user:wynd:rest:import 1 --env=prod
* * * * * php /var/www/html/prod/quick_bo/app/console quick:download:generic cmd --env=prod
* * * * * php /var/www/html/prod/quick_bo/app/console quick:download:generic ping --env=prod
* * * * * php /var/www/html/prod/quick_bo/app/console quick:sync:execute --env=prod

*/10 * * * * php /var/www/html/prod/quick_bo/app/console quick:upload:generic inventories --env=prod
*/10 * * * * php /var/www/html/prod/quick_bo/app/console quick:upload:generic loss.purchased --env=prod
*/10 * * * * php /var/www/html/prod/quick_bo/app/console quick:upload:generic loss.sold --env=prod
*/10 * * * * php /var/www/html/prod/quick_bo/app/console quick:upload:generic tickets --env=prod
0 23 * * * php /var/www/html/prod/quick_bo/app/console quick:upload:generic cashbox.counts --env=prod
*/10 * * * * php /var/www/html/prod/quick_bo/app/console quick:upload:generic enveloppes --env=prod
*/10 * * * * php /var/www/html/prod/quick_bo/app/console quick:upload:generic withdrawals --env=prod
*/10 * * * * php /var/www/html/prod/quick_bo/app/console quick:upload:generic deposits --env=prod
*/10 * * * * php /var/www/html/prod/quick_bo/app/console quick:upload:generic expenses --env=prod
*/10 * * * * php /var/www/html/prod/quick_bo/app/console quick:upload:generic recipe.tickets --env=prod

*/05 * * * * php /var/www/html/prod/quick_bo/app/console quick:upload:generic employee --env=prod
30 * * * * php /var/www/html/prod/quick_bo/app/console quick:upload:generic orders --env=prod
35 * * * * php /var/www/html/prod/quick_bo/app/console quick:upload:generic deliveries --env=prod
*/10 * * * * php /var/www/html/prod/quick_bo/app/console quick:upload:generic transfers --env=prod
*/10 * * * * php /var/www/html/prod/quick_bo/app/console quick:upload:generic returns --env=prod
*/10 * * * * php /var/www/html/prod/quick_bo/app/console quick:upload:generic financial_revenues --env=prod
*/10 * * * * php /var/www/html/prod/quick_bo/app/console quick:upload:generic admin_closing --env=prod
*/10 * * * * php /var/www/html/prod/quick_bo/app/console quick:upload:generic bud_prev --env=prod
*/10 * * * * php /var/www/html/prod/quick_bo/app/console quick:upload:generic sheet_models --env=prod

#Notifications
0 6 * * * php /var/www/html/prod/quick_bo/app/console order:not:delivered:notify --env=prod
0 6 * * * php /var/www/html/prod/quick_bo/app/console previous:loss:missing --env=prod

# Update products status
0 6 * * * php /var/www/html/prod/quick_bo/app/console quick:fiscal:date:refresh --env=prod

#Integ Supervision
0 6 * * * php /var/www/html/prod/quick_supervision/app/console quick:notify:missing:plu --env=integ > /dev/null 2>&1