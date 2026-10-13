/** @type {import('next').NextConfig} */
module.exports = {
  reactStrictMode: true,
  // `images.domains` est déprécié : `remotePatterns` est la forme maintenue,
  // plus fine (protocole / port / chemin contrôlables séparément).
  // Mêmes hôtes qu'avant, protocoles http et https, tout port.
  images: {
    remotePatterns: [
      { protocol: 'http', hostname: 'localhost' },
      { protocol: 'https', hostname: 'localhost' },
      { protocol: 'http', hostname: '127.0.0.1' },
      { protocol: 'https', hostname: '127.0.0.1' },
    ],
  },

  // Le dépôt contient deux lockfiles (racine outillage + `frontent/`). Sans
  // cette racine explicite, Next/Turbopack remonte au lockfile de la racine du
  // dépôt et avertit que la racine de workspace est probablement incorrecte —
  // ce qui fausse aussi la résolution de `node_modules` pendant `next dev`.
  turbopack: {
    root: __dirname,
  },

  // `apiClient` utilise une baseURL relative (`/api/v1`, cf. src/lib/api.ts) :
  // le navigateur appelle donc Next.js sur le port 3000, pas Laravel sur 8000.
  // Sans ce proxy, Next ne connaît pas la route et renvoie 404 — c'est la cause
  // des échecs de chargement de la carte (log « US-015 Échec du chargement »).
  //
  // Cible du proxy :
  //  - `backend:8000` par défaut : dans la stack Docker (cf. docker-compose.yml),
  //    `localhost` désignerait le conteneur frontend lui-même, d'où un
  //    ECONNREFUSED 127.0.0.1:8000. `backend` est le nom de service Laravel.
  //  - `API_PROXY_TARGET` pour surcharger (ex. `next dev` hors conteneur :
  //    API_PROXY_TARGET=http://localhost:8000 npm run dev).
  rewrites: async () => [
    {
      source: '/api/:path*',
      destination: `${process.env.API_PROXY_TARGET ?? 'http://backend:8000'}/api/:path*`,
    },
  ],
}