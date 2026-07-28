# 🏗️ CapyMeal Architecture

> Arquitectura técnica del proyecto CapyMeal.

---

# Objetivo

CapyMeal es una aplicación web pensada para registrar las comidas diarias de una forma simple, cálida y agradable.

Su arquitectura busca ser:

- simple
- escalable
- fácil de mantener
- fácil de entender
- preparada para una futura aplicación móvil

---

# Filosofía técnica

Antes de escribir código nos hacemos una pregunta:

> ¿Esto hace que el proyecto sea más simple?

Si la respuesta es no, probablemente exista una solución mejor.

Preferimos:

- código legible
- componentes pequeños
- responsabilidades claras
- baja complejidad

Antes que:

- optimizaciones prematuras
- lógica difícil de entender
- arquitectura innecesariamente compleja

---

# Arquitectura general

CapyMeal estará dividido en dos aplicaciones independientes.

```
Frontend (Vue)

↓

REST API

↓

Backend (Laravel)

↓

PostgreSQL
```

Cada parte tendrá una única responsabilidad.

---

# Frontend

Tecnologías:

- Vue 3
- Vite
- Vue Router
- Pinia
- Axios
- TailwindCSS

Responsabilidades:

- interfaz
- navegación
- validaciones simples
- consumo de API
- experiencia de usuario

El frontend nunca accederá directamente a la base de datos.

---

# Backend

Tecnologías:

- Laravel
- Sanctum
- Eloquent ORM

Responsabilidades:

- reglas de negocio
- autenticación
- persistencia
- generación de PDF
- validaciones del servidor

---

# Base de datos

Motor:

PostgreSQL

El backend será el único responsable de acceder a la base de datos.

---

# MVP

La primera versión tendrá únicamente las funcionalidades esenciales.

## Pantallas

- Splash
- Mi Día
- Mi Diario
- Exportar PDF
- Ajustes

---

# Modelo inicial

## MealEntry

Representa el registro de un día.

Campos:

- id
- date
- breakfast
- lunch
- snack
- dinner
- notes
- created_at
- updated_at

En el futuro podrán agregarse:

- mood
- photos
- favorite
- location
- tags

---

# Flujo de datos

Usuario

↓

Completa comidas

↓

Frontend valida

↓

POST /meal-entries

↓

Laravel valida

↓

PostgreSQL

↓

Respuesta JSON

↓

Actualización de la interfaz

---

# Organización del proyecto

```
CapyMeal/

docs/

prototype/

frontend/

backend/

docker/
```

---

# Frontend

```
frontend/

src/

assets/

components/

layouts/

router/

stores/

services/

views/

App.vue

main.js
```

---

# Componentes

Los componentes deben ser pequeños.

Ejemplo:

```
components/

CapyButton.vue

CapyHeader.vue

MealCard.vue

DiaryCard.vue

BottomNavigation.vue

DateSelector.vue

CapyMessage.vue
```

Cada componente debe tener una única responsabilidad.

---

# Backend

```
app/

Http/

Controllers/

Models/

Requests/

Services/

Policies/

database/

routes/
```

La lógica de negocio compleja debe vivir en Services.

Los Controllers deben mantenerse pequeños.

---

# API

Convención REST.

Ejemplos:

GET /meal-entries

GET /meal-entries/{id}

POST /meal-entries

PUT /meal-entries/{id}

DELETE /meal-entries/{id}

---

# Exportación PDF

El backend será responsable de generar los PDFs.

Flujo:

Usuario

↓

Selecciona fechas

↓

Solicitud al backend

↓

Generación PDF

↓

Descarga

---

# Escalabilidad

La arquitectura está preparada para incorporar:

- autenticación
- múltiples usuarios
- aplicación móvil
- sincronización en la nube
- almacenamiento de imágenes
- recordatorios

Sin modificar la estructura principal.

---

# Convenciones

## Frontend

Componentes:

PascalCase

Ejemplo:

MealCard.vue

Variables:

camelCase

Constantes:

UPPER_SNAKE_CASE

---

# Git

main

↓

dev

↓

feature/nombre-feature

Cada funcionalidad importante se desarrolla en una rama independiente.

---

# Documentación

Toda decisión importante debe quedar documentada dentro de la carpeta docs.

La documentación forma parte del proyecto y debe mantenerse actualizada.

---

# Principio final

El código puede cambiar.

La tecnología puede cambiar.

La misión de CapyMeal no.

Cada decisión técnica debe respetar la filosofía del proyecto:

> Crear un lugar tranquilo donde guardar los pequeños momentos alrededor de la comida.