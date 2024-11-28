INSERT INTO public.returns SELECT * FROM dblink ('dbname =backupdb port =8080 host =localhost user =postgres password =123456',
format('SELECT  *  FROM public.returns where  date>=%L and date<=%L and origin_restaurant_id=%L ',:startDate::date,:endDate::date ,:restaurantId::int) )
AS t(id integer, employee_id integer, supplier_id integer, origin_restaurant_id integer, date date, valorization double precision,
            comment text, created_at timestamp(0) without time zone,
 updated_at timestamp(0) without time zone, import_id character varying(255)) WHERE id NOT IN (SELECT id FROM public.returns WHERE
 origin_restaurant_id=:restaurantId and date>=:startDate and date<=:endDate);
INSERT INTO public.return_line SELECT * FROM dblink ('dbname =backupdb port =8080 host =localhost user =postgres password =123456',
format('SELECT  *  FROM public.return_line where return_id in ( select id from returns where date>=%L and date<=%L and origin_restaurant_id=%L) ',:startDate::date,:endDate::date ,:restaurantId::int) )
AS t(id integer,product_id integer, return_id integer, qty integer, qty_exp integer, qty_use integer, created_at timestamp(0) without time zone,
 updated_at timestamp(0) without time zone, import_id character varying(255)) WHERE id NOT IN (SELECT id FROM public.return_line where return_id in
 ( select id from returns where date>=:startDate and date<=:endDate and origin_restaurant_id=:restaurantId));
INSERT INTO public.product_purchased_mvmt SELECT * FROM dblink ('dbname =backupdb port =8080 host =localhost user =postgres password =123456',
format('SELECT  *  FROM public.product_purchased_mvmt where date_time>=%L and date_time <=%L and  type = (''returns'') and   origin_restaurant_id=%L  and source_id in
(select id from return_line where return_id in ( select id from returns where   date>=%L and date<=%L and origin_restaurant_id=%L))',
:startDate::date,:endDate::date,:restaurantId::int,:startDate::date,:endDate::date,:restaurantId::int ) )
AS t(id integer , product_id integer, origin_restaurant_id integer, date_time timestamp(0) without time zone, variation double precision ,
source_id numeric ,stock_qty double precision , type character varying (50), buying_cost double precision , label_unit_exped character varying(255),
 label_unit_inventory character varying(255),label_unit_usage character varying(255), inventory_qty double precision , usage_qty double precision,
 deleted boolean, created_at timestamp(0) without time zone, updated_at timestamp(0) without time zone,synchronized boolean,import_id character varying(255))
 WHERE id NOT IN (SELECT id FROM public.product_purchased_mvmt  WHERE date_time>=:startDate and date_time<=:endDate and type = ('returns') and  origin_restaurant_id=:restaurantId and source_id
 in (select id from return_line where return_id in ( select id from returns where  origin_restaurant_id=:restaurantId and date>=:startDate and date<=:endDate)))