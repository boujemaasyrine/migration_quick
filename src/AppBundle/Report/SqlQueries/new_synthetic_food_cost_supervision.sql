SELECT
  date_part('MONTH', date)                                                       AS "month",
  date_part('WEEK',date)                                                             AS "week",
  *,
  100* ventes_pr / ca_brut_ttc as fc_mix,
  100* ventes_pr / ca_net_ht as fc_ideal,
  100 * br / ca_net_ht as br_pourcentage,
  100 * discount / ca_net_ht as discount_pourcentage
FROM
  (
    SELECT
      total_tickets.date,
      (COALESCE(total_ttc, 0))                                                                                        AS ca_brut_ttc,
      (COALESCE(total_ht, 0) - COALESCE(discount, 0) - COALESCE(br,
                                                                0))                                                   AS ca_net_ht,
      COALESCE(discount,
               0)                                                                                                     AS discount,
      COALESCE(br,
               0)                                                                                                     AS br,
      (COALESCE(vente_pr_transformed, 0) + COALESCE(vente_pr_non_transformed,
                                                    0))                                                               AS ventes_pr,
      COALESCE(pertes_i_inv,
               0)                                                                                                     AS pertes_i_inv,
      (COALESCE(pertes_i_vtes_non_transforme, 0) + COALESCE(pertes_i_vtes_transforme,
                                                            0))                                                       AS pertes_i_vtes,
      COALESCE(pertes_i_inv, 0) + (COALESCE(pertes_i_vtes_non_transforme, 0) + COALESCE(pertes_i_vtes_transforme,
                                                                                        0))                           AS pertes_connues
    FROM
      (
        SELECT
          t.date,
          sum(t.totalht)  total_ht,
          sum(t.totalttc) total_ttc
        FROM
          ticket t
        WHERE
          t.date >= :startDate
          AND t.date <= :endDate
          AND t.status NOT IN (-1, 5)
          AND t.origin_restaurant_id = :restaurant_id
        GROUP BY t.date
        ORDER BY t.date ASC
      ) AS total_tickets LEFT JOIN
      (
        SELECT
          t2.date,
          abs(sum(tp.amount)) AS "discount"
        FROM ticket t2 JOIN ticket_payment tp ON t2.id = tp.ticket_id
        WHERE
          lower(tp.type) like lower(:discount)
          AND t2.status NOT IN (-1, 5)
          AND t2.date >= :startDate
          AND t2.date <= :endDate
          AND t2.origin_restaurant_id = :restaurant_id
        GROUP BY t2.date
        ORDER BY t2.date ASC
      ) AS discount_table ON discount_table."date" = total_tickets."date"
      LEFT JOIN
      (
        SELECT
          t2.date,
          abs(sum(tp.amount)) AS "br"
        FROM ticket t2 JOIN ticket_payment tp ON t2.id = tp.ticket_id
        WHERE
          tp.id_payment = :bon_repas_id
          AND t2.status NOT IN (-1, 5)
          AND t2.date >= :startDate
          AND t2.date <= :endDate
          AND t2.origin_restaurant_id = :restaurant_id
        GROUP BY t2.date
        ORDER BY t2.date ASC
      ) AS br_table ON br_table."date" = total_tickets."date"
      LEFT JOIN
      (
        SELECT
          consumed_qty.date                                                          AS "date",
          sum(consumed_qty.qty * pp.buying_cost / (pp.inventory_qty * pp.usage_qty)) AS vente_pr_non_transformed
        FROM
          (
            SELECT
              t.date      AS "date",
              tl.plu      AS "plu",
              SUM(tl.qty) AS "qty"
            FROM
              ticket t JOIN ticket_line tl ON t.id = tl.ticket_id
                                              AND t.status NOT IN (-1, 5)
              where  t.origin_restaurant_id = :restaurant_id
            GROUP BY t.date, tl.plu
          ) consumed_qty
          LEFT JOIN product_sold ps ON ps.code_plu = consumed_qty.plu
          LEFT JOIN product_purchased pp ON pp.id = ps.product_purchased_id
        WHERE
          ps.product_purchased_id IS NOT NULL
          AND consumed_qty.date <= :endDate
          AND consumed_qty.date >= :startDate
        GROUP BY consumed_qty.date
        ORDER BY consumed_qty.date ASC
      ) vente_pr_non_transformed_table ON vente_pr_non_transformed_table."date" = total_tickets."date"
      LEFT JOIN
      (
        SELECT
          t.date                                                                         "date",
          sum(pp.buying_cost * (tl.qty * rl.qty) / (pp.usage_qty * pp.inventory_qty)) AS vente_pr_transformed
        FROM
          ticket t JOIN ticket_line tl ON t.id = tl.ticket_id
          LEFT JOIN product_sold ps ON ps.code_plu = tl.plu
          LEFT JOIN recipe r ON ps.id = r.product_sold_id
          LEFT JOIN solding_canal sc ON sc.id = r.solding_canal_id
          LEFT JOIN recipe_line rl ON r.id = rl.recipe_id
          LEFT JOIN product_purchased pp ON pp.id = rl.product_purchased_id
        WHERE
          ps.product_purchased_id IS NULL
          AND t.status NOT IN (-1, 5)
          AND t.date >= :startDate AND t.date <= :endDate
          AND ( lower(sc.label) = lower(t.destination) OR lower(sc.label) = lower('allcanals') )
          AND t.origin_restaurant_id = :restaurant_id
        GROUP BY date
      ) vente_pr_transformed_table ON vente_pr_transformed_table."date" = total_tickets."date"
      LEFT JOIN
      (
        SELECT
          ls.entry :: DATE                                         "date",
          -- CAST the datetime to a date
          sum(pp.buying_cost * (ll.total_loss / pp.inventory_qty)) "pertes_i_inv"
        FROM loss_sheet ls
          JOIN loss_line ll ON ls.id = ll.loss_sheet_id
          JOIN product_purchased pp ON pp.id = ll.product_id
        WHERE
          ls.type = :loss_sheet_article_type
          AND ls.entry :: DATE <= :endDate
          AND ls.entry :: DATE >= :startDate
          AND ls.origin_restaurant_id = :restaurant_id
        GROUP BY ls.entry
      ) pertes_item_inv ON pertes_item_inv.date = total_tickets.date
      LEFT JOIN
      (
        SELECT
          ls.entry :: DATE                                                                   "date",
          -- CAST the datetime to a date
          sum(pp.buying_cost * (ll.total_loss * rl.qty) / (pp.usage_qty * pp.inventory_qty)) "pertes_i_vtes_transforme"
        FROM loss_sheet ls
          JOIN loss_line ll ON ls.id = ll.loss_sheet_id
          JOIN product_sold ps ON ps.id = ll.product_id
          JOIN recipe r ON ps.id = r.product_sold_id
          JOIN recipe_line rl ON r.id = rl.recipe_id
          JOIN product_purchased pp ON pp.id = rl.product_purchased_id
        WHERE
          ls.type = :loss_sheet_final_product_type AND
          ll.recipe_id IS NOT NULL
          AND ls.entry :: DATE >= :startDate
          AND ls.entry :: DATE <= :endDate
          AND ls.origin_restaurant_id = :restaurant_id
        GROUP BY ls.entry :: DATE
        ORDER BY ls.entry :: DATE
      ) perte_item_vente_p_transf ON perte_item_vente_p_transf.date = total_tickets."date"
      LEFT JOIN
      (
        -- VALORISATION DES ITEMS DE VENTES PRODUITS NON TRANSFORME
        SELECT
          ls.entry :: DATE                                                          "date",
          -- CAST the datetime to a date
          sum(pp.buying_cost * (ll.total_loss / (pp.usage_qty * pp.inventory_qty))) "pertes_i_vtes_non_transforme"
        FROM loss_sheet ls
          JOIN loss_line ll ON ls.id = ll.loss_sheet_id
          JOIN product_sold ps ON ps.id = ll.product_id
          JOIN product_purchased pp ON pp.id = ps.product_purchased_id
        WHERE
          ls.type = :loss_sheet_final_product_type AND
          ll.recipe_id IS NULL
          AND ls.entry :: DATE >= :startDate
          AND ls.entry :: DATE <= :endDate
          AND ls.origin_restaurant_id = :restaurant_id
        GROUP BY ls.entry
        ORDER BY ls.entry :: DATE
      ) perte_item_vente_p_non_transf ON perte_item_vente_p_non_transf.date = total_tickets."date"

  ) as sub_query_1
ORDER BY date ASC