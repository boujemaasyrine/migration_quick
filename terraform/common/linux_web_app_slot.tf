resource "azurerm_linux_web_app_slot" "example" {
  name                          = local.linux_web_app_slot_name[terraform.workspace]
  app_service_id                = azurerm_linux_web_app.example.id
  https_only                    = false
  client_affinity_enabled       =  true
  virtual_network_subnet_id     = local.web_subnet_id
#  public_network_access_enabled = false
  identity {
  type = "SystemAssigned" 
  }
  site_config {
    always_on               = true
    http2_enabled           = true
    application_stack {
      docker_image_name        = "${var.docker_registry_repository}:${var.docker_image_dev_tag}"
      docker_registry_url      = "https://${var.docker_registry_login}"
      docker_registry_username = var.docker_registry_username
      docker_registry_password = var.docker_registry_password
    }
    dynamic "ip_restriction" {
      for_each = var.whitelist_ips_stg
      content {
        ip_address  = ip_restriction.value.ip_address
        priority    = ip_restriction.value.prior
        name        = ip_restriction.value.name
      }
    }
  }
  # app_settings = {
  #   "DATABASE_HOST"      = var.test_database_host
  #   "DATABASE_USER"      = var.test_database_user
  #   "DATABASE_PASSWORD"  = var.test_database_password
  #   "DATABASE_NAME"      = var.test_database_name  
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