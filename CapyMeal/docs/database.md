# 🗄️ CapyMeal Database Design

> Diseño del modelo de datos de CapyMeal.

---

# Objetivo

La base de datos de CapyMeal fue diseñada para almacenar recuerdos diarios relacionados con las comidas.

No pretende registrar información nutricional compleja.

Su objetivo es conservar momentos de una forma simple, escalable y agradable.

---

# Principios

La base de datos debe ser:

- simple
- fácil de extender
- normalizada
- preparada para múltiples usuarios
- compatible con futuras aplicaciones móviles

---

# Modelo conceptual

Un usuario puede registrar muchos días.

Cada día contiene un registro de comidas.

Cada registro representa un único día del calendario.

```
User

↓

MealEntry
```

---

# Entidad principal

## meal_entries

Representa un día completo.

Campos:

```
id

user_id

date

breakfast

lunch

snack

dinner

notes

created_at

updated_at
```

---

# Descripción de campos

## id

Clave primaria.

Tipo:

BIGINT

---

## user_id

Usuario propietario.

Permite escalar a múltiples cuentas.

Tipo:

BIGINT

Relación:

belongsTo(User)

---

## date

Fecha del registro.

Tipo:

DATE

Ejemplo:

2026-07-27

Debe ser única por usuario.

Un usuario no puede tener dos registros para el mismo día.

---

## breakfast

Texto libre.

Ejemplo:

"Café con leche y tostadas."

---

## lunch

Texto libre.

Ejemplo:

"Ravioles caseros."

---

## snack

Texto libre.

Ejemplo:

"Té con budín."

---

## dinner

Texto libre.

Ejemplo:

"Sopa de verduras."

---

## notes

Campo opcional.

Permite guardar un recuerdo.

Ejemplo:

"Hoy almorcé con mis abuelos."

Este campo representa el espíritu de CapyMeal.

---

# Relaciones

## User

```
User

↓

hasMany()

↓

MealEntry
```

---

## MealEntry

```
MealEntry

↓

belongsTo()

↓

User
```

---

# Restricciones

Debe existir solamente un registro por fecha y usuario.

Ejemplo:

```
user_id + date

UNIQUE
```

Esto evita registros duplicados.

---

# Índices

Crear índices sobre:

```
user_id

date

(user_id, date)
```

Esto permitirá búsquedas rápidas por rango de fechas.

---

# Evolución futura

El diseño contempla futuras mejoras.

## Favoritos

```
favorite

boolean
```

---

## Estado de ánimo

```
mood

happy

calm

sad

excited
```

---

## Fotografías

Tabla:

meal_photos

Relación:

MealEntry

↓

hasMany()

↓

MealPhoto

---

## Etiquetas

Ejemplo:

- cumpleaños
- vacaciones
- familia
- trabajo

Modelo:

MealTag

---

## Archivos PDF

En el futuro podrían guardarse los PDFs generados.

Tabla:

exports

---

# Futuras tablas

## users

Autenticación.

---

## meal_photos

Fotografías de comidas.

---

## tags

Etiquetas.

---

## meal_entry_tags

Tabla pivote.

---

## reminders

Recordatorios.

---

## exports

Historial de PDFs.

---

# Modelo futuro

```
User

│

├── MealEntry

│      │

│      ├── MealPhoto

│      ├── MealEntryTag

│      └── Export

│

└── Reminder
```

---

# ¿Por qué no existe una tabla Meals?

Podríamos haber diseñado algo así:

```
Meal

↓

Breakfast

Lunch

Snack

Dinner
```

Pero decidimos no hacerlo.

Razones:

- aumenta la complejidad
- genera más consultas
- no aporta valor al MVP

Para la primera versión, un registro diario es suficiente.

---

# Escalabilidad

Si en el futuro CapyMeal incorpora:

- múltiples comidas
- bebidas
- recetas
- restaurantes
- calorías
- ingredientes

será posible migrar hacia un modelo más complejo sin afectar la filosofía del proyecto.

---

# Filosofía

CapyMeal no guarda solamente datos.

Guarda días.

Y cada día representa una pequeña historia.

La base de datos debe reflejar esa idea.

No diseñamos tablas.

Diseñamos recuerdos.