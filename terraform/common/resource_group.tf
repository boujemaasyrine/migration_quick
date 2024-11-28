resource "azurerm_resource_group" "example" {
  name     = local.resource_group_name
  location = var.location
}
