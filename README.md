# PHP_Projet
Projet de PHP avec Symphony


# Pour installer le projet


```sh
composer install
bin/console doctrine:database:create
bin/console doc:sc:up -f
bin/console doctrine:fixtures:load
yarn install (si vous utilisez des dépendances pour le front)
yarn build (si vous utilisez des dépendances pour le front)
symfony server:start 
