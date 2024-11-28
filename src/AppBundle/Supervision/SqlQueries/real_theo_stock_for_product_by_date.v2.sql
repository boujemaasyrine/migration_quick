-- INPUT
--   :targetDate ::Date
--   :product_id


SELECT
  *,
  COALESCE((
             SELECT COALESCE(sum(qty), 0)
             FROM
               delivery d JOIN delivery_line dl ON d.id = dl.delivery_id
               JOIN product_purchased pp ON pp.id = dl.product_id
             WHERE dl.product_id = product AND
                   d.date :: DATE <= :targetDate :: DATE AND
                   d.date :: DATE > last_inventory_date :: DATE
		               AND d.origin_restaurant_id= :restaurant
           ), 0)
  +
  COALESCE((
             SELECT COALESCE(sum(qty), 0)
             FROM
               delivery d JOIN delivery_line dl ON d.id = dl.delivery_id
                 JOIN product_purchased pps ON pps.id = dl.product_id
                 JOIN product_purchased pp ON pps.primary_item_id = pp.id
             WHERE pp.id = product AND
                   d.date :: DATE <= :targetDate :: DATE AND
                   d.date :: DATE > last_inventory_date :: DATE
			             AND d.origin_restaurant_id= :restaurant
           ), 0) AS "delivered_qty",

  -- Transfer IN

  COALESCE((
             SELECT COALESCE(sum(qty), 0)
             FROM
               transfer t JOIN transfer_line tl ON t.id = tl.transfer_id
               JOIN product_purchased pp ON pp.id = tl.product_id
             WHERE tl.product_id = product AND
                   t.date_transfer :: DATE <= :targetDate :: DATE AND
                   t.date_transfer :: DATE > last_inventory_date :: DATE AND
                   t.type = 'transfer_in'
                   AND t.origin_restaurant_id= :restaurant
           ), 0) AS "transfer_in_inv",
  COALESCE((
             SELECT COALESCE(sum(qty_exp), 0)
             FROM
               transfer t JOIN transfer_line tl ON t.id = tl.transfer_id
               JOIN product_purchased pp ON pp.id = tl.product_id
             WHERE tl.product_id = product AND
                   t.date_transfer :: DATE <= :targetDate :: DATE AND
                   t.date_transfer :: DATE > last_inventory_date :: DATE AND
                   t.type = 'transfer_in'
                   AND t.origin_restaurant_id= :restaurant
           ), 0) AS "transfer_in_exp",
  COALESCE((
             SELECT COALESCE(sum(qty_use), 0)
             FROM
               transfer t JOIN transfer_line tl ON t.id = tl.transfer_id
               JOIN product_purchased pp ON pp.id = tl.product_id
             WHERE tl.product_id = product AND
                   t.date_transfer :: DATE <= :targetDate :: DATE AND
                   t.date_transfer :: DATE > last_inventory_date :: DATE AND
                   t.type = 'transfer_in'
                   AND t.origin_restaurant_id= :restaurant
           ), 0) AS "transfer_in_use",

  -- Transfer OUT

  COALESCE((
             SELECT COALESCE(sum(qty), 0)
             FROM
               transfer t JOIN transfer_line tl ON t.id = tl.transfer_id
               JOIN product_purchased pp ON pp.id = tl.product_id
             WHERE tl.product_id = product AND
                   t.date_transfer :: DATE <= :targetDate :: DATE AND
                   t.date_transfer :: DATE > last_inventory_date :: DATE AND
                   t.type = 'transfer_out'
                    AND t.origin_restaurant_id= :restaurant
           ), 0) AS "transfer_out_inv",
  COALESCE((
             SELECT COALESCE(sum(qty_exp), 0)
             FROM
               transfer t JOIN transfer_line tl ON t.id = tl.transfer_id
               JOIN product_purchased pp ON pp.id = tl.product_id
             WHERE tl.product_id = product AND
                   t.date_transfer :: DATE <= :targetDate :: DATE AND
                   t.date_transfer :: DATE > last_inventory_date :: DATE AND
                   t.type = 'transfer_out'
                   AND t.origin_restaurant_id= :restaurant
           ), 0) AS "transfer_out_exp",
  COALESCE((
             SELECT COALESCE(sum(qty_use), 0)
             FROM
               transfer t JOIN transfer_line tl ON t.id = tl.transfer_id
               JOIN product_purchased pp ON pp.id = tl.product_id
             WHERE tl.product_id = product AND
                   t.date_transfer :: DATE <= :targetDate :: DATE AND
                   t.date_transfer :: DATE > last_inventory_date :: DATE AND
                   t.type = 'transfer_out'
                    AND t.origin_restaurant_id= :restaurant
           ), 0) AS "transfer_out_use",

  -- Pertes

  COALESCE((
             SELECT COALESCE(sum(total_loss), 0)
             FROM loss_sheet ls JOIN loss_line ll ON ls.id = ll.loss_sheet_id
             WHERE
               ll.product_id = product AND
               ls.entry :: DATE <= :targetDate :: DATE AND
               ls.entry :: DATE > last_inventory_date :: DATE
                AND ls.origin_restaurant_id= :restaurant
           ), 0) AS "pertes",

  --Retours

  COALESCE((
             SELECT COALESCE(sum(qty), 0)
             FROM
               returns r JOIN return_line rl ON r.id = rl.return_id
               JOIN product_purchased pp ON pp.id = rl.product_id
             WHERE rl.product_id = product AND
                   r.date :: DATE <= :targetDate :: DATE AND
                   r.date :: DATE > last_inventory_date :: DATE
           ), 0) AS "retours_inv",

  COALESCE((
             SELECT COALESCE(sum(qty_exp), 0)
             FROM
               returns r JOIN return_line rl ON r.id = rl.return_id
               JOIN product_purchased pp ON pp.id = rl.product_id
             WHERE rl.product_id = product AND
                   r.date :: DATE <= :targetDate :: DATE AND
                   r.date :: DATE > last_inventory_date :: DATE
                   AND r.origin_restaurant_id= :restaurant

           ), 0) AS "retours_exp",

  COALESCE((
             SELECT COALESCE(sum(qty_use), 0)
             FROM
               returns r JOIN return_line rl ON r.id = rl.return_id
               JOIN product_purchased pp ON pp.id = rl.product_id
             WHERE rl.product_id = product AND
                   r.date :: DATE <= :targetDate :: DATE AND
                   r.date :: DATE > last_inventory_date :: DATE
                   AND r.origin_restaurant_id= :restaurant

           ), 0) AS "retours_use",


  -- consomation_non_transformed

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

                    WHERE qty > 0 AND t.origin_restaurant_id= :restaurant

                    GROUP BY t.date, tl.plu
                  ) AS consumed_qty
               LEFT JOIN product_sold ps ON ps.code_plu = consumed_qty.plu
               LEFT JOIN product_purchased pp ON pp.id = ps.product_purchased_id
             WHERE
               ps.product_purchased_id IS NOT NULL AND
               pp.id = product AND
               consumed_qty.date :: DATE <= :targetDate :: DATE AND
               consumed_qty.date :: DATE > last_inventory_date :: DATE
             GROUP BY pp.id
           ), 0) AS consomation_non_transformed,

  --  consomation_transformed

  COALESCE((
             SELECT sum((tl.qty * rl.qty))
             FROM
               ticket t JOIN ticket_line tl ON t.id = tl.ticket_id
               LEFT JOIN product_sold ps ON ps.code_plu = tl.plu
               LEFT JOIN recipe r ON ps.id = r.product_sold_id
               LEFT JOIN solding_canal sc ON sc.id = r.solding_canal_id
               LEFT JOIN recipe_line rl ON r.id = rl.recipe_id
               LEFT JOIN product_purchased pp ON pp.id = rl.product_purchased_id
             WHERE
               ps.product_purchased_id IS NULL AND
               pp.id = product AND
               t.date :: DATE <= :targetDate :: DATE AND
               t.date :: DATE > last_inventory_date :: DATE
AND t.origin_restaurant_id= :restaurant

               AND t.status NOT IN (-1, 5)
             GROUP BY pp.id
           ), 0) AS consomation_transformed
FROM
  (
    SELECT
      :targetDate :: DATE AS                          date,
      il.product_id                                   product,
      il.total_inventory_cnt                          last_inventory_total,
      iss.fiscal_date :: DATE                         last_inventory_date,
      (:targetDate :: DATE = iss.fiscal_date :: DATE) real_stock_exist
    FROM inventory_sheet iss JOIN inventory_line_view il ON iss.id = il.inventory_sheet_id
    WHERE
      il.product_id = :product_id AND
      iss.origin_restaurant_id= :restaurant
      AND
      iss.fiscal_date :: DATE = (SELECT iss2.fiscal_date :: DATE last_inventory_date
                                 FROM inventory_sheet iss2 JOIN inventory_line_view il2
                                     ON iss2.id = il2.inventory_sheet_id
                                 WHERE
                                   iss2.fiscal_date :: DATE <= :targetDate :: DATE AND
                                   il2.product_id = il.product_id
                                   AND iss2.origin_restaurant_id= :restaurant

                                 ORDER BY (iss2.fiscal_date, iss2.created_at) DESC
                                 LIMIT 1
      )
  ) AS "main_sub_query"
