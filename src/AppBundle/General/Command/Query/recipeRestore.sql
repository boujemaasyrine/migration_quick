INSERT INTO public.recipe_ticket SELECT * FROM dblink ('dbname =backupdb port =8080 host =localhost user =postgres password =123456',
 format('SELECT *  FROM public.recipe_ticket WHERE date>=%L and date <=%L and origin_restaurant_id=%L',:startDate::date,:endDate::date,:restaurantId::int ) )
 AS t(id integer, owner_id integer, chest_count_id integer, origin_restaurant_id integer, label character varying(100), amount double precision,
            date date, deleted boolean, created_at  timestamp(0) without time zone, updated_at  timestamp(0) without time zone, import_id character varying(255))
 WHERE id NOT IN  (SELECT id FROM public.recipe_ticket where date>=:startDate and date <=:endDate and origin_restaurant_id=:restaurantId)
