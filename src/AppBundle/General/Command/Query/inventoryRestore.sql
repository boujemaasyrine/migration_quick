INSERT INTO public.inventory_sheet SELECT * FROM dblink ('dbname =backupdb port =8080 host =localhost user =postgres password =123456',
format('SELECT  *  FROM public.inventory_sheet where fiscal_date>=%L and fiscal_date<=%L and origin_restaurant_id=%L ',:startDate::date,:endDate::date ,:restaurantId::int) )
AS t(id integer, employee_id integer, sheet_model_id integer, origin_restaurant_id integer, fiscal_date date,status character varying(255), sheet_model_label character varying(255), created_at timestamp(0) without time zone,
 updated_at timestamp(0) without time zone, import_id character varying(255)) WHERE id NOT IN (SELECT id FROM public.inventory_sheet WHERE
 origin_restaurant_id=:restaurantId and fiscal_date>=:startDate and fiscal_date<=:endDate);
 INSERT INTO public.inventory_line SELECT * FROM dblink ('dbname =backupdb port =8080 host =localhost user =postgres password =123456',
format('SELECT  *  FROM public.inventory_line where inventory_sheet_id in ( select id from inventory_sheet where fiscal_date>=%L and fiscal_date<=%L and origin_restaurant_id=%L) ',:startDate::date,:endDate::date ,:restaurantId::int) )
AS t(id integer,inventory_sheet_id integer, product_id integer, product_purchased_historic_id integer,
            total_inventory_cnt double precision, inventory_cnt double precision, usage_cnt double precision, exped_cnt double precision, import_id character varying(255), created_at timestamp(0) without time zone,
 updated_at timestamp(0) without time zone) WHERE id NOT IN (SELECT id FROM public.inventory_line where inventory_sheet_id in
 ( select id from inventory_sheet where fiscal_date>=:startDate and fiscal_date<=:endDate and origin_restaurant_id=:restaurantId));
 INSERT INTO public.product_purchased_mvmt SELECT * FROM dblink ('dbname =backupdb port =8080 host =localhost user =postgres password =123456',
format('SELECT  *  FROM public.product_purchased_mvmt where date_time>=%L and date_time<=%L and  type = (''inventory'') and   origin_restaurant_id=%L ',
:startDate::date,:endDate::date,:restaurantId::int,:startDate::date,:endDate::date,:restaurantId::int ) )
AS t(id integer , product_id integer, origin_restaurant_id integer, date_time timestamp(0) without time zone, variation double precision ,
source_id numeric ,stock_qty double precision , type character varying (50), buying_cost double precision , label_unit_exped character varying(255),
 label_unit_inventory character varying(255),label_unit_usage character varying(255), inventory_qty double precision , usage_qty double precision,
 deleted boolean, created_at timestamp(0) without time zone, updated_at timestamp(0) without time zone,synchronized boolean,import_id character varying(255))
 WHERE id NOT IN (SELECT id FROM public.product_purchased_mvmt  WHERE date_time>=:startDate and date_time<=:endDate and type = ('inventory') and  origin_restaurant_id=:restaurantId)
