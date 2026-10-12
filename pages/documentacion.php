<?php
$titulo_pagina = "Documentación y Portafolio del Caso Práctico";
require_once __DIR__ . '/../db/conexion.php';
require_once __DIR__ . '/../includes/header.php';
?>

<div style="max-width: 900px; margin: 0 auto;">
  
  <div class="card" style="margin-bottom: 2rem;">
    <div style="text-align: center; border-bottom: 2px solid var(--border); padding-bottom: 1.5rem; margin-bottom: 1.5rem;">
      <span class="badge badge-success" style="font-size: 0.85rem; margin-bottom: 0.5rem;">EVALUACIÓN CASO PRÁCTICO</span>
      <h2>Desarrollo de Aplicación Web: Mini Abarrotes Don Luis</h2>
      <p style="color: var(--text-muted); font-size: 0.95rem;">Minimarket - Login, Ventas Diarias, Control de Efectivo y Módulo de Caja</p>
    </div>

    <h3 style="margin-bottom: 1rem; color: var(--primary);">📋 Índice del Portafolio Académico</h3>
    <ol style="padding-left: 1.5rem; line-height: 2; font-size: 0.95rem; margin-bottom: 2rem;">
      <li><strong>Portada y Datos Informativos</strong></li>
      <li><strong>Introducción al Proyecto</strong></li>
      <li><strong>Parte A:</strong> Identificación de Componentes y Módulos</li>
      <li><strong>Parte B:</strong> Elementos Obligatorios (Header, Footer, Roles, Módulos)</li>
      <li><strong>Parte C:</strong> Tecnologías Utilizadas (HTML5, CSS3, JS, PHP, MySQL)</li>
      <li><strong>Parte D:</strong> Estructura del Directorio y Código Fuente</li>
      <li><strong>Parte E:</strong> Complementos Multimedia (Web Audio sintetizado, íconos y banners)</li>
      <li><strong>Parte F:</strong> Recursos y Herramientas (XAMPP, DBeaver, Railway)</li>
      <li><strong>Parte G:</strong> Elementos de Seguridad Web (BCrypt, PDO Prepared Statements, Sanitización)</li>
      <li><strong>Parte H:</strong> Despliegue en Servidor en la Nube (Railway)</li>
      <li><strong>Conclusión y Reflexión Personal</strong></li>
      <li><strong>Bibliografía y Referencias</strong></li>
    </ol>

    <div style="background: #f8fafc; border-left: 4px solid var(--primary); padding: 1.25rem; border-radius: 6px; margin-bottom: 2rem;">
      <h4 style="margin-bottom: 0.5rem;">🏪 Resumen de Módulos Implementados</h4>
      <ul style="padding-left: 1.25rem; font-size: 0.9rem; line-height: 1.8;">
        <li><strong>Autenticación y Roles:</strong> Administrador (gestión total, usuarios, reportes) y Empleado (punto de venta y consulta de stock).</li>
        <li><strong>Módulo de Caja:</strong> Apertura con fondo base, registro de ingresos/egresos, arqueo de turno y cálculo de diferencia de efectivo.</li>
        <li><strong>Módulo de Ventas Diarias (POS):</strong> Carrito en sesión, búsqueda por código de barra, totalizador y cálculo de vuelto interactivo.</li>
        <li><strong>Módulo de Inventario:</strong> Catálogo con alertas de bajo stock y CRUD completo para productos.</li>
        <li><strong>Módulo de Reportes:</strong> Gráficos de recaudación por medio de pago y ranking de productos más vendidos.</li>
      </ul>
    </div>

    <div style="background: #eef2ff; border: 1px solid #c7d2fe; padding: 1.25rem; border-radius: 8px;">
      <h4 style="color: #3730a3; margin-bottom: 0.5rem;">💡 Documento Completo Portafolio</h4>
      <p style="color: #4338ca; font-size: 0.9rem; margin-bottom: 0.75rem;">
        Se ha generado el archivo <code>PORTAFOLIO_CASO_PRACTICO.md</code> en la raíz del proyecto listo para copiar a Word/PDF o entregar con todas las respuestas, tablas y explicaciones teóricas y prácticas requeridas.
      </p>
      <a href="<?php echo BASE_URL; ?>index.php" class="btn btn-primary btn-sm">Volver al Inicio</a>
    </div>

  </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
