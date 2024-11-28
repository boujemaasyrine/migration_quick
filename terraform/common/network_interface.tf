resource "azurerm_network_interface" "vm_spoke_nic" {
  name                = local.network_interface_spoke_name
  location            = var.location
  resource_group_name = azurerm_resource_group.example.name
  enable_accelerated_networking = true
  ip_configuration {
    name                          = local.nic_ip_configuration_spoke_name
    subnet_id                     = local.vm_spoke_subnet_id
    private_ip_address_allocation = "Dynamic"
    public_ip_address_id          = azurerm_public_ip.vm_spoke_ip.id
  }
  depends_on = [azurerm_public_ip.vm_spoke_ip]
  tags = merge(var.common_tags, var.prod_tags)
}

#resource "azurerm_network_interface" "vm_hub_nic" {
#  name                = local.network_interface_hub_name
#  location            = var.location
#  resource_group_name = azurerm_resource_group.example.name
#  enable_accelerated_networking = true
#  ip_configuration {
#    name                          = local.nic_ip_configuration_hub_name
#    subnet_id                     = local.vm_hub_subnet_id
#    private_ip_address_allocation = "Dynamic"
#    public_ip_address_id          = azurerm_public_ip.vm_hub_ip.id
#  }
#  tags = merge(var.common_tags, var.prod_tags)
#}