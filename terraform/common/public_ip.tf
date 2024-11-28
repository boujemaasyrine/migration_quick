resource "azurerm_public_ip" "gw_ip" {
  name                = local.gw_public_ip_name[terraform.workspace]
  resource_group_name = azurerm_resource_group.example.name
  location            = var.location
  allocation_method   = "Static"
  sku = "Standard"
  tags = merge(var.common_tags, var.prod_tags)
}

resource "azurerm_public_ip" "vm_spoke_ip" {
  name                = local.vm_spoke_public_ip_name
  location            = var.location
  resource_group_name = azurerm_resource_group.example.name
  allocation_method   = "Dynamic"
  tags = merge(var.common_tags, var.prod_tags)
}

#resource "azurerm_public_ip" "vm_hub_ip" {
#  name                = local.vm_hub_public_ip_name
#  location            = var.location
#  resource_group_name = azurerm_resource_group.example.name
#  allocation_method   = "Dynamic"
#}