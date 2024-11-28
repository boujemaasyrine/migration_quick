SELECT
  product_purchased.id                                                                 product_id,
  CASE WHEN COALESCE(INITIAL.theorical_initial_stock, 0) < 0
    THEN 0
  ELSE COALESCE(INITIAL.theorical_initial_stock, 0) END AS                             initial_stock,
  COALESCE(INITIAL_VALUE.buying_cost, product_purchased.buying_cost)                   initial_buying_cost,
  COALESCE(INITIAL_VALUE.usage_qty, product_purchased.usage_qty)                       initial_usage_qty,
  COALESCE(INITIAL_VALUE.label_unit_usage, product_purchased.label_unit_usage)         initial_label_unit,
  COALESCE(INITIAL_VALUE.inventory_qty, product_purchased.inventory_qty)               initial_inventory_qty,
  COALESCE(INITIAL_VALUE.label_unit_inventory, product_purchased.label_unit_inventory) initial_label_unit_inventory,
  COALESCE(INITIAL_VALUE.label_unit_exped, product_purchased.label_unit_exped)         initial_label_unit_exped
FROM product_purchased
  LEFT JOIN
  (
    SELECT
      INITIAL_THEORICAL_STOCK.product_id,
      INITIAL_THEORICAL_STOCK.last_inventory_date,
      (INITIAL_THEORICAL_STOCK.initial_stock + INITIAL_THEORICAL_STOCK.variation) theorical_initial_stock
    FROM
      (
        SELECT
          MAX(INITIAL_INVENTORY.product_id) AS product_id,
          MAX(INITIAL_INVENTORY.date_time)  AS last_inventory_date,
          MAX(INITIAL_INVENTORY.stock_qty)  AS initial_stock,
          COALESCE(SUM(MVMTS.variation), 0) AS variation
        FROM
          (
            SELECT DISTINCT ON (product_id)
              id,
              product_id,
              date_time,
              stock_qty
            FROM product_purchased_mvmt
            WHERE
              deleted = FALSE AND type = 'inventory' AND DATE(date_time) <= ? AND stock_qty IS NOT NULL
            ORDER BY product_id, date_time DESC, id) INITIAL_INVENTORY
          LEFT JOIN
          (
            SELECT
              product_purchased_mvmt.product_id,
              product_purchased_mvmt.date_time,
              product_purchased_mvmt.variation,
              product_purchased_mvmt.variation * (product_purchased_mvmt.buying_cost *
                                                  product_purchased_mvmt.inventory_qty) AS variation_value
            FROM product_purchased_mvmt
            WHERE deleted = FALSE AND type != 'inventory' AND DATE(date_time) <= ?
          ) AS MVMTS ON INITIAL_INVENTORY.product_id = MVMTS.product_id AND
                        DATE(MVMTS.date_time) > DATE(INITIAL_INVENTORY.date_time)
        GROUP BY INITIAL_INVENTORY.product_id
      ) AS INITIAL_THEORICAL_STOCK
  ) INITIAL ON INITIAL.product_id = product_purchased.id
  LEFT JOIN
  (
    SELECT DISTINCT ON (product_id)
      id,
      product_id,
      date_time,
      buying_cost,
      usage_qty,
      label_unit_usage,
      inventory_qty,
      label_unit_inventory,
      label_unit_exped
    FROM product_purchased_mvmt
    WHERE deleted = FALSE AND date_time <= ?
    ORDER BY product_id, date_time DESC, id
  ) INITIAL_VALUE ON product_purchased.id = INITIAL_VALUE.product_id

WHERE product_purchased.id IN (312);