locals {
  spoke_virtual_network_name                          = "QSRP-DHW-Net"
  spoke_virtual_network_id                            = format("/subscriptions/%s/resourceGroups/%s/providers/Microsoft.Network/virtualNetworks/%s", data.azurerm_client_config.current.subscription_id, var.vnet_resource_group_name, local.spoke_virtual_network_name)

  dns_resolver_subnet_name                            = format("%s-DNS-RESOLVER", var.prefix)
  dns_resolver_subnet_id                              = format("%s/subnets/%s", local.spoke_virtual_network_id, local.dns_resolver_subnet_name)
}