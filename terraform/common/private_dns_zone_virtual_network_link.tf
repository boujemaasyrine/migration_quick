resource "azurerm_private_dns_zone_virtual_network_link" "dns_db_vnet" {
  name                  = local.private_dns_zone_db_virtual_network_link_name
  private_dns_zone_name = local.private_dns_zone_db_name
  virtual_network_id    = local.spoke_virtual_network_id
  resource_group_name   = azurerm_resource_group.example.name
  depends_on = [azurerm_private_dns_zone.db]
  tags = merge( var.common_tags, var.prod_tags)
}
