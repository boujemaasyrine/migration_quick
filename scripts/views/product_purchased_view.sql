
  -- View: product_purchased_view

-- DROP VIEW product_purchased_view;

CREATE OR REPLACE VIEW product_purchased_view AS 
 SELECT product.id,
    product.name,
    product_purchased.primary_item_id,
    product_purchased.type,
    product_purchased.storage_condition,
    product_purchased.buying_cost,
    product_purchased.status,
    product_purchased.dlc,
    product_purchased.label_unit_exped,
    product_purchased.label_unit_inventory,
    product_purchased.label_unit_usage,
    product_purchased.inventory_qty,
    product_purchased.usage_qty,
    product_purchased.id_item_inv,
    product_purchased.product_category_id,
    product_purchased.external_id,
    product_categories.name AS category_name,
    product.stock_current_qty
   FROM product,
    product_purchased,
    product_categories
  WHERE product.id = product_purchased.id AND product_purchased.product_category_id = product_categories.id;
