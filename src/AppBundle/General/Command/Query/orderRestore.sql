INSERT INTO public.orders SELECT * FROM dblink ('dbname =backupdb port =8080 host =localhost user =postgres password =123456',
format('SELECT  *  FROM public.orders WHERE dateorder>=%L and dateorder <=%L and origin_restaurant_id=%L',:startDate::date,:endDate::date,:restaurantId::int ) )
AS t(id integer, supplier_id integer, employee_id integer, origin_restaurant_id integer, dateorder date,
            datedelivery date, numorder integer, status character varying(10), created_at timestamp(0) without time zone, updated_at timestamp(0) without time zone, synchronized boolean,
            import_id character varying(255)) WHERE id NOT IN (SELECT id FROM public.orders WHERE dateorder>=:startDate and dateorder <=:endDate and origin_restaurant_id=:restaurantId);
INSERT INTO public.order_line SELECT * FROM dblink ('dbname =backupdb port =8080 host =localhost user =postgres password =123456',
format('SELECT  *  FROM public.order_line where order_id in ( select id from orders where  dateorder>=%L and dateorder <=%L and origin_restaurant_id=%L )',:startDate::date,:endDate::date,:restaurantId::int ) )
AS t(id integer,order_id integer, product_id integer, qty double precision, created_at timestamp(0) without time zone, updated_at timestamp(0) without time zone,
            import_id character varying(255)) WHERE id NOT IN (SELECT id FROM public.order_line WHERE   order_id in ( select id from orders where  origin_restaurant_id=:restaurantId and dateorder>=:startDate and dateorder <=:endDate))