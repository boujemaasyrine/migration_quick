resource "azurerm_web_application_firewall_policy" "gw_waf" {
  name                              = local.web_application_firewall_policy_name
  resource_group_name               = azurerm_resource_group.example.name
  location                          = var.location
  policy_settings {
    enabled = true
    mode = "Detection"
  }
  managed_rules {
    managed_rule_set {
      version = "3.2"
    }
  }
  tags = merge(var.common_tags, var.prod_tags)
}