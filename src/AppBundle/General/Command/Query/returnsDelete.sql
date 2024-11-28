delete from return_line where return_id in ( select id from returns where origin_restaurant_id=:restaurantId and date>= :startDate and date <= :endDate);
delete from returns where  origin_restaurant_id=:restaurantId and date>= :startDate and date <= :endDate ;
select count(*) from returns where  origin_restaurant_id=:restaurantId and date>= :startDate and date <= :endDate