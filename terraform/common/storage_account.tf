# resource "azurerm_storage_account" "example" {
#   name                     = lower(local.storage_account_name)
#   resource_group_name      = local.existant_resource_group_name
#   location                 = loca.existant_resource_group_name
#   account_tier             = "Standard"
#   account_replication_type = "LRS"
#   account_kind             = "StorageV2"
#   # public_network_access_enabled = false
#   blob_properties {
#     delete_retention_policy {
#       days    = 7
#     }
#   }
# }