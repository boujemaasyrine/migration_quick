  -- View: transformed_product_recipes

-- DROP VIEW transformed_product_recipes;

CREATE OR REPLACE VIEW transformed_product_recipes AS 
 SELECT product_product_sold_view.id,
    product_product_sold_view.name AS product_sold_name,
    product_product_sold_view.stock_current_qty AS product_sold_qty,
    product_product_sold_view.active,
    product_product_sold_view.product_discr,
    product_product_sold_view.division_id,
    product_product_sold_view.type AS product_sold_type,
    product_product_sold_view.code_plu,
    solding_canal.label,
    solding_canal.type AS solding_canal_type,
    recipe_line.qty,
    product_purchased_view.name,
    product_purchased_view.type,
    product_purchased_view.primary_item_id,
    product_purchased_view.storage_condition,
    product_purchased_view.buying_cost,
    product_purchased_view.status,
    product_purchased_view.dlc,
    product_purchased_view.label_unit_exped,
    product_purchased_view.label_unit_inventory,
    product_purchased_view.label_unit_usage,
    product_purchased_view.inventory_qty,
    product_purchased_view.usage_qty,
    product_purchased_view.id_item_inv,
    product_purchased_view.product_category_id,
    product_purchased_view.external_id,
    product_purchased_view.category_name,
    product_purchased_view.stock_current_qty
   FROM product_product_sold_view,
    recipe,
    recipe_line,
    product_purchased_view,
    solding_canal
  WHERE product_product_sold_view.id = recipe.product_sold_id AND recipe.id = recipe_line.recipe_id AND recipe.solding_canal_id = solding_canal.id AND recipe_line.product_purchased_id = product_purchased_view.id;
