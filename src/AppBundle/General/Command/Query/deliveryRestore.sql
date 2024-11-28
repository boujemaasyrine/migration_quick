INSERT INTO public.delivery SELECT * FROM dblink ('dbname =backupdb port =8080 host =localhost user =postgres password =123456',
format('SELECT  *  FROM public.delivery where  date>=%L and date <=%L and origin_restaurant_id=%L ',:startDate::date,:endDate::date,:restaurantId::int ) )
AS t(id integer,order_id integer,employee_id integer, origin_restaurant_id integer, date timestamp(0) without time zone, deliverybordereau character varying(50),
            valorization double precision, created_at timestamp(0) without time zone, updated_at timestamp(0) without time zone,synchronized boolean,
            import_id character varying(255)) WHERE id NOT IN (SELECT id FROM public.delivery WHERE   origin_restaurant_id=:restaurantId and date>=:startDate and date <= :endDate);
INSERT INTO public.delivery_line SELECT * FROM dblink ('dbname =backupdb port =8080 host =localhost user =postgres password =123456',
format('SELECT  *  FROM public.delivery_line where delivery_id in ( select id from delivery where  date>=%L and date <=%L and origin_restaurant_id=%L )',:startDate::date,:endDate::date,:restaurantId::int ) )
AS t(id integer,delivery_id integer, product_id integer,qty double precision , valorization double precision, created_at timestamp(0) without time zone, updated_at timestamp(0) without time zone,
            import_id character varying(255)) WHERE id NOT IN (SELECT id FROM public.delivery_line WHERE   delivery_id in ( select id from delivery where  origin_restaurant_id=:restaurantId and date>=:startDate and date <= :endDate));
INSERT INTO public.delivery_line_tmp SELECT * FROM dblink ('dbname =backupdb port =8080 host =localhost user =postgres password =123456',
format('SELECT  *  FROM public.delivery_line_tmp where delivery_id in ( select id from delivery where  date>=%L and date <=%L and origin_restaurant_id=%L )',:startDate::date,:endDate::date,:restaurantId::int ) )
AS t(id integer,delivery_id integer, product_id integer,qty double precision , valorization double precision, created_at timestamp(0) without time zone, updated_at timestamp(0) without time zone) WHERE id NOT IN (SELECT id FROM public.delivery_line_tmp WHERE   delivery_id in ( select id from delivery where  origin_restaurant_id=:restaurantId and date>=:startDate and date <= :endDate));
INSERT INTO public.product_purchased_mvmt SELECT * FROM dblink ('dbname =backupdb port =8080 host =localhost user =postgres password =123456',
format('SELECT  *  FROM public.product_purchased_mvmt where date_time>=%L and date_time <=%L and  type=''delivery''and   origin_restaurant_id=%L  and source_id in (select id from delivery_line where delivery_id in ( select id from delivery where   date>=%L and date <=%L and origin_restaurant_id=%L))',:startDate::date,:endDate::date,:restaurantId::int,:startDate::date,:endDate::date,:restaurantId::int ) )
AS t(id integer , product_id integer, origin_restaurant_id integer, date_time timestamp(0) without time zone, variation double precision , source_id numeric ,stock_qty double precision , type character varying (50), buying_cost double precision , label_unit_exped character varying(255), label_unit_inventory character varying(255),label_unit_usage character varying(255), inventory_qty double precision , usage_qty double precision, deleted boolean, created_at timestamp(0) without time zone, updated_at timestamp(0) without time zone,synchronized boolean,import_id character varying(255)) WHERE id NOT IN (SELECT id FROM public.product_purchased_mvmt  WHERE date_time>=:startDate and date_time<=:endDate and type='delivery' and  origin_restaurant_id=:restaurantId and source_id in (select id from delivery_line where delivery_id in ( select id from delivery where  origin_restaurant_id=:restaurantId and date>=:startDate and date <= :endDate)))




