variable "prefix" {}

variable "vnet_resource_group_name" {}

variable "network_config" {
  type = map(string)
  default = {
    dns_resolver_subnet           = "10.9.10.32/28"
  }
}
variable "dns_resolver_exist" {}