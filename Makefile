# Définitions de variables
BACK_DIR=back
FRONT_DIR=front

# Commandes
.PHONY: start-back start-front start-all

# Lancer le serveur Symfony
start-back:
	cd $(BACK_DIR) && symfony server:start

start-storybook:
	cd $(FRONT_DIR) && pnpm storybook

# Lancer le front-end (npm run dev:all)
start-front:
	cd $(FRONT_DIR) && pnpm dev

# Lancer les deux simultanément grâce à `&`
start-all:
	$(MAKE) start-back & $(MAKE) start-front
