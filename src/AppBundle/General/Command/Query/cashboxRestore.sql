INSERT INTO  public.cashbox_count SELECT * FROM
dblink ('dbname =backupdb port =8080 host =localhost user =postgres
password =123456', format('SELECT * FROM public.cashbox_count where date>=%L  and date<=%L and origin_restaurant_id=%L',:startDate::date,:endDate::date,:restaurantId::int ) )
AS t(  id integer, owner_id  integer, cashier_id integer, small_chest_id integer, origin_restaurant_id integer,
            date date, real_ca_counted double precision, theorical_ca double precision, number_cancels integer, total_cancels double precision,
            number_corrections integer, total_corrections double precision, number_abondons integer, total_abondons double precision,
            eft boolean, counted boolean, synchronized boolean, created_at timestamp(0) without time zone, updated_at timestamp(0) without time zone, import_id character varying(255)) WHERE id NOT IN (SELECT id FROM public.cashbox_count WHERE date>=:startDate  and date<=:endDate and origin_restaurant_id=:restaurantId);
INSERT INTO  public.cashbox_real_cash_container SELECT * FROM
dblink ('dbname =backupdb port=8080 host=localhost user=postgres
password =123456', format('SELECT * FROM public.cashbox_real_cash_container where  cashbox_id in (select id from cashbox_count where date>=%L  and date<=%L and origin_restaurant_id=%L)',:startDate::date,:endDate::date,:restaurantId::int ))
AS t(  id integer, cashbox_id integer, total_amount double precision, all_amount boolean, bill_of_100 integer, bill_of_50 integer,
            bill_of_20 integer, bill_of_10 integer, bill_of_5 integer, change double precision, import_id character varying(255)) WHERE id NOT IN (SELECT id FROM public.cashbox_real_cash_container WHERE   cashbox_id in (select id from cashbox_count where date>=:startDate  and date<=:endDate and origin_restaurant_id=:restaurantId));
INSERT INTO  public.cashbox_meal_ticket_container SELECT * FROM
dblink ('dbname =backupdb port=8080 host=localhost user=postgres
password =123456', format('SELECT * FROM public.cashbox_meal_ticket_container where  cashbox_id in (select id from cashbox_count where date>=%L  and date<=%L and origin_restaurant_id=%L)',:startDate::date,:endDate::date,:restaurantId::int ))
AS t(  id integer, cashbox_id integer,import_id character varying(255)) WHERE id NOT IN (SELECT id FROM public.cashbox_meal_ticket_container WHERE   cashbox_id in (select id from cashbox_count where date>=:startDate  and date<=:endDate and origin_restaurant_id=:restaurantId));
INSERT INTO  public.cashbox_foreign_currency_container SELECT * FROM
dblink ('dbname =backupdb port=8080 host=localhost user=postgres
password =123456', format('SELECT * FROM public.cashbox_foreign_currency_container where  cashbox_id in (select id from cashbox_count where date>=%L  and date<=%L and origin_restaurant_id=%L)',:startDate::date,:endDate::date,:restaurantId::int ))
AS t(  id integer, cashbox_id integer,import_id character varying(255)) WHERE id NOT IN (SELECT id FROM public.cashbox_foreign_currency_container WHERE   cashbox_id in (select id from cashbox_count where date>=:startDate  and date<=:endDate and origin_restaurant_id=:restaurantId));
INSERT INTO  public.cashbox_discount_container SELECT * FROM
dblink ('dbname =backupdb port=8080 host=localhost user=postgres
password =123456', format('SELECT * FROM public.cashbox_discount_container where  cashbox_id in (select id from cashbox_count where date>=%L  and date<=%L and origin_restaurant_id=%L)',:startDate::date,:endDate::date,:restaurantId::int ))
AS t(  id integer, cashbox_id integer,import_id character varying(255)) WHERE id NOT IN (SELECT id FROM public.cashbox_discount_container WHERE   cashbox_id in (select id from cashbox_count where date>=:startDate  and date<=:endDate and origin_restaurant_id=:restaurantId));
INSERT INTO  public.cashbox_check_restaurant_container SELECT * FROM
dblink ('dbname =backupdb port=8080 host=localhost user=postgres
password =123456', format('SELECT * FROM public.cashbox_check_restaurant_container where  cashbox_id in (select id from cashbox_count where date>=%L  and date<=%L and origin_restaurant_id=%L)',:startDate::date,:endDate::date,:restaurantId::int ))
AS t(  id integer, cashbox_id integer,import_id character varying(255)) WHERE id NOT IN (SELECT id FROM public.cashbox_check_restaurant_container WHERE   cashbox_id in (select id from cashbox_count where date>=:startDate  and date<=:endDate and origin_restaurant_id=:restaurantId));

INSERT INTO  public.cashbox_ticket_restaurant SELECT * FROM
dblink ('dbname =backupdb port=8080 host=localhost user=postgres
password =123456', format('SELECT * FROM public.cashbox_ticket_restaurant where  check_restaurant_container_id in (select id from cashbox_count where date>=%L  and date<=%L and origin_restaurant_id=%L)',:startDate::date,:endDate::date,:restaurantId::int ))
AS t(  id integer, check_restaurant_container_id integer, small_chest_id integer, qty double precision, unit_value double precision,
            ticket_name character varying(255), id_payment character varying(255), electronic boolean) WHERE id NOT IN (SELECT id FROM public.cashbox_ticket_restaurant WHERE   check_restaurant_container_id in (select id from cashbox_count where date>=:startDate  and date<=:endDate and origin_restaurant_id=:restaurantId));
INSERT INTO  public.cashbox_check_quick_container SELECT * FROM
dblink ('dbname =backupdb port=8080 host=localhost user=postgres
password =123456', format('SELECT * FROM public.cashbox_check_quick_container where  cashbox_id in (select id from cashbox_count where date>=%L  and date<=%L and origin_restaurant_id=%L)',:startDate::date,:endDate::date,:restaurantId::int ))
AS t(  id integer, cashbox_id integer,import_id character varying(255)) WHERE id NOT IN (SELECT id FROM public.cashbox_check_quick_container WHERE   cashbox_id in (select id from cashbox_count where date>=:startDate  and date<=:endDate and origin_restaurant_id=:restaurantId));
INSERT INTO  public.cashbox_check_quick SELECT * FROM
dblink ('dbname =backupdb port=8080 host=localhost user=postgres
password =123456', format('SELECT * FROM public.cashbox_check_quick where  check_quick_container_id in (select id from cashbox_count where date>=%L  and date<=%L and origin_restaurant_id=%L)',:startDate::date,:endDate::date,:restaurantId::int ))
AS t(  id integer, check_quick_container_id integer, small_chest_id integer, qty double precision, unit_value double precision,
            check_name character varying(200), id_payment character varying(10)) WHERE id NOT IN (SELECT id FROM public.cashbox_check_quick WHERE   check_quick_container_id in (select id from cashbox_count where date>=:startDate  and date<=:endDate and origin_restaurant_id=:restaurantId));

INSERT INTO  public.cashbox_bank_card_container SELECT * FROM
dblink ('dbname =backupdb port=8080 host=localhost user=postgres
password =123456', format('SELECT * FROM public.cashbox_bank_card_container where  cashbox_id in (select id from cashbox_count where date>=%L  and date<=%L and origin_restaurant_id=%L)',:startDate::date,:endDate::date,:restaurantId::int ))
AS t(  id integer, cashbox_id integer,import_id character varying(255)) WHERE id NOT IN (SELECT id FROM public.cashbox_bank_card_container WHERE   cashbox_id in (select id from cashbox_count where date>=:startDate  and date<=:endDate and origin_restaurant_id=:restaurantId));
INSERT INTO  public.cashbox_bank_card SELECT * FROM
dblink ('dbname =backupdb port=8080 host=localhost user=postgres
password =123456', format('SELECT * FROM public.cashbox_bank_card where  bank_card_container_id in (select id from cashbox_count where date>=%L  and date<=%L and origin_restaurant_id=%L)',:startDate::date,:endDate::date,:restaurantId::int ))
AS t( id integer , bank_card_container_id integer , small_chest_id integer , amount double precision , card_name character varying(255), id_payment character varying(255)) WHERE id NOT IN (SELECT id FROM public.cashbox_bank_card WHERE   bank_card_container_id in (select id from cashbox_count where date>=:startDate  and date<=:endDate and origin_restaurant_id=:restaurantId))







