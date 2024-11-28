
-- View: sheet_model_line_view

-- DROP VIEW sheet_model_line_view;

CREATE OR REPLACE VIEW sheet_model_line_view AS 
 SELECT sheet_model.label,
    sheet_model.type,
    sheet_model.sheet_type,
    product.name,
    sheet_model_line.order_in_sheet
   FROM sheet_model,
    sheet_model_line,
    product
  WHERE sheet_model.id = sheet_model_line.sheet_id AND sheet_model_line.product_id = product.id;
