GIT_COMMIT            = $(shell git rev-parse --short HEAD)
COMPOSE_FILE_PATH    := -f docker/docker-compose.yml

build:
	$(info Make: Building "$(ENV)" environment image for "$(WORKSPACE)" workspace.)
	@APP_VERSION=$(APP_VERSION) GIT_COMMIT=$(GIT_COMMIT) docker compose --verbose $(COMPOSE_FILE_PATH) build --no-cache --force-rm 

start:
	$(info Make: Starting "$(ENV)" environment container for "$(WORKSPACE)" workspace.)
	@APP_VERSION=$(APP_VERSION) GIT_COMMIT=$(GIT_COMMIT) docker compose $(COMPOSE_FILE_PATH) up -d
 
stop:
	$(info Make: Stopping "$(ENV)" environment container for "$(WORKSPACE)" workspace.)
	@APP_VERSION=$(APP_VERSION) GIT_COMMIT=$(GIT_COMMIT) docker compose $(COMPOSE_FILE_PATH) stop

restart:
	$(info Make: Restarting "$(ENV)" environment container for "$(WORKSPACE)" workspace.)
	@make -s stop
	@make -s start
 
# push:
# 	$(info Make: Pushing "$(TAG)" tagged image.)
# 	@docker push $(IMAGE):$(TAG)
 
# pull:
# 	$(info Make: Pulling "$(TAG)" tagged image.)
# 	@docker pull $(IMAGE):$(TAG)
 