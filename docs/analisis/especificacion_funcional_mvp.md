# ATLAS TECNM: Plataforma de Indicadores Nacionales del TecNM

**Documento:** Especificación Conceptual, Arquitectura Funcional y Plan de Trabajo  
**Proyecto:** ATLAS TECNM  
**Equipo Responsable:** Fábrica de Software  
**Fecha:** Septiembre de 2026  
**Estatus:** Versión Oficial Aprobada para MVP  

---

## 1. Resumen Ejecutivo y Contexto Institucional

El proyecto **ATLAS TECNM** es la plataforma tecnológica estratégica concebida para modernizar la recolección, consolidación, auditoría y análisis de indicadores de la **Dirección General del Tecnológico Nacional de México (TecNM)**, integrando la información de sus **263 institutos y centros** distribuidos en todo el territorio nacional.

### La Problemática Actual
Actualmente, el flujo de información de vinculación y desempeño institucional opera mediante la circulación periódica y descentralizada de hojas de cálculo de formato rígido (formatos institucionales `F.37`, `F.32`, entre otros). Este mecanismo provoca:
* Dispersión de archivos y falta de trazabilidad histórica.
* Errores de captura humana en sumatorias por género y categorías.
* Consolidación manual extenuante en oficinas centrales.
* Retardo de semanas para obtener un panorama nacional del cumplimiento.

### La Solución: ATLAS TECNM
ATLAS TECNM sustituye este esquema por una plataforma web segura, multi-tenant y reactiva, capaz de procesar y proyectar los datos en herramientas directivas de alto impacto:
* **Cartografía Interactiva:** Mapa coropléjico de la República Mexicana por estados y municipios con Apache ECharts.
* **Semáforos de Cumplimiento:** Monitoreo trimestral en tiempo real del estatus de reporte de cada uno de los 263 planteles.
* **Analítica Cruzada e Inclusiva:** Cruces paramétricos inmediatos por género (Docentes y Estudiantes) y tipo de sostenimiento (**Federal** vs. **Descentralizado**).

---

## 2. Alcance del Producto Mínimo Viable (MVP - 7 Semanas)

Para mitigar riesgos y garantizar un despliegue operativo funcional hacia noviembre de 2026, el MVP establece la **arquitectura global completa de navegación y base de datos de los 4 ejes**, activando operativamente dos submódulos de alta prioridad:

1. **Submódulo 2.2: Modelo Talento Emprendedor (MTE)** (Eje 2).
2. **Submódulo 3.1: Movilidad Nacional e Internacional / COMEXTRAS** (Eje 3).

> **Estrategia Visual:** Los 33 submódulos restantes quedan plenamente registrados e integrados en la estructura de menús, permisos y base de datos con distintivos de *"En Desarrollo / Próximamente"*. Esto permite que las autoridades perciban la plataforma completa desde el primer día, evitando la sensación de un sistema fragmentado.

---

## 3. Ecosistema Institucional: Los 4 Ejes y 35 Submódulos

ATLAS TECNM estructura su jerarquía de navegación y módulos a partir del marco oficial de Vinculación del TecNM:

```
ATLAS TECNM
│
├── EJE 1: VINCULACIÓN ESTRATÉGICA
│   ├── 1.1 Consejos de Vinculación
│   ├── 1.2 Convenios
│   ├── 1.3 Seguimiento de Egresados y Bolsa de Trabajo
│   ├── 1.4 Proyectos Estratégicos
│   ├── 1.5 Semiconductores
│   ├── 1.6 Vinculación con Sectores
│   ├── 1.7 Indicadores de Calidad
│   └── 1.8 Marco Normativo
│
├── EJE 2: INNOVACIÓN Y EMPRENDIMIENTO
│   ├── 2.1 Eventos de Innovación y Emprendimiento (InnovaTecNM)
│   ├── 2.2 Modelo Talento Emprendedor (MTE)  ⭐ [FUNCIONAL EN MVP]
│   ├── 2.3 Nodos de Impulso a la Economía Social y Solidaria (NODESS)
│   ├── 2.4 Centros de Innovación e Impulso Empresarial y Social
│   ├── 2.5 Educación Financiera
│   ├── 2.6 Propiedad Intelectual
│   ├── 2.7 Transferencia de Tecnología
│   └── 2.8 Centros de Patentamiento
│
├── EJE 3: INTERCAMBIO ACADÉMICO
│   ├── 3.1 Movilidad Nacional e Internacional / COMEXTRAS  ⭐ [FUNCIONAL EN MVP]
│   ├── 3.2 Servicio Social y Desarrollo Comunitario
│   ├── 3.3 AlfabetizaTec
│   ├── 3.4 Residencias Profesionales
│   ├── 3.5 Educación Dual
│   ├── 3.6 Networking
│   └── 3.7 Unidades de Sitio Regional Especializadas en Vinculación
│
└── EJE 4: EXTENSIÓN
    ├── 4.1 Cursos Masivos Abiertos y en Línea (MOOC) del TecNM
    ├── 4.2 Lenguas Extranjeras y Lenguas Maternas o Indígenas
    ├── 4.3 Educación Continua
    ├── 4.4 Certificaciones (Redes de Centros de Certificación TecNM)
    ├── 4.5 Centros de Innovación Automotriz y Aeroespacial (Red CIIA - TecNM)
    ├── 4.6 Nodos de Creatividad para la Innovación Tecnológica y el Emprendimiento
    ├── 4.7 Programa de Certificación de Laboratorios TecNM
    ├── 4.8 Servicio Externo
    ├── 4.9 Estrategias de Comercialización
    ├── 4.10 Arte y Cultura
    ├── 4.11 Deporte
    └── 4.12 Formación Cívica
```

---

## 4. Arquitectura de Software y Experiencia de Usuario

```
┌─────────────────────────────────────────────────────────────────────────┐
│                      CAPA DE PRESENTACIÓN Y GESTIÓN                     │
│        Filament PHP v3 (TALL Stack: Tailwind, Alpine, Laravel, Livewire)│
│        + Apache ECharts (Mapas vectoriales e interactivos SVG/Canvas)   │
└────────────────────────────────────┬────────────────────────────────────┘
                                     │
┌────────────────────────────────────▼────────────────────────────────────┐
│                         BACKEND Y LÓGICA DE NEGOCIO                     │
│   - Laravel 11                                                          │
│   - Organización modular mediante Filament Clusters (4 Ejes)            │
│   - Multi-Tenancy nativo por plantel (Global Scopes automáticos)        │
│   - Control de Acceso Basado en Roles (RBAC) con Filament Shield        │
│   - Ingesta Asíncrona de Archivos Excel con Colas (Redis) y Dry-Run     │
└────────────────────────────────────┬────────────────────────────────────┘
                                     │
┌────────────────────────────────────▼────────────────────────────────────┐
│                    PERSISTENCIA Y MODELO ANALÍTICO                      │
│   - PostgreSQL 16 (Neon Serverless con PgBouncer Connection Pooling)    │
│   - Esquema Estrella (Dimensiones maestras + Tabla de Hechos central)   │
│   - Columnas Generadas (STORED) para blindaje de cálculos matemáticos   │
│   - Atributos dinámicos en JSONB con indexación GIN                     │
└─────────────────────────────────────────────────────────────────────────┘
```

### Principios Arquitectónicos
1. **Filament Clusters:** Los 4 ejes se implementan como Clusters independientes en `app/Filament/Clusters/` (`VinculacionEstrategica`, `InnovacionEmprendimiento`, `IntercambioAcademico`, `Extension`). Esto otorga orden limpio en código y agrupa los recursos visualmente en el menú lateral.
2. **Multi-Tenancy por Plantel:** Cada uno de los 263 institutos opera como un tenant aislado. Un capturista del Instituto Tecnológico de Puebla únicamente tiene visibilidad y permisos sobre su plantel. Los directivos nacionales tienen alcance irrestricto.
3. **Flujo de Carga Ágil (MVP):** Se implementa un ciclo de dos estados (`Borrador` $\rightarrow$ `Publicado`), posponiendo el flujo complejo de 4 estados con modales de dictamen para la Fase 2, garantizando rapidez operativa.

---

## 5. Matriz de Roles y Perfiles (4 Niveles / 6 Perfiles)

| Nivel | Rol | Identificador | Alcance y Facultades |
| :--- | :--- | :--- | :--- |
| **Nacional** | Super Administrador | `R1_SUPERADMIN` | Acceso irrestricto, configuración global, gestión de periodos y usuarios. |
| **Nacional** | Analista Nacional | `R2_ANALISTA_NAL` | Solo lectura global. Acceso a mapas nacionales, reportes consolidados y exportaciones. |
| **Regional** | Coordinador Regional | `R3_COORD_REGIONAL` | Supervisión y reportes de los planteles adscritos a su zona geográfica. |
| **Plantel** | Administrador de Plantel | `R4_ADMIN_PLANTEL` | Enlace directivo del instituto. Gestión de operadores de su plantel y firma de reportes. |
| **Plantel** | Operador de Módulo | `R5_OPERADOR_MOD` | Captura y edición de datos numéricos en los submódulos asignados (ej. MTE o COMEXTRAS). |
| **Soporte** | Soporte Técnico / DevOps | `R6_SOPORTE` | Mantenimiento técnico, monitoreo de colas y diagnóstico de sincronización. |

---

## 6. Entorno de Ingeniería y Estándares de Trabajo

Para coordinar eficazmente a los 4 desarrolladores de la Fábrica de Software:

1. **Gobernanza Git:**
   * Organización privada en GitHub.
   * Ramas: `main` (producción) $\leftarrow$ `develop` (staging/pruebas) $\leftarrow$ `feature/[eje-submodulo]` (desarrollo).
   * Pull Requests obligatorios con revisión de pares; ramas principales protegidas.
2. **Homogeneidad con Docker (Laravel Sail):**
   * Pila unificada: PHP 8.3, PostgreSQL 16, Redis (colas y caché) y Mailpit (pruebas de correo).
3. **Pipeline de Integración Continua (CI/CD):**
   * GitHub Actions ejecutando validación de estilo con **Laravel Pint** y pruebas unitarias/integración con **Pest/PHPUnit**.
4. **Docs as Code & Bitácoras:**
   * Registro histórico de avances técnicos en `docs/bitacoras/` versionado directamente en el repositorio.

---

## 7. Cronograma Maestro de Ejecución (7 Semanas hacia Noviembre)

* **Semana 1:** Repositorio base, entorno Docker con Sail, conexión a Neon y *Seeders* maestros (263 planteles, 32 estados, 4 ejes, 35 submódulos y periodos).
* **Semanas 2 y 3:** Migraciones, modelos y recursos de Filament para **2.2 MTE** y **3.1 COMEXTRAS**, con aislamiento Multi-Tenancy y Filament Shield.
* **Semana 4:** Ingesta asíncrona de archivos Excel con validación previa (*dry-run*), colas en Redis y modal de reporte de errores.
* **Semana 5:** Tablero directivo nacional: KPIs agregados, semáforo de cumplimiento por plantel y mapa interactivo con Apache ECharts.
* **Semana 6:** Pruebas integrales de estrés, calibración de permisos y validación de sumatorias.
* **Semana 7:** Despliegue en producción, capacitación operativa y entrega de bitácoras.