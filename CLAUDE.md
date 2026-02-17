# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Ardocco is a Moroccan real estate platform specializing in land listings (terrains) in the Casablanca-Settat region. The platform features an Airbnb-style search interface with map integration, listing management workflows, and AI-powered document processing.

## Architecture

The project is a monorepo with four main applications:

```
/
├── frontEnd/       # Public-facing Nuxt 4 site (ardocco.com)
├── appDashboard/   # Authenticated dashboard Nuxt 4 app (app.ardocco.com)
├── backEnd/        # Laravel 12 API (api.ardocco.com)
├── ai/             # Node.js Express AI service (ai.ardocco.com)
└── deploy/         # Docker Compose deployment configs
```

### Frontend (frontEnd/)
- Nuxt 4 with @nuxt/ui component library
- Leaflet maps for terrain visualization
- Server-side proxies requests to backend via `/api/backend/*`
- Uses pnpm as package manager

### Dashboard (appDashboard/)
- Separate Nuxt 4 app for authenticated users
- Shares auth cookies with frontend via configurable `cookieDomain`
- Role-based access: vendeur, agent, expert, admin

### Backend (backEnd/)
- Laravel 12 with Sanctum token authentication
- SQLite in development, MySQL in production
- Queue-based jobs for investment study generation
- API prefix: `/api`

### AI Service (ai/)
- Express.js server using OpenAI GPT-4o
- Processes cadastral plans, land titles, generates investment studies
- Requires `X-AI-Secret-Key` header in production

## Development Commands

### Backend (from `backEnd/`)
```bash
composer dev          # Runs server, queue, logs, vite concurrently
composer test         # Run PHPUnit tests
php artisan serve     # API server only (port 8000)
php artisan pint      # Code formatting
```

### Frontend/Dashboard (from `frontEnd/` or `appDashboard/`)
```bash
pnpm install
pnpm dev              # Development server (port 3000/3001)
pnpm lint             # ESLint
pnpm typecheck        # TypeScript check
pnpm build            # Production build
```

### AI Service (from `ai/`)
```bash
npm install
npm run dev           # Development with watch (port 8003)
npm start             # Production
```

### Docker Deployment (from root)
```bash
./deploy.sh           # Full stack deployment
docker compose -f deploy/docker-compose.yml logs -f
```

## Key Domain Concepts

### Listing Workflow States
`brouillon` -> `soumis` -> `en_revision`/`approuve` -> `publie` -> `vendu`/`refuse`

### User Roles
- **visiteur**: Browse public listings (limited data)
- **acheteur**: Full listing access, favorites, contact requests
- **vendeur**: Create/manage own listings
- **agent**: Review listings, approve/reject, manage leads, validate fiches (expert actions)
- **admin**: Full platform management including all agent/expert actions

### Listing Data Structure
Each listing has associated fiches:
- `FicheTechnique`: Land characteristics, topography, utilities
- `FicheFinanciere`: Price analysis, investment ratios
- `FicheJuridique`: Title status, legal encumbrances
- `EtudeInvestissement`: AI-generated investment studies

## API Structure

### Authentication
All authenticated endpoints use Bearer token via Sanctum:
```
Authorization: Bearer {token}
```

### Main Endpoints
- `POST /api/auth/register|login|logout`
- `GET|POST /api/listings` - CRUD for listings
- `GET /api/public/listings` - Public search
- `GET /api/geo/*` - Morocco geographic data
- `/api/agent/*` - Agent review workflow (agent, admin)
- `/api/admin/*` - Admin management (admin only)
- `/api/expert/*` - Fiche validation (agent, admin)

### AI Service Endpoints
- `POST /api/process/plan-cadastral` - Extract cadastral plan data
- `POST /api/process/titre-foncier` - Extract land title data
- `POST /api/estimate-price` - AI price estimation
- `POST /api/analyze-plans` - Analyze architectural plans
- `POST /api/investment-study/suggest` - Investment recommendations

## Frontend Patterns

### Composables
Located in `frontEnd/app/composables/`:
- `useAuth()` - Authentication state and methods
- `usePublicListings()` - Search and filter listings
- `useCreateListing()` - Listing creation flow
- `useAdminDashboard()` - Admin stats and management

### API Proxy
Frontend proxies backend requests via Nuxt server routes. Client code uses `/api/backend/*` which maps to the Laravel API.

## Environment Variables

### Backend (.env)
- `AI_SERVICE_URL` - AI service endpoint (default: http://localhost:8003)
- `DB_CONNECTION` - sqlite (dev) or mysql (prod)

### Frontend (.env)
- `NUXT_BACKEND_BASE_URL` - Backend API URL
- `NUXT_PUBLIC_COOKIE_DOMAIN` - For cross-subdomain auth

### AI Service (.env)
- `OPENAI_API_KEY` - Required for GPT-4o
- `AI_SECRET_KEY` - API authentication (production)
