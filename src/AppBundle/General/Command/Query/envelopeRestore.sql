  INSERT INTO public.envelope   SELECT * FROM dblink ('dbname =backupdb port =8080 host =localhost user =postgres password =123456',
 format('SELECT *  FROM public.envelope WHERE   origin_restaurant_id=%L and chest_count_id = any(%L)   ',:restaurantId::int,string_to_array(:chest_count_id, ',')::int[] ) )
 AS t(id integer, owner_id integer, cashier_id integer, deposit_id integer, chest_count_id integer, origin_restaurant_id integer,
            "number" integer, reference character varying(255), amount double precision, source_id integer, source character varying(255), status character varying(20), type character varying(20),
            sous_type character varying(20), created_at timestamp(0) without time zone, updated_at timestamp(0) without time zone, synchronized boolean, import_id character varying(255))
             WHERE id NOT IN  (SELECT id FROM public.envelope where origin_restaurant_id=:restaurantId and chest_count_id =any(string_to_array(:chest_count_id, ',')::int[]) )