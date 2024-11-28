resource "random_id" "random" {
  byte_length = 3
}

variable "workspace_name" {
  description = "Name of the workspace where resources will be provisioned"
  type = string
  default = "BBB-BackOffice"
}

variable "subscription_id" {
  description = "ID of the Azure subscription where Terraform should provision resource. Use azure cli command to query id: az account show --query id"
  type        = string
  default     = "f687556b-c4f5-4005-90b1-6604a3b65f37"
}

locals {
###########################################| R E S O U R C E _ G R O U P |##############################################

resource_group_name                                 = format("AUTO-%s-NET", var.workspace_name)
vnet_resource_group_name                            = "QSRP-DHW-Net"

################################################| A P P _ S E R V I C E |###############################################

service_plan_name = {
  quick = format("AUTO-%s-ASP"   , var.workspace_name)
  bk    = format("AUTO-%s-%s-ASP", var.workspace_name, terraform.workspace)
}
linux_web_app_name = {
  quick = format("AUTO-%s-AS", var.workspace_name)
  bk    = format("AUTO-%s-AS-%s", var.workspace_name, terraform.workspace)
}                               
linux_web_app_slot_name = {
  quick = format("AUTO-%s-AS-STAGING", var.workspace_name)
  bk    = format("AUTO-%s-%s-AS-STAGING", var.workspace_name, terraform.workspace)
}

#############################################| C O N T A I N E R _ R E G I S T R Y |#####################################

container_registry_acr_name                         = "autoAcrBo"
container_registry_acr_id                           = format("/subscriptions/%s/resourceGroups/%s/providers/Microsoft.ContainerRegistry/registries/%s", var.subscription_id, local.vnet_resource_group_name, local.container_registry_acr_name)

###############################################| S T O R A G E _ A C C O U N T |########################################

storage_account_name                                = "quickbostorage"
storage_account_id                                  = format("/subscriptions/%s/resourceGroups/%s/providers/Microsoft.Storage/storageAccounts/%s", var.subscription_id, local.resource_group_name, local.storage_account_name)

file_share_name                                     = "optik"
storage_type                                        = "AzureFiles"
mount_path                                          = "/var/www/html/data/prevision_optikitchen"

file_share1_name                                    = format("logs%s", terraform.workspace)
storage_type1                                       = "AzureFiles"
mount_path1                                         = "/var/log"

##########################################| P R I V A T E _ D N S _ Z O N E |############################################

#DATABASE
  private_dns_zone_db_name                           = "privatelink.postgres.database.azure.com"
  private_dns_zone_db_id                             = format("/subscriptions/%s/resourceGroups/%s/providers/Microsoft.Network/privateDnsZones/%s", var.subscription_id, local.resource_group_name, local.private_dns_zone_db_name)
  private_dns_zone_db_virtual_network_link_name      = "dsnVnetZoneDb.com"

#WEB
  private_dns_zone_web_name                          = "privatelink.azurewebsites.net"
  private_dns_zone_web_id                            = format("/subscriptions/%s/resourceGroups/%s/providers/Microsoft.Network/privateDnsZones/%s", var.subscription_id, local.vnet_resource_group_name, local.private_dns_zone_web_name)
  private_dns_zone_web_virtual_network_link_name     = "dsnVnetZoneWeb.com"

#STORAGE
  private_dns_zone_storage_name                      = "privatelink.file.core.windows.net"
  private_dns_zone_storage_id                        = format("/subscriptions/%s/resourceGroups/%s/providers/Microsoft.Network/privateDnsZones/%s", var.subscription_id, local.vnet_resource_group_name, local.private_dns_zone_storage_name)
  private_dns_zone_storage_virtual_network_link_name = "dsnVnetZoneStorage.com"

#########################################| A P P _ G A T E W A Y |######################################################

application_gateway_name = {
  quick = format("AUTO-%s-APPG", var.workspace_name)
  bk    = format("AUTO-%s-%s-APPG", var.workspace_name, terraform.workspace)
} 
web_application_firewall_policy_name                  = format("AUTO-%s-WAF", var.workspace_name)
gateway_ip_configuration_name                         = "appGatewayIpConfig"
probe_name                                            = format("AUTO-%s-PROBE", var.workspace_name)
backend_http_settings_name                            = format("AUTO-%s-Backend", var.workspace_name)
backend_address_pool_name                             = format("AUTO-%s-Backend-Pool", var.workspace_name)
rewrite_rule_set_private_name                         = format("AUTO-%s-PRIVATE-RULE-SET", var.workspace_name)
rewrite_rule_private_name                             = format("AUTO-%s-PRIVATE-RW-RULE", var.workspace_name)
affinity_cookie_name                                  = "ApplicationGatewayAffinity"

# Public
frontend_public_ip_configuration_name                 = "appGwPublicFrontendIpIPv4"
frontend_public_port_name                             = "port_443"
http_public_listener_name                             = format("AUTO-%s-Public-LISTENER", var.workspace_name)
request_routing_rule_public_name                      = format("AUTO-%s-PUBLIC-RULE", var.workspace_name)

# Private
frontend_private_ip_configuration_name                = "appGwPrivateFrontendIpIPv4"
  #HTTPS
  frontend_private_port_name                          = "port_443"
  http_private_listener_name                          = format("AUTO-%s-Private-LISTENER", var.workspace_name)
  request_routing_rule_private_name                   = format("AUTO-%s-PRIVATE-RULE", var.workspace_name)
  
  app_gw_ssl_certificate_name                         = format("AUTO-%s-APP-GW-CERT", random_id.random.hex)
  certificate_secret_id                               = var.cert_secret_id

  #HTTP
  frontend_private_port_name2                         = "port_80"
  http_private_listener_name2                         = format("AUTO-%s-Private-LISTENER-80", var.workspace_name)
  request_routing_rule_private_name2                  = format("AUTO-%s-PRIVATE-RULE-80", var.workspace_name)

###################################| N E T W O R K _ S E C U R I T Y _ G R O U P |#######################################

#SPOKE
  network_security_group_spoke_name                          = format("%s-NET-VM-DB-nsg", var.workspace_name)
  network_security_group_spoke_id                            = format("/subscriptions/%s/resourceGroups/%s/providers/Microsoft.Network/networkSecurityGroups/%s", var.subscription_id, local.resource_group_name, local.network_security_group_spoke_name)
  
  db_network_security_group_name                             = format("AUTO-%s-NET-VM-DB-nsg", var.workspace_name)
  db_network_security_group_id                               = format("/subscriptions/%s/resourceGroups/%s/providers/Microsoft.Network/networkSecurityGroups/%s", var.subscription_id, local.resource_group_name, local.db_network_security_group_name)
  db_network_security_rule_name                              = format("AUTO-%s-NET-VM-DB-nsg-rule", var.workspace_name)
#HUB
  network_security_group_hub_name                            = format("%s-NET-VM-HUB-nsg", var.workspace_name)
  network_security_group_hub_id                              = format("/subscriptions/%s/resourceGroups/%s/providers/Microsoft.Network/networkSecurityGroups/%s", var.subscription_id, local.resource_group_name, local.network_security_group_hub_name)

#########################################| V I R T U A L _ M A C H I N E |##############################################

#HUB
  virtual_machine_hub_name                             = format("HUB-%s-VM",random_id.random.hex)                       # at most 15 characters
  network_interface_hub_name                           = format("AUTO-%s-VM-HUB-NIC", var.workspace_name)
  nic_ip_configuration_hub_name                        = format("AUTO-%s-VM-NIC-IP-HUB-CONFIG", var.workspace_name)

#SPOKE
  virtual_machine_spoke_name                             = "AUTO-TestNetConnectivity"                                   # at most 15 characters
  network_interface_spoke_name                           = format("AUTO-%s-VM-SPOKE-NIC", var.workspace_name)
  nic_ip_configuration_spoke_name                        = format("AUTO-%s-VM-NIC-IP-SPOKE-CONFIG", var.workspace_name)

##########################################| K E Y _ V A U L T |#########################################################

key_vault_name                                         = format("AUTO-VM-%s-KEYS", random_id.random.hex)
key_vault_vm_spoke_prv_key_name                        = format("AUTO-Prv-KEY-%s", random_id.random.hex)
key_vault_vm_spoke_pub_key_name                        = format("AUTO-Pub-KEY-%s", random_id.random.hex)
key_vault_certificate_name                             = format("AUTO-%s-GW-CERT", random_id.random.hex)

user_assigned_identity_name                            = format("AUTO-%s-USER-ASSIGNED-IDENTITY", var.workspace_name)

################################################| P U B L I C _ I P |###################################################

gw_public_ip_name = {
  quick = format("AUTO-%s-GW-PUB-IP"   , var.workspace_name)
  bk    = format("AUTO-%s-%s-GW-PUB-IP", var.workspace_name, terraform.workspace)
}                                     
vm_spoke_public_ip_name                               = format("AUTO-%s-VM-SPOKE-IP", var.workspace_name)
vm_hub_public_ip_name                                 = format("AUTO-%s-VM-HUB-PUB-IP", var.workspace_name)

#####################################| P R I V A T E _ E N D P O I N T |################################################

#WEB
  private_endpoint_web_name = {
    quick = format("AUTO-%s-WEB-PRV-ENDPT"   , var.workspace_name)
    bk    = format("AUTO-%s-%s-WEB-PRV-ENDPT", var.workspace_name, terraform.workspace)
  }
  private_service_web_connection_name                   = format("AUTO-%s-PRV-WEB-CONN-SRV", var.workspace_name)
  private_web_dns_zone_group_name                       = "private-web-dns-zone-group"

#STORAGE
  private_endpoint_storage_name                         = format("AUTO-%s-STORAGE-PRV-ENDPT", var.workspace_name)
  private_service_storage_connection_name               = format("AUTO-%s-PRV-STORAGE-CONN-SRV", var.workspace_name)
  private_storage_dns_zone_group_name                   = "private-storage-dns-zone-group"

###################################| V I R T U A L _ N E T W O R K _ P E E R I N G |####################################

virtual_network_spoke_to_hub_peering_name           = "VNET-PEERING-SPOKE-TO-HUB"
virtual_network_hub_to_spoke_peering_name           = "VNET-PEERING-HUB-TO-SPOKE"

#########################################| V I R T U A L _ N E T W O R K |##############################################

spoke_virtual_network_name                          = "QSRP-DHW-Net"
spoke_virtual_network_id                            = format("/subscriptions/%s/resourceGroups/%s/providers/Microsoft.Network/virtualNetworks/%s", var.subscription_id, local.vnet_resource_group_name, local.spoke_virtual_network_name)

hub_virtual_network_name                            = format("AUTO-%s-HUB-VNET", var.workspace_name)
hub_virtual_network_id                              = format("/subscriptions/%s/resourceGroups/%s/providers/Microsoft.Network/virtualNetworks/%s", var.subscription_id, local.vnet_resource_group_name, local.hub_virtual_network_name)

################################################| S U B N E T S |#######################################################

#SPOKE
gw_subnet_name       = {
  quick = format("%s-WebProxy", var.workspace_name)
  bk    = format("Auto-%s-APPG", var.workspace_name)
}

gw_subnet_id                                          = format("%s/subnets/%s", local.spoke_virtual_network_id, local.gw_subnet_name[terraform.workspace])

web_subnet_name                                       = format("Auto-%s-WebSvc", var.workspace_name)
web_subnet_id                                         = format("%s/subnets/%s", local.spoke_virtual_network_id, local.web_subnet_name)

database_subnet_name                                  = format("%s-DB", var.workspace_name)
database_subnet_id                                    = format("%s/subnets/%s", local.spoke_virtual_network_id, local.database_subnet_name)

endpoint_subnet_name                                  = format("%s-Internal", var.workspace_name)
endpoint_subnet_id                                    = format("%s/subnets/%s", local.spoke_virtual_network_id, local.endpoint_subnet_name)

vm_spoke_subnet_name                                  = format("%s-VM", var.workspace_name)
vm_spoke_subnet_id                                    = format("%s/subnets/%s", local.spoke_virtual_network_id, local.vm_spoke_subnet_name)

storage_endpoint_subnet_name                           = "Auto-BBB-Backoffice-storage"
storage_endpoint_subnet_id                             = format("%s/subnets/%s", local.spoke_virtual_network_id, local.storage_endpoint_subnet_name)

#HUB
vm_hub_subnet_name                                    = format("%s-HUB-VM", var.workspace_name)
vm_hub_subnet_id                                      = format("%s/subnets/%s", local.hub_virtual_network_id, local.vm_hub_subnet_name)

########################################################################################################################
}

variable "network_config" {
  type = map(string)
  default = {
      hub_virtual_network                             = "10.64.0.0/18"
        vm_hub_subnet                                 = "10.64.1.0/28"
  }
}

variable "list_ip" {
  type = map(object({
    quick = string
    bk    = string
  }))
  default = {
    talan_ip   = {
      quick                                         = "197.3.0.225/32"
      bk                                            = "197.3.0.225/32"
    }                                      
    gw_prv_ip = {
      quick                                         = "10.9.10.170"
      bk                                            = "10.9.10.10"
    }                                 
  }
}

variable "location" {
  description = "Azure region where resources should be provisioned"
  type        = string
  default     = "northeurope"
}

variable "whitelist_ips" {
  description = "List of IP addresses for IP restriction"
  type = list(object({
    ip_address = string
    prior     = string
    name      = string
  }))
  default = [
    {
      ip_address = "197.3.0.225/32"
      prior      = "1"
      name       = "talan"
    }
  ]
}

variable "whitelist_ips_stg" {
  description = "List of IP addresses for IP restriction"
  type = list(object({
    ip_address = string
    prior     = string
    name      = string
  }))
  default = [
    {
      ip_address = "197.3.0.225/32"
      prior      = "1"
      name       = "talan"
    },
    {
      ip_address = "109.132.149.82/32"
      prior      = "2"
      name       = "Annie"
    },
    {
      ip_address = "81.82.67.213/32"
      prior      = "3"
      name       = "Latoya"
    },
    {
      ip_address = "212.166.13.234/32"
      prior      = "4"
      name       = "BBB1"
    },
        {
      ip_address = "212.166.25.194/32"
      prior      = "5"
      name       = "BBB HQ"
    }
  ]
}

variable "common_tags" {
  description = "Tags to be applied to the resources"
  type = map(string)
  default = {
    department = "restaurant"
    source = "terraform"
  }
}

variable "prod_tags" {
  description = "Tags to be applied to the resources"
  type = map(string)
  default = {
    environment = "prod"
  }
}

variable "test_tags" {
  description = "Tags to be applied to the resources"
  type = map(string)
  default = {
    environment = "test"
  }
}

variable "postgresql_configurations" {
  type = map(string)
  default = {
  #  "azure.extensions"                      = "POSTGIS,PGCRYPTO"
  #  "client_min_messages"                   = "log"
  #  "debug_pretty_print"                    = "on"
  #  "debug_print_parse"                     = "off"
  #  "debug_print_plan"                      = "off"
  #  "debug_print_rewritten"                 = "off"
  #  "log_checkpoints"                       = "on"
  #  "log_duration"                          = "off"
  #  "log_error_verbosity"                   = "verbose"
  #  "log_line_prefix"                       = "%m [%p] %q[user=%u,db=%d,app=%a,client=%h] "
  #  "log_lock_waits"                        = "off"
  #  "log_min_duration_statement"            = "10"
  #  "log_min_error_statement"               = "error"
  #  "log_min_messages"                      = "warning"
  #  "log_statement"                         = "ddl"
  #  "row_security"                          = "on"
  #  "checkpoint_warning"                    = "0"
  #  "connection_throttle.enable"            = "on"
  #  "maintenance_work_mem"                  = "32000"
  #  "min_wal_size"                          = "512"
  #  "max_wal_size"                          = "512"
  #  "pg_stat_statements.track_utility"      = "off"
  #  "pg_qs.track_utility"                   = "off"
  #  "pg_qs.query_capture_mode"              = "top"
  #  "pgaudit.log"                           = "ddl"
  #  "pgms_wait_sampling.query_capture_mode" = "all"
  #  "temp_buffers"                          = "16384"
  #  "wal_buffers"                           = "8192"
  #  "wal_writer_delay"                      = "200"
  #  "wal_writer_flush_after"                = "128"
    "work_mem"                              = "131072"
  }
}
