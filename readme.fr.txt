=== Fand Pickup Points FAND : Édition Ultime pour WCFM ===

Contributeurs : fandevelop
Mots-clés : wcfm, woocommerce, pickup points, marketplace, vendor locations
Version minimale requise : 6.9
Testé jusqu'à : 6.9
PHP requis : 8.2
Version stable : 1.0.1
Licence : GPLv2 ou ultérieure
URI de la licence : https://www.gnu.org/licenses/gpl-2.0.html

L'extension Fand Pickup Points WCFM permet à chaque boutique d'une marketplace de personnaliser ses points de retrait et ses horaires d'ouverture.

== Description ==

**Fand Pickup Points : Édition Ultime pour WCFM** est la solution la plus complète pour la gestion des points de retrait locaux dans un environnement multi-vendeurs. Conçue spécifiquement comme une extension performante pour la **Marketplace WCFM**, cette extension assure la liaison entre les ventes en ligne et la proximité physique.
Dans une marketplace moderne, la logistique est essentielle. Cette extension donne aux vendeurs une autonomie totale quant à leur présence physique. Fini les paramètres d'expédition globaux inadaptés à tous les magasins ! Chaque vendeur gère son propre réseau de livraison.

### 🚀 Fonctionnalités clés pour les vendeurs :

* **Gestion indépendante des points de retrait :** Les vendeurs peuvent créer, modifier ou supprimer plusieurs points de retrait directement depuis leur tableau de bord WCFM.
* **Horaires d'ouverture personnalisés :** Pour chaque point de retrait, les vendeurs peuvent définir des créneaux horaires précis pour chaque jour de la semaine. Idéal pour les entreprises avec des horaires décalés ou des plages horaires de retrait spécifiques.
* **Intégration des catégories de magasins :** Contrairement aux solutions de base, cette extension permet un tri et un filtrage avancés basés sur les activités et les catégories des magasins. Les clients trouvent ainsi facilement le bon point de retrait pour le type de produit recherché.
* **Interface cartographique visuelle :** Grâce à l'intégration avec Leaflet et OpenStreetMap, les vendeurs peuvent indiquer leur emplacement exact avec une grande précision.

### 🛠 Contrôle administratif et performances :

* **Intégration WCFM fluide :** L'interface s'intègre parfaitement à l'écosystème WCFM, garantissant une prise en main immédiate pour vos vendeurs.

### 👥 Expérience client améliorée :

* **Carte de retrait interactive :** Les clients peuvent visualiser tous les points de retrait disponibles sur une carte interactive et attrayante.
* **Itinéraire :** Intégration en un clic avec Google Maps pour permettre aux clients de se rendre facilement au magasin du vendeur.
* **Disponibilité en temps réel :** La gestion personnalisée des horaires garantit que les options de retrait ne s’affichent que lorsque le magasin est ouvert.

### Pourquoi choisir les points de retrait FAND ?
Gérer une marketplace exige des outils évolutifs. **Fand Pickup Points** a été conçus pour gérer les situations complexes où les vendeurs ont des horaires d’ouverture, des adresses et des catégories de produits différents. En offrant une expérience professionnelle avec Fand Pickup Points, vous renforcez la confiance de vos clients et augmentez vos taux de conversion.
Que vous gériez une marketplace alimentaire locale, un annuaire de salons d’artisanat ou un réseau mondial de vendeurs, ce plugin fournit l’infrastructure professionnelle nécessaire pour gérer les retraits physiques à grande échelle.

== Services externes ==
Ce plugin utilise des services tiers pour fonctionner pleinement :

1. Google Maps (Itinéraire)
* Service : Fournit des itinéraires et des liens de localisation pour les points de prise en charge.
* Données transmises : L’adresse du point de prise en charge est envoyée aux serveurs de Google lorsqu’un utilisateur clique sur le lien « Itinéraire ». Aucune donnée personnelle n’est transmise automatiquement.
* Conditions d’utilisation : https://www.google.com/intl/en/help/terms_maps/
* Politique de confidentialité : https://policies.google.com/privacy

2. OpenStreetMap (via Leaflet)
* Service : Fournit les tuiles cartographiques affichées sur la carte des points de prise en charge.
* Données transmises : Le navigateur de l’utilisateur demande les tuiles cartographiques directement aux serveurs d’OpenStreetMap. L’adresse IP de l’utilisateur est visible par le service lors de ces requêtes. * Politique de confidentialité : https://osmfoundation.org/wiki/Privacy_Policy

== Installation ==

1. Téléversez les fichiers du plugin dans le répertoire `/wp-content/plugins/fand-pickup-points-ultimate`, ou installez-le directement depuis l’interface d’administration de WordPress.
2. Activez le plugin depuis l’interface « Extensions » de WordPress.
3. Configurez vos points de retrait dans le tableau de bord vendeur WCFM.

== Foire aux questions ==

= Ce plugin est-il compatible avec la version gratuite de WCFM ? =
Oui, il est conçu pour fonctionner avec les versions gratuite et Ultimate de WCFM Marketplace.

= Les vendeurs peuvent-ils définir des horaires différents pour chaque jour ? =
Absolument. Chaque point de retrait peut avoir ses propres horaires d’ouverture et de fermeture pour chaque jour de la semaine.

= Puis-je filtrer les points de retrait par catégorie de magasin ? =
Oui. Le plugin vous permet d'organiser et de trier les points de retrait en fonction des catégories de votre magasin WCFM, facilitant ainsi la recherche des points pertinents pour vos clients.

= Est-il possible de masquer temporairement certains points de retrait ? =
Oui, les vendeurs peuvent activer ou désactiver chaque point de retrait directement depuis leur tableau de bord, sans supprimer les données.

= Le plugin prend-il en charge les marqueurs de carte personnalisés ? =
L'édition Ultimate inclut des marqueurs par défaut, mais elle est également compatible avec les icônes Leaflet standard pour une expérience cartographique plus personnalisée.

== Captures d'écran ==

1. **Liste des activités du back-office :** Interface d'administration pour la gestion de la liste complète des activités du magasin.
2. **Tableau de bord du responsable de magasin :** Vue du vendeur pour la gestion des activités du magasin.
3. **Configuration des horaires :** Vue détaillée du sélecteur de créneaux horaires personnalisés pour chaque point de vente.
4. **Carte client :** Carte interactive Leaflet affichant les points de retrait avec des filtres d'activité.

== Journal des modifications ==

= 1.0.1 =
* Ajout de la recherche par géolocalisation

= 1.0.0 =
* Version initiale. Correction des problèmes de sécurité de la base de données et ajout d'un système de cache pour améliorer les performances.