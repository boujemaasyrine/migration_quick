SELECT
  S1.nbre,
  S1.ca_real,
  S1.ca_theoretical,
  COALESCE(S1.rc_real,0) + COALESCE(S9.foreing_currency,0) AS rc_real,
  COALESCE(S2.rc_theoretical,0) + COALESCE(S9.foreing_currency,0) AS rc_theoretical,
  COALESCE(S23.cb_canceled,0) as cb_canceled,
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
  cre_real
FROM
  (
    SELECT
      COUNT(C.owner_id)         AS nbre,
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
    WHERE C.date >= :D1 AND C.date <= :D2 AND C.origin_restaurant_id = :origin_restaurant_id

  ) S1
  ,
  (
    SELECT SUM(TP.amount) AS rc_theoretical
    FROM
      public.cashbox_real_cash_container RC
      LEFT JOIN public.cashbox_count C ON RC.cashbox_id = C.id
      LEFT JOIN public.ticket_payment TP ON RC.id = TP.real_cash_container_id
    WHERE C.date >= :D1 AND C.date <= :D2 AND C.origin_restaurant_id = :origin_restaurant_id
  ) S2
  ,
  (
	SELECT  COALESCE(SUM(TP.amount), 0) AS cb_canceled
	FROM public.ticket_payment TP
	LEFT JOIN ticket on TP.ticket_id = ticket.id
         WHERE ticket.enddate >= :D3 AND ticket.enddate <= :D4
         AND ticket.type =:ticket_type AND ticket.status = -1  AND ticket.counted=true AND
          TP.id_payment in (:cb_ids) AND ticket.origin_restaurant_id = :origin_restaurant_id
 )S23,

  (
    SELECT SUM(TR.qty * TR.unit_value) AS cr_real
    FROM
      public.cashbox_check_restaurant_container CR
      LEFT JOIN public.cashbox_count C ON CR.cashbox_id = C.id
      LEFT JOIN public.cashbox_ticket_restaurant TR ON TR.check_restaurant_container_id = CR.id
    WHERE C.date >= :D1 AND C.date <= :D2 AND C.origin_restaurant_id = :origin_restaurant_id
  ) S3
  ,
  (
    SELECT SUM(TP.amount) AS cr_theoretical
    FROM
      public.cashbox_check_restaurant_container CR
      LEFT JOIN public.cashbox_count C ON CR.cashbox_id = C.id
      LEFT JOIN public.ticket_payment TP ON TP.check_restaurant_container_id = CR.id
    WHERE C.date >= :D1 AND C.date <= :D2 AND C.origin_restaurant_id = :origin_restaurant_id
  ) S4
  ,

  (
  SELECT LEFT_RESULT.bc_real_eft + RIGHT_RESULT.bc_real_non_eft as bc_real FROM(
    SELECT SUM(COALESCE (BCr.amount, 0)) AS bc_real_eft
    FROM
      public.cashbox_bank_card_container BC
      LEFT JOIN public.cashbox_count C ON BC.cashbox_id = C.id
      LEFT JOIN public.cashbox_bank_card BCr ON BCr.bank_card_container_id = BC.id
    WHERE C.date >= :D1 AND C.date <= :D2 AND C.origin_restaurant_id = :origin_restaurant_id
  ) LEFT_RESULT
  ,
   (
    SELECT SUM(TP.amount) AS bc_real_non_eft
    FROM
      public.cashbox_bank_card_container CR
      LEFT JOIN public.cashbox_count C ON CR.cashbox_id = C.id
      LEFT JOIN public.ticket_payment TP ON TP.bank_card_container_id = CR.id
    WHERE C.date >= :D1 AND C.date <= :D2 AND C.eft = TRUE AND C.origin_restaurant_id = :origin_restaurant_id
  ) RIGHT_RESULT
  ) S5
  ,
  (
    SELECT SUM(TP.amount) AS bc_theoretical
    FROM
      public.cashbox_bank_card_container CR
      LEFT JOIN public.cashbox_count C ON CR.cashbox_id = C.id
      LEFT JOIN public.ticket_payment TP ON TP.bank_card_container_id = CR.id
    WHERE C.date >= :D1 AND C.date <= :D2 AND C.origin_restaurant_id = :origin_restaurant_id
  ) S6
  ,
  (
    SELECT SUM(TP.amount) AS mt_theoretical
    FROM
      public.cashbox_meal_ticket_container MT
      LEFT JOIN public.cashbox_count C ON MT.cashbox_id = C.id
      LEFT JOIN public.ticket_payment TP ON TP.meal_ticket_container_id = MT.id
    WHERE C.date >= :D1 AND C.date <= :D2 AND C.origin_restaurant_id = :origin_restaurant_id
  ) S7
  ,
  (
    SELECT ABS(SUM(TP.discount_ttc)) AS d_theoretical
    FROM
      public.cashbox_discount_container D
      LEFT JOIN public.cashbox_count C ON D.cashbox_id = C.id
      LEFT JOIN public.ticket_line TP ON TP.discount_container_id = D.id
    WHERE C.date >= :D1 AND C.date <= :D2 AND C.origin_restaurant_id = :origin_restaurant_id AND TP.date >= :D1 AND TP.date <= :D2 AND TP.origin_restaurant_id = :origin_restaurant_id
  ) S8
,
  (
    SELECT
      SUM(COALESCE(FCC.amount, 0) * FCC.exchange_rate) AS foreing_currency
    FROM
      public.cashbox_foreign_currency_container FC
      LEFT JOIN public.cashbox_count C ON FC.cashbox_id = C.id
      LEFT JOIN public.cashbox_foreign_currency FCC ON FCC.foreign_currency_container_id = FC.id
    WHERE C.date >= :D1 AND C.date <= :D2 AND C.origin_restaurant_id = :origin_restaurant_id
    )S9,
    (
    SELECT
      SUM(COALESCE (W.amount_withdrawal,0)) as withdrawals
      FROM
      public.withdrawal W
      LEFT JOIN public.cashbox_count C ON W.cashbox_count_id = C.id
      WHERE C.date >= :D1 AND C.date <= :D2 AND C.origin_restaurant_id = :origin_restaurant_id
  ) S10,
  (
    SELECT SUM(TP.amount) AS cre_real
    FROM
      public.cashbox_check_restaurant_container CR
      LEFT JOIN public.cashbox_count C ON CR.cashbox_id = C.id
      LEFT JOIN public.ticket_payment TP ON TP.check_restaurant_container_id = CR.id
    WHERE C.date >= :D1 AND C.date <= :D2 AND C.origin_restaurant_id = :origin_restaurant_id AND TP.electronic = TRUE
  ) S11
