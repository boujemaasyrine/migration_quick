SELECT SUM(GLOBAL.valorization) AS totalOut FROM (
  SELECT COALESCE(SUM(r.valorization), 0) AS valorization
    FROM returns r
    WHERE DATE(r.date) <= :D2 AND DATE(r.date) >= :D1
      AND r.origin_restaurant_id = :restaurant

  UNION

  SELECT COALESCE(SUM(t.valorization), 0) AS valorization
    FROM transfer t
      WHERE DATE(t.date_transfer) <= :D2
      AND DATE(t.date_transfer) >= :D1 and t.type = :transfertOut
      AND t.origin_restaurant_id = :restaurant ) as GLOBAL