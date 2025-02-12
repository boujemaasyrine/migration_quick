--d-- INPUT
--   :targetDate ::Date
--   :product_id


SELECT
  *,
  COALESCE((
             SELECT COALESCE(sum(qty), 0)
             FROM
               delivery d
               JOIN delivery_line dl ON d.id = dl.delivery_id
               JOIN product_purchased pp ON pp.id = dl.product_id
             WHERE dl.product_id = :product_id AND
                   d.date <= :targetDate  AND
                   d.date  > last_inventory_date  AND
                   d.origin_restaurant_id = :restaurantID
           ), 0)
  +
  COALESCE((
             SELECT COALESCE(sum(qty), 0)
             FROM
               delivery d
               JOIN delivery_line dl ON d.id = dl.delivery_id
               JOIN product_purchased pps ON pps.id = dl.product_id
               JOIN product_purchased pp ON pps.primary_item_id = pp.id
             WHERE pp.id = :product_id AND
                   d.date  <= :targetDate  AND
                   d.date  > last_inventory_date  AND
                   d.origin_restaurant_id = :restaurantID
           ), 0) AS "delivered_qty",

  -- Transfer IN

  COALESCE((
             SELECT COALESCE(sum(qty), 0)
             FROM
               transfer t
               JOIN transfer_line tl ON t.id = tl.transfer_id
               JOIN product_purchased pp ON pp.id = tl.product_id
             WHERE tl.product_id = :product_id AND
                   t.date_transfer  <= :targetDate  AND
                   t.date_transfer  > last_inventory_date  AND
                   t.type = 'transfer_in' AND
                   t.origin_restaurant_id = :restaurantID
           ), 0) AS "transfer_in_inv",
  COALESCE((
             SELECT COALESCE(sum(qty_exp), 0)
             FROM
               transfer t
               JOIN transfer_line tl ON t.id = tl.transfer_id
               JOIN product_purchased pp ON pp.id = tl.product_id
             WHERE tl.product_id = :product_id AND
                   t.date_transfer  <= :targetDate  AND
                   t.date_transfer  > last_inventory_date  AND
                   t.type = 'transfer_in' AND
                   t.origin_restaurant_id = :restaurantID
           ), 0) AS "transfer_in_exp",
  COALESCE((
             SELECT COALESCE(sum(qty_use), 0)
             FROM
               transfer t
               JOIN transfer_line tl ON t.id = tl.transfer_id
               JOIN product_purchased pp ON pp.id = tl.product_id
             WHERE tl.product_id = :product_id AND
                   t.date_transfer  <= :targetDate  AND
                   t.date_transfer  > last_inventory_date  AND
                   t.type = 'transfer_in' AND
                   t.origin_restaurant_id = :restaurantID
           ), 0) AS "transfer_in_use",

  -- Transfer OUT

  COALESCE((
             SELECT COALESCE(sum(qty), 0)
             FROM
               transfer t
               JOIN transfer_line tl ON t.id = tl.transfer_id
               JOIN product_purchased pp ON pp.id = tl.product_id
             WHERE tl.product_id = :product_id AND
                   t.date_transfer  <= :targetDate  AND
                   t.date_transfer  > last_inventory_date  AND
                   t.type = 'transfer_out' AND
                   t.origin_restaurant_id = :restaurantID
           ), 0) AS "transfer_out_inv",
  COALESCE((
             SELECT COALESCE(sum(qty_exp), 0)
             FROM
               transfer t
               JOIN transfer_line tl ON t.id = tl.transfer_id
               JOIN product_purchased pp ON pp.id = tl.product_id
             WHERE tl.product_id = :product_id AND
                   t.date_transfer  <= :targetDate  AND
                   t.date_transfer  > last_inventory_date  AND
                   t.type = 'transfer_out' AND
                   t.origin_restaurant_id = :restaurantID
           ), 0) AS "transfer_out_exp",
  COALESCE((
             SELECT COALESCE(sum(qty_use), 0)
             FROM
               transfer t
               JOIN transfer_line tl ON t.id = tl.transfer_id
               JOIN product_purchased pp ON pp.id = tl.product_id
             WHERE tl.product_id = :product_id AND
                   t.date_transfer  <= :targetDate  AND
                   t.date_transfer  > last_inventory_date  AND
                   t.type = 'transfer_out' AND
                   t.origin_restaurant_id = :restaurantID
           ), 0) AS "transfer_out_use",

  -- Pertes

  COALESCE((
             SELECT COALESCE(sum(total_loss), 0)
             FROM loss_sheet ls
               JOIN loss_line ll ON ls.id = ll.loss_sheet_id
             WHERE
               ll.product_id = :product_id AND
               ls.entry  <= :targetDate  AND
               ls.entry  > last_inventory_date AND
               ls.origin_restaurant_id = :restaurantID
           ), 0) AS "pertes",

  --Retours

  COALESCE((
             SELECT COALESCE(sum(qty), 0)
             FROM
               returns r
               JOIN return_line rl ON r.id = rl.return_id
               JOIN product_purchased pp ON pp.id = rl.product_id
             WHERE rl.product_id = :product_id AND
                   r.date  <= :targetDate  AND
                   r.date  > last_inventory_date  AND
                   r.origin_restaurant_id = :restaurantID
           ), 0) AS "retours_inv",

  COALESCE((
             SELECT COALESCE(sum(qty_exp), 0)
             FROM
               returns r
               JOIN return_line rl ON r.id = rl.return_id
               JOIN product_purchased pp ON pp.id = rl.product_id
             WHERE rl.product_id = :product_id AND
                   r.date  <= :targetDate  AND
                   r.date  > last_inventory_date  AND
                   r.origin_restaurant_id = :restaurantID
           ), 0) AS "retours_exp",

  COALESCE((
             SELECT COALESCE(sum(qty_use), 0)
             FROM
               returns r
               JOIN return_line rl ON r.id = rl.return_id
               JOIN product_purchased pp ON pp.id = rl.product_id
             WHERE rl.product_id = :product_id AND
                   r.date  <= :targetDate  AND
                   r.date  > last_inventory_date  AND
                   r.origin_restaurant_id = :restaurantID
           ), 0) AS "retours_use",


  -- consomation_non_transformed

  COALESCE((
             SELECT sum(consumed_qty.qty / pp.usage_qty)
             FROM (
                    SELECT
                      tl.date      AS "date",
                      tl.plu      AS "plu",
                      sum(tl.qty) AS "qty"
                    FROM
                      ticket_line tl                              
                    WHERE qty > 0 AND tl.status <> -1 AND tl.status <> 5 AND tl.counted_canceled <> TRUE AND tl.origin_restaurant_id = :restaurantID
                    GROUP BY tl.date, tl.plu
                  ) AS consumed_qty
               LEFT JOIN product_sold ps ON ps.code_plu = consumed_qty.plu
               LEFT JOIN product_purchased pp ON pp.id = ps.product_purchased_id
             WHERE
               ps.product_purchased_id IS NOT NULL AND
               pp.id = :product_id AND
               consumed_qty.date  <= :targetDate  AND
               consumed_qty.date  > last_inventory_date
             GROUP BY pp.id
           ), 0) AS consomation_non_transformed,

  --  consomation_transformed

  COALESCE((
             SELECT sum((tl.qty * rl.qty))
FROM ticket_line tl 
LEFT JOIN product_sold ps ON ps.code_plu = tl.plu
LEFT JOIN recipe r ON ps.id = r.product_sold_id
LEFT JOIN solding_canal sc ON sc.id = r.solding_canal_id
LEFT JOIN recipe_line rl ON r.id = rl.recipe_id
LEFT JOIN product_purchased pp ON pp.id = rl.product_purchased_id
WHERE
ps.product_purchased_id IS NULL AND
pp.id = :product_id AND
tl.date  <= :targetDate  AND
tl.date  > last_inventory_date
AND tl.status NOT IN (-1, 5) AND tl.counted_canceled <> TRUE AND tl.origin_restaurant_id = :restaurantID
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
    FROM inventory_sheet iss
      JOIN inventory_line_view il ON iss.id = il.inventory_sheet_id
    WHERE
      il.product_id = :product_id AND
      iss.fiscal_date :: DATE = (SELECT iss2.fiscal_date :: DATE last_inventory_date
                                 FROM inventory_sheet iss2
                                   JOIN inventory_line_view il2
                                     ON iss2.id = il2.inventory_sheet_id
                                 WHERE
                                   iss2.fiscal_date  <= :targetDate  AND
                                   il2.product_id = :product_id
                                 ORDER BY (iss2.fiscal_date, iss2.created_at) DESC
                                 LIMIT 1
      ) AND
      iss.origin_restaurant_id = :restaurantID
  ) AS "main_sub_query"
