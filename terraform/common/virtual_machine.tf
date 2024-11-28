resource "azurerm_linux_virtual_machine" "vm_spoke" {
  name                = local.virtual_machine_spoke_name
  resource_group_name = azurerm_resource_group.example.name
  location            = var.location
  network_interface_ids = [ azurerm_network_interface.vm_spoke_nic.id ]
  size                  = "Standard_DS1_v2"

  os_disk {
    name                 = "vmSpokeOsDisk"
    caching              = "ReadWrite"
    storage_account_type = "Premium_LRS"
  }

  source_image_reference {
    publisher = "Canonical"
    offer     = "0001-com-ubuntu-server-jammy"
    sku       = "22_04-lts-gen2"
    version   = "latest"
  }

  computer_name       = "vmSpoke"
  admin_username      = var.vm_admin_username
  disable_password_authentication = true

  admin_ssh_key {
    username   =  var.vm_admin_username
    public_key = base64decode(azurerm_key_vault_secret.vm_spoke_public_key.value)
  }
  tags = merge(var.common_tags, var.prod_tags)
  depends_on = [
    azurerm_network_interface.vm_spoke_nic
    ,azurerm_key_vault_secret.vm_spoke_private_key,
    azurerm_key_vault_secret.vm_spoke_public_key
  ]
}

#resource "azurerm_windows_virtual_machine" "vm_hub" {
#  name                = local.virtual_machine_hub_name
#  resource_group_name = azurerm_resource_group.example.name
#  location            = var.location
#  network_interface_ids = [ azurerm_network_interface.vm_hub_nic.id ]
#  size                  = "Standard_DS1_v2"
#
#  os_disk {
#    name                 = "vmHubOsDisk"
#    caching              = "ReadWrite"
#    storage_account_type = "Premium_LRS"
#  }
#
#  source_image_reference {
#    publisher = "MicrosoftWindowsServer"
#    offer     = "WindowsServer"
#    sku       = "2022-Datacenter"
#    version   = "latest"
#  }
#
#  computer_name  = "vmHub"
#  admin_username      = local.vm_admin_username
#  admin_password      = local.vm_admin_password
#
#  tags = merge(var.common_tags, var.prod_tags)
#  depends_on = [ azurerm_network_interface.vm_hub_nic ]
#}
