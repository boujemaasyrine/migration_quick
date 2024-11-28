resource "azurerm_subnet_network_security_group_association" "db_nsg" {
  subnet_id                 = local.database_subnet_id
  network_security_group_id = azurerm_network_security_group.db_nsg.id
}