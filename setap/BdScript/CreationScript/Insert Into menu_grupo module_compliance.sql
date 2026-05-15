Insert Into menu_grupo Values (5, 'module_compliance', 'Modulo para gestionar cumplimiento', 'person-check', 5, 'Cumplimiento', '20260515', null, 2);
SELECT * FROM menu_grupo;
Insert Into menu Values(
31,	'manage_compliance', 'Administración de cumplimientos', 
'2026-05-15', '2026-05-15', 2, 
'/setap/compliance', 'gear', 31, 'Administrar', 5);
Insert Into menu Values(
32, 'my_compliance', 'Mis cumplimientos', 
'2026-05-15', '2026-05-15', 2, 
'/setap/compliance/my', 'clipboard-check', 32, 'Cumplimientos', 5);
Insert Into menu Values(
33, 'manage_assessments', 'Administración de evaluaciones y preguntas', 
'2026-05-15', '2026-05-15', 2, 
'/setap/compliance/assessments', 'ui-checks', 33, 'Administrar Evaluaciones', 5);
Insert Into menu Values(
34, 'compliance_history', 'Historial cumplimiento usuarios', 
'2026-05-15', '2026-05-15', 2, 
'/setap/compliance/history', 'clock-history', 34, 'Historia', 5);
Select * From menu;