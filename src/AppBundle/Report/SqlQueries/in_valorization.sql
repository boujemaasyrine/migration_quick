-- SELECT SUM(GLOBAL.valorization) as totalIn FROM (
--   SELECT COALESCE(SUM(dl.valorization), 0) AS valorization
--     FROM delivery_line dl JOIN delivery d on dl.delivery_id = d.id
--       WHERE d.origin_restaurant_id = :origin_restaurant_id AND d.date <= :D2 AND d.date >= :D1
--       and dl.product_id in (select pp.id from product_purchased pp join product_categories pc on pp.product_category_id = pc.id join category_group cg on cg.id = pc.category_group_id where cg.is_food_cost = true
--         and pp.product_category_id in (select id from product_categories where category_group_id in (select id from category_group where is_food_cost =true))
--       )
--
--   UNION
--
-- SELECT COALESCE(SUM((pm.variation / pm.inventory_qty) * pm.buying_cost),0) AS valorization FROM product_purchased_mvmt pm
--   where pm.origin_restaurant_id = :origin_restaurant_id and pm.type= :transferIn and pm.source_id in
--  (select tl.id from transfer_line tl join transfer t on tl.transfer_id = t.id
--  and tl.product_id in (select pp.id from product_purchased pp join product_categories pc on pp.product_category_id = pc.id join category_group cg on cg.id = pc.category_group_id where cg.is_food_cost = true
-- 	 ) and t.origin_restaurant_id = :origin_restaurant_id and t.date_transfer <= :D2 AND  t.date_transfer >=  :D1)
--     ) as GLOBAL

select SUM(INSTOCK.in_variation_value) as totalIn from (
 SELECT product_purchased_mvmt.product_id,
                   SUM(product_purchased_mvmt.variation) qty,
                   SUM(product_purchased_mvmt.variation * (product_purchased_mvmt.buying_cost / product_purchased_mvmt.inventory_qty)) as in_variation_value
            FROM product_purchased_mvmt where product_purchased_mvmt.origin_restaurant_id = :origin_restaurant_id and deleted = false
			and type in ('transfer_in', 'delivery') and date_time >= :D1 and date_time <= :D2
			   and product_id in (select pp.id from product_purchased pp join product_categories pc on pp.product_category_id = pc.id join category_group cg on cg.id = pc.category_group_id where cg.is_food_cost = true  )
			 GROUP BY product_id
			) as INSTOCK