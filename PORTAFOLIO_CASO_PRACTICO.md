# PORTAFOLIO Y CASO PRÁCTICO: DESARROLLO DE APLICACIÓN WEB

---

## 1. Portada

**Institución / Curso:** Desarrollo de Aplicaciones Web  
**Nombre de la Aplicación:** **Mini Abarrotes Don Luis (Minimarket)**  
**Módulos Clave:** Login con Roles, Ventas Diarias (POS), Control de Efectivo y Módulo de Caja  
**Alumno / Desarrollador:** Scarlett  
**Año Académico:** 2026  
**Tecnologías:** PHP 8.2, MySQL 8 / MariaDB, CSS3, JavaScript ES6, Railway Cloud & DBeaver  

---

## 2. Índice

1. Portada
2. Índice
3. Introducción
4. Desarrollo de Proyecto o Actividad
   - Parte A: Identificar Componentes y Módulos
   - Parte B: Elementos Obligatorios del Sistema (Header, Footer, Roles, Módulos)
   - Parte C: Tecnologías Utilizadas
   - Parte D: Desarrollo y Codificación (Estructura de Archivos)
   - Parte E: Complementos Multimedia
   - Parte F: Recursos Usados y Conexión DBeaver
   - Parte G: Elementos de Seguridad del Proyecto Web
   - Parte H: Despliegue de App Web en Hosting Gratuito (Railway)
5. Conclusión y Reflexión Personal
6. Bibliografía

---

## 3. Introducción

El presente proyecto consiste en el desarrollo integral de una solución web para el comercio de abarrotes de barrio **"Mini Abarrotes Don Luis"**. Tradicionalmente, los minimarkets presentan dificultades en el control del flujo físico de efectivo, el registro exacto de las ventas del día, el cuadre o arqueo de caja de los diferentes turnos y la prevención de quiebres de inventario.

Para responder a estos desafíos, se diseñó e implementó un sistema web dinámico tipo CRUD basado en la arquitectura cliente-servidor con **PHP y MySQL**. La aplicación incorpora control de acceso granular mediante dos perfiles diferenciados (**Administrador** y **Empleado/Cajero**), un **Módulo de Caja** para fiscalizar apertura, egresos por gastos menores, ingresos y arqueo final, un punto de venta (POS) para **Ventas Diarias** con cálculo de vuelto en tiempo real, catálogo de productos con alertas de stock mínimo, gestión de usuarios y balances de rendimiento.

---

## 4. Desarrollo de Proyecto o Actividad

### Parte A: Identificar Componentes y Módulos

#### 1. Módulo Login (Autenticación y Control de Acceso)
- **Descripción:** Punto de entrada seguro que valida credenciales y asigna permisos según el rol asignado (Administrador o Empleado).
- **Componentes:**
  - Banner y logotipo institucional del minimarket.
  - Formulario de autenticación (correo electrónico y contraseña encriptada).
  - Mensajes de alerta dinámica ante credenciales erróneas.
  - Indicador de credenciales de prueba para la evaluación.

#### 2. Módulo Inicio (Home / Dashboard)
- **Descripción:** Panel central de bienvenida y monitoreo ejecutivo en tiempo real.
- **Componentes:**
  - Banner principal informativo con accesos rápidos a las funciones operativas.
  - Tarjetas de métricas dinámicas: Total de ventas del día, Estado de la caja (Abierta/Cerrada), Total de productos y Alertas de bajo stock.
  - Resumen de las últimas 5 transacciones registradas.
  - Indicador del turno de caja vigente.

#### 3. Módulo de Caja y Control de Efectivo
- **Descripción:** Módulo neurálgico que permite el control riguroso del flujo físico de dinero en el mostrador.
- **Componentes:**
  - Panel de apertura de turno con ingreso de fondo base (sencillo/cambio).
  - Registro de movimientos extraordinarios de efectivo: Egresos (pago de pan, proveedores, retiro) e Ingresos.
  - Tarjetas de arqueo en tiempo real: Fondo inicial + Ventas efectivo + Ingresos - Egresos = Saldo Teórico en gaveta.
  - Formulario de cierre con cálculo automático de discrepancias (exacto, sobrante o faltante).
  - Historial de cierres de caja y turnos auditables.

#### 4. Módulo de Ventas Diarias (POS - Punto de Venta)
- **Descripción:** Plataforma interactiva para registrar compras en mostrador de manera ágil.
- **Componentes:**
  - Catálogo interactivo de productos con tarjetas visuales, precios y existencias.
  - Barra de búsqueda y filtrado por nombre o código de barra.
  - Carrito de compras / Ticket en sesión de usuario.
  - Selector de método de pago (Efectivo, Transferencia, Tarjeta).
  - Calculadora de vuelto interactiva en tiempo real con Web Audio API de caja registradora.
  - Descuento automático del stock de inventario tras confirmar la venta.

#### 5. Módulo de Catálogo e Inventario (CRUD)
- **Descripción:** Mantenimiento de mercadería con control de existencias y precios.
- **Componentes:**
  - Formulario modal para registrar y editar productos.
  - Filtros por categoría y botón de alerta de bajo stock.
  - Control de permisos: Empleados solo pueden consultar stock; Administradores pueden crear, modificar y eliminar.

#### 6. Módulo de Gestión de Usuarios y Roles (Exclusivo Administrador)
- **Descripción:** Creación y modificación de operadores del sistema.
- **Componentes:**
  - Listado de usuarios con identificación de roles mediante badges de color.
  - Modales para dar de alta nuevos cajeros o modificar contraseñas con encriptación BCrypt.

#### 7. Módulo de Reportes y Balances
- **Descripción:** Análisis financiero de ingresos por rango de fechas.
- **Componentes:**
  - Filtro por fecha inicial y final.
  - Barras porcentuales de recaudación por forma de pago.
  - Ranking de los productos con mayor rotación y recaudación.
  - Botón de impresión limpia de balance.

---

### Parte B: Elementos Obligatorios

| Requerimiento | Implementación en Mini Abarrotes Don Luis |
|---|---|
| **Cabecera (Header)** | Visible en todas las vistas mediante `includes/header.php`. Contiene el isotipo 🏪, enlaces a cada módulo según rol, y etiqueta del usuario activo con su rol. |
| **Pie de Página (Footer)** | Visible en todas las páginas mediante `includes/footer.php`. Detalla los datos del minimarket, año 2026 y créditos del desarrollador. |
| **Menú de Navegación** | Menú con enlaces dinámicos: Inicio, Ventas Diarias, Módulo de Caja, Inventario, Usuarios (Admin), Reportes (Admin) y Salir. |
| **Login con Roles (Mín. 2)** | Roles implementados: `administrador` y `empleado`. El administrador gestiona usuarios y reportes; el empleado está acotado a la operación del POS, caja y consulta de stock. |
| **Módulos CRUD y de Negocio** | Módulo de Caja (apertura, egresos, cierre, arqueo), Módulo de Ventas (carrito, cobro, descuento de stock) y Módulo de Productos (CRUD de inventario con alertas). |
| **Búsqueda y Filtros** | Búsqueda por texto y código en ventas e inventario, filtros por categoría, checkbox de bajo stock y filtros por rango de fechas en reportes. |

---

### Parte C: Tecnologías Utilizadas

- **Front-end:**
  - **HTML5:** Marcado semántico (`<header>`, `<nav>`, `<main>`, `<section>`, `<footer>`).
  - **CSS3:** Sistema de diseño modular con variables CSS (`:root`), flexbox, css grid responsivo, tarjetas y botones con micro-interacciones.
  - **JavaScript (ES6):** Filtrado dinámico, cálculo en vivo de vuelto para cajeros y síntesis de audio para confirmaciones acústicas.
- **Back-end:**
  - **PHP 8.2:** Programación estructurada y modular, manejo de sesiones nativas (`$_SESSION`), controladores CRUD y validaciones del lado del servidor.
- **Base de Datos:**
  - **MySQL / MariaDB:** Tablas relacionales con llaves foráneas (`FOREIGN KEY`), índices únicos e integridad referencial (`ON DELETE CASCADE`).
- **Herramientas de Desarrollo y Administración:**
  - **XAMPP:** Servidor web Apache y MariaDB local.
  - **DBeaver:** Gestor gráfico de bases de datos para administración local y remota vía SSL/TCP.
  - **Railway Cloud:** Plataforma en la nube para el despliegue del aplicativo y base de datos con variables de entorno.

---

### Parte D: Desarrollo y Codificación

#### Estructura de Directorios del Proyecto:
```text
Scarlett/
├── index.php                      # Dashboard principal con métricas y accesos
├── Procfile                       # Archivo de inicio para despliegue Cloud
├── railway.json                   # Configuración del motor Nixpacks en Railway
├── PORTAFOLIO_CASO_PRACTICO.md    # Portafolio completo listo para entrega
├── css/
│   └── styles.css                 # Hoja de estilos moderna y responsiva
├── js/
│   └── scripts.js                 # Interactividad, cálculo de vuelto y Web Audio
├── db/
│   ├── conexion.php               # Conexión PDO universal (XAMPP / Railway / DBeaver)
│   └── datos.sql                  # Script DDL/DML con tablas y datos de prueba
├── includes/
│   ├── header.php                 # Cabecera y menú de navegación según rol
│   └── footer.php                 # Pie de página y créditos
├── media/
│   ├── imagenes/                  # Logotipos y recursos visuales
│   └── audio/                     # Efectos sonoros del sistema
└── pages/
    ├── login.php                  # Formulario de inicio de sesión con roles
    ├── logout.php                 # Destrucción segura de sesiones
    ├── caja.php                   # MÓDULO DE CAJA: Apertura, egresos, ingresos y arqueo
    ├── ventas.php                 # MÓDULO DE VENTAS: POS, carrito, stock y vuelto
    ├── productos.php              # MÓDULO INVENTARIO: CRUD y alertas de stock
    ├── usuarios.php               # MÓDULO USUARIOS: Gestión de cuentas y roles
    ├── reportes.php               # MÓDULO REPORTES: Balance financiero y gráficos
    └── documentacion.php          # Vista web del caso práctico
```

---

### Parte E: Complementos Multimedia

1. **Audio Interactivo (Efecto Caja Registradora / POS):**  
   Implementado mediante **Web Audio API** sintetizado en `js/scripts.js`. Al completar una venta o registrar una transacción, el navegador emite un tono acústico sinusoidal agradable (`oscillator`) que confirma la operación sin depender de descargas de archivos externos pesados.
2. **Íconos y Banners Dinámicos:**  
   Integración de hero banners con gradientes modernos inspirados en tiendas de abarrotes de alta categoría, badges contextuales y simbología visual para estados de caja y niveles de stock.
3. **Animaciones y Micro-interacciones:**  
   Transiciones suaves en CSS (`transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1)`) en tarjetas, botones y efectos de foco en formularios.

---

### Parte F: Recursos Usados y Conexión con DBeaver

#### 1. Configuración de Base de Datos para DBeaver:
El script `db/datos.sql` y la conexión `db/conexion.php` están diseñados con compatibilidad total para **DBeaver**:
- **Host Local:** `localhost` | **Puerto:** `3306`
- **Base de Datos:** `don_luis_db`
- **Usuario:** `root` | **Contraseña:** *(vacía por defecto en XAMPP)*
- **Host en Railway (Remoto DBeaver):** Se toman los datos de la pestaña *Connect* del servicio MySQL en Railway (`MYSQLHOST`, `MYSQLPORT`, `MYSQLUSER`, `MYSQLPASSWORD`, `MYSQLDATABASE`), configurando una conexión estándar MySQL en DBeaver.

#### 2. Credenciales por Defecto de Demostración:
- **Administrador:** `admin@donluis.com` | Clave: `admin123`
- **Empleado (Cajero):** `cajero@donluis.com` | Clave: `empleado123`

---

### Parte G: Elementos de Seguridad del Proyecto Web

1. **Protección contra Inyecciones SQL (SQL Injection):**  
   Se utiliza **PDO (PHP Data Objects)** con consultas preparadas (`prepared statements`) y paso de parámetros vinculados (`execute([$param1, $param2])`), eliminando cualquier concatenación de variables en las sentencias SQL.
2. **Cifrado Fuerte de Contraseñas:**  
   Uso del algoritmo estándar de la industria **BCrypt** mediante las funciones nativas `password_hash()` con costo computacional seguro y `password_verify()`. Ninguna contraseña se almacena en texto plano.
3. **Mitigación de Ataques Cross-Site Scripting (XSS):**  
   Todo dato ingresado por el usuario o renderizado en el navegador pasa por una función de sanitización que aplica `htmlspecialchars($data, ENT_QUOTES, 'UTF-8')`.
4. **Control de Acceso y Sesiones Seguras:**  
   Funciones de guardia `requerirLogin()` y `requerirAdmin()` que validan el estado de la sesión antes de permitir el renderizado de cualquier vista sensible, redirigiendo de inmediato ante accesos indebidos. Al cerrar sesión, las cookies se invalidan y la sesión se destruye en el servidor.

---

### Parte H: Despliegue de App Web en Hosting Gratuito (Railway)

La aplicación está lista para despliegue automático en la plataforma en la nube **Railway**:
1. Se incluye el archivo `Procfile` y `railway.json` que configuran el servidor web PHP.
2. `db/conexion.php` detecta automáticamente si la aplicación se ejecuta en Railway mediante la lectura de `getenv('DATABASE_URL')` o `getenv('MYSQLHOST')`, cambiando la conexión sin necesidad de modificar el código fuente.
3. En la sección de bases de datos de Railway, se importa el archivo `db/datos.sql` directamente desde el panel o a través de **DBeaver** conectado a los datos públicos de Railway.
4. **Enlace / URL de Despliegue:**  
   *(Espacio para pegar la URL generada en Railway, por ejemplo: `https://mini-donluis-production.up.railway.app`)*

---

## 5. Conclusión y Reflexión Personal

El desarrollo del sistema para **Mini Abarrotes Don Luis** permitió consolidar de manera práctica las competencias fundamentales del ciclo de desarrollo de software web. La arquitectura modular adoptada garantiza que cada área del minimarket (arqueo de caja, facturación de ventas y control de mercadería) interactúe con integridad y consistencia.

Una de las principales ventajas obtenidas fue el diseño del **Módulo de Caja**, el cual resuelve un problema real en los negocios familiares: el descontrol del dinero en efectivo y las discrepancias al cierre de la jornada. Asimismo, la separación de privilegios entre Administrador y Empleado proporciona seguridad operativa y minimiza el riesgo de errores humanos. En conclusión, la combinación de PHP, MySQL, prácticas de codificación segura y un diseño visual cuidado da como resultado una herramienta profesional, escalable y plenamente alineada con los requerimientos académicos del caso práctico.

---

## 6. Bibliografía

1. The PHP Group. (2024). *PHP: Manual de PHP - PDO y Preparación de Sentencias*. https://www.php.net/manual/es/book.pdo.php
2. MySQL AB. (2024). *MySQL 8.0 Reference Manual: Relational Database Integrity*. Oracle Corporation. https://dev.mysql.com/doc/
3. Mozilla Developer Network (MDN). (2024). *Web Audio API y Seguridad Web en Formularios*. MDN Web Docs. https://developer.mozilla.org/es/
4. Railway Corp. (2024). *Deploying PHP and MySQL Applications with Nixpacks*. https://docs.railway.app/
5. OWASP Foundation. (2023). *OWASP Top Ten: Cross-Site Scripting (XSS) and SQL Injection Prevention Cheat Sheet*. https://cheatsheetseries.owasp.org/
