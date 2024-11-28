SELECT
  COALESCE((
             SELECT COALESCE(sum(qty * pp.inventory_qty), 0)
             FROM
               delivery d JOIN delivery_line dl ON d.id = dl.delivery_id
               JOIN product_purchased pp ON pp.id = dl.product_id
             WHERE dl.product_id = :productID AND
                   d.date :: DATE <= :endDate :: DATE AND
                   d.date :: DATE >= :startDate :: DATE
                   AND d.origin_restaurant_id = :restaurant_id
           ), 0) AS "delivered_qty",
  COALESCE((
             SELECT COALESCE(sum(qty), 0) +
                    COALESCE(sum(qty_exp * pp.inventory_qty), 0) +
                    COALESCE(sum(qty_use * pp.usage_qty * pp.inventory_qty), 0)
             FROM
               transfer t JOIN transfer_line tl ON t.id = tl.transfer_id
               JOIN product_purchased pp ON pp.id = tl.product_id
             WHERE tl.product_id = :productID AND
                   t.date_transfer :: DATE <= :endDate :: DATE AND
                   t.date_transfer :: DATE >= :startDate :: DATE AND
                   t.type = 'transfer_in'
                   AND t.origin_restaurant_id = :restaurant_id
           ), 0) AS "transfer_in",
  COALESCE((
             SELECT COALESCE(sum(qty), 0) +
                    COALESCE(sum(qty_exp * pp.inventory_qty), 0) +
                    COALESCE(sum(qty_use * pp.usage_qty * pp.inventory_qty), 0)
             FROM
               transfer t JOIN transfer_line tl ON t.id = tl.transfer_id
               JOIN product_purchased pp ON pp.id = tl.product_id
             WHERE tl.product_id = :productID AND
                   t.date_transfer :: DATE <= :endDate :: DATE AND
                   t.date_transfer :: DATE >= :startDate :: DATE AND
                   t.type = 'transfer_out'
                   AND t.origin_restaurant_id = :restaurant_id
           ), 0) AS "transfer_out",
  COALESCE((
             SELECT COALESCE(sum(total_loss), 0)
             FROM loss_sheet ls JOIN loss_line ll ON ls.id = ll.loss_sheet_id
             WHERE
               ll.product_id = :productID AND
               ls.entry :: DATE <= :endDate :: DATE AND
               ls.entry :: DATE >= :startDate  :: DATE
               AND ls.origin_restaurant_id = :restaurant_id
           ), 0) AS "pertes",
  COALESCE((
             SELECT COALESCE(sum(qty), 0) +
                    COALESCE(sum(qty_exp * pp.inventory_qty), 0) +
                    COALESCE(sum(qty_use * pp.usage_qty * pp.inventory_qty), 0)
             FROM
               returns r JOIN return_line rl ON r.id = rl.return_id
               JOIN product_purchased pp ON pp.id = rl.product_id
             WHERE rl.product_id = :productID AND
                   r.date :: DATE <= :endDate :: DATE AND
                   r.date :: DATE >= :startDate  :: DATE
                   AND r.origin_restaurant_id = :restaurant_id
           ), 0) AS "retours",
  COALESCE((
             SELECT sum(consumed_qty.qty / pp.usage_qty)
             FROM (
                    SELECT
                      t.date      AS "date",
                      tl.plu      AS "plu",
                      sum(tl.qty) AS "qty"
                    FROM
                      ticket t
                      JOIN ticket_line tl ON t.id = tl.ticket_id
                                             AND t.status <> -1 AND t.status <> 5
                    WHERE qty > 0
                          AND t.origin_restaurant_id = :restaurant_id
                    GROUP BY t.date, tl.plu
                  ) AS consumed_qty
               LEFT JOIN product_sold ps ON ps.code_plu = consumed_qty.plu
               LEFT JOIN product_purchased pp ON pp.id = ps.product_purchased_id
             WHERE
               ps.product_purchased_id IS NOT NULL AND
               pp.id = :productID AND
               consumed_qty.date :: DATE <= :endDate :: DATE AND
               consumed_qty.date :: DATE >= :startDate  :: DATE
             GROUP BY pp.id
           ), 0) AS consomation_non_transformed,
  COALESCE((
             SELECT sum((tl.qty * rl.qty) / pp.usage_qty)
             FROM
               ticket t JOIN ticket_line tl ON t.id = tl.ticket_id
               LEFT JOIN product_sold ps ON ps.code_plu = tl.plu
               LEFT JOIN recipe r ON ps.id = r.product_sold_id
               LEFT JOIN solding_canal sc ON sc.id = r.solding_canal_id
               LEFT JOIN recipe_line rl ON r.id = rl.recipe_id
               LEFT JOIN product_purchased pp ON pp.id = rl.product_purchased_id
             WHERE
               ps.product_purchased_id IS NULL AND
               pp.id = :productID AND
               t.date :: DATE <= :endDate :: DATE AND
               t.date :: DATE >= :startDate  :: DATE
               AND t.status NOT IN (-1, 5)
               AND t.origin_restaurant_id = :restaurant_id
             GROUP BY pp.id
           ), 0) AS consomation_transformed
From inventory_sheet