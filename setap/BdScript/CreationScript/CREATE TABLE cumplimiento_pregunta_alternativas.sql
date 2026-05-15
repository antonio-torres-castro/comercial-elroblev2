CREATE TABLE cumplimiento_pregunta_alternativas (
    id INT NOT NULL AUTO_INCREMENT,
    cumplimiento_pregunta_id INT NOT NULL,
    alternativa VARCHAR(1000) NOT NULL,
    es_correcta TINYINT NOT NULL DEFAULT 0,
    orden_visualizacion INT NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    KEY idx_alternativas_pregunta (cumplimiento_pregunta_id),
    CONSTRAINT fk_alternativas_pregunta
        FOREIGN KEY (cumplimiento_pregunta_id)
        REFERENCES cumplimiento_preguntas(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
