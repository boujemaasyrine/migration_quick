-- View: inventory_line_view

-- DROP VIEW inventory_line_view;

CREATE OR REPLACE VIEW inventory_line_view AS
 SELECT inventory_line.id,
    inventory_line.inventory_sheet_id,
    inventory_line.product_id,
    inventory_line.total_inventory_cnt,
    inventory_line.inventory_cnt,
    inventory_line.usage_cnt,
    inventory_line.exped_cnt,
    inventory_line.created_at,
    inventory_line.updated_at
   FROM inventory_line
  WHERE inventory_line.total_inventory_cnt IS NOT NULL;

