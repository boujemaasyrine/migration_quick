INSERT INTO public.ticket SELECT * FROM dblink ('dbname =backupdb port =8080 host =localhost user =postgres password =123456', format('SELECT  *  FROM public.ticket  WHERE   date>=%L and date<=%L and origin_restaurant_id=%L',:startDate::date,:endDate::date,:restaurantId::int) ) AS t1( id numeric, cashbox_count_id integer, origin_restaurant_id integer, type character varying(10), cancelled_flag boolean, num bigint, startdate timestamp(0) without time zone , enddate timestamp(0) without time zone , invoicenumber character varying(50), status integer, invoicecancelled character varying(100), totalht double precision, totalttc double precision, paid boolean, deliverytime timestamp(0) without time zone, operator integer, operatorname character varying(50), responsible character varying(50), workstation integer, workstationname character varying(100), originid integer, origin character varying(100) , destinationid integer, destination character varying(50), entity integer, customer integer, date date, counted boolean, external_id character varying(255), counted_canceled boolean, created_at timestamp(0) without time zone, updated_at timestamp(0) without time zone, synchronized boolean, import_id character varying(255)) WHERE id NOT IN  (SELECT id FROM public.ticket WHERE date>=:startDate and date <=:endDate and origin_restaurant_id=:restaurantId);
INSERT INTO public.ticket_intervention SELECT * FROM dblink ('dbname =backupdb port =8080 host =localhost user =postgres password =123456', format('SELECT *  FROM public.ticket_intervention WHERE  ticket_id in (select id from ticket where date>=%L and date<=%L and origin_restaurant_id=%L)',:startDate::date,:endDate::date,:restaurantId::int ) ) AS t(id INTEGER,ticket_id NUMERIC,action character varying(50), managerid character varying(10),managername character varying(100),itemid character varying(30),itemlabel character varying(50) ,itemprice double precision ,itemplu character varying(10),itemqty INTEGER ,itemamount double precision,itemcode character varying(20),date timestamp(0) without time zone ,posttotal boolean,import_id character varying(255)) WHERE id NOT IN  (SELECT id FROM public.ticket_intervention where ticket_id  in (SELECT id FROM public.ticket WHERE date>=:startDate and date <=:endDate and origin_restaurant_id=:restaurantId));
INSERT INTO public.ticket_payment SELECT * FROM dblink ('dbname =backupdb port =8080 host =localhost user =postgres password =123456', format('SELECT  *  FROM public.ticket_payment WHERE  ticket_id in (select id from ticket where date>=%L and date<=%L and origin_restaurant_id=%L)',:startDate::date,:endDate::date,:restaurantId::int ) ) AS t(id numeric, ticket_id numeric, real_cash_container_id integer, check_restaurant_container_id integer, bank_card_container_id integer, check_quick_container_id integer, meal_ticket_container_id integer, foreign_currency_container_id integer, num integer, label character varying(50), id_payment character varying(50), code character varying(50), amount double precision , type character varying(30), operator character varying(30), first_name character varying(30) , last_name character varying(30) , electronic boolean, import_id character varying(255)) WHERE id NOT IN  (SELECT id FROM public.ticket_payment where ticket_id  in (SELECT id FROM public.ticket WHERE date>=:startDate and date <=:endDate and origin_restaurant_id=:restaurantId));
INSERT INTO public.ticket_line SELECT * FROM dblink ('dbname =backupdb port =8080 host =localhost user =postgres password =123456', format('SELECT  *  FROM public.ticket_line WHERE   date>=%L and date<=%L and origin_restaurant_id=%L and ticket_id in (select id from ticket where date>=%L and date<=%L and origin_restaurant_id=%L)',:startDate::date,:endDate::date,:restaurantId::int,:startDate::date,:endDate::date,:restaurantId::int) ) AS t(id numeric, ticket_id numeric, discount_container_id integer, line integer, qty integer, price double precision, totalht double precision, totaltva double precision, totalttc double precision, category character varying(100), division integer, product integer, label character varying(100), description character varying(100), plu character varying(10), combo boolean, composition boolean , parentline integer, tva double precision, is_discount boolean, revenue_price double precision, mvmt_recorded boolean, discount_id character varying(255), discount_code character varying(255), discount_label character varying(255), discount_ht double precision, discount_tva double precision, discount_ttc double precision, import_id character varying(255), startdate timestamp(0) without time zone, enddate timestamp(0) without time zone, status integer, date date, counted_canceled boolean, origin_restaurant_id integer, flag_va boolean) WHERE id NOT IN  (SELECT id FROM public.ticket_line where  origin_restaurant_id=:restaurantId and date>=:startDate and date <=:endDate );
INSERT INTO public.product_purchased_mvmt SELECT * FROM dblink ('dbname =backupdb port =8080 host =localhost user =postgres password =123456',
format('SELECT  *  FROM public.product_purchased_mvmt where  type = (''sold'') and  date_time <= %L and date_time >= %L and  origin_restaurant_id=%L',:d2::timestamp ,:d1::timestamp,:restaurantId::int ) )
AS t(id integer , product_id integer, origin_restaurant_id integer, date_time timestamp(0) without time zone, variation double precision ,
source_id numeric ,stock_qty double precision , type character varying (50), buying_cost double precision , label_unit_exped character varying(255),
 label_unit_inventory character varying(255),label_unit_usage character varying(255), inventory_qty double precision , usage_qty double precision,
 deleted boolean, created_at timestamp(0) without time zone, updated_at timestamp(0) without time zone,synchronized boolean,import_id character varying(255))
 WHERE id NOT IN (SELECT id FROM public.product_purchased_mvmt  WHERE  date_time <= :d2 and date_time >= :d1  and type = ('sold') and  origin_restaurant_id=:restaurantId )
