SELECT DISTINCT ON (product_id)
                id
                FROM   product_purchased_mvmt
                where product_purchased_mvmt.origin_restaurant_id =:restaurantId and deleted = false and type = 'inventory' and date_time <= :endDate and stock_qty is not null    ORDER  BY product_id, date_time DESC, id DESC