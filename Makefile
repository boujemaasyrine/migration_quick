####################| N O M O N C L A T U R E |##############

ALLOWED_WORKSPACES := quick bk
ALLOWED_ENVIRONMENTS := dev prod

##########################| B A S E |########################

build-base:
	@make fix-permissions
	@make -f docker/images/base/Makefile build
	@make -s clean

start-base:
	@make -f docker/images/base/Makefile start

stop-base:
	@make -f docker/images/base/Makefile stop

restart-base:
	@make -f docker/images/base/Makefile restart

######################| F I N A L |##########################

build:
	@make check-args
	@make check-base-image-tag
	@make -f docker/Makefile build
	@make -s clean

start:
	@make check-args
	@make -f docker/Makefile start

stop:
	@make check-args
	@make -f docker/Makefile stop

restart:
	@make -s restart

########################| A L L |##########################

build-all:
	@make check-args
	@make build-base
	$(eval BASE_GIT_COMMIT := $(shell docker images --filter=reference='phpfpm-apache' --format "{{.Tag}}" | awk 'NR==1' | awk -F'-' '{print $$2}'))
	@echo "BASE_GIT_COMMIT is set to $(BASE_GIT_COMMIT)"
	@echo "Running build with BASE_GIT_COMMIT=$(BASE_GIT_COMMIT)"
	@make build BASE_GIT_COMMIT=$(BASE_GIT_COMMIT)

start-all:
	@make start-base
	@make start

stop-all:
	@make stop-base
	@make stop

restart-all:
	@make restart-base
	@make restart

###########################################################

check-base-image-tag:
ifndef BASE_GIT_COMMIT
	$(error BASE_GIT_COMMIT is missing.)
endif

check-args:
ifndef WORKSPACE
	$(error The WORKSPACE variable is missing.)
endif

ifeq ($(filter $(WORKSPACE),$(ALLOWED_WORKSPACES)),)
	$(error Invalid value for WORKSPACE: $(WORKSPACE))
endif

ifndef ENV
	$(error The ENV variable is missing.)
endif

ifeq ($(filter $(ENV), $(ALLOWED_ENVIRONMENTS)),)
	$(error Invalid value for ENV: $(ENV))
endif

ifndef APP_VERSION
	$(error The APP_VERSION variable is missing.)
endif

clean:
	@docker system prune --volumes --force

fix-permissions:
	@./docker/images/base/fix-permissions.sh

###########################################################