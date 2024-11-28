resource "azurerm_postgresql_flexible_server_database" "private" {
  name      = var.postgresql_flexible_server_database_name
  server_id = azurerm_postgresql_flexible_server.private.id
  collation = "en_US.utf8"
  charset   = "utf8"
  lifecycle {
    prevent_destroy = true
  }

  depends_on = [ azurerm_postgresql_flexible_server.private ]
}