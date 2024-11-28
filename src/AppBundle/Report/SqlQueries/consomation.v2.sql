SELECT
  COALESCE((
             SELECT COALESCE(sum(qty), 0)
             FROM
               delivery d JOIN delivery_line dl ON d.id = dl.delivery_id
               JOIN product_purchased pp ON pp.id = dl.product_id
             WHERE dl.product_id = :productID AND
                   d.date  <= :endDate  AND
                   d.date  > :startDate  AND
                   d.origin_restaurant_id = :restaurantID
           ), 0) AS "delivered_qty",

  COALESCE((
             SELECT COALESCE(sum(qty), 0)
             FROM
               transfer t JOIN transfer_line tl ON t.id = tl.transfer_id
               JOIN product_purchased pp ON pp.id = tl.product_id
             WHERE tl.product_id = :productID AND
                   t.date_transfer  <= :endDate  AND
                   t.date_transfer  > :startDate  AND
                   t.type = 'transfer_in' AND
                   t.origin_restaurant_id = :restaurantID
           ), 0) AS "transfer_in_inv",
  COALESCE((
             SELECT COALESCE(sum(qty_exp), 0)
             FROM
               transfer t JOIN transfer_line tl ON t.id = tl.transfer_id
               JOIN product_purchased pp ON pp.id = tl.product_id
             WHERE tl.product_id = :productID AND
                   t.date_transfer  <= :endDate  AND
                   t.date_transfer  > :startDate  AND
                   t.type = 'transfer_in' AND
                   t.origin_restaurant_id = :restaurantID
           ), 0) AS "transfer_in_exp",
  COALESCE((
             SELECT COALESCE(sum(qty_use), 0)
             FROM
               transfer t JOIN transfer_line tl ON t.id = tl.transfer_id
               JOIN product_purchased pp ON pp.id = tl.product_id
             WHERE tl.product_id = :productID AND
                   t.date_transfer  <= :endDate  AND
                   t.date_transfer  > :startDate  AND
                   t.type = 'transfer_in' AND
                   t.origin_restaurant_id = :restaurantID
           ), 0) AS "transfer_in_use",

  COALESCE((
             SELECT COALESCE(sum(qty), 0)
             FROM
               transfer t JOIN transfer_line tl ON t.id = tl.transfer_id
               JOIN product_purchased pp ON pp.id = tl.product_id
             WHERE tl.product_id = :productID AND
                   t.date_transfer  <= :endDate  AND
                   t.date_transfer  >= :startDate  AND
                   t.type = 'transfer_out' AND
                   t.origin_restaurant_id = :restaurantID
           ), 0) AS "transfer_out_inv",
  COALESCE((
             SELECT COALESCE(sum(qty_exp), 0)
             FROM
               transfer t JOIN transfer_line tl ON t.id = tl.transfer_id
               JOIN product_purchased pp ON pp.id = tl.product_id
             WHERE tl.product_id = :productID AND
                   t.date_transfer  <= :endDate  AND
                   t.date_transfer  > :startDate  AND
                   t.type = 'transfer_out' AND
                   t.origin_restaurant_id = :restaurantID
           ), 0) AS "transfer_out_exp",
  COALESCE((
             SELECT COALESCE(sum(qty_use), 0)
             FROM
               transfer t JOIN transfer_line tl ON t.id = tl.transfer_id
               JOIN product_purchased pp ON pp.id = tl.product_id
             WHERE tl.product_id = :productID AND
                   t.date_transfer  <= :endDate  AND
                   t.date_transfer  > :startDate  AND
                   t.type = 'transfer_out' AND
                   t.origin_restaurant_id = :restaurantID
           ), 0) AS "transfer_out_use",

/* loss purchased */
  COALESCE((
             SELECT COALESCE(sum(total_loss), 0)
             FROM loss_sheet ls JOIN loss_line ll ON ls.id = ll.loss_sheet_id
             WHERE
               ll.product_id = :productID AND
               ls.entry  <= :endDate  AND
               ls.entry  > :startDate  AND
               ls.origin_restaurant_id = :restaurantID
           ), 0)
  +
  /* loss sold */
  COALESCE((
             SELECT COALESCE(sum(total_loss * rl.qty / pp.usage_qty), 0)
             FROM loss_sheet ls JOIN loss_line ll ON ls.id = ll.loss_sheet_id
               JOIN product_sold ps ON ps.id = ll.product_id
               JOIN recipe r ON r.product_sold_id = ps.id
               JOIN recipe_line rl ON rl.recipe_id = r.id
               JOIN product_purchased pp ON rl.product_purchased_id = pp.id
             WHERE
               pp.id = :productID AND
               ls.entry  <= :endDate  AND
               ls.entry  > :startDate  AND
               ls.origin_restaurant_id = :restaurantID
           ), 0) AS "pertes",

  COALESCE((
             SELECT COALESCE(sum(qty), 0)
             FROM
               returns r JOIN return_line rl ON r.id = rl.return_id
               JOIN product_purchased pp ON pp.id = rl.product_id
             WHERE rl.product_id = :productID AND
                   r.date  <= :endDate  AND
                   r.date  > :startDate  AND
                   r.origin_restaurant_id = :restaurantID
           ), 0) AS "retours_inv",

  COALESCE((
             SELECT COALESCE(sum(qty_exp), 0)
             FROM
               returns r JOIN return_line rl ON r.id = rl.return_id
               JOIN product_purchased pp ON pp.id = rl.product_id
             WHERE rl.product_id = :productID AND
                   r.date  <= :endDate  AND
                   r.date  > :startDate  AND
                   r.origin_restaurant_id = :restaurantID
           ), 0) AS "retours_exp",

  COALESCE((
             SELECT COALESCE(sum(qty_use), 0)
             FROM
               returns r JOIN return_line rl ON r.id = rl.return_id
               JOIN product_purchased pp ON pp.id = rl.product_id
             WHERE rl.product_id = :productID AND
                   r.date  <= :endDate  AND
                   r.date  > :startDate  AND
                   r.origin_restaurant_id = :restaurantID
           ), 0) AS "retours_use",
COALESCE(( SELECT  ABS(SUM(product_purchased_mvmt.variation))
            FROM product_purchased_mvmt where product_purchased_mvmt.origin_restaurant_id = :restaurantID and
            product_purchased_mvmt.product_id= :productID and
             deleted = false and type in ('sold') and date_time > :startDate and date_time <= :endDate
           ), 0) AS consomation