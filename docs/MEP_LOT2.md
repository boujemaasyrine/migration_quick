# Mep Lot2

1. Libellé bon de recette/depence Ecart Wynd/BO: 
    - quick:parameters:add
    
2. Cash book: 
    - quick_dev:credit:amount:initialize 
    - quick_dev:administrative:closing:update
     
3. Holidays:
    - quick:holidays:init:csv
    
4. Add cron tab for Money Orders
    - money_booking notifications : money booking
      00 08 * * *  php /var/www/html/quick_bo_belux/app/console quick:moneybooking:booking:notifications --env=prod
     
    - money_booking notifications : money reception
     00 21 * * *  php /var/www/html/quick_bo_belux/app/console quick:moneybooking:delivery:notifications --env=prod
     
5. Update financial Revenu
    - quick:financial:revenu:import