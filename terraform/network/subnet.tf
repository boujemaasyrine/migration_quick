resource "azurerm_subnet" "dns_resolver_subnet" {
  count = var.dns_resolver_exist ? 0 : 1

  name                 = local.dns_resolver_subnet_name
  resource_group_name  = var.vnet_resource_group_name
  virtual_network_name = local.spoke_virtual_network_name
  address_prefixes     = [var.network_config.dns_resolver_subnet]
  private_endpoint_network_policies_enabled = false
}