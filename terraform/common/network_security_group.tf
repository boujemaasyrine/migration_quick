resource "azurerm_network_security_group" "db_nsg" {
  name                = local.db_network_security_group_name
  location            = azurerm_resource_group.example.location
  resource_group_name = azurerm_resource_group.example.name
}