INSERT INTO public.loss_sheet SELECT * FROM dblink ('dbname =backupdb port =8080 host =localhost user =postgres password =123456',
format('SELECT  *  FROM public.loss_sheet where type=''finalProduct'' and entry <= %L and entry >= %L and origin_restaurant_id=%L ',:d2::timestamp  ,:d1::timestamp ,:restaurantId::int) )
AS t(id integer, employee_id integer, model_id integer, origin_restaurant_id integer, type character varying(255), status character varying(10),
            entry timestamp(0) without time zone, sheet_model_label character varying(255), created_at timestamp(0) without time zone,
 updated_at timestamp(0) without time zone, import_id character varying(255)) WHERE id NOT IN (SELECT id FROM public.loss_sheet WHERE
 origin_restaurant_id=:restaurantId and entry <= :d2 and entry >= :d1 and type='finalProduct' );
INSERT INTO public.loss_line SELECT * FROM dblink ('dbname =backupdb port =8080 host =localhost user =postgres password =123456',
format('SELECT  *  FROM public.loss_line where loss_sheet_id in ( select id from loss_sheet where entry <= %L and entry >= %L and origin_restaurant_id=%L and type=''finalProduct'') ',:d1::timestamp  ,:d2::timestamp  ,:restaurantId::int) )
AS t(id integer,loss_sheet_id integer,product_id integer,  recipe_id integer, recipe_historic_id integer,
            product_purchased_historic_id integer, first_entry double precision, second_entry double precision, third_entry double precision,
            total_loss double precision, total_revenue_price double precision, created_at timestamp(0) without time zone,
 updated_at timestamp(0) without time zone, import_id character varying(255)) WHERE id NOT IN (SELECT id FROM public.loss_line where loss_sheet_id in
 ( select id from loss_sheet where entry <= :d2 and entry >= :d1 and origin_restaurant_id=:restaurantId and type='finalProduct'));
 INSERT INTO public.product_purchased_mvmt SELECT * FROM dblink ('dbname =backupdb port =8080 host =localhost user =postgres password =123456',
format('SELECT  *  FROM public.product_purchased_mvmt where  type = (''sold_loss'') and  date_time <= %L and date_time >= %L and  origin_restaurant_id=%L',:d2::timestamp ,:d1::timestamp ,:restaurantId::int ) )
AS t(id integer , product_id integer, origin_restaurant_id integer, date_time timestamp(0) without time zone, variation double precision ,
source_id numeric ,stock_qty double precision , type character varying (50), buying_cost double precision , label_unit_exped character varying(255),
 label_unit_inventory character varying(255),label_unit_usage character varying(255), inventory_qty double precision , usage_qty double precision,
 deleted boolean, created_at timestamp(0) without time zone, updated_at timestamp(0) without time zone,synchronized boolean,import_id character varying(255))
 WHERE id NOT IN (SELECT id FROM public.product_purchased_mvmt  WHERE   date_time <= :d2 and date_time >= :d1  and type = ('sold_loss') and  origin_restaurant_id=:restaurantId )

