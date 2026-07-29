# 🖼️ CapyMeal UX Blueprint

> Wireframes y estructura visual de la aplicación.

---

# Objetivo

Este documento define la estructura visual de todas las pantallas de CapyMeal antes del desarrollo.

No representa el diseño final.

Representa la organización del contenido, la navegación y la experiencia del usuario.

---

# Navegación principal

```

Mi Día
│
├── Diario
│
├── Exportar PDF
│
└── Ajustes

```

Siempre debe ser posible volver a "Mi Día" con un solo toque.

---

# Pantalla 1 — Splash

Objetivo:

Dar la bienvenida.

```

+--------------------------------------+

🌸 CapyMeal

🐹
(Capi saludando)

"Las comidas pasan.
Los recuerdos quedan."

[ Comenzar ]

+--------------------------------------+

```

Componentes:

- Logo
- Ilustración de Capi
- Botón principal

---

# Pantalla 2 — Mi Día

Es la pantalla principal.

```

+--------------------------------------+

🐹 Hola, Mechi

Martes 28 de julio

📅 Cambiar fecha

----------------------------------------

☀️ Desayuno

[________________________]

----------------------------------------

🍝 Almuerzo

[________________________]

----------------------------------------

🧁 Merienda

[________________________]

----------------------------------------

🌙 Cena

[________________________]

----------------------------------------

📝 Recuerdo del día

[________________________]

----------------------------------------

🩷 Guardar mi día

+--------------------------------------+

```

Componentes:

- Header
- Date Picker
- 4 Meal Cards
- Notes Card
- Primary Button

---

# Pantalla 3 — Confirmación

```

+--------------------------------------+

🐹

"Listo 🌸

Este día ya forma parte
de tu diario."

[ Ver mi diario ]

[ Registrar otro día ]

+--------------------------------------+

```

---

# Pantalla 4 — Mi Diario

```

+--------------------------------------+

📖 Mi Diario

📅 Desde [ ]

📅 Hasta [ ]

🔍 Buscar

----------------------------------------

28 julio

☀️ Café

🍝 Ravioles

📝 Almorcé con mamá

[ Ver ]

----------------------------------------

27 julio

...

+--------------------------------------+

```

Cada tarjeta debe ser muy fácil de leer.

---

# Pantalla 5 — Detalle del día

```

+--------------------------------------+

📅 28 julio

☀️ Desayuno

Texto...

🍝 Almuerzo

Texto...

🧁 Merienda

Texto...

🌙 Cena

Texto...

📝 Recuerdo

Texto...

-------------------------

✏️ Editar

🗑 Eliminar

📄 Exportar PDF

+--------------------------------------+

```

---

# Pantalla 6 — Exportar PDF

```

+--------------------------------------+

📄 Exportar

Desde

[ calendario ]

Hasta

[ calendario ]

☑ Incluir portada

☑ Incluir recuerdos

🩷 Generar PDF

+--------------------------------------+

```

---

# Pantalla 7 — Ajustes

```

+--------------------------------------+

⚙️ Ajustes

🎨 Tema

ℹ️ Sobre CapyMeal

🐹 Conocer a Capi

📚 Documentación

❤️ Versión

+--------------------------------------+

```

---

# Navegación inferior

En móvil:

```

🏠 Diario PDF ⚙️

```

En escritorio:

Menú lateral.

---

# Componentes reutilizables

- CapyHeader
- CapyButton
- MealCard
- DateSelector
- EmptyState
- DiaryCard
- PDFCard
- CapyMessage
- ConfirmationModal

---

# Estados vacíos

## Diario vacío

```

🐹

Todavía no escribimos
ningún recuerdo.

[ Registrar mi primer día ]

```

---

## Sin resultados

```

🐹

No encontré registros
para esas fechas.

```

---

## Error

```

🐹

Algo salió mal.

Intentemos nuevamente.

```

---

# Responsive

Desktop

- Sidebar
- Dos columnas

Tablet

- Una columna amplia

Mobile

- Navegación inferior
- Componentes apilados

---

# Principios UX

- Una acción principal por pantalla.
- Máximo tres clics para completar cualquier tarea frecuente.
- Los botones principales siempre visibles.
- Tipografía grande y legible.
- Mucho espacio en blanco.
- Capi acompaña, nunca distrae.

---

# Resultado esperado

Al terminar el desarrollo, la aplicación debe sentirse como abrir una libreta bonita al final del día, donde guardar recuerdos sea tan natural como escribir una nota para uno mismo.