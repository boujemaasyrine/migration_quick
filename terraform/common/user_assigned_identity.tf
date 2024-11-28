resource "azurerm_user_assigned_identity" "base" {
  location            = var.location
  name                = local.user_assigned_identity_name
  resource_group_name = azurerm_resource_group.example.name
}