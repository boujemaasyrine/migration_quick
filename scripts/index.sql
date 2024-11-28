/* Tickets */
CREATE INDEX CONCURRENTLY IF NOT EXISTS ticket_date_index ON ticket (date);
