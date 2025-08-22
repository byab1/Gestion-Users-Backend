# Mini Application de Gestion des Utilisateurs

Cette application web permet de gérer des utilisateurs avec authentification, rôles, upload de photo, et un tableau de données avec pagination et recherche.

---

## 🛠 Technologies utilisées

### Backend
- **Symfony 7.3**
- **Doctrine ORM**
- **JWT Authentication** (LexikJWTAuthenticationBundle)
- **PHPUnit** pour les tests unitaires
- **MySQL** pour la base de données
- **VichUploaderBundle** pour la gestion des fichiers (photos)

---

## 📂 Structure du projet

![Texte alternatif](doc/structure.png)


---

## ⚙️ Instructions d’installation

### 1. Backend Symfony

1. Cloner le dépôt :

```bash
git clone <repo-url>
cd backend 
```

2. Installer les dépendances :

```bash
composer install
```

3. Configurer la base de données .env :

```bash
DATABASE_URL="mysql://user:password@127.0.0.1:3306/api_gestion_users_db"
```

4. Créer la base de données et le schéma

```bash
php bin/console doctrine:database:create
php bin/console doctrine:schema:create
```

5. Générer les clés JWT :

```bash
php bin/console lexik:jwt:generate-keypair
``` 

6. Lancer le serveur Symfony :
```bash
symfony serve
# ou
php -S 127.0.0.1:8000 -t public
``` 

### 2. TESTS

Exécuter les tests PHPUnit :

```bash
php bin/phpunit
```

Assurez-vous que la base de données test est configurée dans .env.test :

```bash
DATABASE_URL="mysql://user:password@127.0.0.1:3306/api_gestion_users_db_test"
```
Exemple: Lancer un seul test précis dans un fichier
```bash
./vendor/bin/phpunit --filter testListUsers tests/Controller/UserControllerTest.php
```
