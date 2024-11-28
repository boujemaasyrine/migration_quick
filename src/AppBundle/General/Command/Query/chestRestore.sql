INSERT INTO  public.chest_count( id, last_chest_count_id, owner_id, origin_restaurant_id, date,
            closure_date, closure, eft, real_total, theorical_total, gap,
            created_at, updated_at, synchronized, import_id) SELECT * FROM
dblink ('dbname =backupdb port =8080 host =localhost user =postgres
password =123456', format('SELECT * FROM public.chest_count where id = any(%L)  and origin_restaurant_id=%L',string_to_array(:chest_count_id, ',')::int[],:restaurantId::int) )
AS t( id integer, last_chest_count_id integer, owner_id integer, origin_restaurant_id integer, date timestamp(0) without time zone ,
            closure_date timestamp(0) without time zone , closure boolean, eft boolean, real_total double precision, theorical_total double precision, gap double precision,
            created_at timestamp(0) without time zone , updated_at timestamp(0) without time zone , synchronized boolean, import_id character varying(255)) WHERE id NOT IN  (SELECT id FROM public.chest_count WHERE id =any(string_to_array(:chest_count_id, ',')::int[]))

