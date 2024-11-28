SELECT SUM(GLOBAL.valorization) AS totalOut FROM (
  SELECT COALESCE(SUM(r.valorization), 0) AS valorization
    FROM returns r
    WHERE r.origin_restaurant_id = :origin_restaurant_id AND r.date <= :D2 AND r.date >= :D1
    and r.id in (
    select distinct r.id
    from returns r join return_line rl on r.id = rl.return_id join product_purchased pp on rl.product_id = pp.id join product_categories pc on pp.product_category_id = pc.id join category_group cg on cg.id = pc.category_group_id
    where cg.is_food_cost = true
    )


  UNION


    SELECT COALESCE(SUM((ABS(pm.variation) / pm.inventory_qty) * pm.buying_cost),0) AS valorization FROM product_purchased_mvmt pm
  where pm.origin_restaurant_id = :origin_restaurant_id and pm.type = :transfertOut and pm.source_id in
 (select tl.id from transfer_line tl join transfer t on tl.transfer_id = t.id
 join product_purchased pp on tl.product_id = pp.id join product_categories pc on pp.product_category_id = pc.id join category_group cg on cg.id = pc.category_group_id
 where cg.is_food_cost = true and t.origin_restaurant_id = :origin_restaurant_id and t.date_transfer <= :D2 AND  t.date_transfer >= :D1)
    ) as GLOBAL