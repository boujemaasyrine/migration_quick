resource "azurerm_private_dns_resolver_inbound_endpoint" "inbount-edpt1" {
  name                    = var.private_dns_resolver_inbound_endpoint_name
  location                = var.location
  private_dns_resolver_id = azurerm_private_dns_resolver.this.id
  ip_configurations {
    private_ip_allocation_method = "Dynamic"
    subnet_id                    = var.subnet_id
  }
}