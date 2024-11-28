# Brief Documentation: Building Docker Images with Customized Tags and Workspace

## Purpose

This documentation outlines the procedure for building Docker images with customized tags and workspace configurations using the provided command.

## Requirements
    
    Docker is installed
    Make is installed

## Command Syntax
1. **Build Base image**
   Build the latest base image Using the last commit hash as TAG
```bash
make build-base
```
2. **Build Final image**
   Build the latest final image using the preferred base image
```bash
make build BASE_GIT_COMMIT=<BASE_IMAGE_GIT_COMMIT> APP_VERSION=<APP_VERSION> WORKSPACE=<WORKSPACE_NAME> ENV=<ENVIRONMENT_TAG>
```
3. **Build ALL**
   Build the latest final image with the latest  base image
```bash
make build-all APP_VERSION=<APP_VERSION> WORKSPACE=<WORKSPACE_NAME> ENV=<ENVIRONMENT_TAG>
```
**Parameters**
``` bash

    BASE_GIT_COMMIT: Specifies the git commit hash when base image is built
    
    APP_VERSION: Defines the application version.
    
    WORKSPACE: Indicates the name of the workspace to be configured within the Docker image.
    
    ENV: Specifies the environment tag or configuration for the Docker image.
```

## Usage Example

```bash

make build BASE_BASE_GIT_COMMIT=1 APP_VERSION=1.3 WORKSPACE=bk ENV=dev

```

```bash
Notes
    Replace <BASE_IMAGE_GIT_COMMIT>, <APP_VERSION>, <WORKSPACE_NAME>, and <ENVIRONMENT_TAG> with appropriate values according to your project requirements.
    
    This command simplifies the Docker image building process, enabling quick and efficient customization for various development or deployment needs.
```
