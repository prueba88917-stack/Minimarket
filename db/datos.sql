
-- 1. Tabla de Usuarios (Roles: administrador, cajero/empleado)
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nombre` VARCHAR(100) NOT NULL,
  `correo` VARCHAR(120) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `rol` ENUM('administrador', 'empleado') NOT NULL DEFAULT 'empleado',
  `telefono` VARCHAR(30) NULL,
  `estado` TINYINT(1) NOT NULL DEFAULT 1,
  `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Tabla de Categorías de Productos
CREATE TABLE IF NOT EXISTS `categorias` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nombre` VARCHAR(80) NOT NULL,
  `descripcion` VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Tabla de Productos (Minimarket / Abarrotes)
CREATE TABLE IF NOT EXISTS `productos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `codigo_barra` VARCHAR(50) NOT NULL UNIQUE,
  `nombre` VARCHAR(150) NOT NULL,
  `categoria_id` INT NULL,
  `precio_compra` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `precio_venta` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `stock` INT NOT NULL DEFAULT 0,
  `stock_minimo` INT NOT NULL DEFAULT 5,
  `unidad` VARCHAR(20) DEFAULT 'unidad',
  `imagen` VARCHAR(255) DEFAULT 'producto_default.png',
  `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`categoria_id`) REFERENCES `categorias`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Tabla Módulo de Caja (Aperturas y Cierres / Control de Efectivo)
CREATE TABLE IF NOT EXISTS `cajas` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `usuario_id` INT NOT NULL,
  `monto_inicial` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `monto_final` DECIMAL(10,2) NULL,
  `ventas_efectivo` DECIMAL(10,2) DEFAULT 0.00,
  `ventas_transferencia` DECIMAL(10,2) DEFAULT 0.00,
  `ingresos_extra` DECIMAL(10,2) DEFAULT 0.00,
  `egresos_gastos` DECIMAL(10,2) DEFAULT 0.00,
  `diferencia` DECIMAL(10,2) DEFAULT 0.00,
  `estado` ENUM('abierta', 'cerrada') DEFAULT 'abierta',
  `observaciones` TEXT NULL,
  `fecha_apertura` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `fecha_cierre` TIMESTAMP NULL,
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Tabla de Movimientos de Caja (Ingresos / Egresos / Retiros)
CREATE TABLE IF NOT EXISTS `movimientos_caja` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `caja_id` INT NOT NULL,
  `usuario_id` INT NOT NULL,
  `tipo` ENUM('ingreso', 'egreso') NOT NULL,
  `concepto` VARCHAR(200) NOT NULL,
  `monto` DECIMAL(10,2) NOT NULL,
  `fecha` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`caja_id`) REFERENCES `cajas`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Tabla de Ventas Diarias
CREATE TABLE IF NOT EXISTS `ventas` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `caja_id` INT NULL,
  `usuario_id` INT NOT NULL,
  `cliente_nombre` VARCHAR(120) DEFAULT 'Cliente General',
  `total` DECIMAL(10,2) NOT NULL,
  `metodo_pago` ENUM('efectivo', 'transferencia', 'tarjeta') DEFAULT 'efectivo',
  `monto_recibido` DECIMAL(10,2) DEFAULT 0.00,
  `vuelto` DECIMAL(10,2) DEFAULT 0.00,
  `fecha_venta` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`caja_id`) REFERENCES `cajas`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Detalle de Ventas
CREATE TABLE IF NOT EXISTS `detalle_ventas` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `venta_id` INT NOT NULL,
  `producto_id` INT NOT NULL,
  `cantidad` INT NOT NULL,
  `precio_unitario` DECIMAL(10,2) NOT NULL,
  `subtotal` DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (`venta_id`) REFERENCES `ventas`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`producto_id`) REFERENCES `productos`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- INSERCIÓN DE DATOS INICIALES Y PRUEBAS
-- Passwords hasheadas con password_hash('admin123', PASSWORD_BCRYPT) y 'empleado123'
-- Hash de 'admin123': $2y$10$vO8uXfJpI5V3W8rB1QjTquW0R1uW7K2jD8q2H2YyY2o1o1X1qX1qO -> Usaremos hash standard verificable
INSERT INTO `usuarios` (`nombre`, `correo`, `password`, `rol`, `telefono`, `estado`) VALUES
('Don Luis Administrador', 'admin@donluis.com', '$2y$10$D9pKmfi8AzEggnAezK6n2OD/aJePGLsVeInPX/i5gksG85.f1/Tm.', 'administrador', '+56 9 1234 5678', 1),
('Carlos Cajero', 'cajero@donluis.com', '$2y$10$U.bx//KvRxniuQdnXffN0e6WcMWjwc9urh9BxHVIJsh4mmZSf9t/W', 'empleado', '+56 9 8765 4321', 1)
ON DUPLICATE KEY UPDATE `nombre`=VALUES(`nombre`);

INSERT INTO `categorias` (`id`, `nombre`, `descripcion`) VALUES
(1, 'Abarrotes y Despensa', 'Arroz, fideos, aceites, harinas y enlatados'),
(2, 'Lácteos y Refrigerados', 'Leche, quesos, yogures, mantequillas'),
(3, 'Bebidas y Jugos', 'Aguas minerales, gaseosas, néctares y cervezas'),
(4, 'Snacks y Golosinas', 'Galletas, papas fritas, chocolates'),
(5, 'Aseo y Limpieza', 'Detergentes, jabones, desinfectantes')
ON DUPLICATE KEY UPDATE `nombre`=VALUES(`nombre`);

INSERT INTO `productos` (`codigo_barra`, `nombre`, `categoria_id`, `precio_compra`, `precio_venta`, `stock`, `stock_minimo`, `unidad`, `imagen`) VALUES
('78010001', 'Arroz Grado 1 1kg', 1, 950.00, 1350.00, 45, 10, 'kg', 'arroz.png'),
('78010002', 'Aceite Vegetal 900ml', 1, 1400.00, 1990.00, 30, 8, 'botella', 'aceite.png'),
('78010003', 'Fideos Espagueti 400g', 1, 550.00, 890.00, 60, 15, 'paquete', 'fideos.png'),
('78010004', 'Azúcar Blanca 1kg', 1, 800.00, 1150.00, 35, 10, 'kg', 'azucar.png'),
('78020001', 'Leche Entera 1L', 2, 750.00, 1090.00, 40, 12, 'litro', 'leche.png'),
('78020002', 'Queso Gauda Laminado 250g', 2, 1600.00, 2290.00, 18, 6, 'paquete', 'queso.png'),
('78030001', 'Bebida Coca-Cola 1.5L', 3, 1200.00, 1750.00, 50, 15, 'botella', 'bebida.png'),
('78030002', 'Jugo Néctar Durazno 1.5L', 3, 900.00, 1390.00, 25, 8, 'botella', 'jugo.png'),
('78040001', 'Galletas Tritón Chocolate 126g', 4, 450.00, 750.00, 4, 10, 'paquete', 'galleta.png'),
('78040002', 'Papas Fritas Lays Clásicas 150g', 4, 980.00, 1490.00, 3, 8, 'bolsa', 'papas.png'),
('78050001', 'Detergente Líquido 1L', 5, 1800.00, 2690.00, 15, 5, 'botella', 'detergente.png')
ON DUPLICATE KEY UPDATE `stock`=VALUES(`stock`);

-- Caja de demostración inicial abierta
INSERT INTO `cajas` (`id`, `usuario_id`, `monto_inicial`, `monto_final`, `ventas_efectivo`, `ventas_transferencia`, `ingresos_extra`, `egresos_gastos`, `estado`, `observaciones`) VALUES
(1, 1, 50000.00, NULL, 4830.00, 0.00, 0.00, 5000.00, 'abierta', 'Apertura de turno de la mañana')
ON DUPLICATE KEY UPDATE `monto_inicial`=VALUES(`monto_inicial`);

-- Movimiento de egreso de prueba (pago de panadería)
INSERT INTO `movimientos_caja` (`caja_id`, `usuario_id`, `tipo`, `concepto`, `monto`) VALUES
(1, 1, 'egreso', 'Pago a proveedor de pan fresco', 5000.00)
ON DUPLICATE KEY UPDATE `monto`=VALUES(`monto`);

-- Venta de prueba
INSERT INTO `ventas` (`id`, `caja_id`, `usuario_id`, `cliente_nombre`, `total`, `metodo_pago`, `monto_recibido`, `vuelto`) VALUES
(1, 1, 1, 'Cliente Mostrador', 4830.00, 'efectivo', 5000.00, 170.00)
ON DUPLICATE KEY UPDATE `total`=VALUES(`total`);

INSERT INTO `detalle_ventas` (`venta_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES
(1, 1, 2, 1350.00, 2700.00),
(1, 2, 1, 1990.00, 1990.00),
(1, 4, 1, 140.00, 140.00)
ON DUPLICATE KEY UPDATE `cantidad`=VALUES(`cantidad`);
