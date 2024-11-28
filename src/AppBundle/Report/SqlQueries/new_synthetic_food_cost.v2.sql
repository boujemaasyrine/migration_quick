SELECT
  date_part('MONTH', date)   AS "month",
  date_part('WEEK', date)    AS "week",
  *,
  100 * br / ca_net_ht       AS br_pourcentage,
  100 * discount / ca_net_ht AS discount_pourcentage
FROM
  (
    SELECT
      total_tickets.date,
      (COALESCE(total_ttc, 0))                                      AS ca_brut_ttc,
      (COALESCE(total_ht, 0) - COALESCE(discount, 0) - COALESCE(br,
                                                                0)) AS ca_net_ht,
      COALESCE(discount, 0)                                         AS discount,
      COALESCE(br, 0)                                               AS br,
      COALESCE(revenu_price.ventes_pr, 0)                           AS ventes_pr,
      COALESCE(pertes_i_inv, 0)                                     AS pertes_i_inv,
      COALESCE(pertes_i_vtes, 0)                                    AS pertes_i_vtes
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
          AND t.status NOT IN (-1, 5) AND t.counted_canceled <> TRUE
        GROUP BY t.date
        ORDER BY t.date ASC
      ) AS total_tickets LEFT JOIN
      (
        SELECT
          t2.date,
          abs(sum(tl.discount_ttc)) AS "discount"
        FROM ticket t2 JOIN ticket_line tl ON t2.id = tl.ticket_id
        WHERE
          tl.is_discount = TRUE
          AND t2.status NOT IN (-1, 5) AND t2.counted_canceled <> TRUE
          AND t2.date >= :startDate
          AND t2.date <= :endDate
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
          tp.id_payment = :bon_repas_label_type
          AND t2.status NOT IN (-1, 5) AND t2.counted_canceled <> TRUE
          AND t2.date >= :startDate
          AND t2.date <= :endDate
        GROUP BY t2.date
        ORDER BY t2.date ASC
      ) AS br_table ON br_table."date" = total_tickets."date"
      LEFT JOIN
      (
        SELECT
          t2.date,
          abs(sum(tl.revenue_price)) AS "ventes_pr"
        FROM ticket t2 JOIN ticket_line tl ON t2.id = tl.ticket_id
        WHERE
          t2.status NOT IN (-1, 5) AND t2.counted_canceled <> TRUE
          AND t2.date >= :startDate
          AND t2.date <= :endDate
        GROUP BY t2.date
        ORDER BY t2.date ASC
      ) AS revenu_price ON revenu_price.date = total_tickets.date
      LEFT JOIN
      (
        SELECT
          entry :: DATE                  date,
          sum(ll.total_revenue_price) AS pertes_i_inv
        FROM loss_sheet ls JOIN loss_line ll ON ls.id = ll.loss_sheet_id
        WHERE ls.type = :articleLossSheetLabel
        GROUP BY entry :: DATE
        ORDER BY entry :: DATE ASC
      ) AS inv_items_table ON inv_items_table.date = total_tickets.date
      LEFT JOIN
      (
        SELECT
          entry :: DATE                  date,
          sum(ll.total_revenue_price) AS pertes_i_vtes
        FROM loss_sheet ls JOIN loss_line ll ON ls.id = ll.loss_sheet_id
        WHERE ls.type = :finalLossSheetLabel
        GROUP BY entry :: DATE
        ORDER BY entry :: DATE ASC
      ) AS inv_vtes_table ON inv_vtes_table.date = total_tickets.date
  ) AS sub_query_1
ORDER BY date ASC;





