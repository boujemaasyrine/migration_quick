resource "azurerm_private_dns_zone" "db" {
  name                = local.private_dns_zone_db_name
  resource_group_name = azurerm_resource_group.example.name
  tags = merge( var.common_tags, var.prod_tags)
}