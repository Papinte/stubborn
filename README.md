Stubborn - Boutique en ligne de sweat-shirts
Bienvenue dans le projet Stubborn, une application e-commerce développée avec Symfony 7.2 pour vendre des sweat-shirts personnalisés.
Prérequis

PHP 8.2 ou supérieur
Composer

Node.js (version 14.x ou supérieure) et npm

MySQL ou PostgreSQL
Clé API Stripe (publique et secrète)

Installation
Pour lancer l’application, suivez ces étapes dans l’ordre. Les assets JavaScript et CSS doivent être compilés avant de démarrer le serveur.

Clonez le dépôt :
git clone [<url-de-ton-depot>](https://github.com/Papinte/stubborn)
cd stubborn


Installez les dépendances PHP :
composer install


Installez les dépendances JavaScript :
npm install


Compilez les assets avec Webpack Encore :
npm run dev


Configurez la base de données dans \texttt{.env} :
DATABASE_URL="mysql://root:@127.0.0.1:3306/stubborn?serverVersion=10.4.32-MariaDB&charset=utf8mb4"


Créez la base de données :
php bin/console doctrine:database:create


Exécutez les migrations :
php bin/console doctrine:migrations:migrate


Configurez les clés Stripe dans {.env} :
STRIPE_PUBLIC_KEY=pk_test_...
STRIPE_SECRET_KEY=sk_test_...


Lancez l’application :

Sur Windows, exécutez :
.\run-tests-and-start.bat

Ce script effectue des tâches supplémentaires (comme exécuter des tests) et démarre le serveur. Assurez-vous d’avoir exécuté \texttt{npm run dev} au préalable.

Sur d’autres systèmes :
symfony server:start





Accès

Boutique : http://localhost:8000/products
Back-office : http://localhost:8000/admin
Connexion/Inscription : http://localhost:8000/login, http://localhost:8000/register


Documentation
Consultez le fichier \texttt{docs/documentation.pdf} pour une description complète de l'application, incluant les fonctionnalités, l'architecture technique, et les instructions d'installation.
