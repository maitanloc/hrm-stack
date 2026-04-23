# Deprecated Temp Deploy Scripts

The import-oriented scripts in this folder were used for emergency/manual VPS work and are now blocked.

Reason:

- they bypass UTF-8 validation
- they imported SQL through unsafe client defaults
- they can permanently replace Vietnamese characters with `?`

Use one of these instead:

- repo root: `./import-db.sh`
- VPS: `deploy/vps/import-db.sh`
