# Reglas de Organización y Modularización de Documentación

Esta regla es de cumplimiento **OBLIGATORIO** para cualquier agente de IA o desarrollador que genere o modifique documentación en el repositorio `ATLAS TECNM`.

---

## 1. Prohibición de Archivos Sueltos en la Raíz de `docs/`
- **NUNCA** coloques archivos sueltos (`.md`, `.txt`, `.pdf`, `.docx`, etc.) directamente en la raíz de `docs/` ni en la raíz del proyecto.
- El único archivo permitido en la raíz de `docs/` es el índice general `docs/README.md`.

---

## 2. Taxonomía y Categorización Modular de Carpetas

Todo nuevo documento debe clasificarse estrictamente dentro de la subcarpeta que corresponda a su naturaleza:

| Carpeta | Naturaleza de los Documentos | Ejemplos |
| :--- | :--- | :--- |
| `docs/ejecutivos/` | Documentos de alto nivel para Dirección General, resúmenes de proyecto, bitácoras de hitos y presentaciones estratégicas. | `DOCUMENTO_FINAL_PROYECTO_ATLAS_TECNM.txt`, `bitacora_avances.md` |
| `docs/base_datos/` | Modelado dimensional (Esquema Estrella), arquitectura de datos, diccionarios de datos, diagramas ERD y justificaciones técnicas de persistencia. | `arquitectura_bd.md`, `justificacion_data_warehouse_esquema_estrella.md`, `diagrama_erd_atlas_tecnm.svg` |
| `docs/tecnica/` | Manuales para desarrolladores, arquitectura de software en Laravel/Filament, guías de configuración de entorno y diseño UI/UX de componentes/widgets. | `manual_tecnico_desarrolladores.md`, `guia_entorno_desarrollo.md`, `diseno_tablero_directivo_nacional.md` |
| `docs/modulos/<modulo_id>/` | Documentación técnica, minutas, lógica de negocio y formatos normativos propios de un submódulo específico. | `docs/modulos/3.1_comextras/` (minutas, resúmenes y carpeta `formatos_origen/` para archivos fuente XLSX/PDF) |
| `docs/analisis/` | Especificaciones funcionales de requisitos, alcance de MVPs, matrices de roles y perfiles institucionales. | `especificacion_funcional_mvp.md` |
| `docs/identidad/` | Manuales de marca oficiales, identidad gráfica, logotipos y lineamientos visuales del TecNM. | `Manual_de_Identidad_Grafica_TecNM_2026.pdf` |

---

## 3. Creación de Nuevas Carpetas Modulares
- Si un nuevo documento pertenece a un módulo o temática que aún no tiene carpeta (por ejemplo, el submódulo `2.2 MTE` o el submódulo `2.1 InnovaTecNM`):
  1. **Se debe crear la subcarpeta correspondiente antes de guardar el documento.**
     - Formato de nombres para submódulos: `docs/modulos/<codigo>_<nombre_corto>/` (ejemplo: `docs/modulos/2.2_mte/`).
     - Formato para otras temáticas: `docs/<categoria>/` en minúsculas y `snake_case` (sin espacios).
  2. Si el módulo contiene archivos fuente institucionales (plantillas Excel, PDFs de oficios tipo), deben ubicarse dentro de una subcarpeta `formatos_origen/`.
  3. Tras crear la carpeta y documento, debe actualizarse el índice en `docs/README.md`.

---

## 4. Estándares de Nomenclatura y Formato
- **Nombres de archivo:** Minúsculas y guiones bajos (`snake_case`), sin espacios ni acentos (ejemplo: `guia_entorno_desarrollo.md`, NO `Guía Entorno Desarrollo.md`).
- **Excepción ejecutiva:** Los documentos estructurados expresamente para copia/exportación formal directiva pueden utilizar mayúsculas institucionales si se justifica (ejemplo: `DOCUMENTO_FINAL_PROYECTO_ATLAS_TECNM.txt`), siempre dentro de `docs/ejecutivos/`.
- **Evitar redundancias:** Antes de crear un documento, verifica si ya existe uno con contenido similar para actualizarlo en vez de duplicarlo.
