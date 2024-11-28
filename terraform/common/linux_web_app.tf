resource "azurerm_linux_web_app" "example" {
  name                = local.linux_web_app_name[terraform.workspace]
  location            = var.location
  resource_group_name = azurerm_resource_group.example.name
  service_plan_id     = azurerm_service_plan.example.id
  https_only          = false
  virtual_network_subnet_id     = local.web_subnet_id
#  public_network_access_enabled = false
  identity {
    type = "SystemAssigned" 
    }
  site_config {
    always_on           = true
    http2_enabled       = true
    application_stack {
      docker_image_name     = "${var.docker_registry_repository}:${var.docker_image_prod_tag}"
      docker_registry_url   = "https://${var.docker_registry_login}"
      docker_registry_username = var.docker_registry_username
      docker_registry_password = var.docker_registry_password
    }
    #TEMPORAIRE
    dynamic "ip_restriction" {
      for_each = var.whitelist_ips
      content {
        ip_address  = ip_restriction.value.ip_address
        priority    = ip_restriction.value.prior
        name        = ip_restriction.value.name
      }
    }
  }
  # MOUNT PREVISION_OPTIKITCHEN ONLY ON QUICK WORKSPACE
  dynamic "storage_account" {
    for_each = terraform.workspace == "quick" ? [1] : []
    content {
      access_key   = var.storage_account_access_key
      account_name = local.storage_account_name
      name         = local.storage_account_name
      share_name   = local.file_share_name
      type         = local.storage_type
      mount_path   = local.mount_path
    }
  }
  #FOR_LOGS
  storage_account {
    access_key   = var.storage_account_access_key
    account_name = local.storage_account_name
    name         = "logs"
    share_name   = local.file_share1_name
    type         = local.storage_type1
    mount_path   = local.mount_path1
  }
  # app_settings = {
  #   "DATABASE_HOST"      = var.prod_database_host
  #   "DATABASE_USER"      = var.prod_database_user
  #   "DATABASE_PASSWORD"  = var.prod_database_password
  #   "DATABASE_NAME"      = var.prod_database_name  
  # }
  logs {
    detailed_error_messages = true
    http_logs {
      file_system {
        retention_in_days = 7
        retention_in_mb   = 35
      }
    }
    application_logs {
      file_system_level = "Verbose"
    }
  }
}