# PageOne
An online book reading application

## 🐳 Docker Setup

Ce projet est configuré pour fonctionner avec Docker Desktop, ce qui facilite l'installation et le démarrage sans avoir à configurer manuellement PHP, MySQL, etc.

### Prérequis
- [Docker Desktop](https://www.docker.com/products/docker-desktop/) installé sur votre machine
- Compatible avec Windows, macOS et Linux

### Commandes pour lancer l'application

1. **Cloner le projet** (si ce n'est pas déjà fait)

2. **Nettoyer l'environnement pour éviter les conflits** :
   ```
   docker compose down --remove-orphans
   ```

3. **Reconstruire les images Docker** (nécessaire seulement la première fois ou après des modifications) :
   ```
   docker compose build
   ```

4. **Lancer les conteneurs Docker** :
   ```
   docker compose up -d
   ```
   Cette commande démarre tout le processus d'installation et de configuration automatiquement.

5. **Vérifier que tout fonctionne** :
   ```
   docker compose ps
   ```
   Vous devriez voir tous les conteneurs en état "running".

6. **Accéder à l'application** :
   - Application web : [http://localhost:8080/login](http://localhost:8080)

> **Note** : Lors du premier démarrage, l'application effectue automatiquement les opérations suivantes :
> - Installation des dépendances
> - Mise à jour du schéma de la base de données
> - Importation des livres depuis l'API Gutendex
> - Compilation des assets frontend
> 
> Tout cela est géré par le script docker-entrypoint.sh. Vous n'avez plus besoin d'exécuter ces commandes manuellement !

### Autres commandes utiles

- **Arrêter les conteneurs** :
  ```
  docker compose down
  ```

- **Voir les logs en temps réel** :
  ```
  docker compose logs -f
  ```

- **Reconstruire et redémarrer les services** :
  ```
  docker compose up -d --build
  ```

- **Exécuter une commande dans le conteneur PHP** :
  ```
  docker compose exec php php bin/console cache:clear
  docker compose exec php composer require symfony/package-name
  ```

- **Accéder à la base de données** :
  ```
  docker compose exec database mysql -uroot -proot app
  ```

- **Lancer les migrations de base de données** :
  ```
  docker compose exec php php bin/console doctrine:migrations:migrate
  ```

- **Importer manuellement des livres depuis l'API Gutendex** (déjà fait automatiquement au démarrage) :
  ```
  docker compose exec php php bin/console app:import-books
  ```

- **Réinitialiser complètement l'environnement** (supprime les volumes) :
  ```
  docker compose down -v
  ```

### Résolution des problèmes courants

- Si vous avez des conflits de ports (8080 déjà utilisé), modifiez le fichier compose.yaml pour changer le port exposé, par exemple:
  ```yaml
  nginx:
    ports:
      - "8081:80"  # Utilise le port 8081 au lieu de 8080
  ```
