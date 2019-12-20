
DROP TABLE IF EXISTS users;
CREATE TABLE users (
    id serial primary key NOT NULL,
    username text NOT NULL,
    password text NOT NULL,
    description text
);

INSERT INTO users (username, password, description) VALUES ('danny', 'blarps', 'testmeister');

