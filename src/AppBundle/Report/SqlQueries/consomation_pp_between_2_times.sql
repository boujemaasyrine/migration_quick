SELECT
  COALESCE(
      (SELECT sum(consumed_qty.qty)
       FROM (
              SELECT
                tl.enddate   AS "date",
                tl.plu      AS "plu",
                sum(tl.qty) AS "qty"
              FROM
                 ticket_line tl
              WHERE qty > 0 and tl.status NOT IN (-1, 5)  AND tl.origin_restaurant_id = :origin_restaurant_id AND tl.counted_canceled <> TRUE
              GROUP BY tl.enddate, tl.plu
            ) AS consumed_qty
         LEFT JOIN product_sold ps ON ps.code_plu = consumed_qty.plu
         LEFT JOIN product_purchased pp ON pp.id = ps.product_purchased_id
       WHERE
         ps.product_purchased_id IS NOT NULL AND
         pp.id = :product_id AND
         consumed_qty.date <= :t2 AND
         consumed_qty.date >= :t1
       GROUP BY pp.id)
      , 0
  ) AS non_transformed,
  COALESCE(
      (SELECT sum(tl.qty * rl.qty)
       FROM
         ticket_line tl
         LEFT JOIN product_sold ps ON ps.code_plu = tl.plu
         LEFT JOIN recipe r ON ps.id = r.product_sold_id
         LEFT JOIN solding_canal sc ON sc.id = r.solding_canal_id
         LEFT JOIN recipe_line rl ON r.id = rl.recipe_id
         LEFT JOIN product_purchased pp ON pp.id = rl.product_purchased_id
       WHERE
         ps.product_purchased_id IS NULL AND
         pp.id = :product_id AND
         tl.enddate <= :t2 AND
         tl.enddate >= :t1
          AND tl.origin_restaurant_id = :origin_restaurant_id
         AND tl.status NOT IN (-1, 5) AND tl.counted_canceled <> TRUE
       GROUP BY pp.id
      ), 0) AS transformed
