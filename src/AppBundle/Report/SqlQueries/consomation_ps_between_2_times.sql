SELECT sum(tl.qty) AS "qty"
FROM
   ticket_line tl
WHERE
  tl.status NOT IN (-1, 5) AND tl.counted_canceled <> TRUE AND
  qty > 0 AND
  tl.plu = :plu AND
  tl.enddate <= :t2 AND
  tl.enddate >= :t1 AND
  tl.origin_restaurant_id = :origin_restaurant_id