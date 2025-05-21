#!/bin/sh
set -e

# Fonction pour attendre que la base de données soit prête
wait_for_db() {
  echo "Waiting for MySQL to be ready..."
  # Boucle jusqu'à ce que la connexion à la BDD fonctionne
  max_attempts=30
  attempt=0
  until php -r "try { \$pdo = new PDO('mysql:host=database;dbname=app', 'root', 'root'); echo \"Database connected!\n\"; exit(0); } catch (PDOException \$e) { echo \"Database connection failed: \" . \$e->getMessage() . \"\n\"; exit(1); };" > /dev/null 2>&1
  do
    attempt=$((attempt+1))
    echo "Database connection attempt $attempt of $max_attempts failed - retrying in 5 seconds..."
    if [ $attempt -ge $max_attempts ]; then
      echo "Could not connect to database after $max_attempts attempts - giving up"
      exit 1
    fi
    sleep 5
  done
  echo "Successfully connected to the database!"
}

# Copier le .env.docker vers .env.local
echo "Setting up environment..."
cp .env.docker .env.local

# Installer les dépendances
echo "Installing Composer dependencies..."
composer install --no-interaction --prefer-dist --optimize-autoloader

# Créer les clés JWT si nécessaire
if [ ! -d config/jwt ]; then
    echo "Generating JWT keys..."
    mkdir -p config/jwt
    php bin/console lexik:jwt:generate-keypair --overwrite
fi

# Attendre que la base de données soit prête
wait_for_db

# Mettre à jour la base de données avec Doctrine
echo "Running database migrations..."
php bin/console doctrine:migrations:migrate --no-interaction || true

echo "Ensuring database schema is up to date..."
php bin/console doctrine:schema:update --force --complete

# Vérifier si des livres existent déjà
book_count=$(php -r "try { 
    \$pdo = new PDO('mysql:host=database;dbname=app', 'root', 'root'); 
    
    # Vérifier si la table book existe
    \$stmt = \$pdo->query(\"SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'app' AND table_name = 'book'\");
    if (\$stmt->fetchColumn() == 0) {
        echo \"0\"; # La table n'existe pas encore
    } else {
        # La table existe, compter les livres
        \$stmt = \$pdo->query('SELECT COUNT(*) FROM book');
        echo \$stmt->fetchColumn();
    }
} catch (PDOException \$e) { 
    echo \"0\"; # En cas d'erreur, on considère qu'il n'y a pas de livres
}")

if [ "$book_count" -eq "0" ]; then
  # Importer les livres depuis l'API Gutendex
  echo "No books found in database. Importing books from Gutendex API..."
  php bin/console app:import-books
else
  echo "Database already contains $book_count books. Skipping import."
fi

# Compiler les assets webpack
echo "Installing and building frontend assets..."
npm install
npm run build

# Vider le cache
echo "Clearing cache..."
php bin/console cache:clear --no-warmup
php bin/console cache:warmup

# Définir les permissions
echo "Setting file permissions..."
chown -R www-data:www-data var

echo "Application ready! Access it at http://localhost:8080"

# Démarrer PHP-FPM
exec php-fpm
