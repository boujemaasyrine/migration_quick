SELECT	INITIAL_THEORICAL_STOCK.product_id,
    INITIAL_THEORICAL_STOCK.last_inventory_date,
    (INITIAL_THEORICAL_STOCK.initial_stock + INITIAL_THEORICAL_STOCK.variation) theorical_initial_stock FROM (
    SELECT MAX(INITIAL_INVENTORY.product_id) as product_id, MAX(INITIAL_INVENTORY.date_time) as last_inventory_date, MAX(INITIAL_INVENTORY.stock_qty) as initial_stock, COALESCE(SUM(MVMTS.variation),0) as variation  FROM (
    SELECT DISTINCT ON (product_id)
    id, product_id, date_time, stock_qty
    FROM   product_purchased_mvmt
    where type = 'inventory' and date_time < :D1
    ORDER  BY product_id, date_time DESC, id) INITIAL_INVENTORY
    LEFT JOIN (
        SELECT product_purchased_mvmt.product_id, product_purchased_mvmt.date_time , product_purchased_mvmt.variation, product_purchased_mvmt.variation * (product_purchased_mvmt.buying_cost * product_purchased_mvmt.inventory_qty) as variation_value
        FROM product_purchased_mvmt where type != 'inventory' and date_time < :D1
    ) as MVMTS on INITIAL_INVENTORY.product_id = MVMTS.product_id and MVMTS.date_time >= INITIAL_INVENTORY.date_time
    GROUP BY INITIAL_INVENTORY.product_id
) as INITIAL_THEORICAL_STOCK