    </div><!-- Fin .container -->
  </main><!-- Fin .main-content -->

  <!-- Pie de Página (Footer) Obligatorio -->
  <footer class="site-footer">
    <div class="container">
      <div class="footer-content">
        <div>
          <h4 style="color:white; margin-bottom:0.5rem; display:flex; align-items:center; gap:0.5rem;">
            <span>🏪</span> Mini Abarrotes Don Luis
          </h4>
          <p style="font-size:0.88rem; max-width:420px; color:#94a3b8;">
            Sistema de punto de venta (POS), control de ventas diarias, flujo de caja y gestión de inventario para minimarkets de barrio.
          </p>
        </div>

        <div>
          <h5 style="color:white; margin-bottom:0.5rem; font-size:0.95rem;">Enlaces Rápidos</h5>
          <ul style="list-style:none; font-size:0.85rem; line-height:1.9;">
            <li><a href="<?php echo BASE_URL; ?>index.php" style="color:#cbd5e1;">Inicio / Dashboard</a></li>
            <li><a href="<?php echo BASE_URL; ?>pages/ventas.php" style="color:#cbd5e1;">Punto de Venta (POS)</a></li>
            <li><a href="<?php echo BASE_URL; ?>pages/caja.php" style="color:#cbd5e1;">Control de Caja y Efectivo</a></li>
            <li><a href="<?php echo BASE_URL; ?>pages/productos.php" style="color:#cbd5e1;">Catálogo e Inventario</a></li>
          </ul>
        </div>

        <div>
          <h5 style="color:white; margin-bottom:0.5rem; font-size:0.95rem;">Información del Negocio</h5>
          <p style="font-size:0.85rem; color:#cbd5e1;"><strong>Atención:</strong> Lunes a Domingo</p>
          <p style="font-size:0.85rem; color:#cbd5e1;"><strong>Horario:</strong> 08:30 - 22:00 hrs</p>
          <p style="font-size:0.85rem; color:#cbd5e1;"><strong>Ubicación:</strong> Santiago, Chile</p>
        </div>
      </div>

      <div class="footer-bottom">
        <p>&copy; <?php echo date('Y'); ?> <strong>Mini Abarrotes Don Luis</strong> - Todos los derechos reservados. Desarrollado con PHP, MySQL, CSS y JS.</p>
      </div>
    </div>
  </footer>

  <!-- Scripts -->
  <script src="<?php echo BASE_URL; ?>js/scripts.js"></script>
</body>
</html>
