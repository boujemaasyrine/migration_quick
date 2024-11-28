CREATE OR REPLACE VIEW product_purchased_historic_view AS (
  SELECT
    p.id,
    p.last_date_synchro date,
    p.name,
    p.active,
    p.global_product_id,
    p.origin_restaurant_id,
    pp.primary_item_id,
    pp.secondary_item_id,
    pps.supplier_id,
    pp.product_category_id,
    pp.external_id,
    pp.buying_cost,
    pp.status,
    pp.label_unit_exped,
    pp.label_unit_inventory,
    pp.label_unit_usage,
    pp.inventory_qty,
    pp.usage_qty,
    pp.id_item_inv,
    false as historical_product,
    pp.id as item_id
  FROM product p JOIN product_purchased pp ON p.id = pp.id JOIN product_purchased_supplier pps ON pps.product_purchased_id = pp.id
  UNION ALL (
    SELECT
      pph.original_id id,
      pph.start_date date,
      pph.name,
      pph.active,
      pph.global_product_id,
      pph.primary_item_id,
      pph.origin_restaurant_id,
      pph.secondary_item_id,
      pphs.supplier_id,
      pph.product_category_id,
      pph.external_id,
      pph.buying_cost,
      pph.status,
      pph.label_unit_exped,
      pph.label_unit_inventory,
      pph.label_unit_usage,
      pph.inventory_qty,
      pph.usage_qty,
      pph.id_item_inv,
      true as historical_product,
      pph.id as item_id
    FROM product_purchased_historic pph JOIN product_purchased_historic_supplier pphs ON pphs.product_purchased_historic_id = pph.id
    ORDER BY date DESC)
)
