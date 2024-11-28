output "inbound_dns_resolver_ip_address" {
  value = azurerm_private_dns_resolver_inbound_endpoint.inbount-edpt1.ip_configurations
}