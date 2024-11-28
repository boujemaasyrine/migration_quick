-- THIS SQL QUERY EXTRACT THE FOOD COST SYNTHETIC REPORT
-- Inputs:
--   - startDate
--   - endDate
--   - transfer_in_type ('transfer_in')
--   - transfer_out_type ('transfer_out')
--   - discount_label_type ('discount')
--   - bon_repas_label_type ('Bon Repas')
--   - loss_sheet_article_type ('article')
--   - loss_sheet_final_product_type ('finalProduct')

SELECT
  date_part('MONTH', main_query.date)                                                       AS "month",
  date_part('WEEK',main_query.date)                                                         AS "week",
  *,
  COALESCE(fc_ideal,0) - COALESCE(fc_real,0)                                                                        AS "pertes_totales", -- Pertes Totales
  COALESCE(fc_ideal,0) - COALESCE(fc_real,0)
  - COALESCE(pertes_i_inv,0) - COALESCE(pertes_i_vtes,0)                                    AS "pertes_inconnues", --PERTES INCONNUES
  COALESCE(fc_ideal,0) + COALESCE(fc_perte_inv,0) + COALESCE(fc_perte_vtes,0)               AS "fc_theo", --FC THEO
  COALESCE(inital_stock,0) + COALESCE(entree,0)
  - COALESCE(out,0) - COALESCE(final_stock,0)                                               AS "conso_real", --Consommation réelle

  CASE WHEN (fc_ideal - fc_real) = 0 OR (fc_ideal - fc_real) ISNULL THEN NULL
  ELSE abs(pertes_i_inv/ (fc_ideal - fc_real)) END                                          AS "pertes_i_inv_percent", --Pourcentage des pertes des items d'inventaires

  CASE WHEN (fc_ideal - fc_real) = 0 OR (fc_ideal - fc_real) ISNULL THEN NULL
  ELSE abs(pertes_i_vtes/ (fc_ideal - fc_real)) END                                         AS "pertes_i_vtes_percent", --Pourcentage des pertes des items de ventes

  CASE WHEN (fc_ideal - fc_real) = 0 OR (fc_ideal - fc_real) ISNULL THEN NULL
  ELSE  abs(pertes_connues/ (fc_ideal - fc_real))    END                                    AS "pertes_connues_percent", --Pourcentage des pertes connues

  CASE WHEN (fc_ideal - fc_real) = 0 OR (fc_ideal - fc_real) ISNULL THEN NULL
  ELSE  abs((fc_ideal - fc_real - pertes_i_inv - pertes_i_vtes)/ (fc_ideal - fc_real)) END  AS "pertes_inconnues_percent", --Pourcentage des pertes inconnues

  CASE WHEN ca_net_ht = 0 OR ca_net_ht ISNULL  THEN NULL
  ELSE  abs((fc_ideal - fc_real )/ca_net_ht)   END                                          AS "pertes_totales_percent", --Pourcentage des pertes totales

  CASE WHEN ca_net_ht = 0 OR ca_net_ht ISNULL  THEN NULL
  ELSE  abs(br_amount/ca_net_ht) * 100 END                                                  AS "br_percent", --Pourcentage des BR par rapport ca net ht

  CASE WHEN ca_net_ht = 0 OR ca_net_ht ISNULL  THEN NULL
  ELSE  abs(discount/ca_net_ht) * 100   END                                                 AS "discount_percent", --Pourcentage des discount

  CASE WHEN ca_net_ht = 0 OR ca_net_ht ISNULL  THEN NULL
  ELSE abs(fc_real - ((abs(discount/ca_net_ht) - abs(discount/ca_net_ht)) * 100)) END       AS "fc_real_net", --FC Real NET

  CASE WHEN ca_net_ht = 0 OR ca_net_ht ISNULL  THEN NULL
  ELSE 100 - abs(fc_real - ((abs(discount/ca_net_ht) - abs(discount/ca_net_ht)) * 100)) END AS "marge_brute", -- MARGE BRUTE

  100 - abs(COALESCE(fc_ideal,0) +COALESCE(fc_perte_inv,0) + COALESCE(fc_perte_vtes,0))                                         AS "marge_theo", -- MARGE THEO
  100 - abs(fc_real)                                                                        AS "marge_real" -- MARGE REELLE
FROM
  (
    SELECT
      *,
      CASE WHEN ca_brut_ttc = 0 OR ca_brut_ttc ISNULL  THEN NULL
      ELSE  100 * COALESCE(ventes_pr_ht,0) / ca_brut_ttc  END                                           AS "fc_mix", --FC MIX

      CASE WHEN ca_net_ht = 0 OR ca_net_ht ISNULL  THEN NULL
      ELSE 100 * COALESCE(ventes_pr_ht,0) / ca_net_ht  END                                              AS "fc_ideal", -- FC IDEAL
      COALESCE(delivery,0) + COALESCE(transfer_in,0)                                                                AS "entree",
      COALESCE(transfer_out,0)                                                                          AS "out",

      CASE WHEN ca_net_ht = 0 OR ca_net_ht ISNULL  THEN NULL
      ELSE
      100 * (COALESCE(inital_stock,0) + COALESCE(delivery,0) + COALESCE(transfer_in,0)
             - COALESCE(transfer_out,0) - COALESCE(final_stock,0))/ca_net_ht END  AS "fc_real", -- FC REAL

      CASE WHEN ca_net_ht = 0 OR ca_net_ht ISNULL  THEN NULL
      ELSE COALESCE(pertes_i_inv,0)/ca_net_ht  END                                                      AS "fc_perte_inv", -- FC Pertes Inventaires

      CASE WHEN ca_net_ht = 0 OR ca_net_ht ISNULL  THEN NULL
      ELSE COALESCE(pertes_i_vtes,0)/ca_net_ht END                                                      AS "fc_perte_vtes" -- FC Pertes Ventes
    FROM
      (
        SELECT
          t.date                                                            AS "date",
          COALESCE(COALESCE(sum(t.totalttc),0) + COALESCE(sum(discount),0) - COALESCE(sum(br_amount),0),0)      AS "ca_brut_ttc",
          COALESCE(COALESCE(sum(t.totalht),0) + COALESCE(sum(discount),0) - COALESCE(sum(br_amount),0),0)       AS "ca_net_ht",
          sum(t.totalht) AS total_ht,
          sum(discount) AS total_discount,
          COALESCE(sum(COALESCE(t.totalttc,0)),0)                                       AS "total_ttc",
          COALESCE(sum(COALESCE(discount,0)),0)                                         AS "discount", --SOMME DES DISCOUNT
          COALESCE(sum(COALESCE(br_amount,0)),0)                                        AS "br_amount", --SOMME BON REPAS TOTALS
          COALESCE(MAX(vente_pr_non_transformed),0)                         AS "ventes_pr_non_transformed", --SOMME DES VENTES PR NON TRANSFORME (I.INV)
          COALESCE(MAX(vente_pr_transformed),0)                             AS "ventes_pr_transformed", --SOMME DES VENTES PR TRANSFORME (I.INV)
          COALESCE(MAX(vente_pr_non_transformed + vente_pr_transformed),0)  AS "ventes_pr_ht",
          COALESCE(MAX(pertes_i_inv),0)                                     AS "pertes_i_inv", --PERTES ITEMS INV
          COALESCE(MAX(pertes_i_vtes),0)                                    AS "pertes_i_vtes", --PERTES ITEMS VENTES
          COALESCE(MAX(pertes_i_inv),0)+ COALESCE(MAX(pertes_i_vtes),0)     AS "pertes_connues", --PERTES CONNUES
          COALESCE(MAX(pertes_i_vtes_non_transforme)  ,0)                   AS "pertes_i_vtes_non_transforme",
          COALESCE(MAX(pertes_i_vtes_transforme),0)                         AS "pertes_i_vtes_transforme",
          (
            SELECT
              COALESCE(SUM((il.total_inventory_cnt / pp.inventory_qty) * pp.buying_cost),0)
            FROM
              (
                SELECT *
                FROM
                  inventory_sheet
                WHERE fiscal_date < t.date
                ORDER BY fiscal_date DESC
                LIMIT 1
              ) AS last_inventory
              JOIN inventory_line_view il ON il.inventory_sheet_id = last_inventory.id
              LEFT JOIN product_purchased pp ON il.product_id = pp.id
            GROUP BY last_inventory.fiscal_date
          )                                                                             AS "inital_stock", -- INITIAL STOCK
          COALESCE((
                     SELECT sum(COALESCE(d.valorization,0))
                     FROM
                       delivery d
                     WHERE
                       d.date::date = t.date
                   )  ,0)                                                               AS "delivery",
          COALESCE( (
                      SELECT sum(COALESCE(transfer.valorization,0))
                      FROM
                        transfer
                      WHERE
                        transfer.date_transfer::date = t.date
                        AND  transfer.type = :transfer_in_type
                    )  ,0)                                                              AS "transfer_in",
          COALESCE( (
                      SELECT sum(COALESCE(transfer.valorization,0))
                      FROM
                        transfer
                      WHERE
                        transfer.date_transfer::date = t.date
                        AND  transfer.type = :transfer_out_type
                    )  ,0)                                                                        AS "transfer_out",
          COALESCE((
                     SELECT
                       SUM((COALESCE(il.total_inventory_cnt,0) / pp.inventory_qty) * pp.buying_cost)
                     FROM
                       (
                         SELECT *
                         FROM
                           inventory_sheet
                         WHERE fiscal_date = t.date
                       ) AS inventory
                       JOIN inventory_line_view il ON il.inventory_sheet_id = inventory.id
                       LEFT JOIN product_purchased pp ON il.product_id = pp.id
                     GROUP BY inventory.fiscal_date
                   ),0)                                                                 AS "final_stock"

        FROM ticket t

          --SOMME DES DISCOUNT
          LEFT JOIN
          (
            SELECT
              t2.id     AS "ticket_id2",
              tp.amount AS "discount"
            FROM ticket t2 JOIN ticket_payment tp ON t2.id = tp.ticket_id
            WHERE
              tp.type = :discount_label_type
              AND  t2.status <> -1  AND t2.status <> 5 AND t2.counted_canceled <> TRUE
          ) AS "DISCOUNT_TABLE" ON t.id = ticket_id2

          --SOMME BON REPAS TOTALS
          LEFT JOIN
          (
            SELECT
              t2.id     AS "ticket_id3",
              tp.amount AS "br_amount"
            FROM ticket t2 JOIN ticket_payment tp ON t2.id = tp.ticket_id
            WHERE
              LOWER(tp.label) LIKE :bon_repas_label_type
              AND  t2.status <> -1  AND t2.status <> 5 AND t2.counted_canceled <> TRUE
          ) AS "BR_TABLE" ON t.id = ticket_id3

          --SOMME DES VENTES PR NON TRANSFORME (I.INV)
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
                  AND  t.status <> -1  AND t.status <> 5 AND t.counted_canceled <> TRUE
                GROUP BY t.date, tl.plu
              ) consumed_qty
              LEFT JOIN product_sold ps ON ps.code_plu = consumed_qty.plu
              LEFT JOIN product_purchased pp ON pp.id = ps.product_purchased_id
            WHERE ps.product_purchased_id IS NOT NULL
            GROUP BY consumed_qty.date
          ) AS vente_pr_non_t_table ON vente_pr_non_t_table.date = t.date

          --SOMME DES VENTES PR TRANSFORME (I.INV)
          LEFT JOIN
          (
            SELECT
              t.date "date",
              sum(pp.buying_cost * (tl.qty * rl.qty) / (pp.usage_qty * pp.inventory_qty)) AS vente_pr_transformed
            FROM
              ticket t JOIN ticket_line tl ON t.id = tl.ticket_id
              LEFT JOIN product_sold ps ON ps.code_plu = tl.plu
              LEFT JOIN recipe r ON ps.id = r.product_sold_id
              LEFT JOIN solding_canal sc ON sc.id = r.solding_canal_id
              LEFT JOIN recipe_line rl ON r.id = rl.recipe_id
              LEFT JOIN product_purchased pp ON pp.id = rl.product_purchased_id
            WHERE
              ps.product_purchased_id IS NULL AND
              sc.wynd_mapping_column = t.destinationid :: TEXT
              AND  t.status <> -1  AND t.status <> 5 AND t.counted_canceled <> TRUE
            GROUP BY date
          ) AS ventre_pr_t_table ON vente_pr_non_t_table.date = t.date

          --PERTES ITEMS INV (Valorisation)
          LEFT JOIN
          (
            SELECT
              ls.entry::date "date", -- CAST the datetime to a date
              sum(pp.buying_cost * (ll.total_loss / pp.inventory_qty )) "pertes_i_inv"
            FROM loss_sheet ls
              JOIN loss_line ll ON ls.id = ll.loss_sheet_id
              JOIN product_purchased pp ON pp.id =  ll.product_id
            WHERE
              ls.type = :loss_sheet_article_type
            GROUP BY ls.entry
          ) "perte_i_inv_table" ON perte_i_inv_table.date = t.date

          --PERTES ITEMS VENTES (Valorisation)
          LEFT JOIN
          (
            SELECT
              loss_item_vts_non_transform_table.date date,
              pertes_i_vtes_non_transforme,
              pertes_i_vtes_transforme,
              (pertes_i_vtes_transforme + pertes_i_vtes_non_transforme) pertes_i_vtes
            FROM
              (
                -- VALORISATION DES ITEMS DE VENTES PRODUITS NON TRANSFORME
                SELECT
                 ls.entry::date "date", -- CAST the datetime to a date
                 sum(pp.buying_cost * (ll.total_loss/(pp.usage_qty * pp.inventory_qty ) )) "pertes_i_vtes_non_transforme"
               FROM loss_sheet ls
                 JOIN loss_line ll ON ls.id = ll.loss_sheet_id
                 JOIN product_sold ps ON ps.id =  ll.product_id
                 JOIN product_purchased pp ON pp.id = ps.product_purchased_id
               WHERE
                 ls.type = :loss_sheet_final_product_type AND
                 ll.recipe_id IS NULL
               GROUP BY ls.entry) as "loss_item_vts_non_transform_table"
              JOIN
              (
                -- VALORISATION DES ITEMS DE VENTES PRODUITS TRANSFORME
                SELECT
                 ls.entry::date "date", -- CAST the datetime to a date
                 sum(pp.buying_cost *(ll.total_loss * rl.qty) / (pp.usage_qty * pp.inventory_qty )) "pertes_i_vtes_transforme"
               FROM loss_sheet ls
                 JOIN loss_line ll ON ls.id = ll.loss_sheet_id
                 JOIN product_sold ps ON ps.id =  ll.product_id
                 JOIN recipe r ON ps.id = r.product_sold_id
                 JOIN recipe_line rl ON r.id = rl.recipe_id
                 JOIN product_purchased pp ON pp.id= rl.product_purchased_id
               WHERE
                 ls.type = :loss_sheet_final_product_type AND
                 ll.recipe_id IS NOT NULL
               GROUP BY ls.entry) as "loss_item_vts_transform_table"
               ON loss_item_vts_non_transform_table.date = loss_item_vts_transform_table.date
          ) "perte_i_vtes_table" ON perte_i_vtes_table.date = t.date

        AND  t.status <> -1  AND t.status <> 5 AND t.counted_canceled <> TRUE
        GROUP BY t.date
      ) as "sub_main_query"
  ) AS "main_query"
WHERE date >= :startDate AND date <= :endDate
ORDER BY date ASC

