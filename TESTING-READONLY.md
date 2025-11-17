# Plan de Pruebas: Modo Solo Lectura para Documentos Publicados

## Objetivo
Verificar que los documentos en estado "Publicado" sean de solo lectura y que los documentos en estado "Borrador" sean editables normalmente.

---

## Pre-requisitos

1. ✅ Plugin instalado y activado en WordPress
2. ✅ Al menos un "Tipo de documento" configurado con campos
3. ✅ Acceso al panel de administración de WordPress

---

## Test 1: Crear Documento en Borrador

### Pasos:
1. Ve a **Documentos → Añadir nuevo**
2. Selecciona un tipo de documento
3. Completa los campos:
   - Título
   - Campos dinámicos según el tipo de documento
   - Metadatos (Autoría, Palabras clave)
   - Si hay campos de tipo array/repeater, agrega algunos elementos

### Resultado Esperado:
- ✅ Todos los campos son editables
- ✅ Puedes agregar/eliminar elementos en campos array
- ✅ El drag & drop funciona en campos array
- ✅ Puedes editar en los campos rich text (TinyMCE)
- ✅ Botón "Guardar borrador" funciona
- ✅ **NO** aparece el mensaje de advertencia sobre documento publicado

---

## Test 2: Guardar como Borrador

### Pasos:
1. Haz clic en "Guardar borrador"
2. Espera a que se guarde
3. Recarga la página

### Resultado Esperado:
- ✅ El documento se guarda correctamente
- ✅ Todos los datos ingresados se mantienen
- ✅ Los campos siguen siendo editables
- ✅ Estado del documento: "Borrador"

---

## Test 3: Editar Documento en Borrador

### Pasos:
1. Modifica algunos campos
2. Agrega un nuevo elemento en un campo array (si existe)
3. Cambia el orden de elementos con drag & drop (si existe)
4. Guarda los cambios

### Resultado Esperado:
- ✅ Puedes modificar todos los campos sin restricciones
- ✅ Los cambios se guardan correctamente
- ✅ La funcionalidad de edición es completamente normal

---

## Test 4: Publicar el Documento

### Pasos:
1. Desde el documento en borrador, haz clic en **"Publicar"**
2. Confirma la publicación
3. Observa la pantalla después de publicar

### Resultado Esperado:
- ✅ El documento se publica correctamente
- ✅ Estado cambia a "Publicado"
- ✅ **Aparece un mensaje de advertencia** en la parte superior:
  > "Este documento está publicado y no se puede editar. Solo puedes descargarlo en los formatos disponibles."

---

## Test 5: Verificar Modo Solo Lectura (PRINCIPAL)

### Pasos:
1. Con el documento publicado abierto, verifica cada elemento:

#### A. Campo de Título:
- ❌ NO debe ser editable
- ✅ Debe verse en gris/deshabilitado
- ✅ Cursor debe mostrar "not-allowed"

#### B. Campos de Secciones:
- ❌ Campos de texto: NO editables
- ❌ Campos textarea: NO editables
- ❌ Campos select: NO editables
- ❌ Campos checkbox: NO editables
- ❌ Campos rich text (TinyMCE): NO editables
- ✅ Todos deben verse deshabilitados visualmente

#### C. Campos Array/Repeater:
- ❌ Botón "Añadir elemento": NO visible o deshabilitado
- ❌ Botón "Eliminar": NO visible en cada item
- ❌ Handle de drag & drop (≡): NO visible
- ❌ NO se puede reordenar elementos
- ❌ Los campos dentro de cada item: NO editables

#### D. Metadatos:
- ❌ Campo "Autoría": NO editable
- ❌ Campo "Palabras clave": NO editable

#### E. Botones de Acción:
- ❌ Botón "Actualizar": deshabilitado
- ❌ Botón "Guardar borrador": deshabilitado (si existe)

### Resultado Esperado:
- ✅ **NINGÚN campo es editable**
- ✅ Todos los campos se ven visualmente deshabilitados (gris)
- ✅ Al intentar hacer clic en cualquier campo, nada sucede
- ✅ El mensaje de advertencia es visible

---

## Test 6: Intentar Enviar el Formulario

### Pasos:
1. Con el documento publicado abierto
2. Intenta hacer clic en el botón "Actualizar" (aunque esté deshabilitado)
3. Si logras hacer submit del form de alguna manera (por consola o tecla Enter)

### Resultado Esperado:
- ✅ Aparece un alert con el mensaje:
  > "Este documento está publicado y no se puede editar. Solo puedes descargarlo en los formatos disponibles."
- ✅ El formulario NO se envía
- ✅ No se guardan cambios

---

## Test 7: Verificar Opciones de Exportación

### Pasos:
1. Con el documento publicado
2. Ve a la lista de documentos (**Documentos → Todos los documentos**)
3. Busca el documento publicado
4. Pasa el mouse sobre el título

### Resultado Esperado:
- ✅ Aparece la opción "Exportar DOCX" (u otros formatos configurados)
- ✅ Al hacer clic, el documento se descarga correctamente
- ✅ **Esta es la única acción permitida para documentos publicados**

---

## Test 8: Volver a Borrador

### Pasos:
1. Con el documento publicado abierto
2. En el metabox "Publicar", cambia el estado de "Publicado" a "Borrador"
3. Haz clic en "Aceptar"
4. Haz clic en "Actualizar"

### Resultado Esperado:
- ✅ El documento vuelve a estado "Borrador"
- ✅ El mensaje de advertencia **desaparece**
- ✅ **Todos los campos vuelven a ser editables**
- ✅ Botones de agregar/eliminar vuelven a aparecer
- ✅ Drag & drop vuelve a funcionar

---

## Test 9: Re-publicar

### Pasos:
1. Desde el borrador, vuelve a publicar
2. Verifica que todo vuelva a modo solo lectura

### Resultado Esperado:
- ✅ El modo solo lectura se activa nuevamente
- ✅ Comportamiento idéntico al Test 5

---

## Test 10: Estilos Visuales (CSS)

### Pasos:
1. Con documento publicado, inspecciona visualmente:

### Resultado Esperado:
- ✅ Campos deshabilitados tienen fondo gris claro (#f5f5f5)
- ✅ Opacidad reducida (0.7) en campos
- ✅ Cursor "not-allowed" al pasar sobre campos
- ✅ El body tiene la clase `documentate-readonly-mode`
- ✅ La sección tiene la clase `documentate-published`

---

## Test 11: Verificación en Consola del Navegador

### Pasos:
1. Abre las DevTools (F12)
2. Ve a la consola
3. Con un documento publicado, verifica:

```javascript
// Debe retornar true
console.log(documentateReadOnly.isPublished);

// Debe mostrar el mensaje
console.log(documentateReadOnly.message);

// Debe retornar true
console.log($('body').hasClass('documentate-readonly-mode'));
```

### Resultado Esperado:
- ✅ No hay errores de JavaScript en la consola
- ✅ Las variables están definidas correctamente

---

## Checklist Final

- [ ] Test 1: Crear documento en borrador ✅
- [ ] Test 2: Guardar como borrador ✅
- [ ] Test 3: Editar documento en borrador ✅
- [ ] Test 4: Publicar el documento ✅
- [ ] Test 5: Verificar modo solo lectura (CRÍTICO) ✅
- [ ] Test 6: Intentar enviar el formulario ✅
- [ ] Test 7: Verificar opciones de exportación ✅
- [ ] Test 8: Volver a borrador ✅
- [ ] Test 9: Re-publicar ✅
- [ ] Test 10: Estilos visuales ✅
- [ ] Test 11: Verificación en consola ✅

---

## Problemas Conocidos a Verificar

### Si encuentras estos problemas, reporta:

1. ❌ Los campos siguen siendo editables después de publicar
2. ❌ El mensaje de advertencia no aparece
3. ❌ Los botones de agregar/eliminar siguen visibles
4. ❌ Drag & drop sigue funcionando
5. ❌ Se pueden guardar cambios en documentos publicados
6. ❌ Errores en la consola de JavaScript
7. ❌ Estilos no se aplican correctamente

---

## Archivos Modificados/Creados

### PHP:
- `includes/custom-post-types/class-documentate-documents.php` (líneas 550-776, 1474-1710)
- `includes/document/meta/class-document-meta-box.php` (líneas 58-84)
- `includes/class-documentate-admin-helper.php` (líneas 88-122)

### JavaScript:
- `admin/js/documentate-readonly.js` (NUEVO)

### CSS:
- `admin/css/documentate-readonly.css` (NUEVO)

---

## Comandos de Verificación de Sintaxis

```bash
# PHP
php -l includes/custom-post-types/class-documentate-documents.php
php -l includes/document/meta/class-document-meta-box.php
php -l includes/class-documentate-admin-helper.php

# JavaScript
node -c admin/js/documentate-readonly.js

# Verificar que los archivos existen
ls -lh admin/js/documentate-readonly.js
ls -lh admin/css/documentate-readonly.css
```

---

## Notas Adicionales

- El modo solo lectura se activa **SOLO** cuando el `post_status` es `'publish'`
- Otros estados como `'draft'`, `'pending'`, `'auto-draft'` permiten edición normal
- La funcionalidad usa tanto PHP (atributo `disabled`) como JavaScript para máxima cobertura
- Los estilos CSS proporcionan feedback visual inmediato
