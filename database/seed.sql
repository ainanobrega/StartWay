-- Dados demonstrativos opcionais; crie usuários reais pela interface de cadastro.
INSERT INTO habilidades (nome) VALUES ('Python'),('Comunicação'),('JavaScript'),('SQL') ON DUPLICATE KEY UPDATE nome=VALUES(nome);
