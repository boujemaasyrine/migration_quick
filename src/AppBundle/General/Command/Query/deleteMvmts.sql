delete from product_purchased_mvmt where type='purchased_loss'  and  origin_restaurant_id=:restaurantId and date_time <= :endDate and date_time >= :startDate;
delete from product_purchased_mvmt where type='sold_loss'  and  origin_restaurant_id=:restaurantId and date_time <= :endDate and date_time >= :startDate;
delete from product_purchased_mvmt where   origin_restaurant_id=:restaurantId and date_time>= :startDate and date_time <= :endDate and type in ('transfer_in','transfer_out');
delete from product_purchased_mvmt where type='sold' and  origin_restaurant_id=:restaurantId and date_time>= :startDate and date_time <= :endDate;
delete from product_purchased_mvmt where  type ='returns' and  origin_restaurant_id=:restaurantId and date_time>= :startDate and date_time <= :endDate;
delete from product_purchased_mvmt where  type='delivery' and origin_restaurant_id=:restaurantId and date_time>= :startDate and date_time <= :endDate

