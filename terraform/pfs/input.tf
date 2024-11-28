variable "location" {}
variable "resource_group_name" {}

variable "postgresql_flexible_server_name" {}
variable "postgresql_flexible_server_database_name" {}

variable "db_administrator_login" {}
variable "db_administrator_password" {}
variable "postgresql_configurations" {}

variable "subnet_id" {}
variable "private_dns_zone_id" {}

variable "db_server_sku_name" {}
variable "db_zone" {}
variable "storage_mb" {}
variable "is_highly_availible" {}