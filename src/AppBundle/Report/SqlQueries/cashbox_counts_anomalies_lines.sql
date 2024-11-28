SELECT
  S.cashier_id,
  S.cashier_name,
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
      QU.id                  AS cashier_id,
      CONCAT(QU.first_name)  AS cashier_name,
      COUNT(C.cashier_id)    AS nbre,
      SUM(C.real_ca_counted) AS ca_real
    FROM
      public.cashbox_count C
      LEFT JOIN public.cashbox_real_cash_container RC ON RC.cashbox_id = C.id
      LEFT JOIN public.quick_user QU ON C.cashier_id = QU.id
    WHERE C.origin_restaurant_id = :origin_restaurant_id AND C.date >= :D1 AND C.date <= :D2
    GROUP BY QU.id
  ) S
  LEFT JOIN
  (
    SELECT
      C.cashier_id                                                AS cashier_id,
      COALESCE((SUM(C.real_ca_counted) - SUM(C.theorical_ca)), 0) AS diff_caisse,
      CASE WHEN SUM(C.real_ca_counted) = 0
        THEN 100
      ELSE COALESCE((SUM(C.real_ca_counted) - SUM(C.theorical_ca)), 0) * 100 / SUM(C.real_ca_counted) END
                                                                  AS diff_caisse_percent
    FROM
      public.cashbox_count C
      LEFT JOIN public.cashbox_real_cash_container RC ON RC.cashbox_id = C.id
    WHERE C.origin_restaurant_id = :origin_restaurant_id AND C.date >= :D1 AND C.date <= :D2
    GROUP BY C.cashier_id
    HAVING
      CASE WHEN SUM(C.real_ca_counted) = 0
        THEN 100
      ELSE ABS((COALESCE((SUM(C.real_ca_counted) - SUM(C.theorical_ca)), 0) * 100 / SUM(C.real_ca_counted))) END
      BETWEEN :diffC1 AND :diffC2
  ) S1
    ON S.cashier_id = S1.cashier_id

  LEFT JOIN
  (
    SELECT
      C.cashier_id,
      COALESCE(SUM(C.total_cancels), 0) AS total_cancels,
      CASE WHEN SUM(C.real_ca_counted) = 0
        THEN 100
      ELSE COALESCE(SUM(C.total_cancels), 0) * 100 / SUM(C.real_ca_counted) END
                                        AS total_cancels_percent
    FROM
      public.cashbox_count C
      LEFT JOIN public.cashbox_real_cash_container RC ON RC.cashbox_id = C.id
    WHERE C.origin_restaurant_id = :origin_restaurant_id AND C.date >= :D1 AND C.date <= :D2
    GROUP BY C.cashier_id
    HAVING
      CASE WHEN SUM(C.real_ca_counted) = 0
        THEN 100
      ELSE ABS(COALESCE(SUM(C.total_cancels), 0) * 100 / SUM(C.real_ca_counted)) END
      BETWEEN :annulations1 AND :annulations2
  ) S2
    ON S.cashier_id = S2.cashier_id

  LEFT JOIN
  (
    SELECT
      C.cashier_id,
      COALESCE(SUM(C.total_corrections), 0) AS total_corrections,
      CASE WHEN SUM(C.real_ca_counted) = 0
        THEN 100
      ELSE COALESCE(SUM(C.total_corrections), 0) * 100 / SUM(C.real_ca_counted) END
                                            AS total_corrections_percent
    FROM
      public.cashbox_count C
      LEFT JOIN public.cashbox_real_cash_container RC ON RC.cashbox_id = C.id
    WHERE C.origin_restaurant_id = :origin_restaurant_id AND C.date >= :D1 AND C.date <= :D2
    GROUP BY C.cashier_id
    HAVING CASE WHEN SUM(C.real_ca_counted) = 0
      THEN 100
           ELSE ABS(COALESCE(SUM(C.total_corrections), 0) * 100 / SUM(C.real_ca_counted)) END
    BETWEEN :corrections1 AND :corrections2
  ) S3
    ON S.cashier_id = S3.cashier_id

  LEFT JOIN
  (
    SELECT
      C.cashier_id,
      COALESCE(SUM(C.total_abondons), 0)                                         AS total_abondons,
      CASE WHEN SUM(C.real_ca_counted) = 0
        THEN 100
      ELSE COALESCE(SUM(C.total_abondons), 0) * 100 / SUM(C.real_ca_counted) END AS total_abondons_percent
    FROM
      public.cashbox_count C
      LEFT JOIN public.cashbox_real_cash_container RC ON RC.cashbox_id = C.id
    WHERE C.origin_restaurant_id = :origin_restaurant_id AND C.date >= :D1 AND C.date <= :D2
    GROUP BY C.cashier_id
    HAVING CASE WHEN SUM(C.real_ca_counted) = 0
      THEN 100
           ELSE ABS(COALESCE(SUM(C.total_abondons), 0) * 100 / SUM(C.real_ca_counted)) END
    BETWEEN :abandons1 AND :abandons2
  ) S4
    ON S.cashier_id = S4.cashier_id

  LEFT JOIN
  (
    SELECT *
    FROM
      (
        SELECT
          S01._cashier_id                                                 AS cashier_id,
          COALESCE(S01._rc_real, 0) + COALESCE(S02._foreing_currency, 0) AS rc_real,
          S01._real_ca_counted AS real_ca_counted,
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
    WHERE ABS(S00.rc_real_percent) BETWEEN ABS(:especes1) AND ABS(:especes2)
  ) S5
    ON S.cashier_id = S5.cashier_id

  LEFT JOIN
  (
    SELECT
      C.cashier_id,
      COALESCE(SUM(TR.qty * TR.unit_value), 0) AS cr_real,
      CASE WHEN SUM(C.real_ca_counted) = 0
        THEN 100
      ELSE COALESCE(SUM(TR.qty * TR.unit_value), 0) * 100 / SUM(C.real_ca_counted) END
                                               AS cr_real_percent
    FROM
      PUBLIC.cashbox_check_restaurant_container CR
      LEFT JOIN PUBLIC.cashbox_count C ON CR.cashbox_id = C.id
      LEFT JOIN PUBLIC.cashbox_ticket_restaurant TR ON TR.check_restaurant_container_id = CR.id
    WHERE C.origin_restaurant_id = :origin_restaurant_id AND C.date >= :D1 AND C.date <= :D2
    GROUP BY C.cashier_id

    HAVING CASE WHEN SUM(C.real_ca_counted) = 0
      THEN 100
           ELSE ABS(COALESCE(SUM(TR.qty * TR.unit_value), 0) * 100 / SUM(C.real_ca_counted)) END
    BETWEEN :titreRestaurant1 AND :titreRestaurant2
  ) S6
    ON S.cashier_id = S6.cashier_id

ORDER BY S.ca_real ASC
            