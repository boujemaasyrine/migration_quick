-- SELECT
-- 	sum(COALESCE(vente_pr_non_transformed,0)) totalNonTransformed, sum(COALESCE(vente_pr_transformed,0)) totalTransformed
--
-- FROM
--   (
--     SELECT
--       consumed_qty.date                                                          AS "date",
--       sum(consumed_qty.qty * pp.buying_cost / (pp.inventory_qty * pp.usage_qty)) AS vente_pr_non_transformed
--     FROM
--       (
--         SELECT
--           t.date      AS "date",
--           tl.plu      AS "plu",
--           SUM(tl.qty) AS "qty"
--         FROM
--           ticket t JOIN ticket_line tl ON t.id = tl.ticket_id
--                                           AND t.status NOT IN (-1, 5) AND t.counted_canceled <> TRUE
--         GROUP BY t.date, tl.plu
--       ) consumed_qty
--       LEFT JOIN product_sold ps ON ps.code_plu = consumed_qty.plu
--       LEFT JOIN product_purchased pp ON pp.id = ps.product_purchased_id
--     WHERE
--       ps.product_purchased_id IS NOT NULL
--       AND consumed_qty.date <= :D2 AND consumed_qty.date >= :D1
--     GROUP BY consumed_qty.date
--     ORDER BY consumed_qty.date ASC
--   ) non_transformed FULL OUTER JOIN
--   (
-- SELECT
--   t.date                                                                         "date",
--   sum(pp.buying_cost * (tl.qty * rl.qty) / (pp.usage_qty * pp.inventory_qty)) AS vente_pr_transformed
-- FROM
--   ticket t JOIN ticket_line tl ON t.id = tl.ticket_id
--   LEFT JOIN product_sold ps ON ps.code_plu = tl.plu
--   LEFT JOIN recipe r ON ps.id = r.product_sold_id
--   LEFT JOIN solding_canal sc ON sc.id = r.solding_canal_id
--   LEFT JOIN recipe_line rl ON r.id = rl.recipe_id
--   LEFT JOIN product_purchased pp ON pp.id = rl.product_purchased_id
-- WHERE
--   ps.product_purchased_id IS NULL
--   AND t.status NOT IN (-1, 5) AND t.counted_canceled <> TRUE
--   AND t.date >= :D1 AND t.date <= :D2
-- AND ( lower(sc.label) = lower(t.destination) OR lower(sc.label) = lower('allcanals') )
-- GROUP BY DATE
-- ) transformed ON non_transformed.date = transformed.date

SELECT
  SUM(tl.revenue_price) AS totalRevenuePrice
FROM  ticket_line tl
WHERE tl.origin_restaurant_id = :origin_restaurant_id AND tl.date >= :D1 AND tl.date <= :D2 AND tl.status NOT IN (-1, 5) AND tl.counted_canceled <> TRUE
