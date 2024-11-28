output "web_app_slot_hostname" {
  value = azurerm_linux_web_app_slot.example.default_hostname
}

output "vm_spoke_public_ip_address" {
  value = azurerm_linux_virtual_machine.vm_spoke.public_ip_address
}

output "app_gw_private_ip" {
  value = var.list_ip.gw_prv_ip[terraform.workspace]
}

# output "azurerm_dns_zone_name_servers" {
#   value = azurerm_dns_zone.dns_zone.name_servers  
# }