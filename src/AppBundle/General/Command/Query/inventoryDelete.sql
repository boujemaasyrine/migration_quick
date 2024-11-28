delete from inventory_line where inventory_sheet_id in ( select id from  inventory_sheet where   origin_restaurant_id =:restaurantId
and fiscal_date>= :startDate and fiscal_date <= :endDate);
delete from inventory_sheet where origin_restaurant_id =:restaurantId and fiscal_date>= :startDate and fiscal_date <= :endDate ;
select count(*) from inventory_line where inventory_sheet_id in ( select id from  inventory_sheet where   origin_restaurant_id =:restaurantId and fiscal_date>= :startDate and fiscal_date <= :endDate)