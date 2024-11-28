output "pfs_fqdn" {
  value = azurerm_postgresql_flexible_server.private.fqdn
}

output "pfs_username" {
  value = azurerm_postgresql_flexible_server.private.administrator_login 
}

output "pfs_password" {
  value = azurerm_postgresql_flexible_server.private.administrator_password
}

output "pfs_database_name" {
  value = azurerm_postgresql_flexible_server_database.private.name
}