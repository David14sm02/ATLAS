# Acervo Documental y Arquitectura de Información — ATLAS TECNM

Bienvenido al directorio de documentación de **ATLAS TECNM** (Plataforma de Indicadores Nacionales del Tecnológico Nacional de México).

Todos los documentos del proyecto están modularizados por carpetas temáticas para evitar redundancias y facilitar la consulta por perfiles de usuario (Directivos, Desarrolladores, Administradores de BD y Analistas).

---

## Estructura Modular de Carpetas

```
docs/
├── ejecutivos/             # Documentos directivos, resúmenes finales y bitácora histórica
├── base_datos/             # Modelado dimensional (Esquema Estrella), diccionarios y diagramas ERD
├── tecnica/                # Manuales de arquitectura de software, frontend y guías de desarrollo
├── modulos/                # Documentación técnica, minutas y formatos fuente de cada submódulo
│   └── 3.1_comextras/      # Submódulo 3.1: Comisiones al Extranjero (COMEXTRAS)
├── analisis/               # Especificaciones funcionales, requerimientos y alcance del MVP
└── identidad/              # Manuales de identidad gráfica oficial y tokens visuales TecNM 2026
```

---

## Índice por Categoría

### 1. Documentos Ejecutivos (`docs/ejecutivos/`)
* **[DOCUMENTO_FINAL_PROYECTO_ATLAS_TECNM.txt](ejecutivos/DOCUMENTO_FINAL_PROYECTO_ATLAS_TECNM.txt):** Resumen ejecutivo integral del proyecto, justificación, logros alcanzados y proyección/roadmap (formato optimizado para exportar a PDF).
* **[bitacora_avances.md](ejecutivos/bitacora_avances.md):** Bitácora oficial de avances de ingeniería y registro de hitos del Hito 0 al Hito 5.

### 2. Base de Datos y Persistencia (`docs/base_datos/`)
* **[arquitectura_bd.md](base_datos/arquitectura_bd.md):** Especificación técnica del Esquema Estrella en PostgreSQL (Neon Serverless), tabla de hechos central y dimensiones.
* **[justificacion_data_warehouse_esquema_estrella.md](base_datos/justificacion_data_warehouse_esquema_estrella.md):** Fundamentación de por qué se adoptó un Data Mart OLAP en lugar de un sistema escolar transaccional tradicional.
* **[diagrama_erd_atlas_tecnm.svg](base_datos/diagrama_erd_atlas_tecnm.svg):** Diagrama Entidad-Relación vectorial de alta definición.
* **[ver_diagrama_erd.html](base_datos/ver_diagrama_erd.html):** Visor web interactivo con zoom y controles para inspeccionar el modelo.

### 3. Documentación Técnica y Desarrollo (`docs/tecnica/`)
* **[manual_tecnico_desarrolladores.md](tecnica/manual_tecnico_desarrolladores.md):** Manual maestro de onboarding para desarrolladores (8 módulos: arquitectura, Laravel 11, Filament v3, Clusters, Modelos y Widgets).
* **[guia_entorno_desarrollo.md](tecnica/guia_entorno_desarrollo.md):** Guía paso a paso para configurar el entorno local (Herd/Sail, PHP 8.4, Composer, Git).
* **[diseno_tablero_directivo_nacional.md](tecnica/diseno_tablero_directivo_nacional.md):** Especificaciones de diseño UI/UX y tokens de los 3 bloques narrativos del Dashboard principal.

### 4. Módulos y Submódulos Institucionales (`docs/modulos/`)
* **`3.1_comextras/` (Comisiones al Extranjero):**
  * **[minuta_reunion_comextras.md](modulos/3.1_comextras/minuta_reunion_comextras.md):** Minuta de acuerdos técnicos del submódulo.
  * **[resumen_modulo_comextras.md](modulos/3.1_comextras/resumen_modulo_comextras.md):** Ficha técnica sintética del proceso.
  * **[RESUMEN_EJECUTIVO_REUNION_COMEXTRAS.txt](modulos/3.1_comextras/RESUMEN_EJECUTIVO_REUNION_COMEXTRAS.txt):** Dossier ejecutivo estructurado para presentaciones del área de vinculación.
  * **`formatos_origen/`:** Acervo normativo y documental original (Circular 0045, macro-base Excel histórica y formatos tipo de oficios M00).

### 5. Análisis y Especificación Funcional (`docs/analisis/`)
* **[especificacion_funcional_mvp.md](analisis/especificacion_funcional_mvp.md):** Definición del ecosistema institucional de los 4 ejes y 35 submódulos, catálogo de perfiles y roles (R1 a R6) y matriz de alcances.

### 6. Identidad Visual Oficial (`docs/identidad/`)
* **[Manual_de_Identidad_Grafica_TecNM_2026.pdf](identidad/Manual_de_Identidad_Grafica_TecNM_2026.pdf):** Manual oficial que norma los colores institucionales (Azul TecNM, Dorado, Verde y Guinda), tipografías y proporciones de logos.

---

> **Regla de Gobernanza de Documentación:**  
> Ningún archivo nuevo `.md`, `.txt` o recurso documental debe colocarse directamente en la raíz de `docs/`. Siempre debe clasificarse en la subcarpeta correspondiente o crearse una nueva carpeta modular con nomenclatura en minúsculas y guiones bajos (`snake_case`).
