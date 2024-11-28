SELECT
  EXTRACT(DOW FROM date) as Entryday, SUM(net_ht) as totalHT
  FROM financial_revenue
  WHERE origin_restaurant_id = :origin_restaurant_id and date >= :D1 and date <= :D2
  group by entryDay

