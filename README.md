# RALegal — Étude de Me Ridha Ajmi

Site web de l'étude d'avocats **RALegal**, Me Ridha Ajmi — Fribourg & Genève, Suisse.

## À propos

Site vitrine (single-page) pour un cabinet d'avocats fondé en 2006. Contenu récupéré depuis les archives (Wayback Machine) de l'ancien site ralegal.ch, puis reconstruit avec un design moderne.

- **Langues de travail :** français, anglais, arabe
- **Bureaux :** Fribourg (siège) & Genève
- **Domaines :** droit commercial, des sociétés, des contrats, des personnes, des étrangers, du travail, civil & famille, poursuites & faillites, médiation

## Structure

```
new_site/
  index.html      — page unique (design responsive)
  img/            — images (Unsplash, libres de droits)
```

## Design

- Palette : navy + or
- Typographie : Cormorant Garamond (titres) + Inter (texte)
- Icônes SVG inline (pas d'emojis)
- Images : objets/architecture uniquement (pas de figures humaines)

## Déploiement

Site statique — servir `new_site/` avec n'importe quel serveur HTTP :

```bash
cd new_site && python3 -m http.server 8091
```
