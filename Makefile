# Définitions de variables
BACK_DIR=back
FRONT_DIR=front

# Commandes
.PHONY: start-back start-front start-all create-symlinks

# Créer les liens symboliques
create-symlinks:
	./install_assets.sh

# Lancer le serveur Symfony
start-back:
	cd $(BACK_DIR) && symfony server:start

start-storybook: create-symlinks
	cd $(FRONT_DIR) && pnpm storybook

# Lancer le front-end (npm run dev:all)
start-front: create-symlinks
	cd $(FRONT_DIR) && pnpm dev

# Lancer les deux simultanément grâce à `&`
start-all:
	$(MAKE) start-back & $(MAKE) start-front

# lancer la console de docker
cli:
	docker exec -it uniservice-web /bin/bash && cd /var/www/uniservice
