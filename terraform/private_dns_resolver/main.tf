resource "azurerm_private_dns_resolver" "this" {
  name                = "example-resolver"
  resource_group_name = var.resource_group_name
  location            = var.location
  virtual_network_id  = var.virtual_network_id
}