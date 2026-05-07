INSERT INTO usuario_tipos(nombre, descripcion)
Select 'gobernor' nombre, 'Gobernador de Setap, asociado a proveedor' descripcion where not exists(Select id from usuario_tipos Where nombre = 'gobernor');

SELECT * FROM comerci3_bdsetap.usuario_tipos;