resource "azurerm_application_gateway" "example" {
  name                = local.application_gateway_name[terraform.workspace]
  resource_group_name = azurerm_resource_group.example.name
  location            = var.location
  enable_http2        = true
  sku {
    name = var.gw_sku
    tier = var.gw_sku
  }
  firewall_policy_id = azurerm_web_application_firewall_policy.gw_waf.id
  autoscale_configuration {
    min_capacity = 1
    max_capacity = 4
  }
  identity {
    type = "UserAssigned"
    identity_ids = [azurerm_user_assigned_identity.base.id]
  }
  ssl_certificate {
    name     = local.app_gw_ssl_certificate_name
    key_vault_secret_id = local.certificate_secret_id
  }
  gateway_ip_configuration {
    name      = local.gateway_ip_configuration_name
    subnet_id = local.gw_subnet_id
  }
  backend_address_pool{
    name  = local.backend_address_pool_name
    fqdns = [azurerm_linux_web_app.example.default_hostname]
  }
  probe {
    name                                      = local.probe_name
    protocol                                  = "Https"
    path                                      = "/"
    interval                                  = 30
    timeout                                   = 120
    unhealthy_threshold                       = 3
    pick_host_name_from_backend_http_settings = true
    match {
      status_code = ["200-399"]
    }
  }
  backend_http_settings {
    name                  = local.backend_http_settings_name
    affinity_cookie_name  = local.affinity_cookie_name
    cookie_based_affinity = "Disabled"
    probe_name            = local.probe_name
    path                  = "/"
    port                  = 443
    protocol              = "Https"
    request_timeout       = 360
    pick_host_name_from_backend_address =true
  }

  #PUBLIC
  frontend_ip_configuration {
    name                 = local.frontend_public_ip_configuration_name
    public_ip_address_id = azurerm_public_ip.gw_ip.id
  }

  frontend_port {
    name = local.frontend_public_port_name
    port = 443
  }
  http_listener {
    name                           = local.http_public_listener_name
    frontend_ip_configuration_name = local.frontend_public_ip_configuration_name
    frontend_port_name             = local.frontend_public_port_name
    protocol                       = "Https"
    ssl_certificate_name           = local.app_gw_ssl_certificate_name
  }
  request_routing_rule {
    name                       = local.request_routing_rule_public_name
    priority                   = 9
    rule_type                  = "Basic"
    http_listener_name         = local.http_public_listener_name
    backend_address_pool_name  = local.backend_address_pool_name
    backend_http_settings_name = local.backend_http_settings_name
    rewrite_rule_set_name      = local.rewrite_rule_set_private_name
  }

  #PRIVATE
  frontend_ip_configuration {
    name                            = local.frontend_private_ip_configuration_name
    subnet_id                       = local.gw_subnet_id
    private_ip_address_allocation   = "Static"
    private_ip_address              = var.list_ip.gw_prv_ip[terraform.workspace]
  }
  #HTTPS
  frontend_port {
    name = local.frontend_private_port_name
    port = 443
  }
  http_listener {
    name                           = local.http_private_listener_name
    frontend_ip_configuration_name = local.frontend_private_ip_configuration_name
    frontend_port_name             = local.frontend_private_port_name
    protocol                       = "Https"
    ssl_certificate_name           = local.app_gw_ssl_certificate_name
  }
  request_routing_rule {
    name                       = local.request_routing_rule_private_name
    priority                   = 8
    rule_type                  = "Basic"
    http_listener_name         = local.http_private_listener_name
    backend_address_pool_name  = local.backend_address_pool_name
    backend_http_settings_name = local.backend_http_settings_name
    rewrite_rule_set_name      = local.rewrite_rule_set_private_name
  }
  #HTTP
  frontend_port {
    name = local.frontend_private_port_name2
    port = 80
  }
  http_listener {
    name                           = local.http_private_listener_name2
    frontend_ip_configuration_name = local.frontend_private_ip_configuration_name
    frontend_port_name             = local.frontend_private_port_name2
    protocol                       = "Http"
  }
  request_routing_rule {
    name                       = local.request_routing_rule_private_name2
    priority                   = 7
    rule_type                  = "Basic"
    http_listener_name         = local.http_private_listener_name2
    backend_address_pool_name  = local.backend_address_pool_name
    backend_http_settings_name = local.backend_http_settings_name
    rewrite_rule_set_name      = local.rewrite_rule_set_private_name
  }
  rewrite_rule_set {
    name = local.rewrite_rule_set_private_name
    rewrite_rule {
      name = local.rewrite_rule_private_name
      rule_sequence = 1
      condition{
        ignore_case = true
        negate      = false
        variable                = "http_resp_Location"
        pattern                 = "(http:?)://.*azurewebsites.net(.*)$"
      }
      response_header_configuration {
        header_name = "Location"
        header_value = "{http_resp_Location_1}://${var.dns_zone_name}{http_resp_Location_2}"
      }
    }
  }
  tags = merge(var.common_tags, var.prod_tags)
}