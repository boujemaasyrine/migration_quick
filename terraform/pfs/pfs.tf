resource "azurerm_postgresql_flexible_server" "private" {
  name                   = var.postgresql_flexible_server_name
  resource_group_name    = var.resource_group_name
  location               = var.location
  delegated_subnet_id    = var.subnet_id
  private_dns_zone_id    = var.private_dns_zone_id
  version                = "13"
  administrator_login    = var.db_administrator_login
  administrator_password = var.db_administrator_password
  zone                   = var.db_zone
  maintenance_window {
    day_of_week  = 2
    start_hour   = 3
    start_minute = 0
  }
  backup_retention_days = 14
  dynamic "high_availability" {
    for_each = var.is_highly_availible ? [1] : []
    content {
      mode                      = "ZoneRedundant"
      standby_availability_zone = local.standby_availability_zone[terraform.workspace]
    }
  }
  auto_grow_enabled      = true
  storage_mb             = var.storage_mb
  sku_name               = var.db_server_sku_name
}