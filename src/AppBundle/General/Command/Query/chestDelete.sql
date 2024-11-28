select count(*) from chest_count where id = any (string_to_array(:chest_count_id, ',')::int[]);
delete from chest_cashbox_fund where chest_count_id = any (string_to_array(:chest_count_id, ',')::int[]);
delete from chest_exchange where chest_exchange_fund_id = any (string_to_array(:chest_count_id, ',')::int[]);
delete from chest_exchange_fund where chest_count_id = any (string_to_array(:chest_count_id, ',')::int[]);
delete from cashbox_ticket_restaurant where small_chest_id = any (string_to_array(:chest_count_id, ',')::int[]);
delete from cashbox_check_quick  where small_chest_id = any (string_to_array(:chest_count_id, ',')::int[]);
delete from cashbox_bank_card  where small_chest_id = any (string_to_array(:chest_count_id, ',')::int[]);
delete from cashbox_count  where small_chest_id = any (string_to_array(:chest_count_id, ',')::int[]);
delete from chest_small_chest where chest_count_id = any (string_to_array(:chest_count_id, ',')::int[]);
delete from chest_tirelire where chest_count_id = any (string_to_array(:chest_count_id, ',')::int[]);
update chest_count set last_chest_count_id=null where last_chest_count_id = any (string_to_array(:chest_count_id, ',')::int[]);
delete from chest_count where id = any (string_to_array(:chest_count_id, ',')::int[])