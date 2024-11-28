plugin "terraform" {
  enabled = true
  preset  = "recommended"
}
plugin "azurerm" {
  enabled = true
  version = "0.24.0"
  source  = "github.com/terraform-linters/tflint-ruleset-azurerm"
}

# Disallow variables, data sources, and locals that are declared but never used.
rule "terraform_unused_declarations" {
enabled = true
}
