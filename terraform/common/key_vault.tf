resource "azurerm_key_vault" "vm_keys" {
  name                        = local.key_vault_name
  location                    = var.location
  resource_group_name         = azurerm_resource_group.example.name
  enabled_for_disk_encryption = true
  tenant_id                   = data.azurerm_client_config.current.tenant_id
  purge_protection_enabled    = false
  enabled_for_template_deployment = true
  enabled_for_deployment      = true
  soft_delete_retention_days = 7

  sku_name = "standard"
  network_acls {
    default_action = "Allow"
    bypass         = "AzureServices"
  }
  tags = merge(var.common_tags, var.prod_tags)
}