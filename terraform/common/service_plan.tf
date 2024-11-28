resource "azurerm_service_plan" "example" {
  name                = local.service_plan_name[terraform.workspace]
  location            = azurerm_resource_group.example.location
  resource_group_name = azurerm_resource_group.example.name
  os_type             = "Linux"
  sku_name            = var.ap_sku_name
}