INSERT INTO public.withdrawal SELECT * FROM dblink ('dbname =backupdb port =8080 host =localhost user =postgres password =123456',
format('SELECT  *  FROM public.withdrawal WHERE date>=%L and date <=%L and origin_restaurant_id=%L',:startDate::date,:endDate::date,:restaurantId::int ) )
AS t(id integer, member_id integer, responsible_id integer,cashbox_count_id integer ,origin_restaurant_id integer, date date,
            amount_withdrawal double precision, status_count character varying(20),envelope_id integer, created_at timestamp(0) without time zone, updated_at timestamp(0) without time zone, synchronized boolean,
            import_id character varying(255)) WHERE id NOT IN (SELECT id FROM public.withdrawal WHERE date>=:startDate and date <=:endDate and origin_restaurant_id=:restaurantId)

