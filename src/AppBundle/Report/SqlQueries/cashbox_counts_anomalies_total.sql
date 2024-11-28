SELECT
  S.nbre,
  S.ca_real,

  S1.diff_caisse,
  S1.diff_caisse_percent,

  S2.total_cancels,
  S2.total_cancels_percent,

  S3.total_corrections,
  S3.total_corrections_percent,

  S4.total_abondons,
  S4.total_abondons_percent,

  S5.rc_real,
  S5.rc_real_percent,

  S6.cr_real,
  S6.cr_real_percent

FROM
  (
    SELECT
      COUNT(C.cashier_id)    AS nbre,
      SUM(C.real_ca_counted) AS ca_real
    FROM
      public.cashbox_count C
      LEFT JOIN public.cashbox_real_cash_container RC ON RC.cashbox_id = C.id
      LEFT JOIN public.quick_user QU ON C.cashier_id = QU.id
    WHERE C.origin_restaurant_id = :origin_restaurant_id AND C.date >= :D1 AND C.date <= :D2
  ) S
  ,
  (
    SELECT
      SUM(C.real_ca_counted) - SUM(C.theorical_ca) AS diff_caisse,
      CASE WHEN SUM(C.real_ca_counted) = 0
        THEN 100
      ELSE (SUM(C.real_ca_counted) - SUM(C.theorical_ca)) * 100 / SUM(C.real_ca_counted) END
                                                   AS diff_caisse_percent
    FROM
      public.cashbox_count C
      LEFT JOIN public.cashbox_real_cash_container RC ON RC.cashbox_id = C.id
    WHERE C.origin_restaurant_id = :origin_restaurant_id AND C.date >= :D1 AND C.date <= :D2
  ) S1
  ,
  (
    SELECT
      SUM(C.total_cancels) AS total_cancels,
      CASE WHEN SUM(C.real_ca_counted) = 0
        THEN 100
      ELSE SUM(C.total_cancels) * 100 / SUM(C.real_ca_counted) END
                           AS total_cancels_percent
    FROM
      public.cashbox_count C
      LEFT JOIN public.cashbox_real_cash_container RC ON RC.cashbox_id = C.id
    WHERE C.origin_restaurant_id = :origin_restaurant_id AND C.date >= :D1 AND C.date <= :D2
  ) S2
  ,
  (
    SELECT
      SUM(C.total_corrections) AS total_corrections,
      CASE WHEN SUM(C.real_ca_counted) = 0
        THEN 100
      ELSE SUM(C.total_corrections) * 100 / SUM(C.real_ca_counted) END
                               AS total_corrections_percent
    FROM
      public.cashbox_count C
      LEFT JOIN public.cashbox_real_cash_container RC ON RC.cashbox_id = C.id
    WHERE C.origin_restaurant_id = :origin_restaurant_id AND C.date >= :D1 AND C.date <= :D2
  ) S3
  ,
  (
    SELECT
      SUM(C.total_abondons) AS total_abondons,
      CASE WHEN SUM(C.real_ca_counted) = 0
        THEN 100
      ELSE SUM(C.total_abondons) * 100 / SUM(C.real_ca_counted) END
                            AS total_abondons_percent
    FROM
      public.cashbox_count C
      LEFT JOIN public.cashbox_real_cash_container RC ON RC.cashbox_id = C.id
    WHERE C.origin_restaurant_id = :origin_restaurant_id AND C.date >= :D1 AND C.date <= :D2
  ) S4
  ,
  (
    SELECT
      COALESCE(S01._rc_real, 0) + COALESCE(S02._foreing_currency, 0) AS rc_real,
      CASE WHEN S01._real_ca_counted = 0
        THEN 100
      ELSE (COALESCE(S01._rc_real, 0) + COALESCE(S02._foreing_currency, 0)) * 100 / S01._real_ca_counted
      END                                                            AS rc_real_percent
    FROM
      (
        SELECT
          SUM(C.real_ca_counted) AS _real_ca_counted,
          SUM(RC.total_amount)   AS _rc_real
        FROM public.cashbox_count C
          LEFT JOIN public.cashbox_real_cash_container RC ON RC.cashbox_id = C.id
        WHERE C.origin_restaurant_id = :origin_restaurant_id AND C.date >= :D1 AND C.date <= :D2
      ) S01
      ,
      (
        SELECT COALESCE(SUM(COALESCE(FCC.amount, 0) * FCC.exchange_rate), 0) AS _foreing_currency
        FROM
          public.cashbox_foreign_currency_container FC
          LEFT JOIN public.cashbox_count C ON FC.cashbox_id = C.id
          LEFT JOIN public.cashbox_foreign_currency FCC ON FCC.foreign_currency_container_id = FC.id
        WHERE C.origin_restaurant_id = :origin_restaurant_id AND C.date >= :D1 AND C.date <= :D2
      ) S02
  ) S5
  ,
  (
    SELECT
      SUM(C.real_ca_counted)      AS cr_real_cr,
      SUM(TR.qty * TR.unit_value) AS cr_real,
      CASE WHEN SUM(C.real_ca_counted) = 0
        THEN 100
      ELSE SUM(TR.qty * TR.unit_value) * 100 / SUM(C.real_ca_counted) END
                                  AS cr_real_percent
    FROM
      public.cashbox_check_restaurant_container CR
      LEFT JOIN public.cashbox_count C ON CR.cashbox_id = C.id
      LEFT JOIN public.cashbox_ticket_restaurant TR ON TR.check_restaurant_container_id = CR.id
    WHERE C.origin_restaurant_id = :origin_restaurant_id AND C.date >= :D1 AND C.date <= :D2
  ) S6

ORDER BY S.ca_real ASC
            