--DROP VIEW product_purchased_historic_view;
--truncate table sync_cmd_queue;
INSERT INTO user_restaurant values (2,:restaurant_id);
update product set origin_restaurant_id = :restaurant_id;
update parameter set origin_restaurant_id = :restaurant_id;
update transfer set origin_restaurant_id = :restaurant_id;
update returns set origin_restaurant_id = :restaurant_id;
update sheet_model set origin_restaurant_id = :restaurant_id;
update inventory_sheet set origin_restaurant_id = :restaurant_id;
update loss_sheet set origin_restaurant_id = :restaurant_id;
update administrative_closing set origin_restaurant_id = :restaurant_id;
update admin_closing_tmp set origin_restaurant_id = :restaurant_id;
update cashbox_count set origin_restaurant_id = :restaurant_id;
update chest_count set origin_restaurant_id = :restaurant_id;
update withdrawal set origin_restaurant_id = :restaurant_id;
update expense set origin_restaurant_id = :restaurant_id;
update recipe_ticket set origin_restaurant_id = :restaurant_id;
update envelope set origin_restaurant_id = :restaurant_id;
update ticket set origin_restaurant_id = :restaurant_id;
update ca_prev set origin_restaurant_id = :restaurant_id;
update financial_revenue set origin_restaurant_id = :restaurant_id;
update deposit set origin_restaurant_id = :restaurant_id;
update coef_base set origin_restaurant_id = :restaurant_id;
update coef_base set origin_restaurant_id = :restaurant_id;
update delivery set origin_restaurant_id = :restaurant_id;
update product_purchased_mvmt set origin_restaurant_id = :restaurant_id;
update optikitchen set origin_restaurant_id = :restaurant_id;
update delivery_tmp set origin_restaurant_id = :restaurant_id;
update control_stock_tmp set origin_restaurant_id = :restaurant_id;
update product_sold_historic set origin_restaurant_id = :restaurant_id;
update procedure set origin_restaurant_id = :restaurant_id;
update notification set origin_restaurant_id = :restaurant_id;
update rapport_line_tmp set origin_restaurant_id = :restaurant_id;
update orders set origin_restaurant_id = :restaurant_id;
update supplier_planning set origin_restaurant_id = :restaurant_id;
update order_help_fixed_coef set origin_restaurant_id = :restaurant_id;
insert into restaurant_payment_method( restaurant_id ,payment_method_id) select :restaurant_id, id from payment_method;
insert into product_purchased_supplier( product_purchased_id ,supplier_id) select pp.id, s.id from product_purchased pp, ( select id from supplier limit 1) as s;
update product_categories set active = true;




