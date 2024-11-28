delete from ticket_line where  origin_restaurant_id=:restaurantId and  date>= :startDate and date <= :endDate;
delete from ticket_payment where ticket_id in (select id from ticket where origin_restaurant_id=:restaurantId and date>= :startDate and date <= :endDate);
delete from ticket_intervention where ticket_id in (select id from ticket where origin_restaurant_id=:restaurantId and date>= :startDate and date <= :endDate) ;
delete from ticket where origin_restaurant_id=:restaurantId and  date>= :startDate and date <= :endDate ;
select count(*) from ticket where origin_restaurant_id=:restaurantId and  date>= :startDate and date <= :endDate