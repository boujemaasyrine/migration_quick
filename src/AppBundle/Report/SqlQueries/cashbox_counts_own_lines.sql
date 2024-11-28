SELECT
  S1.owner_id,
  S1.owner_name,
  S1.nbre,
  S1.ca_real,
  S1.ca_theoretical,
  COALESCE(S1.rc_real,0) + COALESCE(S9.foreing_currency,0) AS rc_real,
  COALESCE(S2.rc_theoretical,0) + COALESCE(S9.foreing_currency,0) AS rc_theoretical,
  COALESCE(S23.cb_canceled,0)/S1.nbre as cb_canceled,
  S3.cr_real,
  S4.cr_theoretical,
  S5.bc_real,
  S6.bc_theoretical,
  S7.mt_theoretical,
  S8.d_theoretical,
  S1.nbr_cancels,
  S1.total_cancels,
  S1.nbr_corrections,
  S1.total_corrections,
  S1.nbr_abondons,
  S1.total_abondons,
  S10.withdrawals as withdrawals,
  S11.cre_real
FROM
  (
    SELECT
      QU.id                                  AS owner_id,
      CONCAT(QU.first_name)                  AS owner_name,
      COUNT(C.owner_id)                      AS nbre,
      COALESCE(SUM(C.real_ca_counted), 0)    AS ca_real,
      COALESCE(SUM(C.theorical_ca), 0)       AS ca_theoretical,
      COALESCE(SUM(RC.total_amount), 0)      AS rc_real,
      COALESCE(SUM(C.number_cancels), 0)     AS nbr_cancels,
      COALESCE(SUM(C.total_cancels), 0)      AS total_cancels,
      COALESCE(SUM(C.number_corrections), 0) AS nbr_corrections,
      COALESCE(SUM(C.total_corrections), 0)  AS total_corrections,
      COALESCE(SUM(C.number_abondons), 0)    AS nbr_abondons,
      COALESCE(SUM(C.total_abondons), 0)     AS total_abondons
    FROM
      public.cashbox_count C
      LEFT JOIN public.cashbox_real_cash_container RC ON RC.cashbox_id = C.id
      LEFT JOIN public.quick_user QU ON C.owner_id = QU.id
    WHERE C.origin_restaurant_id = :origin_restaurant_id and  C.date >= :D1 AND C.date <= :D2
    GROUP BY QU.id
  ) S1
  LEFT JOIN
  (
    SELECT
      C.owner_id,
      COALESCE(SUM(TP.amount), 0) AS rc_theoretical
    FROM
      public.cashbox_real_cash_container RC
      LEFT JOIN public.cashbox_count C ON RC.cashbox_id = C.id
      LEFT JOIN public.ticket_payment TP ON RC.id = TP.real_cash_container_id
    WHERE C.date >= :D1 AND C.date <= :D2
    GROUP BY C.owner_id
  ) S2
    ON S1.owner_id = S2.owner_id
     LEFT JOIN
     (
    SELECT
      C.owner_id,
      COALESCE(SUM(TP.amount), 0) AS cb_canceled
    FROM
      public.ticket_payment TP
      LEFT JOIN ticket on TP.ticket_id = ticket.id
       LEFT JOIN  quick_user on quick_user.wynd_id = CAST(ticket.responsible as INTEGER )
      LEFT JOIN  public.cashbox_count C on c.cashier_id = quick_user.id

    WHERE ticket.origin_restaurant_id = :origin_restaurant_id AND ticket.enddate >= :D3 AND ticket.enddate <= :D4
    AND ticket.type =:ticket_type AND ticket.status = :canceled  AND ticket.counted=true AND TP.id_payment in (:cb_ids)
	GROUP BY c.owner_id
  ) S23
    ON S1.owner_id = S23.owner_id
  LEFT JOIN
  (
    SELECT
      C.owner_id,
      COALESCE(SUM(TR.qty * TR.unit_value), 0) AS cr_real
    FROM
      public.cashbox_check_restaurant_container CR
      LEFT JOIN public.cashbox_count C ON CR.cashbox_id = C.id
      LEFT JOIN public.cashbox_ticket_restaurant TR ON TR.check_restaurant_container_id = CR.id
    WHERE C.origin_restaurant_id = :origin_restaurant_id AND C.date >= :D1 AND C.date <= :D2
    GROUP BY C.owner_id
  ) S3
    ON S1.owner_id = S3.owner_id

  LEFT JOIN
  (
    SELECT
      C.owner_id,
       COALESCE(SUM(TP.amount), 0) AS cr_theoretical
    FROM
      public.cashbox_check_restaurant_container CR
      LEFT JOIN public.cashbox_count C ON CR.cashbox_id = C.id
      LEFT JOIN public.ticket_payment TP ON TP.check_restaurant_container_id = CR.id
    WHERE C.origin_restaurant_id = :origin_restaurant_id AND C.date >= :D1 AND C.date <= :D2
    GROUP BY C.owner_id
  ) S4
    ON S1.owner_id = S4.owner_id

  LEFT JOIN
  (
  SELECT LEFT_RESULT.bc_real_eft + RIGHT_RESULT.bc_real_non_eft as bc_real, LEFT_RESULT.owner_id as owner_id FROM(
    SELECT
      C.owner_id,
      COALESCE(SUM(BCr.amount), 0) AS bc_real_eft
    FROM
      public.cashbox_bank_card_container BC
      LEFT JOIN public.cashbox_count C ON BC.cashbox_id = C.id
      LEFT JOIN public.cashbox_bank_card BCr ON BCr.bank_card_container_id = BC.id
    WHERE C.origin_restaurant_id = :origin_restaurant_id AND C.date >= :D1 AND C.date <= :D2
    GROUP BY C.owner_id
  ) LEFT_RESULT
  JOIN
   (
   SELECT
      C.owner_id,
      COALESCE(SUM(TP.amount), 0) AS bc_real_non_eft
    FROM
      public.cashbox_bank_card_container CR
      LEFT JOIN public.cashbox_count C ON CR.cashbox_id = C.id
      LEFT JOIN public.ticket_payment TP ON TP.bank_card_container_id = CR.id
    WHERE C.origin_restaurant_id = :origin_restaurant_id AND C.date >= :D1 AND C.date <= :D2 AND C.eft = TRUE
    GROUP BY C.owner_id
  ) RIGHT_RESULT on LEFT_RESULT.owner_id = RIGHT_RESULT.owner_id
  ) S5
    ON S1.owner_id = S5.owner_id

  LEFT JOIN
  (
    SELECT
      C.owner_id,
      COALESCE(SUM(TP.amount), 0) AS bc_theoretical
    FROM
      public.cashbox_bank_card_container CR
      LEFT JOIN public.cashbox_count C ON CR.cashbox_id = C.id
      LEFT JOIN public.ticket_payment TP ON TP.bank_card_container_id = CR.id
    WHERE C.origin_restaurant_id = :origin_restaurant_id AND C.date >= :D1 AND C.date <= :D2
    GROUP BY C.owner_id
  ) S6
    ON S1.owner_id = S6.owner_id

  LEFT JOIN
  (
    SELECT
      C.owner_id,
      COALESCE(SUM(TP.amount), 0) AS mt_theoretical
    FROM
      public.cashbox_meal_ticket_container MT
      LEFT JOIN public.cashbox_count C ON MT.cashbox_id = C.id
      LEFT JOIN public.ticket_payment TP ON TP.meal_ticket_container_id = MT.id
    WHERE C.origin_restaurant_id = :origin_restaurant_id AND C.date >= :D1 AND C.date <= :D2
    GROUP BY C.owner_id
  ) S7
    ON S1.owner_id = S7.owner_id

  LEFT JOIN
  (
    SELECT
      C.owner_id,
      COALESCE(ABS(SUM(TP.discount_ttc)), 0) AS d_theoretical
    FROM
      public.cashbox_discount_container D
      LEFT JOIN public.cashbox_count C ON D.cashbox_id = C.id
      LEFT JOIN public.ticket_line TP ON TP.discount_container_id = D.id
    WHERE C.origin_restaurant_id = :origin_restaurant_id AND C.date >= :D1 AND C.date <= :D2 AND TP.origin_restaurant_id = :origin_restaurant_id AND TP.date >= :D1 AND TP.date <= :D2
    GROUP BY C.owner_id
  ) S8
    ON S1.owner_id = S8.owner_id

  LEFT JOIN
  (
    SELECT
      C.owner_id,
      COALESCE(SUM(COALESCE(FCC.amount, 0) * FCC.exchange_rate), 0) AS foreing_currency
    FROM
      public.cashbox_foreign_currency_container FC
      LEFT JOIN public.cashbox_count C ON FC.cashbox_id = C.id
      LEFT JOIN public.cashbox_foreign_currency FCC ON FCC.foreign_currency_container_id = FC.id
    WHERE C.origin_restaurant_id = :origin_restaurant_id AND C.date >= :D1 AND C.date <= :D2
    GROUP BY C.owner_id
  ) S9
    ON S1.owner_id = S9.owner_id
    LEFT JOIN
   (
    SELECT
       C.owner_id,
      SUM(COALESCE (W.amount_withdrawal,0)) as withdrawals
      FROM
      public.withdrawal W
      LEFT JOIN public.cashbox_count C ON W.cashbox_count_id = C.id
      WHERE C.date >= :D1 AND C.date <= :D2
      GROUP BY C.owner_id
  ) S10
   ON S1.owner_id=S10.owner_id
   LEFT JOIN
     (
        SELECT
          C.owner_id,
           COALESCE(SUM(TP.amount), 0) AS cre_real
        FROM
          public.cashbox_check_restaurant_container CR
          LEFT JOIN public.cashbox_count C ON CR.cashbox_id = C.id
          LEFT JOIN public.ticket_payment TP ON TP.check_restaurant_container_id = CR.id
        WHERE C.origin_restaurant_id = :origin_restaurant_id AND C.date >= :D1 AND C.date <= :D2 AND TP.electronic = TRUE
        GROUP BY C.owner_id
  ) S11
    ON S1.owner_id = S11.owner_id
ORDER BY ca_real ASC
            