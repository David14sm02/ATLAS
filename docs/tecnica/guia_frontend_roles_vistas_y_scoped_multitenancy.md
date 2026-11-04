# Guía de Arquitectura para el Equipo de Frontend: Scoped Multi-Tenancy, Roles v2.1 y Diseño de Vistas

**Proyecto:** ATLAS TECNM — Plataforma de Indicadores Nacionales del Tecnológico Nacional de México  
**Audiencia:** Equipo de Desarrollo Frontend, Arquitectura y Líderes Técnicos  
**Fecha:** 03 de Noviembre de 2026  

---

## Parte 1: Aclaración Arquitectónica — ¿Qué es *Multi-Tenancy* vs. *Scoped Multi-Tenancy*?

Durante las conversaciones técnicas surgió una confusión de términos sobre qué significa que ATLAS TECNM sea **"Multi-Tenant" (Multi-Plantel)**. Esta sección aclara que **ambos enfoques hablados en el equipo apuntan exactamente al mismo modelo ligero de una sola base de datos**.

### 1.1 El Malentendido Común: Los 2 Tipos de Multi-Tenancy en la Industria
En ingeniería de software existen dos maneras muy distintas de construir un sistema donde participan 266 instituciones:

| Característica | ❌ Opción A: Multi-Tenancy Multi-Base *(Pesado — NUNCA se usó en ATLAS)* | ✅ Opción B: Scoped Multi-Tenancy en Base Única *(El diseño de ATLAS desde el Día 1)* |
| :--- | :--- | :--- |
| **¿Cómo guarda los datos?** | Crea **266 bases de datos separadas** (o 266 esquemas distintos) y 266 subdominios (`cancun.atlas...`, `celaya.atlas...`). | Usa **1 sola base de datos central (Esquema Estrella / Data Mart)** donde todas las tablas operativas tienen la columna `plantel_id`. |
| **¿Qué pasa cuando Dirección General pide el Mapa Nacional?** | El servidor tendría que abrir 266 conexiones simultáneas y unirlas en memoria. Es lento, costoso y difícil de mantener. | Hace **una sola consulta SQL (`SUM` / `COUNT`)** sobre la tabla `rep_registros_base` y responde en milisegundos. |
| **¿Cómo se aísla a cada Tecnológico?** | Por conexión física de base de datos. | **Por Alcance Lógico (*Scoped*)**: Laravel inyecta automáticamente `WHERE plantel_id = X` según el usuario que inició sesión. |

### 1.2 ¿Qué significa entonces la palabra *"Scoped"* que implementó Ángel?
Cuando desde el inicio del proyecto dijimos *"el sistema tendrá aislamiento Multi-Tenant por plantel y por rol"*, siempre nos referimos a la **Opción B (Base de Datos Única con `plantel_id`)**, jamás a crear 266 bases de datos.

Lo que Ángel implementó y documentó como **Scoped Multi-Tenancy** (`app/Models/Traits/BelongsToPlantel.php` y `app/Models/Scopes/PlantelScope.php`) es el **mecanismo automático en código Laravel** para que esa Opción B sea 100% segura:
1. **"Scope" significa "Alcance o Filtro Automático":** En lugar de que cada programador de Frontend o Backend tenga que acordarse de escribir manualmente `->where('plantel_id', auth()->user()->plantel_id)` en cada tabla o pantalla nueva, el modelo `RepRegistroBase` ya lleva puesto el candado `PlantelScope`.
2. **¿Cómo actúa ese candado automático según quién inicia sesión?**
   * **Si entra un usuario de Plantel (`R4` Director / Subdirector o `R5` Operador):** El `PlantelScope` filtra automáticamente todas las consultas con `WHERE plantel_id = su_plantel`. Es imposible que vea datos de otro Tecnológico.
   * **Si entra un Supervisor Estatal de OPDs (`R3`):** El `PlantelScope` filtra automáticamente los Tecnológicos Descentralizados de su Estado (`WHERE entidad_id = su_estado AND sostenimiento = 'DESCENTRALIZADO'`).
   * **Si entra un Directivo Nacional (`R1` o `R2`):** El `PlantelScope` detecta que `$user->esNacional()` es `true` y le permite ver el consolidado de los **266 planteles**.

> **Conclusión para todo el equipo:**  
> No hubo cambio de arquitectura. Siempre hemos tenido **una sola base de datos en Esquema Estrella en Neon PostgreSQL**, y hoy están unidos tanto el **candado automático (`BelongsToPlantel` / `PlantelScope`)** como el **sistema completo de Roles v2.1 (`R1`–`R6`), los 266 planteles oficiales y la Caja Negra de Auditoría Forense (`RegistraBitacoraForense`)**.

---

## Parte 2: Puesta en Marcha para el Equipo de Frontend

### 2.1 Comandos para actualizar su entorno local
Una vez descargados los últimos cambios de la rama `main`, ejecuten en su terminal:

```bash
# 1. Bajar la versión unificada de main
git pull origin main

# 2. Recrear tablas y cargar los 266 planteles reales + los 13 usuarios de prueba
php artisan migrate:fresh --seed

# 3. Limpiar caché de vistas y configuración
php artisan optimize:clear

# 4. Levantar el servidor local
php artisan serve
```

* Acceso al sistema: **`http://localhost:8000/admin`**
* Si van a editar clases de Tailwind en vivo, abran una segunda terminal y ejecuten:
  ```bash
  npm run dev
  ```
* *(Quienes utilicen Docker/Sail en lugar de PHP local pueden usar `./vendor/bin/sail up -d` y ejecutar los comandos anteponiendo `./vendor/bin/sail`).*

---

## Parte 3: Directorio de los 13 Usuarios de Prueba (Qué ve cada Perfil)

El seeder `database/seeders/UsersRolesSeeder.php` ya dejó creados **13 perfiles reales de prueba** para que el equipo de Frontend pueda iniciar sesión y diseñar la experiencia de cada tipo de usuario.

> 🔑 **Contraseña para todas las cuentas de prueba:** `password`

### Nivel 1 y 2: Autoridades Nacionales (Oficinas Centrales TecNM)
Ven el **Dashboard Ejecutivo Nacional** (Pulso Nacional + Mapa Interactivo de la República + Paridad y Sectores), los registros de los **266 planteles** y la **Bitácora Forense Nacional**.

| Correo Electrónico | Rol (`rol_clave`) | Cargo / Perfil | Particularidad en la Interfaz |
| :--- | :--- | :--- | :--- |
| `admin@tecnm.mx` | `R1_SUPERADMIN` | Superadministrador DTI / Planeación | Acceso total a todos los módulos y catálogos. |
| `ramon.jimenez@tecnm.mx` | `R1_AUTORIZADOR_SEV_DG` | Director General del TecNM | Vista ejecutiva estratégica nacional. |
| `andrea.zarate@tecnm.mx` | `R1_AUTORIZADOR_SEV_DG` | Secretaria de Extensión y Vinculación | Vista ejecutiva y autorización nacional. |
| `marco.trujillo@tecnm.mx` | `R2_REVISOR_DVEIA` | Director de Vinculación e Intercambio | Revisor nacional de submódulos de vinculación. |
| `auditoria.oic@tecnm.mx` | `R2_AUDITOR_OIC` | Auditor del Órgano Interno de Control | **Modo Solo Lectura Estricto**: Ve todo el país y la Bitácora Forense, pero **no tiene botones de Crear ni Editar**. |

### Nivel 3: Supervisión Territorial / Estatal (OPDs)
| Correo Electrónico | Rol (`rol_clave`) | Estado Asignado | Particularidad en la Interfaz |
| :--- | :--- | :--- | :--- |
| `supervisor.jalisco@tecnm.mx` | `R3_SUPERVISOR_TERRITORIAL` | **Jalisco** (`entidad_id = 14`) | Solo Lectura. En los módulos y en la Bitácora de Auditoría solo ve los **Tecnológicos Descentralizados de Jalisco** (ej. ITS de Zapopan). |

### Nivel 4: Directores de Plantel y Subdirectores con Firma Delegada
**NO ven el Mapa Nacional ni el Pulso Nacional.** Ven el widget local de su plantel (`PulsoPlantelWidget`), todos los submódulos de su propio Tecnológico y tienen acceso al **Perfil de Bitácora de Auditoría de su Plantel** para supervisar qué capturan sus jefes de departamento.

| Correo Electrónico | Rol (`rol_clave`) | Instituto / Centro Asignado | Tipo de Institución |
| :--- | :--- | :--- | :--- |
| `director.cancun@tecnm.mx` | `R4_DIRECTOR_TITULAR` | **I.T. de Cancún** | Federal (`IT_FEDERAL`) |
| `vinculacion.cancun@tecnm.mx` | `R4_SUBDIRECTOR_DELEGADO` | **I.T. de Cancún** (Jefe de Gestión y Vinculación) | Federal — **Puede firmar/publicar por ausencia del Director** |
| `director.zapopan@tecnm.mx` | `R4_DIRECTOR_TITULAR` | **I.T.S. de Zapopan** | Descentralizado (`IT_DESCENTRALIZADO`) |
| `director.cenidet@tecnm.mx` | `R4_DIRECTOR_TITULAR` | **CENIDET** (Cuernavaca) | Centro Especializado (`CENTRO_ESPECIALIZADO`) |

### Nivel 5: Operadores / Jefes de Departamento de Submódulo
Solo ven los datos de su plantel, **NO tienen acceso a la Bitácora de Auditoría** y en el menú de navegación **solo les aparecen los submódulos que tienen explícitamente asignados** en la tabla `user_submodulos`.

| Correo Electrónico | Rol (`rol_clave`) | Instituto Asignado | Submódulos Asignados |
| :--- | :--- | :--- | :--- |
| `comextras.cancun@tecnm.mx` | `R5_OPERADOR_MOD` | I.T. de Cancún | **Solo `3.1 COMEXTRAS`** *(El módulo `2.2 MTE` le aparece oculto)* |
| `mte.cancun@tecnm.mx` | `R5_OPERADOR_MOD` | I.T. de Cancún | **Solo `2.2 MTE`** *(El módulo `3.1 COMEXTRAS` le aparece oculto)* |
| `operador.zapopan@tecnm.mx` | `R5_OPERADOR_MOD` | I.T.S. de Zapopan | **Ambos (`3.1 COMEXTRAS` y `2.2 MTE`)** |

---

## Parte 4: ¿Cómo Diseñar y Programar Vistas por Rol en Filament v3?

### 4.1 Regla de Oro: NO hacer un solo archivo `.blade.php` gigante con `if / else`
Para evitar que dos desarrolladores de Frontend editen el mismo archivo y generen conflictos de Git, **cada vista o bloque del tablero debe ser un Widget o Página independiente**.

En Filament, cada Widget tiene un método `canView(): bool` que decide a qué rol se le muestra:

```php
// Ejemplo en un Widget para Directores de Plantel (R4) y Operadores (R5)
public static function canView(): bool
{
    $user = auth()->user();

    return $user ? ($user->esAdminPlantel() || $user->esOperador()) : false;
}
```

### 4.2 Métodos de Rol disponibles en `auth()->user()` (`app/Models/User.php`)
Ya no necesitan comparar textos de roles a mano; usen estos métodos listos en el modelo `User`:

* `$user->esNacional()` $\rightarrow$ `true` para `R1` y `R2` (Oficinas Centrales).
* `$user->esAuditorOIC()` $\rightarrow$ `true` para `R2_AUDITOR_OIC`.
* `$user->esSupervisorTerritorial()` $\rightarrow$ `true` para `R3` (Supervisores Estatales/Regionales).
* `$user->esAdminPlantel()` $\rightarrow$ `true` para `R4_DIRECTOR_TITULAR` y `R4_SUBDIRECTOR_DELEGADO`.
* `$user->esOperador()` $\rightarrow$ `true` para `R5_OPERADOR_MOD` (Capturistas).
* `$user->esPlantel()` $\rightarrow$ `true` si el usuario pertenece a un plantel específico (`plantel_id != null`).
* `$user->esSoloLectura()` $\rightarrow$ `true` si el usuario no debe ver botones de crear/editar/borrar.
* `$user->puedeCapturarSubmodulo('3.1')` $\rightarrow$ `true` si el usuario tiene permiso de ver/capturar ese submódulo (`1` al `35`).

### 4.3 ¿Dónde están las vistas actuales y dónde crear las nuevas?
* **Vistas del Dashboard Nacional (`R1`/`R2`/`R3`):**
  * `resources/views/filament/widgets/pulso-nacional-widget.blade.php`
  * `resources/views/filament/widgets/mapa-republica-widget.blade.php`
  * `resources/views/filament/widgets/inclusion-sectores-widget.blade.php`
* **Vista Local de Plantel (`R4`/`R5`):**
  * Actualmente controlada por `app/Filament/Widgets/PulsoPlantelWidget.php`.
  * Si el equipo de Frontend va a diseñar un tablero visual completo para el **Director de Plantel (`R4`)** o para el **Jefe de Vinculación (`R4_SUBDIRECTOR_DELEGADO`)** (por ejemplo, semáforo de los 35 submódulos de su Tecnológico, entregas pendientes de sus jefes de departamento, botón de firma trimestral), pueden crear una vista Blade dedicada en `resources/views/filament/widgets/` y conectarla a su propio Widget.
* **Vistas de los 4 Ejes (Clusters) y Formularios de Submódulos:**
  * `resources/views/filament/clusters/submodulos-overview.blade.php`
  * `app/Filament/Clusters/IntercambioAcademico/Resources/ComextrasResource.php`
  * `app/Filament/Clusters/InnovacionEmprendimiento/Resources/MteResource.php`

---

## Parte 5: ¿Cómo Manejar los Estilos CSS? (Tailwind vs. Hoja de Tema Institucional)

El proyecto utiliza una arquitectura de estilos **híbrida** pensada para no romper el **Modo Claro / Modo Oscuro** nativo de Filament y respetar el **Manual de Identidad Gráfica TecNM 2026**:

### 5.1 Paleta Oficial de Colores TecNM 2026
* **Azul TecNM (Primario):** `#1B396A` (*Pantone 294 C*)
* **Dorado Institucional (Advertencia / En Borrador):** `#A57F2C` (*Pantone 1255 C*)
* **Verde Oficial (Éxito / Publicado):** `#1E5B4F` (*Pantone 626 C*)
* **Guinda Institucional (Peligro / Rezagado):** `#9B2247` (*Pantone 7420 C*)

### 5.2 Reglas Prácticas para el Equipo de Frontend
1. **Maquetación, Grids y Espaciados $\rightarrow$ Clases de Tailwind CSS en el `.blade.php`:**
   * Usen clases utilitarias (`grid`, `grid-cols-1 md:grid-cols-3`, `gap-6`, `rounded-xl`, `p-5`, `shadow-sm`).
   * **Obligatorio soportar Modo Claro y Modo Oscuro:** Siempre acompañen los fondos y textos con su variante `dark:` (ejemplo: `bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 text-slate-900 dark:text-white`).
2. **Clases Institucionales Reutilizables $\rightarrow$ Archivo `public/css/atlas-theme.css`:**
   * El archivo **`public/css/atlas-theme.css`** ya se carga automáticamente en todas las pantallas del sistema desde `AdminPanelProvider.php`.
   * Cuando diseñen un componente visual que se va a repetir en varias vistas (ej. `.atlas-card-header`, `.badge-tecnm-dorado`, degradados institucionales o estilos de tablas), declárenlo dentro de `public/css/atlas-theme.css`.
   * **Ventaja:** Todo lo que editen en `public/css/atlas-theme.css` se refleja inmediatamente en el navegador al presionar `F5` sin necesidad de compilar.
3. **¿Cuándo usar `style="..."` inline?:**
   * **Únicamente** para valores numéricos calculados dinámicamente por PHP (por ejemplo, el ancho de una barra de avance: `style="width: {{ $porcentaje }}%;"`).
   * **Eviten** poner colores de fondo o de texto fijos dentro de `style="..."`, ya que ignoran el cambio entre tema claro y oscuro.

---

## Parte 6: Flujo de Trabajo en GitHub para el Equipo

Para que varios desarrolladores de Frontend avancen al mismo tiempo sin pisarse cambios:

1. **Nunca hacer commits directos sobre `main`.**
2. **Crear una rama por vista o tarea (`feature/`):**
   ```bash
   git checkout main
   git pull origin main
   git checkout -b feature/ui-tablero-director-plantel
   ```
3. **Checklist antes de abrir Pull Request (PR):**
   * [ ] Probar la vista iniciando sesión con un rol **Nacional** (`admin@tecnm.mx`), un **Director de Plantel** (`director.cancun@tecnm.mx`) y un **Operador** (`comextras.cancun@tecnm.mx`).
   * [ ] Verificar que la vista se vea bien tanto en **Modo Claro** como en **Modo Oscuro**.
   * [ ] Subir la rama (`git push origin feature/...`) y crear el **Pull Request** en GitHub adjuntando captura de pantalla del cambio visual.

