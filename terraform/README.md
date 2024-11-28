## I- Terraform Authentication
1. **install terraform:**\
[Download link](https://developer.hashicorp.com/terraform/downloads)
2. **install azure cli (az)**
3. Run the following commands
``` bash
 az login 
 az account show
 az account list --query "[?user.name=='<microsoft_account_email>'].{Name:name, ID:id, Default:isDefault}" --output Table
 az account set --subscription "<subscription_id_or_subscription_name>"
```
Referances: \
[Azure Documentation](https://learn.microsoft.com/en-us/azure/developer/terraform/get-started-cloud-shell-bash?tabs=bash) \
[Terraform Documentation](https://registry.terraform.io/providers/hashicorp/azurerm/latest/docs/guides/azure_cli)

## II - Get Started:
1. **Define global variables:**
```bash
    RESOURCE_GROUP_NAME=tfstate
    CONTAINER_REGISTRY_NAME=acr$RANDOM
    STORAGE_ACCOUNT_NAME=tfstate$RANDOM
    CONTAINER_NAME=tfstate
 ```
2. **Create a resource group**
```bash
    az group create --name $RESOURCE_GROUP_NAME --location northeurope
```
## III - Prepare Docker Image:

1. **Create an azure Container Registry**
```bash
    az acr create --resource-group $RESOURCE_GROUP_NAME --name $CONTAINER_REGISTRY_NAME --sku Basic --admin-enabled true
```
2. **Push your image in azure Container Registry**
```bash
    az acr login --name $CONTAINER_REGISTRY_NAME
    docker tag localImage:<image_tag> $CONTAINER_REGISTRY_NAME.azurecr.io/bo-quick:<image_tag> 
    docker push $CONTAINER_REGISTRY_NAME.azurecr.io/bo-quick:tag    
```
3. **Get Container Registry details**
```bash
    echo $CONTAINER_REGISTRY_NAME.azurecr.io && az acr credential show -n $CONTAINER_REGISTRY_NAME  --resource-group $RESOURCE_GROUP_NAME --query passwords[0].value
```
## IV- Prepare State Storage:

1. **Create a Storage account and a container to save your terraform state**
```bash
    az storage account create --resource-group $RESOURCE_GROUP_NAME --name $STORAGE_ACCOUNT_NAME --sku Standard_LRS --encryption-services blob
    az storage container create --name $CONTAINER_NAME --account-name $STORAGE_ACCOUNT_NAME
```
2. **Configure terraform backend state**
```bash
    ACCOUNT_KEY=$(az storage account keys list --resource-group $RESOURCE_GROUP_NAME --account-name $STORAGE_ACCOUNT_NAME --query '[0].value' -o tsv)
    export ARM_ACCESS_KEY=$ACCOUNT_KEY
```
Referances: \
[Container Registry](https://learn.microsoft.com/en-us/azure/container-registry/container-registry-get-started-azure-cli) \
[Store state in azure storage](https://learn.microsoft.com/en-us/azure/developer/terraform/store-state-in-azure-storage?tabs=azure-cli#code-try-3) 


## ToDo:
 Document ** Every Manual config ** \
1. How to push an image to docker registry
2. How to configure SSL certificate
3. How to configure Dns zone + record 
   