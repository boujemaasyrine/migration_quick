resource "azurerm_network_security_rule" "db_rule" {
  name                        = local.db_network_security_rule_name
  priority                    = 100
  direction                   = "Inbound"
  access                      = "Allow"
  protocol                    = "Tcp"
  source_port_range           = "*"
  destination_port_range      = "*"
  source_address_prefix       = "AppService"
  destination_address_prefix  = "AppService"
  resource_group_name         = azurerm_resource_group.example.name
  network_security_group_name = azurerm_network_security_group.db_nsg.name
}