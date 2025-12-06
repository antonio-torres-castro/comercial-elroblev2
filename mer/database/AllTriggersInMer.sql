log_delivery_creation	INSERT	deliveries	BEGIN
     INSERT INTO delivery_activity_log (delivery_id, action, description, user_type)
     VALUES (NEW.id, 'entrega_creada', CONCAT('Entrega creada - Cliente: ', NEW.customer_name, ', Dirección: ', NEW.delivery_address), 'system');
 END	AFTER	2025-12-05 22:41:25.57	ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION	root@localhost	utf8mb4	utf8mb4_0900_ai_ci	utf8mb4_0900_ai_ci
update_driver_stats_after_delivery	UPDATE	deliveries	BEGIN
     DECLARE driver_id_var INT;
     DECLARE is_success TINYINT(1);
     
     -- Solo procesar si cambió el estado de entrega
     IF OLD.status != NEW.status AND NEW.assigned_driver_id IS NOT NULL THEN
         SET driver_id_var = NEW.assigned_driver_...	AFTER	2025-12-05 22:41:25.55	ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION	root@localhost	utf8mb4	utf8mb4_0900_ai_ci	utf8mb4_0900_ai_ci
update_stock_on_order	INSERT	order_items	BEGIN
     DECLARE current_stock INT;
     
     SELECT stock_quantity INTO current_stock 
     FROM products 
     WHERE id = NEW.product_id;
     
     IF current_stock IS NULL THEN
         SET current_stock = 0;
     END IF;
     
     -- Actualizar stock
     U...	AFTER	2025-11-22 17:01:25.85	ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION	root@localhost	utf8mb4	utf8mb4_0900_ai_ci	utf8mb4_0900_ai_ci
restore_stock_on_cancellation	UPDATE	orders	BEGIN
     IF NEW.payment_status = 'cancelled' AND OLD.payment_status != 'cancelled' THEN
         INSERT INTO stock_movements (product_id, store_id, movement_type, quantity, reference_type, reference_id, notes)
         SELECT product_id, store_id, 'in', q...	AFTER	2025-11-22 17:01:16.15	ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION	root@localhost	utf8mb4	utf8mb4_0900_ai_ci	utf8mb4_0900_ai_ci
log_appointment_insert	INSERT	store_appointments	BEGIN
     INSERT INTO `appointment_status_history` (
         `appointment_id`, `old_status`, `new_status`, 
         `changed_by`, `changed_at`
     ) VALUES (
         NEW.id, NULL, NEW.status, 
         NEW.created_by, NEW.created_at
     );
 END	AFTER	2025-11-29 23:21:16.05	ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION	root@localhost	utf8mb4	utf8mb4_0900_ai_ci	utf8mb4_0900_ai_ci
log_appointment_update	UPDATE	store_appointments	BEGIN
     IF OLD.status != NEW.status THEN
         INSERT INTO `appointment_status_history` (
             `appointment_id`, `old_status`, `new_status`, 
             `changed_by`, `changed_at`
         ) VALUES (
             NEW.id, OLD.status, NEW.status,...	AFTER	2025-11-29 23:21:16.08	ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION	root@localhost	utf8mb4	utf8mb4_0900_ai_ci	utf8mb4_0900_ai_ci
log_config_changes_insert	INSERT	store_configurations	BEGIN
     INSERT INTO `configuration_logs` (
         `store_id`, 
         `action`, 
         `category`, 
         `config_key`, 
         `new_value`,
         `created_at`
     ) VALUES (
         NEW.store_id,
         'INSERT',
         NEW.category,
       ...	AFTER	2025-11-29 23:07:21.17	ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION	root@localhost	utf8mb4	utf8mb4_0900_ai_ci	utf8mb4_0900_ai_ci
log_config_changes_update	UPDATE	store_configurations	BEGIN
     INSERT INTO `configuration_logs` (
         `store_id`, 
         `action`, 
         `category`, 
         `config_key`, 
         `old_value`,
         `new_value`,
         `created_at`
     ) VALUES (
         NEW.store_id,
         'UPDATE',
        ...	AFTER	2025-11-29 23:07:21.20	ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION	root@localhost	utf8mb4	utf8mb4_0900_ai_ci	utf8mb4_0900_ai_ci