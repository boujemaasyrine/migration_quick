resource "azurerm_postgresql_flexible_server_configuration" "pgsql" {
  for_each  = var.postgresql_configurations
  name      = each.key
  server_id = azurerm_postgresql_flexible_server.private.id
  value     = each.value
  
  depends_on = [ azurerm_postgresql_flexible_server.private ]
}