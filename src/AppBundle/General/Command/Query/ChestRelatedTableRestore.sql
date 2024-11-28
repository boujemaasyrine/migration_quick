INSERT INTO  public.chest_tirelire SELECT * FROM
dblink ('dbname =backupdb port =8080 host =localhost user=postgres
password =123456', format('SELECT * FROM public.chest_tirelire where id = any(%L)   ',string_to_array(:chest_count_id, ',')::int[] ) )
AS t( id integer, chest_count_id integer, real_total double precision, theorical_total double precision, gap double precision, total_cash_envelopes double precision,
            total_tr_envelopes double precision) WHERE id NOT IN (SELECT id FROM public.chest_tirelire WHERE id =any(string_to_array(:chest_count_id, ',')::int[]));
INSERT INTO  public.chest_small_chest SELECT * FROM
dblink ('dbname =backupdb port =8080 host =localhost user =postgres
password =123456', format('SELECT * FROM public.chest_small_chest where id = any(%L)   ',string_to_array(:chest_count_id, ',')::int[] ) )
AS t( id integer, chest_count_id integer, total_cash double precision, electronic_deposed boolean, real_total double precision,
            theorical_total double precision, gap double precision, real_cash_total double precision, real_tr_total double precision, real_tr_total_detail text,
            theorical_tr_total_detail text, real_tre_total double precision, real_cbtotal double precision, real_check_quick_total double precision,
            real_foreign_currency_total double precision, theorical_cash_total double precision, theorical_tr_total double precision,
            theorical_tre_total double precision, theorical_cbtotal double precision, theorical_check_quick_total double precision,
            theorical_foreign_currency_total double precision, global_id integer, import_id character varying(255)) WHERE id NOT IN (SELECT id FROM public.chest_small_chest WHERE id =any(string_to_array(:chest_count_id, ',')::int[]));

INSERT INTO  public.chest_exchange_fund SELECT * FROM
dblink ('dbname =backupdb port =8080 host =localhost user =postgres
password =123456', format('SELECT * FROM public.chest_exchange_fund where id = any(%L)   ',string_to_array(:chest_count_id, ',')::int[] ) )
AS t(  id integer, chest_count_id integer, real_total double precision, theorical_total double precision, import_id character varying(255)) WHERE id NOT IN (SELECT id FROM public.chest_exchange_fund WHERE id =any(string_to_array(:chest_count_id, ',')::int[]));
INSERT INTO  public.chest_exchange SELECT * FROM
dblink ('dbname =backupdb port =8080 host =localhost user =postgres
password =123456', format('SELECT * FROM public.chest_exchange where chest_exchange_fund_id = any(%L)   ',string_to_array(:chest_count_id, ',')::int[] ) )
AS t(  id integer, chest_exchange_fund_id integer, qty double precision, unit_param_id integer, unit_value double precision, unit_label character varying(255) ,
            exchange_type character varying(255)) WHERE id NOT IN (SELECT id FROM public.chest_exchange WHERE chest_exchange_fund_id =any(string_to_array(:chest_count_id, ',')::int[]));
INSERT INTO  public.chest_cashbox_fund SELECT * FROM
dblink ('dbname =backupdb port =8080 host =localhost user =postgres
password =123456', format('SELECT * FROM public.chest_cashbox_fund where chest_count_id = any(%L)   ',string_to_array(:chest_count_id, ',')::int[] ) )
AS t(  id integer, chest_count_id integer, nbr_of_cashboxes integer, initial_cashbox_funds double precision,
            nbr_of_cashboxes_th integer, initial_cashbox_funds_th double precision, import_id character varying(255)) WHERE id NOT IN (SELECT id FROM public.chest_cashbox_fund WHERE chest_count_id =any(string_to_array(:chest_count_id, ',')::int[]))







