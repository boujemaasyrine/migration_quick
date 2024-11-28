BASE_GIT_COMMIT         = $(shell git rev-parse --short HEAD)
COMPOSE_FILE_PATH      := -f docker/images/base/docker-compose.yml

build:
	$(info Make: Building base image.)
	@BASE_GIT_COMMIT=$(BASE_GIT_COMMIT) docker compose $(COMPOSE_FILE_PATH) build --no-cache --force-rm

start:
	$(info Make: Starting base image container.)
	@BASE_GIT_COMMIT=$(BASE_GIT_COMMIT) docker compose $(COMPOSE_FILE_PATH) up -d --no-deps phpfpm-apache

stop:
	$(info Make: Starting base image container.)
	@BASE_GIT_COMMIT=$(BASE_GIT_COMMIT) docker compose $(COMPOSE_FILE_PATH) stop

restart:
	$(info Make: Restarting base image container.)
	@make -s stop
	@make -s start

# push:
# 	$(info Make: Pushing "$(BASE_TAG)" BASE_TAGged image.)
# 	@docker push $(IMAGE):$(BASE_TAG)
 
# pull:
# 	$(info Make: Pulling "$(BASE_TAG)" BASE_TAGged image.)
# 	@docker pull $(IMAGE):$(BASE_TAG)
 