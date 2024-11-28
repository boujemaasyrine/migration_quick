terraform {
  required_version = ">= 1.0"
  required_providers {
    azurerm = {
      source  = "hashicorp/azurerm"
      version = ">= 3.0, < 4.0"
    }
    random = {
      source  = "hashicorp/random"
      version = ">= 3.0"
    }
  }
  backend "azurerm" {
    resource_group_name  = "AUTO-BBB-BackOffice-NET"
    storage_account_name = "quickbostorage"
    container_name       = "tfstate"
    key                  = "terraform.tfstate"
  }
}

provider "azurerm" {
#  subscription_id = "your_subscription_id"
#  client_id       = "your_client_id"
#  client_secret   = "your_client_secret"
#  tenant_id       = "your_tenant_id"
  features {
    key_vault {
      purge_soft_deleted_certificates_on_destroy = true
      recover_soft_deleted_certificates          = true
    }
    resource_group {
      prevent_deletion_if_contains_resources     = true
    }
  }
}