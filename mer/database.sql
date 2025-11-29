CREATE TABLE stores (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  slug VARCHAR(80) NOT NULL UNIQUE,
  logo_url VARCHAR(255) NULL,
  primary_color VARCHAR(20) NULL,
  address VARCHAR(255) NULL,
  delivery_time_days_min INT NULL,
  delivery_time_days_max INT NULL,
  contact_email VARCHAR(150) NULL,
  payout_delay_days INT NULL,
  commission_rate_percent DECIMAL(5,2) NULL,
  commission_min_amount DECIMAL(10,2) NULL
  ,tax_rate_percent DECIMAL(5,2) NULL
);

CREATE TABLE product_groups (
  id INT AUTO_INCREMENT PRIMARY KEY,
  store_id INT NOT NULL,
  name VARCHAR(100) NOT NULL,
  FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE
);

CREATE TABLE products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  store_id INT NOT NULL,
  name VARCHAR(150) NOT NULL,
  description TEXT,
  price DECIMAL(10,2) NOT NULL,
  group_id INT NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
  FOREIGN KEY (group_id) REFERENCES product_groups(id)
);

CREATE TABLE pickup_locations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  store_id INT NULL,
  name VARCHAR(120) NOT NULL,
  address VARCHAR(255) NOT NULL,
  FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE SET NULL
);

CREATE TABLE shipping_methods (
  id INT AUTO_INCREMENT PRIMARY KEY,
  store_id INT NOT NULL,
  name VARCHAR(100) NOT NULL,
  type ENUM('delivery','pickup') NOT NULL DEFAULT 'delivery',
  pickup_location_id INT NULL,
  lead_time_days INT NULL,
  cost DECIMAL(10,2) NOT NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
  FOREIGN KEY (pickup_location_id) REFERENCES pickup_locations(id) ON DELETE SET NULL
);

CREATE TABLE product_shipping_methods (
  product_id INT NOT NULL,
  shipping_method_id INT NOT NULL,
  PRIMARY KEY (product_id, shipping_method_id),
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  FOREIGN KEY (shipping_method_id) REFERENCES shipping_methods(id) ON DELETE CASCADE
);

CREATE TABLE group_shipping_methods (
  group_id INT NOT NULL,
  shipping_method_id INT NOT NULL,
  PRIMARY KEY (group_id, shipping_method_id),
  FOREIGN KEY (group_id) REFERENCES product_groups(id) ON DELETE CASCADE,
  FOREIGN KEY (shipping_method_id) REFERENCES shipping_methods(id) ON DELETE CASCADE
);

CREATE TABLE coupons (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(50) NOT NULL UNIQUE,
  type ENUM('free_shipping','percent','amount') NOT NULL,
  value DECIMAL(10,2) NULL,
  expires_at DATETIME NOT NULL,
  active TINYINT(1) NOT NULL DEFAULT 1
);

INSERT INTO stores (name, slug, logo_url, primary_color, address, delivery_time_days_min, delivery_time_days_max, contact_email, payout_delay_days, commission_rate_percent, commission_min_amount, tax_rate_percent) VALUES
('Tienda A', 'tienda-a', NULL, '#1e90ff', 'Av. Principal 123, Ciudad', 1, 3, 'a@example.com', 7, 10.00, 1.00, 19.00),
('Tienda B', 'tienda-b', NULL, '#ff7f50', 'Calle Secundaria 456, Ciudad', 2, 5, 'b@example.com', 10, 7.50, 1.50, 19.00);

INSERT INTO product_groups (store_id, name) VALUES (1, 'Productos'), (1, 'Servicios'), (2, 'Productos'), (2, 'Servicios');

INSERT INTO products (store_id, name, description, price, group_id) VALUES
(1, 'Cafe Arabe', 'Cafe molido 500g', 8.50, 1),
(1, 'Te de Hierbas', 'Te mixto 40 bolsitas', 6.20, 1),
(1, 'Instalacion de Purificador', 'Servicio a domicilio', 35.00, 2),
(2, 'Cafe Colombia', 'Cafe en grano 1kg', 12.90, 3),
(2, 'Filtro de Agua', 'Filtro domestico', 29.00, 4);

INSERT INTO pickup_locations (store_id, name, address) VALUES
(NULL, 'Central Principal', 'Centro Logistico 789, Ciudad'),
(2, 'Bodega Norte B', 'Av. Norte 101, Ciudad');

INSERT INTO shipping_methods (store_id, name, type, pickup_location_id, lead_time_days, cost) VALUES
(1, 'Despacho Normal A', 'delivery', NULL, 3, 5.00),
(1, 'Despacho Express A', 'delivery', NULL, 1, 9.90),
(1, 'Retiro Central', 'pickup', 1, 0, 0.00),
(2, 'Despacho Normal B', 'delivery', NULL, 4, 6.00),
(2, 'Despacho Express B', 'delivery', NULL, 2, 11.50),
(2, 'Retiro Bodega Norte', 'pickup', 2, 0, 0.00);

INSERT INTO group_shipping_methods (group_id, shipping_method_id) VALUES
(1, 1),
(1, 2),
(2, 2),
(3, 4),
(3, 5),
(4, 5);

INSERT INTO product_shipping_methods (product_id, shipping_method_id) VALUES
(3, 2),
(4, 4),
(5, 5);

INSERT INTO coupons (code, type, value, expires_at, active) VALUES
('ENVIOGRATIS', 'free_shipping', NULL, DATE_ADD(NOW(), INTERVAL 30 DAY), 1),
('DESC10', 'percent', 10.00, DATE_ADD(NOW(), INTERVAL 30 DAY), 1),
('MENOS2000', 'amount', 2000.00, DATE_ADD(NOW(), INTERVAL 30 DAY), 1);

CREATE TABLE orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  customer_name VARCHAR(150) NOT NULL,
  email VARCHAR(150) NULL,
  phone VARCHAR(50) NULL,
  address VARCHAR(255) NULL,
  city VARCHAR(100) NULL,
  notes TEXT NULL,
  coupon_id INT NULL,
  subtotal DECIMAL(10,2) NOT NULL,
  discount DECIMAL(10,2) NOT NULL,
  shipping DECIMAL(10,2) NOT NULL,
  total DECIMAL(10,2) NOT NULL,
  payment_method ENUM('transbank','transfer','cash') NULL,
  payment_status ENUM('pending','paid','failed') NOT NULL DEFAULT 'pending',
  payment_reference VARCHAR(100) NULL,
  FOREIGN KEY (coupon_id) REFERENCES coupons(id) ON DELETE SET NULL
);

CREATE TABLE payments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  method ENUM('transbank','transfer','cash') NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  status ENUM('pending','paid','failed') NOT NULL DEFAULT 'pending',
  transaction_id VARCHAR(100) NULL,
  transfer_code VARCHAR(100) NULL,
  pickup_location_id INT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  paid_at DATETIME NULL,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  FOREIGN KEY (pickup_location_id) REFERENCES pickup_locations(id) ON DELETE SET NULL
);

CREATE TABLE order_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  product_id INT NOT NULL,
  store_id INT NOT NULL,
  qty INT NOT NULL,
  unit_price DECIMAL(10,2) NOT NULL,
  shipping_method_id INT NULL,
  shipping_cost_per_unit DECIMAL(10,2) NOT NULL,
  line_subtotal DECIMAL(10,2) NOT NULL,
  line_shipping DECIMAL(10,2) NOT NULL,
  line_total DECIMAL(10,2) NOT NULL,
  delivery_address VARCHAR(255) NULL,
  delivery_city VARCHAR(100) NULL,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
  FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE RESTRICT,
  FOREIGN KEY (shipping_method_id) REFERENCES shipping_methods(id) ON DELETE SET NULL
);

CREATE TABLE order_store_totals (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  store_id INT NOT NULL,
  subtotal DECIMAL(10,2) NOT NULL,
  discount DECIMAL(10,2) NOT NULL,
  shipping DECIMAL(10,2) NOT NULL,
  total DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE RESTRICT
);
CREATE TABLE order_notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  store_id INT NOT NULL,
  channel ENUM('email','log') NOT NULL,
  content TEXT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE RESTRICT
);

CREATE TABLE store_payouts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  store_id INT NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  status ENUM('scheduled','paid','cancelled') NOT NULL DEFAULT 'scheduled',
  scheduled_at DATETIME NULL,
  paid_at DATETIME NULL,
  method VARCHAR(50) NULL,
  reference VARCHAR(100) NULL,
  commission_percent DECIMAL(5,2) NOT NULL,
  commission_min DECIMAL(10,2) NOT NULL,
  commission_amount DECIMAL(10,2) NOT NULL,
  commission_vat_percent DECIMAL(5,2) NOT NULL,
  commission_vat_amount DECIMAL(10,2) NOT NULL,
  commission_gross_amount DECIMAL(10,2) NOT NULL,
  net_amount DECIMAL(10,2) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE RESTRICT
);

-- 8. Movimientos de stock
CREATE TABLE IF NOT EXISTS stock_movements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    store_id INT NOT NULL,
    movement_type ENUM('in', 'out', 'adjustment') NOT NULL,
    quantity INT NOT NULL,
    reference_type ENUM('purchase', 'sale', 'adjustment', 'return', 'damage') NOT NULL,
    reference_id INT,
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
    INDEX idx_product (product_id),
    INDEX idx_store (store_id),
    INDEX idx_date (created_at),
    INDEX idx_type (movement_type)
);