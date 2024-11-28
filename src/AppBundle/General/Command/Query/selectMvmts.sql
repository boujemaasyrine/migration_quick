 select res.variation ,res.product_id ,res.type,res.inventory_qty from (SELECT


                  COALESCE(SUM(MVMTS.variation),0) as variation, MVMTS.inventory_qty,
                  MVMTS.product_id as product_id,
                  MVMTS.type as type


                FROM (
                SELECT DISTINCT ON (product_id)
                id, product_id, date_time, stock_qty , (buying_cost / inventory_qty) as inventory_buying_cost
                FROM   product_purchased_mvmt
                where product_purchased_mvmt.origin_restaurant_id =:restaurantId and deleted = false and type = 'inventory' and date_time <= :endDate and stock_qty is not null
                ORDER  BY product_id, date_time DESC, id DESC) INITIAL_INVENTORY
                LEFT JOIN (
                    SELECT product_purchased_mvmt.id,product_purchased_mvmt.type,product_purchased_mvmt.product_id, product_purchased_mvmt.date_time , product_purchased_mvmt.variation, product_purchased_mvmt.buying_cost, product_purchased_mvmt.inventory_qty
                    FROM product_purchased_mvmt where product_purchased_mvmt.origin_restaurant_id = :restaurantId and deleted = false and type != 'inventory' and date_time <= :endDate
                ) as MVMTS on INITIAL_INVENTORY.product_id =MVMTS.product_id  and DATE(MVMTS.date_time) > DATE(INITIAL_INVENTORY.date_time)
                GROUP BY MVMTS.product_id,MVMTS.type,MVMTS.inventory_qty
                ) as res