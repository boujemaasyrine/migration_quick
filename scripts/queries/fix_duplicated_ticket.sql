/* Update deposit CB */
update deposit set total_amount = total_amount - 83.6, synchronized = false where id = 13;
update deposit set total_amount = total_amount - 94.61, synchronized = false where id = 14;
update deposit set total_amount = total_amount - 53.68, synchronized = false where id = 20;

/* Update Expense Deposit CB */
update expense set amount = amount - 83.6, synchronized = false where id = 33;
update expense set amount = amount - 94.61, synchronized = false where id = 35;
update expense set amount = amount - 53.68, synchronized = false where id = 45;

/* Update expense Cashbox error */
update expense set amount = 120.87, synchronized = false where id = 21;
update expense set amount = 832.61, synchronized = false where id = 31;
update expense set amount = 176.24, synchronized = false where id = 34;
update expense set amount = 100.20, synchronized = false where id = 37;
update expense set amount = 189.04, synchronized = false where id = 46;
update expense set amount = 55.97, synchronized = false where id = 47;

/* Delete expense and change it by recipe ticket*/
delete from expense where id = 65;
insert into recipe_ticket (id, owner_id, chest_count_id, label, amount, date, created_at, updated_at, synchronized, deleted)
VALUES (21, 23, null, 'cashbox_error', 0.33, '2016-07-26', '2016-07-27 06:36:35', '2016-07-27 06:36:35', false, false);
