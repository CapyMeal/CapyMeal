<div align="center">
  
# 🐹 CapyMeal — Guía de desarrollo

Todo lo que necesitás para levantar el proyecto desde cero.

</div>


### Requisitos

  - [Docker Desktop](https://www.docker.com/products/docker-desktop/)
  - Git

Nada más. No necesitás instalar PHP, Node ni PostgreSQL en tu máquina.



## Levantar el proyecto

```bash
git clone https://github.com/CapyMeal/CapyMeal.git
cd CapyMeal/prototype/v0.1

docker compose up --build
```

La **primera vez** tarda unos minutos porque:
  - Descarga las imágenes de Docker
  - Instala Laravel en el contenedor del backend
  - Ejecuta las migraciones automáticamente

A partir de la segunda vez es mucho más rápido:

```bash
docker compose up
```



## URLs

| Servicio    | URL                        |
|-------------|----------------------------|
| Frontend    | http://localhost:5174       |
| Backend API | http://localhost:8080       |
| PostgreSQL  | localhost:5433              |



## Estructura del stack

```
docker-compose.yml
│
├── frontend/   → Vue 3 + Vite   (Node 20)
├── backend/    → Laravel 12     (PHP 8.2)
└── db          → PostgreSQL 18
```

Los servicios se comunican entre sí por red interna de Docker.
El frontend llama a la API en `http://localhost:8080`.



## Comandos útiles

```bash
# Ver logs de un servicio
docker logs capymeal-backend
docker logs capymeal-frontend
docker logs capymeal-db

# Entrar al contenedor del backend
docker exec -it capymeal-backend bash

# Correr migraciones manualmente
docker exec -it capymeal-backend php artisan migrate

# Detener todo
docker compose down

# Detener y borrar la base de datos (reset completo)
docker compose down -v

# Tests del backend
docker exec -it capymeal-backend php artisan test

# Lint (no modifica nada, solo reporta)
docker exec -it capymeal-backend composer lint
docker exec -it capymeal-frontend npm run lint

# Lint (aplica los arreglos automáticos)
docker exec -it capymeal-backend composer format
docker exec -it capymeal-frontend npm run format
```

Todo esto corre también solo en cada push/PR vía GitHub Actions.



## Branching

```
dev                                  ← producción (Render y Vercel deployan desde acá)
  └── feature/nombre-feature         ← desarrollo
```

`main` existe pero no se usa para deployar -- `dev` es la rama real de producción.
Siempre trabajar en una rama `feature/` que sale de `dev`.



## Notas

- El código de `backend/` y `frontend/` está montado como volumen.
  Cualquier cambio que hagas en los archivos se refleja en tiempo real sin reiniciar Docker.
- La base de datos persiste en un volumen Docker (`db_data`).
  Para resetearla completamente usá `docker compose down -v`.
- Si un deploy a `dev` rompe algo en producción, ver [RUNBOOK.md](./RUNBOOK.md).
