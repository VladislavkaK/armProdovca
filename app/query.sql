CREATE  TABLE  managers (
  id SERIAL PRIMARY KEY ,
  name TEXT NOT NULL,
  position TEXT NOT NULL,
  plan INT CHECK (plan >= 0),
  total INT CHECK (total >= 0),
  login TEXT NOT NULL UNIQUE,
  password TEXT NOT NULL
);

CREATE TABLE sales(
  id SERIAL PRIMARY KEY,
  count INT CHECK (count > 0) NOT NULL,
  price INT CHECK (price > 0) NOT NULL
);

CREATE TABLE goods(
  id SERIAL PRIMARY KEY,
  title TEXT NOT NULL,
  price INT CHECK (price > 0) NOT NULL,
  count INT CHECK (count >= 0) NOT NULL DEFAULT 0
);
ALTER TABLE sales ADD COLUMN manager_id INT REFERENCES managers;
ALTER TABLE sales ADD COLUMN good_id INT REFERENCES goods;