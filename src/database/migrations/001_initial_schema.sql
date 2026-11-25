CREATE TABLE IF NOT EXISTS users (
  id        INTEGER PRIMARY KEY,
  username  TEXT NOT NULL COLLATE NOCASE UNIQUE,
  email     TEXT UNIQUE COLLATE NOCASE,
  passhash  TEXT NOT NULL,
  role      TEXT NOT NULL DEFAULT 'user' CHECK (role IN ('user', 'admin')),
  created   INTEGER NOT NULL DEFAULT (unixepoch())
);
CREATE INDEX idx_users_role ON users(role);

CREATE TABLE IF NOT EXISTS albums (
  id             INTEGER PRIMARY KEY,
  slug           TEXT NOT NULL UNIQUE,
  author         INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  title          TEXT NOT NULL DEFAULT 'Untitled album',
  description    TEXT NOT NULL DEFAULT '',
  visibility     TEXT NOT NULL DEFAULT 'public' CHECK (visibility IN ('public', 'unlisted', 'private')),
  cover          INTEGER,
  views          INTEGER NOT NULL DEFAULT 0,
  created        INTEGER NOT NULL DEFAULT (unixepoch()),
  updated        INTEGER NOT NULL DEFAULT (unixepoch())
);
CREATE INDEX idx_albums_user    ON albums(author, created);
CREATE INDEX idx_albums_listing ON albums(visibility, created);

CREATE TABLE IF NOT EXISTS images (
  id            INTEGER PRIMARY KEY,
  album         INTEGER NOT NULL REFERENCES albums(id) ON DELETE CASCADE,
  filename      TEXT NOT NULL UNIQUE,
  thumb         TEXT NOT NULL UNIQUE,
  original_name TEXT NOT NULL DEFAULT '',
  mime          TEXT NOT NULL,
  width         INTEGER NOT NULL,
  height        INTEGER NOT NULL,
  bytes         INTEGER NOT NULL,
  caption       TEXT NOT NULL DEFAULT '',
  position      INTEGER NOT NULL DEFAULT 0,
  created       INTEGER NOT NULL DEFAULT (unixepoch())
);
CREATE INDEX idx_images_album ON images(album, position);

CREATE TABLE IF NOT EXISTS favorites (
  user       INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  album      INTEGER NOT NULL REFERENCES albums(id) ON DELETE CASCADE,
  created    INTEGER NOT NULL DEFAULT (unixepoch()),
  PRIMARY KEY (user, album)
);
CREATE INDEX idx_favorites_album ON favorites(album);