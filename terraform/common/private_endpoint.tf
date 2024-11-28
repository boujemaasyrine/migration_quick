resource "azurerm_private_endpoint" "web_app" {
  name                = local.private_endpoint_web_name[terraform.workspace]
  location            = azurerm_resource_group.example.location
  resource_group_name = azurerm_resource_group.example.name
  subnet_id           = local.endpoint_subnet_id

  private_dns_zone_group {
    name                 = local.private_web_dns_zone_group_name
    private_dns_zone_ids = [local.private_dns_zone_web_id]
  }

  private_service_connection {
    name                           = local.private_service_web_connection_name
    private_connection_resource_id = azurerm_linux_web_app.example.id
    is_manual_connection           = false
    subresource_names              = ["sites"]
  }
  tags = merge(var.common_tags, var.prod_tags)
}

resource "azurerm_private_endpoint" "storage_private_endpoint" {
  name                               = local.private_endpoint_storage_name
  location                           = var.location
  resource_group_name                = local.resource_group_name
  subnet_id                          = local.storage_endpoint_subnet_id

  private_service_connection {
    name                             = local.private_service_storage_connection_name
    private_connection_resource_id   = local.storage_account_id
    is_manual_connection             = false
    subresource_names                = ["file"]
  }
  private_dns_zone_group {
   name                             = local.private_storage_dns_zone_group_name
   private_dns_zone_ids             = [local.private_dns_zone_storage_id]
  }
  tags = merge( var.common_tags, var.prod_tags)
}


