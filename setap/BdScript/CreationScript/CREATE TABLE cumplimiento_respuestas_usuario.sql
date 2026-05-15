CREATE TABLE cumplimiento_respuestas_usuario (
    id INT NOT NULL AUTO_INCREMENT,
    cumplimiento_lectura_id INT NOT NULL,
    cumplimiento_pregunta_id INT NOT NULL,
    cumplimiento_pregunta_alternativa_id INT NOT NULL,
    correcta TINYINT NOT NULL DEFAULT 0,
    fecha_creacion TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_respuestas_lectura (cumplimiento_lectura_id),
    CONSTRAINT fk_respuesta_lectura
        FOREIGN KEY (cumplimiento_lectura_id)
        REFERENCES cumplimiento_lecturas(id),
    CONSTRAINT fk_respuesta_pregunta
        FOREIGN KEY (cumplimiento_pregunta_id)
        REFERENCES cumplimiento_preguntas(id),
    CONSTRAINT fk_respuesta_alternativa
        FOREIGN KEY (cumplimiento_pregunta_alternativa_id)
        REFERENCES cumplimiento_pregunta_alternativas(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;