# Revue de Sécurité - Symfony with Bug

Cette documentation liste les vulnérabilités identifiées dans le projet.

## 1. Injections
- **SQL Injection** : Présente dans `AdminController::executeQuery`, `ProductController::createProduct`, `UserController::searchUser`, `UserRepository::findByUsernameUnsafe` et `UserRepository::debugQuery`.
- **Command Injection** : Présente dans `AdminController::executeCommand`, `AdminController::downloadBackup` et `FileController::fileInfo`.
- **Code Execution (RCE)** : Présente via `eval()` dans `UserService::calculateDiscount` et via la désérialisation non sécurisée.

## 2. Contrôle d'Accès Défaillant
- **Absence d'Authentification/Autorisation** : Les routes `/admin/*` ne sont pas protégées dans `AdminController` et `security.yaml`.
- **IDOR (Insecure Direct Object Reference)** : `UserController::deleteUser` permet de supprimer n'importe quel utilisateur via son ID.
- **Mass Assignment** : `UserController::updateUser` permet de modifier n'importe quel champ de l'entité `User`, y compris `roles` et `isAdmin`.

## 3. Exposition de Données Sensibles
- **Données Utilisateurs** : `AdminController::dashboard` expose les mots de passe et clés API en clair.
- **Secrets codés en dur** : Présents dans `AdminController`, `UserService`, `CacheService` et `security.yaml`.
- **Mots de passe en clair** : L'entité `User` stocke `plainPassword` en base de données et le hasher est configuré en `plaintext`.
- **Logs non sécurisés** : `UserService::logUserLogin` enregistre les mots de passe dans un fichier lisible par tous dans `/tmp`.

## 4. Inclusions de Fichiers et SSRF
- **Path Traversal** : `FileController::downloadFile`, `FileController::readFile` et `AdminController::viewLogs` permettent de lire des fichiers arbitraires du système.
- **SSRF (Server-Side Request Forgery)** : `FileController::fetchRemoteFile` permet d'effectuer des requêtes vers des services internes.

## 5. Téléchargement de Fichiers (Unrestricted File Upload)
- `FileController::uploadFile` n'effectue aucune validation sur le type, l'extension ou la taille des fichiers.

## 6. Désérialisation Non Sécurisée
- Utilisation de `unserialize()` sur des données contrôlées par l'utilisateur dans `ProductController::importProducts`, `CacheService::get` et `UserService::createSession`.

## 7. Cross-Site Scripting (XSS)
- `UserController::showProfile` affiche le contenu de `bio` sans échappement HTML.

## 8. Configuration de Sécurité Faible
- **Mode Debug** : Activé dans `public/index.php`.
- **CORS** : Configuration permissive (`*`) dans `AdminController::getConfig`.
- **Tokens Prédictibles** : `UserService::generateToken` et `generateResetToken` utilisent des algorithmes faibles (md5, base64).
- **Gestion des Sessions** : Identifiants de session prédictibles (ID utilisateur) et stockage non sécurisé dans `/tmp`.

## 9. Autres Vulnérabilités
- **Race Condition** : `ProductController::purchaseProduct` est vulnérable au TOCTOU (Time-of-Check to Time-of-Use).
- **Open Redirect** : `UserController::loginRedirect` redirige vers n'importe quelle URL fournie par l'utilisateur.
- **ReDoS (Regex Denial of Service)** : `UserService::validateInput` utilise une regex vulnérable au backtracking catastrophique.
- **Type Juggling** : `ProductController::verifyCoupon` utilise une comparaison lâche (`==`).
- **Timing Attacks** : Comparaison de clés API et de mots de passe non sécurisée.
