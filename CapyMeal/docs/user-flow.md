# 🌸 CapyMeal User Flow

> Experiencia de uso de CapyMeal.

---

# Objetivo

El objetivo de CapyMeal es que registrar un día sea una experiencia tranquila, agradable y sin fricciones.

El usuario nunca debería preguntarse:

> "¿Qué hago ahora?"

Cada pantalla debe invitar naturalmente al siguiente paso.

---

# Filosofía

CapyMeal no premia.

No castiga.

No genera culpa.

Simplemente acompaña.

Cada interacción debe sentirse cálida y sencilla.

---

# Flujo principal

```
Abrir CapyMeal

↓

Pantalla "Mi Día"

↓

Registrar comidas

↓

Guardar

↓

Confirmación de Capi

↓

Fin
```

Ese es el flujo más importante de toda la aplicación.

Si este recorrido funciona bien, el producto cumple su propósito.

---

# Primera apertura

## Objetivo

Transmitir calma.

La primera pantalla no debe sentirse vacía.

Debe dar la bienvenida.

---

## Pantalla

Elementos:

- Logo
- Capi
- Fecha actual
- Mensaje de bienvenida
- Botón "Comenzar"

Mensaje sugerido:

> "Hola 🌸
>
> Guardemos juntos los pequeños momentos de hoy."

---

# Pantalla "Mi Día"

Es la pantalla principal.

Siempre debe abrir aquí.

---

## Encabezado

Debe mostrar:

- Fecha
- Selector de fecha
- Saludo de Capi

Ejemplo:

```
🐹 Hola, Mechi

Lunes 27 de julio
```

---

## Registro diario

Cuatro tarjetas:

☀️ Desayuno

🍝 Almuerzo

🧁 Merienda

🌙 Cena

Cada una contiene:

- icono
- título
- textarea

---

## Recuerdo del día

Al finalizar las comidas aparece una tarjeta opcional.

Título:

📝 Recuerdo del día

Placeholder:

> ¿Hubo algo especial hoy?

Este campo representa el corazón de CapyMeal.

---

## Guardar

Botón principal.

Texto:

🩷 Guardar mi día

---

# Después de guardar

Animación suave.

Mensaje de Capi:

> "Listo 🌸
>
> Este día ya forma parte de tu diario."

Botón:

Volver al inicio.

---

# Mi Diario

Objetivo:

Consultar recuerdos anteriores.

---

## Vista

Lista cronológica.

Cada tarjeta muestra:

- fecha
- comidas registradas
- primeras líneas del recuerdo

Botón:

Ver detalle.

---

# Detalle del día

Permite:

- editar
- eliminar
- exportar ese día

---

# Exportar PDF

Flujo:

Usuario

↓

Selecciona fecha inicial

↓

Selecciona fecha final

↓

Vista previa

↓

Generar PDF

↓

Descarga

---

# Ajustes

Incluye:

- Tema
- Información
- Documentación
- Acerca de CapyMeal

No debe ser una pantalla sobrecargada.

---

# Estados vacíos

CapyMeal debe acompañar incluso cuando no hay información.

---

## Diario vacío

Ilustración:

Capi con su libreta cerrada.

Mensaje:

> "Todavía no guardamos ningún recuerdo."

Botón:

Registrar mi primer día.

---

## Sin resultados

Ilustración:

Capi buscando entre hojas.

Mensaje:

> "No encontré registros para esas fechas."

---

# Mensajes de Capi

Los mensajes deben sentirse naturales.

Ejemplos:

Bienvenida

> Qué lindo volver a verte.

Guardar

> Guardé este momento para vos.

Error

> Algo salió mal.
>
> Intentemos nuevamente.

Registro vacío

> Hoy todavía no escribimos nada.

PDF

> Preparé tu diario.

Nunca usar mensajes técnicos.

---

# Principios de navegación

La navegación debe requerir la menor cantidad posible de pasos.

El usuario nunca debería necesitar más de tres acciones para:

- registrar un día
- encontrar una fecha
- exportar un PDF

---

# Accesibilidad

CapyMeal debe ser cómoda para cualquier persona.

Requisitos:

- botones grandes
- buen contraste
- textos legibles
- iconografía clara
- navegación consistente

---

# Diseño emocional

Cada pantalla debe responder una pregunta.

Pantalla inicial

→ ¿Cómo te doy la bienvenida?

Mi Día

→ ¿Cómo hago que registrar sea agradable?

Diario

→ ¿Cómo hago que recordar sea fácil?

PDF

→ ¿Cómo convierto estos días en un recuerdo bonito?

---

# Regla principal

Si una funcionalidad agrega complejidad sin mejorar la experiencia del usuario, no debe implementarse.

CapyMeal siempre debe sentirse como abrir una libreta bonita al final del día.