resource "azurerm_network_interface_security_group_association" "vm_spoke_nic_nsg" {
  network_interface_id      = azurerm_network_interface.vm_spoke_nic.id
  network_security_group_id = local.network_security_group_spoke_id
  depends_on = [azurerm_network_interface.vm_spoke_nic]
}

#resource "azurerm_network_interface_security_group_association" "vm_hub_nic_nsg" {
#  network_interface_id      = azurerm_network_interface.vm_hub_nic.id
#  network_security_group_id = local.network_security_group_hub_id
#  depends_on = [azurerm_network_interface.vm_hub_nic]
#}