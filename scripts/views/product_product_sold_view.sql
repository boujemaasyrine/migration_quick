-- View: product_product_sold_view

-- DROP VIEW product_product_sold_view;

CREATE OR REPLACE VIEW product_product_sold_view AS 
 SELECT product.id,
    product.name,
    product.stock_current_qty,
    product.active,
    product.product_discr,
    product_sold.division_id,
    product_sold.product_purchased_id,
    product_sold.type,
    product_sold.code_plu
   FROM product,
    product_sold
  WHERE product.id = product_sold.id;
