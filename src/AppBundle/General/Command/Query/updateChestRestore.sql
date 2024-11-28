UPDATE  public.chest_count SET last_chest_count_id= ( SELECT t.last_chest_count_id FROM
dblink ('dbname =backupdb port =8080 host =localhost user =postgres
password =123456', format('SELECT  id, last_chest_count_id,  origin_restaurant_id   FROM public.chest_count where id = %L  and origin_restaurant_id=%L',:id::int,:restaurantId::int) )
AS t( id integer, last_chest_count_id integer, origin_restaurant_id integer )) where id=:id