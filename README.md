# Mini Application de Gestion des Utilisateurs

Cette application web permet de gérer des utilisateurs avec authentification, rôles, upload de photo, et un tableau de données avec pagination et recherche.

---

## 🛠 Technologies utilisées

### Backend
- **Symfony 6**
- **Doctrine ORM**
- **JWT Authentication** (LexikJWTAuthenticationBundle)
- **PHPUnit** pour les tests unitaires
- **MySQL** pour la base de données
- **VichUploaderBundle** pour la gestion des fichiers (photos)

---

## 📂 Structure du projet

backend/ # Symfony API
├─ src/
| |- Command/ # Commande création compte admin    
│ ├─ Controller/ # Contrôleurs API
│ ├─ Entity/ # Entités Doctrine
│ ├─ DTO/ # Data Transfer Objects
│ ├─ Repository/ # Repositories
│ └─ Service/ # Service
├─ tests/ # Tests unitaires et fonctionnels
├─ config/
│ ├─ packages/
│ │ ├─ security.yaml
│ │ └─ lexik_jwt_authentication.yaml
│ └─ routes.yaml
