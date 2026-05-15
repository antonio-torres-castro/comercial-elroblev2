INSERT INTO cumplimiento_documentos
(
    id,
    proveedor_id,
    nombre,
    codigo,
    descripcion,
    requiere_evaluacion,
    puntaje_minimo,
    cantidad_preguntas,
    vigencia_dias,
    estado_tipo_id
)
VALUES
(
    1,
    3,
    'Programa de Integridad y Ética Empresarial',
    'PIEE-001',
    'Programa corporativo de integridad y ética empresarial',
    1,
    100.00,
    5,
    365,
    2
);

INSERT INTO cumplimiento_documento_versiones
(
    id,
    cumplimiento_documento_id,
    version,
    titulo,
    resumen,
    contenido_html,
    publicado,
    fecha_publicacion,
    fecha_inicio_vigencia,
    creado_por_usuario_id
)
VALUES
(
    1,
    1,
    '1.0',
    'Programa de Integridad y Ética Empresarial',
    'Versión inicial del programa corporativo de integridad y ética empresarial.',
    '<h1>Programa de Integridad y Ética Empresarial</h1>
<h2>1. Objetivo</h2>
<p>El presente Programa de Integridad y Ética Empresarial tiene como finalidad establecer los principios, conductas esperadas, mecanismos de prevención y lineamientos éticos que deben regir el actuar de todos quienes formen parte de la empresa.</p>
<h2>2. Principios Éticos</h2>
<ul>
<li>Honestidad</li>
<li>Respeto</li>
<li>Responsabilidad</li>
<li>Cumplimiento de la ley</li>
<li>Probidad y transparencia</li>
</ul>
<h2>3. Conductas Esperadas</h2>
<ul>
<li>Presentarse correctamente uniformado</li>
<li>Mantener higiene personal adecuada</li>
<li>Utilizar elementos de protección personal</li>
<li>Respetar protocolos de seguridad</li>
<li>Cuidar instalaciones del cliente</li>
<li>Mantener comportamiento profesional</li>
</ul>
<h2>4. Prohibiciones</h2>
<ul>
<li>Consumir alcohol o drogas durante la jornada</li>
<li>Sustraer materiales o bienes</li>
<li>Falsificar registros</li>
<li>Compartir credenciales de acceso</li>
<li>Divulgar información confidencial</li>
<li>Realizar actos de corrupción</li>
</ul>
<h2>5. Canal de Denuncias</h2>
<p>La empresa dispondrá de mecanismos para reportar incumplimientos, conductas indebidas y riesgos.</p>
<h2>6. Declaración</h2>
<p>Declaro haber leído y comprendido el Programa de Integridad y Ética Empresarial de la empresa.</p>',
    1,
    NOW(),
    CURRENT_DATE(),
    1
);

INSERT INTO cumplimiento_preguntas
(id,cumplimiento_documento_version_id,pregunta,orden_visualizacion,estado_tipo_id)
VALUES
(1,1,'¿Está permitido utilizar materiales del cliente para uso personal?',1,2),
(2,1,'¿Debe informar una condición insegura?',2,2),
(3,1,'¿Puede compartir su contraseña con otro trabajador?',3,2),
(4,1,'¿Se permite presentarse bajo efectos de alcohol?',4,2),
(5,1,'¿Debe mantener trato respetuoso con clientes y compañeros?',5,2);

INSERT INTO cumplimiento_pregunta_alternativas
(id,cumplimiento_pregunta_id,alternativa,es_correcta,orden_visualizacion)
VALUES
(1,1,'Sí',0,1),
(2,1,'No',1,2),

(3,2,'Sí',1,1),
(4,2,'No',0,2),

(5,3,'Sí',0,1),
(6,3,'No',1,2),

(7,4,'Sí',0,1),
(8,4,'No',1,2),

(9,5,'Sí',1,1),
(10,5,'No',0,2);

