INSERT INTO public.expense SELECT * FROM dblink ('dbname =backupdb port =8080 host =localhost user =postgres password =123456',
 format('SELECT *  FROM public.expense WHERE  date_expense=%L and origin_restaurant_id=%L',:date::date,:restaurantId::int ) )
 AS t(id integer, responsible_id integer, chest_count_id integer, origin_restaurant_id integer, group_expense character varying(40),
            sous_group character varying(40), comment text, tva double precision , amount double precision, reference integer, date_expense date, created_at timestamp(0) without time zone,
            updated_at  timestamp(0) without time zone, synchronized boolean, import_id character varying(255))
 WHERE id NOT IN  (SELECT id FROM public.expense where date_expense=:date and origin_restaurant_id=:restaurantId)