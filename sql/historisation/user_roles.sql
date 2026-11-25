-- Rôles métier des utilisateurs (User::roles, table de liaison user_roles), utilisés par le suivi des opérations :
-- rôle 8 = INTERVENANT (assignation des tâches, alertes mail). À exécuter avant intevenant.sql. Relançable.

IF OBJECT_ID('roles', 'U') IS NULL
    CREATE TABLE roles (
        id INT IDENTITY (1, 1),
        role_name VARCHAR(255) NOT NULL,
        date_creation DATETIME,
        date_modification DATETIME,
        CONSTRAINT PK_roles_id PRIMARY KEY (id)
    );
GO

IF NOT EXISTS (SELECT 1 FROM roles WHERE id = 8)
BEGIN
    SET IDENTITY_INSERT roles ON;
    INSERT INTO roles (id, role_name, date_creation, date_modification) VALUES (8, 'INTERVENANT', GETDATE(), GETDATE());
    SET IDENTITY_INSERT roles OFF;
END
GO

IF OBJECT_ID('user_roles', 'U') IS NULL
    CREATE TABLE user_roles (
        user_id INT NOT NULL,
        role_id INT NOT NULL,
        CONSTRAINT PK_user_roles PRIMARY KEY (user_id, role_id),
        CONSTRAINT FK_user_roles_user_id FOREIGN KEY (user_id) REFERENCES users (id),
        CONSTRAINT FK_user_roles_role_id FOREIGN KEY (role_id) REFERENCES roles (id)
    );
GO
