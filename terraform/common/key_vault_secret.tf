
resource "azurerm_key_vault_secret" "vm_spoke_private_key" {
  name         = local.key_vault_vm_spoke_prv_key_name
  value        = base64encode(tls_private_key.terraform_generated_private_key.private_key_openssh)
  key_vault_id = azurerm_key_vault.vm_keys.id
  depends_on   = [ tls_private_key.terraform_generated_private_key ]
  tags = merge(var.common_tags, var.prod_tags)
}

resource "azurerm_key_vault_secret" "vm_spoke_public_key" {
  name         = local.key_vault_vm_spoke_pub_key_name
  value        = base64encode(tls_private_key.terraform_generated_private_key.public_key_openssh)
  key_vault_id = azurerm_key_vault.vm_keys.id
  depends_on   = [ tls_private_key.terraform_generated_private_key ]
  tags = merge(var.common_tags, var.prod_tags)
}