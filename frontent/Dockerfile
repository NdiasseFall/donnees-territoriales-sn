# =============================================================================
# Frontend Next.js (App Router) — frontent/
# Build multi-stage : dépendances → compilation → exécution production
# =============================================================================

# --- Étape 1 : dépendances ---------------------------------------------------
FROM node:22-alpine AS deps
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci

# --- Étape 2 : build ---------------------------------------------------------
FROM node:22-alpine AS build
WORKDIR /app
COPY . .
COPY --from=deps /app/node_modules ./node_modules

# URL de l'API injectée à la compilation (inlinée par Next.js côté navigateur).
ARG NEXT_PUBLIC_API_URL=http://localhost:8000/api/v1
ENV NEXT_PUBLIC_API_URL=${NEXT_PUBLIC_API_URL}
ENV NEXT_TELEMETRY_DISABLED=1
RUN npm run build

# --- Étape 3 : exécution -----------------------------------------------------
FROM node:22-alpine AS runner
WORKDIR /app
ENV NODE_ENV=production
ENV NEXT_TELEMETRY_DISABLED=1
COPY --from=build /app ./

EXPOSE 3000

CMD ["npm", "start"]
