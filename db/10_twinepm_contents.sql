---
--- Add admin credential.
---

INSERT INTO credentials (id, hash) VALUES (
    0,
    '$2y$10$jIHgcQ3be3GXj98QC2Y4p.ErOh/EbiefZwmxo2Z1uixHBD884V3Ze');


---
--- Add admin account.
---

INSERT INTO accounts (id) VALUES (0);


---
--- Add secretaryBot credential. No ID is needed as the serial sequence starts at 1.
---

INSERT INTO credentials (name, hash) VALUES (
    'secretaryBot',
    '$2y$10$jIHgcQ3be3GXj98QC2Y4p.ErOh/EbiefZwmxo2Z1uixHBD884V3Ze');


---
--- Add secretaryBot account.
---

INSERT INTO accounts (id, name) VALUES (1, 'secretaryBot');