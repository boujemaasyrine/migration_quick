SELECT
  S1.max_diff_caisse_percent,
  S2.max_cancels_percent,
  S3.max_corrections_percent,
  S4.max_abondons_percent,
  S5.max_rc_real_percent,
  S6.max_cr_real_percent

FROM (
       SELECT MAX(S01.diff_caisse_percent) AS max_diff_caisse_percent
       FROM
         (
           SELECT CASE WHEN SUM(C.real_ca_counted) = 0
             THEN NULL
                  ELSE ABS((SUM(C.real_ca_counted) - SUM(C.theorical_ca)) * 100 / SUM(C.real_ca_counted)) END
             AS diff_caisse_percent
           FROM
             public.cashbox_count C
             LEFT JOIN public.cashbox_real_cash_container RC ON RC.cashbox_id = C.id
           WHERE C.origin_restaurant_id = :origin_restaurant_id AND C.date >= :D1 AND C.date <= :D2
           GROUP BY C.cashier_id
         ) S01
     ) S1
  ,
  (
    SELECT MAX(S02.total_cancels_percent) AS max_cancels_percent
    FROM
      (
        SELECT CASE WHEN SUM(C.real_ca_counted) = 0
          THEN NULL
               ELSE ABS(SUM(C.total_cancels) * 100 / SUM(C.real_ca_counted)) END
          AS total_cancels_percent
        FROM
          public.cashbox_count C
          LEFT JOIN public.cashbox_real_cash_container RC ON RC.cashbox_id = C.id
        WHERE C.origin_restaurant_id = :origin_restaurant_id AND C.date >= :D1 AND C.date <= :D2
        GROUP BY C.cashier_id
      ) S02
  ) S2
  ,
  (
    SELECT MAX(S03.total_corrections_percent) AS max_corrections_percent
    FROM
      (
        SELECT CASE WHEN SUM(C.real_ca_counted) = 0
          THEN NULL
               ELSE ABS(SUM(C.total_corrections) * 100 / SUM(C.real_ca_counted)) END
          AS total_corrections_percent
        FROM
          public.cashbox_count C
          LEFT JOIN public.cashbox_real_cash_container RC ON RC.cashbox_id = C.id
        WHERE C.origin_restaurant_id = :origin_restaurant_id AND C.date >= :D1 AND C.date <= :D2
        GROUP BY C.cashier_id
      ) S03
  ) S3
  ,
  (
    SELECT MAX(S04.total_abondons_percent) AS max_abondons_percent
    FROM
      (
        SELECT CASE WHEN SUM(C.real_ca_counted) = 0
          THEN NULL
               ELSE ABS(SUM(C.total_abondons) * 100 / SUM(C.real_ca_counted)) END
          AS total_abondons_percent
        FROM
          public.cashbox_count C
          LEFT JOIN public.cashbox_real_cash_container RC ON RC.cashbox_id = C.id
        WHERE C.origin_restaurant_id = :origin_restaurant_id AND C.date >= :D1 AND C.date <= :D2
        GROUP BY C.cashier_id
      ) S04
  ) S4
  ,
  (
    SELECT MAX(S05.rc_real_percent) AS max_rc_real_percent
    FROM
      (
        SELECT *
        FROM
          (
            SELECT
              S01._cashier_id                                                 AS cashier_id,
              COALESCE(S01._rc_real, 0) + COALESCE(S02._foreing_currency, 0) AS rc_real,
              CASE WHEN S01._real_ca_counted = 0
                THEN 100
              ELSE (COALESCE(S01._rc_real, 0) + COALESCE(S02._foreing_currency, 0)) * 100 / S01._real_ca_counted
              END                                                            AS rc_real_percent
            FROM
              (
                SELECT
                  C.cashier_id           AS _cashier_id,
                  SUM(C.real_ca_counted) AS _real_ca_counted,
                  SUM(RC.total_amount)   AS _rc_real
                FROM public.cashbox_count C
                  LEFT JOIN public.cashbox_real_cash_container RC ON RC.cashbox_id = C.id
                WHERE C.origin_restaurant_id = :origin_restaurant_id AND C.date >= :D1 AND C.date <= :D2
                GROUP BY C.cashier_id
              ) S01
              LEFT JOIN
              (
                SELECT
                  C.cashier_id                                                  AS _cashier_id,
                  COALESCE(SUM(COALESCE(FCC.amount, 0) * FCC.exchange_rate), 0) AS _foreing_currency
                FROM
                  public.cashbox_foreign_currency_container FC
                  LEFT JOIN public.cashbox_count C ON FC.cashbox_id = C.id
                  LEFT JOIN public.cashbox_foreign_currency FCC ON FCC.foreign_currency_container_id = FC.id
                WHERE C.origin_restaurant_id = :origin_restaurant_id AND C.date >= :D1 AND C.date <= :D2
                GROUP BY C.cashier_id
              ) S02
                ON S01._cashier_id = S02._cashier_id
          ) S00
      ) S05
  ) S5
  ,
  (
    SELECT MAX(S06.cr_real_percent) AS max_cr_real_percent
    FROM
      (
        SELECT CASE WHEN SUM(C.real_ca_counted) = 0
          THEN NULL
               ELSE ABS(SUM(TR.qty * TR.unit_value) * 100 / SUM(C.real_ca_counted)) END
          AS cr_real_percent
        FROM
          public.cashbox_check_restaurant_container CR
          LEFT JOIN public.cashbox_count C ON CR.cashbox_id = C.id
          LEFT JOIN public.cashbox_ticket_restaurant TR ON TR.check_restaurant_container_id = CR.id
        WHERE C.origin_restaurant_id = :origin_restaurant_id AND C.date >= :D1 AND C.date <= :D2
        GROUP BY C.cashier_id
      ) S06
  ) S6

