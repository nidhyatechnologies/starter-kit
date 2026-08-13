---
paths:
  - 'resources/views/pages/{users,roles,audit}/**'
---

# Usersrolesaudit

## Privilege-safe user management
Only Super Admins may manage Super Admin accounts. Role managers may delegate only permissions they currently hold. Record security and access-management changes through RecordAuditEvent.
