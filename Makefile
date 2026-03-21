BACK_DIR=back

# Commandes
.PHONY: start-back start-front start-all

# Lancer le serveur Symfony
start-back:
	cd $(BACK_DIR) && symfony server:start

# Lancer le front-end (npm run dev)
start-front:
	npm run dev

# Lancer les deux simultanément grâce à `&`
start-all:
	$(MAKE) start-back & $(MAKE) start-front

# lancer la console de docker
cli:
	docker exec -it uniservice-web /bin/bash && cd /var/www/uniservice
