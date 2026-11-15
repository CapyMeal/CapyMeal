import breakfastIllustration from '../assets/icons/desayuno.png'
import lunchIllustration     from '../assets/icons/almuerzo.png'
import snackIllustration     from '../assets/icons/merienda.png'
import dinnerIllustration    from '../assets/icons/cena.png'
import notesIllustration     from '../assets/icons/diario.png'

// Única fuente de verdad de cómo se dibuja cada campo del día. Antes cada
// vista armaba su propia lista (ilustraciones en "Hoy", emoji en el
// diario y en la vista previa del PDF) y la misma comida se veía de dos
// maneras distintas según la pantalla. DESIGN.md, "regla de una sola voz".
export const MEAL_KEYS = ['breakfast', 'lunch', 'snack', 'dinner']

export const MEAL_ILLUSTRATIONS = {
  breakfast: breakfastIllustration,
  lunch:     lunchIllustration,
  snack:     snackIllustration,
  dinner:    dinnerIllustration,
  notes:     notesIllustration,
}
