INSERT INTO public.expense SELECT * FROM dblink ('dbname =backupdb port =8080 host =localhost user =postgres password =123456',
 format('SELECT *  FROM public.expense WHERE  date_expense>=%L and date_expense <=%L and origin_restaurant_id=%L',:startDate::date,:endDate::date,:restaurantId::int ) )
 AS t(id integer, responsible_id integer, chest_count_id integer, origin_restaurant_id integer, group_expense character varying(40),
            sous_group character varying(40), comment text, tva double precision , amount double precision, reference integer, date_expense date, created_at timestamp(0) without time zone,
            updated_at  timestamp(0) without time zone, synchronized boolean, import_id character varying(255))
 WHERE id NOT IN  (SELECT id FROM public.expense where date_expense>=:startDate and date_expense<=:endDate and origin_restaurant_id=:restaurantId);
 INSERT INTO public.deposit SELECT * FROM dblink ('dbname =backupdb port =8080 host =localhost user =postgres password =123456',
 format('SELECT *  FROM public.deposit WHERE  expense_id in (select id from expense where date_expense>=%L and date_expense <=%L and origin_restaurant_id=%L)',:startDate::date,:endDate::date,:restaurantId::int ) )
 AS t(id integer, owner_id integer, expense_id integer, chest_count_id integer, origin_restaurant_id integer,
            reference integer, source character varying(20), destination character varying(20), affiliate_code character varying(20), type character varying(20), sous_type character varying(20),
            total_amount double precision, created_at timestamp(0) without time zone, updated_at timestamp(0) without time zone, synchronized boolean, import_id character varying(255))
 WHERE id NOT IN  (SELECT id FROM public.deposit where expense_id in (select id from expense where date_expense>=:startDate and date_expense<=:endDate and origin_restaurant_id=:restaurantId))