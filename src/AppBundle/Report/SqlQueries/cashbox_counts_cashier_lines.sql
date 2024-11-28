SELECT
  S1.cashier_id,
  S1.cashier_name,
  S1.nbre,
  S1.ca_real,
  S1.ca_theoretical,
  COALESCE(S1.rc_real,0) + COALESCE(S11.foreing_currency,0) AS rc_real,
  S10.d_theoretical,
  COALESCE(S2.rc_theoretical,0) + COALESCE(S11.foreing_currency,0) AS rc_theoretical,
  COALESCE(S23.cb_canceled,0)/S1.nbre as cb_canceled,
  S3.cr_real,
  S4.cr_theoretical,
  S5.bc_real,
  S6.bc_theoretical,
  S7.mt_theoretical,
  S8.cq_real,
  S9.cq_theoretical,
  S1.nbr_cancels,
  S1.total_cancels,
  S1.nbr_corrections,
  S1.total_corrections,
  S1.nbr_abondons,
  S1.total_abondons,
  S12.withdrawals AS withdrawals,
  S13.cre_real
FROM
  (
    SELECT
      QU.id                     AS cashier_id,
      CONCAT(QU.first_name)     AS cashier_name,
      COUNT(C.cashier_id)       AS nbre,
      SUM(C.real_ca_counted)    AS ca_real,
      SUM(C.theorical_ca)       AS ca_theoretical,
      SUM(RC.total_amount)      AS rc_real,
      SUM(C.number_cancels)     AS nbr_cancels,
      SUM(C.total_cancels)      AS total_cancels,
      SUM(C.number_corrections) AS nbr_corrections,
      SUM(C.total_corrections)  AS total_corrections,
      SUM(C.number_abondons)    AS nbr_abondons,
      SUM(C.total_abondons)     AS total_abondons
    FROM
      public.cashbox_count C
      LEFT JOIN public.cashbox_real_cash_container RC ON RC.cashbox_id = C.id
      LEFT JOIN public.quick_user QU ON C.cashier_id = QU.id
    WHERE C.origin_restaurant_id = :origin_restaurant_id AND C.date >= :D1 AND C.date <= :D2
    GROUP BY QU.id
  ) S1
  LEFT JOIN
  (
    SELECT
      C.cashier_id,
      SUM(TP.amount) AS rc_theoretical
    FROM
      public.cashbox_real_cash_container RC
      LEFT JOIN public.cashbox_count C ON RC.cashbox_id = C.id
      LEFT JOIN public.ticket_payment TP ON RC.id = TP.real_cash_container_id
    WHERE C.origin_restaurant_id = :origin_restaurant_id AND C.date >= :D1 AND C.date <= :D2
    GROUP BY C.cashier_id
  ) S2
    ON S1.cashier_id = S2.cashier_id
     LEFT JOIN
     (
    SELECT
      C.cashier_id,
      COALESCE(SUM(TP.amount), 0) AS cb_canceled
    FROM
      public.ticket_payment TP
      LEFT JOIN ticket on TP.ticket_id = ticket.id
       LEFT JOIN  quick_user on quick_user.wynd_id = ticket.operator
      LEFT JOIN  public.cashbox_count C on c.cashier_id = quick_user.id

    WHERE ticket.origin_restaurant_id = :origin_restaurant_id AND ticket.enddate >= :D5 AND ticket.enddate <= :D6
    AND ticket.type = :ticket_type AND ticket.status = :canceled  AND ticket.counted=true AND TP.id_payment in (:cb_ids)
	GROUP BY c.cashier_id
  ) S23
    ON S1.cashier_id = S23.cashier_id
  LEFT JOIN
  (
    SELECT
      C.cashier_id,
      SUM(TR.qty * TR.unit_value) AS cr_real
    FROM
      public.cashbox_check_restaurant_container CR
      LEFT JOIN public.cashbox_count C ON CR.cashbox_id = C.id
      LEFT JOIN public.cashbox_ticket_restaurant TR ON TR.check_restaurant_container_id = CR.id
    WHERE C.origin_restaurant_id = :origin_restaurant_id AND C.date >= :D1 AND C.date <= :D2
    GROUP BY C.cashier_id
  ) S3
    ON S1.cashier_id = S3.cashier_id

  LEFT JOIN
  (
    SELECT
      C.cashier_id,
      SUM(TP.amount) AS cr_theoretical
    FROM
      public.cashbox_check_restaurant_container CR
      LEFT JOIN public.cashbox_count C ON CR.cashbox_id = C.id
      LEFT JOIN public.ticket_payment TP ON TP.check_restaurant_container_id = CR.id
    WHERE C.origin_restaurant_id = :origin_restaurant_id AND C.date >= :D1 AND C.date <= :D2
    GROUP BY C.cashier_id
  ) S4
    ON S1.cashier_id = S4.cashier_id

  LEFT JOIN
  (
  SELECT LEFT_RESULT.bc_real_eft + RIGHT_RESULT.bc_real_non_eft as bc_real, LEFT_RESULT.cashier_id as cashier_id FROM(
    SELECT
      C.cashier_id,
      SUM(COALESCE (BCr.amount, 0)) AS bc_real_eft
    FROM
      public.cashbox_bank_card_container BC
      LEFT JOIN public.cashbox_count C ON BC.cashbox_id = C.id
      LEFT JOIN public.cashbox_bank_card BCr ON BCr.bank_card_container_id = BC.id
    WHERE C.origin_restaurant_id = :origin_restaurant_id AND C.date >= :D1 AND C.date <= :D2
    GROUP BY C.cashier_id ) LEFT_RESULT
    JOIN
    (
      SELECT
      C.cashier_id,
      SUM(TP.amount) AS bc_real_non_eft
    FROM
      public.cashbox_bank_card_container CR
      LEFT JOIN public.cashbox_count C ON CR.cashbox_id = C.id
      LEFT JOIN public.ticket_payment TP ON TP.bank_card_container_id = CR.id
    WHERE C.origin_restaurant_id = :origin_restaurant_id AND C.date >= :D1 AND C.date <= :D2
    GROUP BY C.cashier_id
    ) RIGHT_RESULT on LEFT_RESULT.cashier_id = RIGHT_RESULT.cashier_id
  ) S5
    ON S1.cashier_id = S5.cashier_id

  LEFT JOIN
  (
    SELECT
      C.cashier_id,
      SUM(TP.amount) AS bc_theoretical
    FROM
      public.cashbox_bank_card_container CR
      LEFT JOIN public.cashbox_count C ON CR.cashbox_id = C.id
      LEFT JOIN public.ticket_payment TP ON TP.bank_card_container_id = CR.id
    WHERE C.origin_restaurant_id = :origin_restaurant_id AND C.date >= :D1 AND C.date <= :D2
    GROUP BY C.cashier_id
  ) S6
    ON S1.cashier_id = S6.cashier_id

  LEFT JOIN
  (
    SELECT
      C.cashier_id,
      SUM(TP.amount) AS mt_theoretical
    FROM
      public.cashbox_meal_ticket_container MT
      LEFT JOIN public.cashbox_count C ON MT.cashbox_id = C.id
      LEFT JOIN public.ticket_payment TP ON TP.meal_ticket_container_id = MT.id
    WHERE C.origin_restaurant_id = :origin_restaurant_id AND C.date >= :D1 AND C.date <= :D2
    GROUP BY C.cashier_id
  ) S7
    ON S1.cashier_id = S7.cashier_id

  LEFT JOIN
  (
    SELECT
      C.cashier_id,
      SUM(Qq.qty * Qq.unit_value) AS cq_real
    FROM
      public.cashbox_check_quick_container Q
      LEFT JOIN public.cashbox_count C ON Q.cashbox_id = C.id
      LEFT JOIN public.cashbox_check_quick Qq ON Qq.check_quick_container_id = Q.id
    WHERE C.origin_restaurant_id = :origin_restaurant_id AND C.date >= :D1 AND C.date <= :D2
    GROUP BY C.cashier_id
  ) S8
    ON S1.cashier_id = S8.cashier_id

  LEFT JOIN
  (
    SELECT
      C.cashier_id,
      SUM(TP.amount) AS cq_theoretical
    FROM
      public.cashbox_check_quick_container Q
      LEFT JOIN public.cashbox_count C ON Q.cashbox_id = C.id
      LEFT JOIN public.ticket_payment TP ON TP.check_quick_container_id = Q.id
    WHERE C.origin_restaurant_id = :origin_restaurant_id AND C.date >= :D1 AND C.date <= :D2
    GROUP BY C.cashier_id
  ) S9
    ON S1.cashier_id = S9.cashier_id

  LEFT JOIN
  (
    SELECT
      C.cashier_id,
      ABS(SUM(TP.discount_ttc)) AS d_theoretical
    FROM
      public.cashbox_discount_container D
      LEFT JOIN public.cashbox_count C ON D.cashbox_id = C.id
      LEFT JOIN public.ticket_line TP ON TP.discount_container_id = D.id
    WHERE C.origin_restaurant_id = :origin_restaurant_id AND C.date >= :D1 AND C.date <= :D2 AND TP.origin_restaurant_id = :origin_restaurant_id AND TP.date >= :D1 AND TP.date <= :D2
    GROUP BY C.cashier_id
  ) S10
    ON S1.cashier_id = S10.cashier_id

  LEFT JOIN
  (
    SELECT
      C.cashier_id,
      COALESCE(SUM(COALESCE(FCC.amount, 0) * FCC.exchange_rate), 0) AS foreing_currency
    FROM
      public.cashbox_foreign_currency_container FC
      LEFT JOIN public.cashbox_count C ON FC.cashbox_id = C.id
      LEFT JOIN public.cashbox_foreign_currency FCC ON FCC.foreign_currency_container_id = FC.id
    WHERE C.origin_restaurant_id = :origin_restaurant_id AND C.date >= :D1 AND C.date <= :D2
    GROUP BY C.cashier_id
  ) S11
    ON S1.cashier_id = S11.cashier_id
    LEFT JOIN
   (
    SELECT
       C.cashier_id,
      SUM(COALESCE (W.amount_withdrawal,0)) as withdrawals
      FROM
      public.withdrawal W
      LEFT JOIN public.cashbox_count C ON W.cashbox_count_id = C.id
      WHERE C.date >= :D1 AND C.date <= :D2 and C.origin_restaurant_id = :origin_restaurant_id
      GROUP BY C.cashier_id

  ) S12
   ON S1.cashier_id=S12.cashier_id
   LEFT JOIN
   (
      SELECT
        C.cashier_id,
        SUM(TP.amount) AS cre_real
      FROM
        public.cashbox_check_restaurant_container CR
        LEFT JOIN public.cashbox_count C ON CR.cashbox_id = C.id
        LEFT JOIN public.ticket_payment TP ON TP.check_restaurant_container_id = CR.id
      WHERE C.origin_restaurant_id = :origin_restaurant_id AND C.date >= :D1 AND C.date <= :D2 AND TP.electronic
      GROUP BY C.cashier_id
    ) S13
    ON S1.cashier_id = S13.cashier_id

ORDER BY ca_real ASC
