# TntSearch Module

## Description

TntSearch est un module Symfony pour Thelia qui fournit un système de recherche avancé et performant. Il s'agit d'une implémentation basée sur la bibliothèque TNT Search pour offrir des fonctionnalités de recherche full-text rapides et efficaces.

## Caractéristiques

- **Recherche full-text** : Recherche rapide et précise dans le contenu
- **Indexation automatique** : Index automatique des données pour des performances optimales
- **Support multi-langue** : Gestion des recherches dans différentes langues
- **Stemming** : Traitement linguistique pour améliorer la pertinence des résultats
- **Stop words** : Filtrage des mots vides pour optimiser les recherches
- **Logging des recherches** : Enregistrement des requêtes pour analyse

## Structure du Module
## Installation

1. Placez le module dans le dossier `local/modules/TntSearch`
2. Activez le module depuis l'administration Thelia
3. Configurez les paramètres selon vos besoins

## Configuration

### Fichiers de Configuration

- `config.xml` : Configuration générale du module
- `module.xml` : Métadonnées du module
- `routing.xml` : Routes du module
- `schema.xml` : Schéma de base de données

### Base de Données

Le module utilise plusieurs tables pour stocker :
- Les index de recherche
- Les logs de recherche
- Les configurations

## Utilisation

### Indexation

Le module indexe automatiquement :
- **Produits** : Noms, descriptions, références
- **Catégories** : Noms et descriptions
- **Marques** : Informations sur les marques
- **Contenus** : Pages et articles
- **Clients** : Données clients (si activé)
- **Commandes** : Informations de commande

### Recherche Front-Office

La recherche est disponible via :
- Interface de recherche standard
- API REST pour intégrations personnalisées
- Boucles Thelia pour templates

### Administration

L'interface d'administration permet :
- Configuration des paramètres de recherche
- Gestion des index
- Consultation des logs de recherche
- Réindexation manuelle

## API et Hooks

### Services Principaux

- `ItemIndexation` : Service d'indexation
- `Search` : Service de recherche
- `Stemmer` : Service de stemming
- `StopWord` : Gestion des mots vides

### Événements

- `ExtendQueryEvent` : Extension des requêtes
- `SaveRequestEvent` : Sauvegarde des requêtes
- `StemmerEvent` : Traitement de stemming
- `StopWordEvent` : Filtrage des mots vides
- `WeightEvent` : Calcul des poids

## Commandes Console

Le module fournit des commandes pour :
- Réindexation complète
- Nettoyage des index
- Optimisation des performances

## Performances

### Index Stockés

Les index sont stockés dans `local/TNTIndexes/` :
- `brand_fr_FR.index`
- `category_fr_FR.index`
- `content_fr_FR.index`
- `customer.index`
- `folder_fr_FR.index`
- `order.index`
- `product_fr_FR.index`

### Optimisations

- Index pré-calculés pour des recherches rapides
- Cache des résultats fréquents
- Tokenisation efficace du texte
- Algorithmes de stemming optimisés
- Recherche par phrase

## Compatibilité

- **Thelia** : Version 2.5+

## Support Multi-langue

Le module supporte nativement :
- Français (fr_FR)
- Autres langues via configuration

## Maintenance

### Tâches Régulières

- Réindexation périodique
- Nettoyage des logs anciens
- Optimisation des index
- Surveillance des performances

### Dépannage

- Vérification des permissions sur les fichiers d'index
- Contrôle de l'espace disque
- Analyse des logs d'erreur

## Licence

Ce module est distribué sous licence compatible avec Thelia.

## Support
