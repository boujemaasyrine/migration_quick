SELECT SUM(GLOBAL.valorization) as totalIn FROM (
  SELECT COALESCE(SUM(d.valorization), 0) AS valorization
    FROM delivery d
      WHERE DATE(d.date) <= :D2 AND DATE(d.date) >= :D1
        AND d.origin_restaurant_id = :restaurant

  UNION

  SELECT COALESCE(SUM(t.valorization), 0) AS valorization
    FROM transfer t
    WHERE DATE(t.date_transfer) <= :D2 AND
    DATE(t.date_transfer) >= :D1 AND t.type = :transferIn
    AND t.origin_restaurant_id = :restaurant) as GLOBAL