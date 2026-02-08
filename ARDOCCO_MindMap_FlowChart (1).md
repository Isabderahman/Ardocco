# 🏗️ ARDOCCO — Plateforme Immobilière (Maroc)
## Mind Map & Flow Chart — Document de Référence pour Claude

---

## 🧠 MIND MAP — Vue d'ensemble du projet

```
                            ╔═══════════════════════════════╗
                            ║      ARDOCCO — Plateforme     ║
                            ║   Immobilière Terrains Maroc  ║
                            ╚═══════════════╤═══════════════╝
                                            │
       ┌────────────┬────────────┬──────────┼──────────┬─────────────┬────────────┐
       │            │            │          │          │             │            │
  ┌────┴────┐ ┌─────┴─────┐ ┌───┴───┐ ┌────┴────┐ ┌───┴────┐ ┌────┴─────┐ ┌────┴─────┐
  │  SITE   │ │  5 RÔLES  │ │MODULES│ │ CONTRAT │ │        │ │          │ │          │
  │PRINCIPAL│ │UTILISATEUR│ │SPÉCIF.│ │& BUDGET │ │        │ │          │ │          │
  └────┬────┘ └─────┬─────┘ └───┬───┘ └────┬────┘ │        │ │          │ │          │
       │      ┌─────┼─────┐     │          │      │        │ │          │ │          │
       │      │     │     │     │          │      │        │ │          │ │          │
       │   Visiteur │  Agent    │          │      │        │ │          │ │          │
       │   Acheteur │  Expert   │          │      │        │ │          │ │          │
       │   Vendeur  │  Admin    │          │      │        │ │          │ │          │
       │            │           │          │      │        │ │          │ │          │
```

---

## 📌 1. SITE PRINCIPAL

```
SITE PRINCIPAL
│
├── 🏠 Page d'accueil
│     ├── Présentation de la plateforme
│     ├── CTA "Acheter" → Recherche terrains
│     └── CTA "Vendre" → Formulaire annonce
│
├── 🔍 Interface de Recherche (type Airbnb)
│     ├── Carte interactive (Mapbox / Google Maps)
│     ├── Liste de résultats avec aperçu
│     └── Filtres avancés :
│           ├── Prix (min / max)
│           ├── Surface (m²)
│           ├── Localisation (ville / région)
│           └── Rentabilité estimée
│
└── 📄 Page Détail Terrain
      ├── Galerie photos
      ├── Plans cadastraux / parcellaires
      ├── Analyse financière & rentabilité
      └── Formulaire de contact avec l'agent
```

---

## 📌 2. RÔLES & PERMISSIONS (5 profils)

### 👁️ Rôle 1 — Visiteur (NON connecté)

```
VISITEUR (non authentifié)
│
├── ✅ Peut faire :
│     ├── Parcourir la page d'accueil
│     ├── Rechercher sur carte interactive
│     ├── Filtrer (prix, surface, localisation, rentabilité)
│     ├── Voir les pages détail terrain (APERÇU LIMITÉ)
│     └── Contacter via formulaire (avec upload de documents)
│
├── ⛔ NE VOIT PAS les données complètes :
│     ├── Pas de coordonnées du vendeur / agent
│     ├── Pas d'analyse financière détaillée
│     ├── Pas de plans complets
│     └── Pas d'accès au contact direct agent
│
└── 🔒 Doit s'inscrire / se connecter pour accéder aux détails
```

### 🛒 Rôle 2 — Acheteur (connecté)

```
ACHETEUR (utilisateur connecté)
│
├── 🔐 Authentification
│     ├── Inscription (email / mot de passe)
│     ├── Connexion sécurisée (JWT Auth)
│     └── Récupération de mot de passe
│
├── Tout ce que le Visiteur peut faire +
│
├── 📄 Accès complet aux détails terrain
│     ├── Photos complètes
│     ├── Plans cadastraux / parcellaires
│     ├── Analyse financière & rentabilité
│     └── Coordonnées agent / vendeur
│
├── ⭐ Sauvegarder recherches & favoris
│
├── 📩 Demander contact agent
│
├── 📎 Envoyer documents via formulaire
│
└── 📊 Tableau de Bord
      ├── Suivi de ses demandes & échanges
      └── Notifications
```

### 🏷️ Rôle 3 — Vendeur (connecté)

```
VENDEUR (utilisateur connecté)
│
├── Tout ce que l'Acheteur peut faire +
│
├── 📝 CRUD Annonces
│     ├── Créer une annonce
│     ├── Modifier une annonce
│     ├── Supprimer une annonce
│     └── ⚠️ RÈGLE : L'annonce n'est visible publiquement
│           qu'après APPROBATION par l'administrateur
│
├── 🖼️ Gestion Médias
│     ├── Upload photos
│     └── Upload plans
│
└── 📊 Tableau de Bord
      ├── Voir le statut de ses annonces :
      │     ├── ⏳ En attente d'approbation (par défaut)
      │     ├── ✅ Approuvée (visible publiquement)
      │     ├── ❌ Rejetée (avec motif admin)
      │     └── 🔒 Vendue / Désactivée
      ├── Suivi des demandes reçues
      └── Notifications
```

### 🤝 Rôle 4 — Agent (rôle dédié)

```
AGENT
│
├── 📩 Répondre aux demandes de contact
│
├── 👥 Gérer les leads & échanges
│     ├── Suivi des acheteurs intéressés
│     └── Historique des interactions
│
└── 🔗 Associer un terrain à un projet / dossier
```

### 🎓 Rôle 5 — Expert (module "Expertise")

```
EXPERT
│
├── 📋 Produire / Valider expertises
│     ├── Expertise Technique (état du terrain)
│     ├── Expertise Juridique (statut foncier)
│     └── Expertise Financière (évaluation)
│
└── 📎 Attacher pièces & conclusions
      ├── Au terrain concerné
      └── Au dossier / projet associé
```

---

## 📌 3. ESPACE ADMINISTRATEUR (Rôle 6)

```
ADMINISTRATEUR
│
├── 👥 Gestion Utilisateurs
│     ├── Liste / recherche utilisateurs
│     ├── Activation / désactivation comptes
│     └── Gestion Rôles & permissions
│           ├── Visiteur → Acheteur → Vendeur
│           ├── Agent
│           ├── Expert
│           └── Admin
│
├── 📋 Gestion Annonces
│     ├── ✅ Approuver / ❌ Rejeter les nouvelles annonces
│     ├── File d'attente des annonces en attente
│     ├── Modération du contenu
│     ├── Suppression de contenu
│     └── Mise en avant d'annonces
│
├── 📁 Gestion Projets
│     └── Suivi projets immobiliers en cours
│
└── 📈 Outils d'Analyse
      ├── Statistiques de fréquentation
      ├── Performances annonces
      └── Rapports de suivi
```

---

## 📌 4. MODULES SPÉCIFIQUES

```
MODULES SPÉCIFIQUES
│
├── 🗺️ Module Cartographique Interactif
│     ├── Affichage terrains sur carte
│     ├── Zoom / filtrage géographique
│     ├── Clustering de marqueurs
│     └── API : Mapbox ou Google Maps
│
├── 💰 Module Analyse Financière
│     ├── Calcul de rentabilité
│     ├── Estimation de valeur
│     └── Projections financières
│
├── 🎓 Module "Expertise" (→ utilisé par le rôle Expert)
│     ├── Expertise Technique (état du terrain)
│     ├── Expertise Juridique (statut foncier)
│     ├── Expertise Financière (évaluation)
│     └── Pièces jointes & conclusions attachées au terrain/dossier
│
└── 📩 Formulaire de Contact
      ├── Champs classiques (nom, email, message)
      └── Upload de documents (PDF, images)
```

---

## 📌 5. CONTRAT & BUDGET

```
CONTRAT & BUDGET
│
├── 💵 Montant Total : 33 000 MAD HT
│     ├── Acompte initial (signature)   → 30% →  9 900 MAD
│     ├── Livraison version Bêta        → 40% → 13 200 MAD
│     └── Livraison finale & mise en ligne → 30% →  9 900 MAD
│
├── ⏱️ Durée : 9 semaines
│
├── 🛡️ Garantie & Support
│     ├── 15 jours d'assistance post-livraison
│     ├── Corrections bugs incluses
│     └── Période de test client : 2 mois
│
├── 📜 Propriété Intellectuelle
│     ├── Client = propriétaire total après paiement intégral
│     └── Prestataire = droit de mention portfolio
│
├── 🔒 Confidentialité
│     └── Engagement mutuel pendant et après le contrat
│
└── ⚖️ Juridique
      ├── Droit marocain applicable
      ├── Résiliation : mise en demeure 5 jours ouvrables
      └── Tribunaux compétents : Marrakech
```

---

---

## 🔄 FLOW CHART — Parcours de Développement (6 phases)

```
╔══════════════════════════════════════════════════════════════════════════╗
║                      FLOW CHART — PHASES PROJET                        ║
╚══════════════════════════════════════════════════════════════════════════╝

  ┌─────────────────────────────────┐
  │  PHASE 1 — Étude & Conception   │
  │         UX / UI                 │
  │  ● Analyse du besoin            │
  │  ● Wireframes                   │
  │  ● Maquettes Figma              │
  │                                 │
  │  💵 PAIEMENT : 9 900 MAD (30%) │
  └──────────────┬──────────────────┘
                 │
                 ▼
  ┌─────────────────────────────────┐
  │  PHASE 2 — Développement        │
  │         Front-End               │
  │  ● Intégration maquettes        │
  │  ● Pages : Accueil, Recherche,  │
  │    Détail, Espace User/Admin    │
  │  ● Interactivité & responsive   │
  └──────────────┬──────────────────┘
                 │
                 ▼
  ┌─────────────────────────────────┐
  │  PHASE 3 — Développement        │
  │         Back-End & API          │
  │  ● Architecture serveur         │
  │  ● Auth (JWT, CSRF)             │
  │  ● CRUD annonces & utilisateurs │
  │  ● API REST                     │
  └──────────────┬──────────────────┘
                 │
                 ▼
  ┌─────────────────────────────────┐
  │  PHASE 4 — Intégration Modules  │
  │  ● Module Cartographique        │
  │    (Mapbox / Google Maps)       │
  │  ● Module Analyse Financière    │
  │  ● Module Expertise             │
  │  ● Formulaire contact + upload  │
  │                                 │
  │  💵 PAIEMENT : 13 200 MAD (40%)│
  │     (livraison version bêta)    │
  └──────────────┬──────────────────┘
                 │
                 ▼
  ┌─────────────────────────────────┐
  │  PHASE 5 — Tests & Optimisation │
  │  ● Tests fonctionnels           │
  │  ● Tests de sécurité            │
  │  ● Optimisation performances    │
  │  ● SEO & meta tags              │
  │  ● Mise en production           │
  │                                 │
  │  💵 PAIEMENT : 9 900 MAD (30%) │
  │     (livraison finale)          │
  └──────────────┬──────────────────┘
                 │
                 ▼
  ┌─────────────────────────────────┐
  │  PHASE 6 — Formation &          │
  │         Accompagnement          │
  │  ● Formation client (15 jours)  │
  │  ● Corrections bugs incluses    │
  │  ● Période test : 2 mois       │
  │                                 │
  │  ✅ PROJET LIVRÉ & VALIDÉ       │
  └─────────────────────────────────┘
```

---

## 🔄 FLOW CHART — Parcours par Rôle

```
  [Quelqu'un arrive sur ARDOCCO]
              │
              ▼
  ┌───────────────────────┐
  │    Page d'accueil     │
  │  ┌────────┬────────┐  │
  │  │Acheter │ Vendre │  │
  │  └───┬────┴────┬───┘  │
  └──────┼─────────┼──────┘
         │         │
         ▼         ▼
═══════════════════════════════════════════════════════
  PARCOURS VISITEUR (non connecté)
═══════════════════════════════════════════════════════
         │         │
         ▼         │
  ┌──────────┐     │
  │Recherche │     │
  │+ Filtres │     │
  │+ Carte   │     │
  └────┬─────┘     │
       │           │
       ▼           │
  ┌──────────────────┐     │
  │ Liste résultats  │     │
  │ (carte + liste)  │     │
  │ ⚠️ Aperçu limité │     │
  └────┬─────────────┘     │
       │                   │
       ▼                   │
  ┌──────────────────┐     │
  │ Page Détail      │     │
  │ 🔒 Données       │     │
  │   partielles :   │     │
  │  → Titre, photos │     │
  │  → Localisation  │     │
  │  ⛔ Pas de :      │     │
  │  → Plans complets│     │
  │  → Analyse $     │     │
  │  → Contact agent │     │
  └────┬─────────────┘     │
       │                   │
       ▼                   ▼
  ┌───────────────────────────┐
  │  CTA : "Connectez-vous   │
  │   pour accéder à tout"   │
  └─────────────┬─────────────┘
                │
                ▼
═══════════════════════════════════════════════════════
  INSCRIPTION / CONNEXION
═══════════════════════════════════════════════════════
                │
       ┌────────┼────────┬─────────┐
       │        │        │         │
       ▼        ▼        ▼         ▼

  ACHETEUR   VENDEUR   AGENT    EXPERT
       │        │        │         │
       ▼        ▼        ▼         ▼
  ┌─────────┐┌─────────┐┌────────┐┌──────────┐
  │Détails  ││Créer    ││Répondre││Produire  │
  │complets ││annonce  ││demandes││expertise │
  │terrain  ││         ││contact ││technique │
  │         ││         ││        ││juridique │
  │Favoris  ││Upload   ││Gérer   ││financière│
  │         ││photos & ││leads & ││          │
  │Demander ││plans    ││échanges││Attacher  │
  │contact  ││         ││        ││pièces au │
  │agent    ││Suivre   ││Associer││terrain / │
  │         ││statut : ││terrain ││dossier   │
  │Envoyer  ││⏳→Admin ││à un    ││          │
  │documents││✅Publié ││projet  ││          │
  │         ││❌Rejeté ││        ││          │
  │Suivi via││🔒Vendu  ││        ││          │
  │dashboard││         ││        ││          │
  └─────────┘└────┬────┘└────────┘└──────────┘
                  │
                  ▼
═══════════════════════════════════════════════════════
  WORKFLOW APPROBATION ANNONCE
═══════════════════════════════════════════════════════
                  │
  ┌───────────────┴───────────────┐
  │  Vendeur soumet annonce       │
  │  → Statut : ⏳ EN ATTENTE     │
  └───────────────┬───────────────┘
                  │
                  ▼
  ┌───────────────────────────────┐
  │  ADMIN examine l'annonce      │
  │  ┌──────────┬──────────┐      │
  │  │    ✅    │    ❌    │      │
  │  │ Approuver│ Rejeter  │      │
  │  └────┬─────┴────┬─────┘      │
  └───────┼──────────┼────────────┘
          │          │
          ▼          ▼
  ┌────────────┐ ┌────────────────┐
  │ PUBLIÉE    │ │ REJETÉE        │
  │ Visible    │ │ Notif vendeur  │
  │ par tous   │ │ + motif refus  │
  │ (visiteurs │ │ → Peut corriger│
  │ & connectés│ │   et resoumettre│
  └────────────┘ └────────────────┘
```

---

## 🔄 FLOW CHART — Parcours Administrateur

```
  [Admin se connecte]
         │
         ▼
  ┌──────────────────────┐
  │   Dashboard Admin    │
  └──┬────┬────┬────┬────┘
     │    │    │    │
     ▼    ▼    ▼    ▼
  ┌────┐┌────┐┌────┐┌─────────┐
  │User││Ann.││Proj││Analytics│
  │Mgmt││Mgmt││Mgmt││& Stats  │
  └─┬──┘└─┬──┘└─┬──┘└─────────┘
    │      │     │
    ▼      ▼     ▼
  Activer  Valider  Suivre
  Désact.  Modérer  Projets
  Rôles    Suppr.   en cours
           Mettre
           en avant
```

---

> **📎 Note :** Ce document sert de référence structurée pour guider Claude dans le développement, la planification et la compréhension globale du projet ARDOCCO. Chaque module, phase et parcours utilisateur est détaillé pour permettre une assistance précise et contextuelle.
