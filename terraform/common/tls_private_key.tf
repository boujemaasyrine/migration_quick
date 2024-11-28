resource "tls_private_key" "terraform_generated_private_key" {
  algorithm = "RSA"
  rsa_bits  = 4096
}
