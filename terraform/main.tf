module "common" {
  source = "./common"
  
  gw_sku = var.gw_sku
  ap_sku_name                      = local.ap_sku_name[terraform.workspace]

  docker_image_dev_tag             = local.docker_image_dev_tag[terraform.workspace]
  docker_image_prod_tag            = local.docker_image_prod_tag[terraform.workspace]
  docker_registry_repository       = local.docker_registry_repository[terraform.workspace]
  docker_registry_username         = local.docker_registry_username
  docker_registry_password         = local.docker_registry_password
  docker_registry_login            = local.docker_registry_login

  dns_zone_name                    = local.dns_zone_name[terraform.workspace]
  cert_secret_id                   = local.cert_secret_id[terraform.workspace]

  # prod_database_host               = module.prod_pfs.pfs_fqdn
  # prod_database_user               = module.prod_pfs.pfs_username
  # prod_database_password           = module.prod_pfs.pfs_password
  # prod_database_name               = module.prod_pfs.pfs_database_name

  # test_database_host               = module.prod_pfs.pfs_fqdn
  # test_database_user               = module.prod_pfs.pfs_username
  # test_database_password           = module.prod_pfs.pfs_password
  # test_database_name               = module.prod_pfs.pfs_database_name

  storage_account_access_key       = local.storage_account_access_key

  vm_admin_username                = local.vm_admin_username
}

module "prod_pfs" {
  source = "./pfs"
  is_highly_availible = true

  resource_group_name                      = local.resource_group_name
  location                                 = var.location 

  postgresql_flexible_server_name          = local.postgresql_flexible_server_name[terraform.workspace]
  postgresql_flexible_server_database_name = local.postgresql_flexible_server_database_name[terraform.workspace]
  postgresql_configurations                = var.postgresql_configurations
  
  storage_mb                               = local.storage_mb[terraform.workspace]
  db_zone                                  = local.db_zone[terraform.workspace]

  db_administrator_login                   = local.db_administrator_login[terraform.workspace]
  db_administrator_password                = local.db_administrator_password[terraform.workspace]

  subnet_id                                = local.database_subnet_id
  private_dns_zone_id                      = local.private_dns_zone_db_id

  db_server_sku_name                       = local.db_server_sku_name[terraform.workspace]

  depends_on = [ module.common ]
}

module "test_pfs" {
  source = "./pfs"
  count               = terraform.workspace == "bk" ? 1 : 0
  is_highly_availible = false

  resource_group_name                      = local.resource_group_name
  location                                 = var.location

  postgresql_flexible_server_name          = local.postgresql_flexible_server_name["test"]
  postgresql_flexible_server_database_name = local.postgresql_flexible_server_database_name["test"]
  postgresql_configurations                = var.postgresql_configurations
  
  storage_mb                               = local.storage_mb["test"]
  db_zone                                  = local.db_zone["test"]

  db_administrator_login                   = local.db_administrator_login["test"]
  db_administrator_password                = local.db_administrator_password["test"]

  subnet_id                                = local.database_subnet_id
  private_dns_zone_id                      = local.private_dns_zone_db_id

  db_server_sku_name                       = local.db_server_sku_name["test"]

  depends_on = [ module.common ]
}

# module "network" {
#   source = "./network"
#   prefix = var.workspace_name
#   dns_resolver_exist = false

#   vnet_resource_group_name = local.vnet_resource_group_name

#   depends_on = [ module.common ]
# }

# module "dns_resolver" {
#   source = "./private_dns_resolver"
#   location = var.location
#   resource_group_name = local.resource_group_name
#   private_dns_resolver_inbound_endpoint_name = local.private_dns_resolver_inbound_endpoint_name
  
#   virtual_network_id = module.network.spoke_virtual_network_id
#   subnet_id          = module.network.dns_resolver_subnet_id
  
#   depends_on = [ module.prod_pfs, module.test_pfs, module.network]
# }