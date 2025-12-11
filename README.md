# phpBB Extensions

## API

This repository contains a phpBB extension that exposes a small JSON API. Every endpoint requires an API key which can be supplied either as the `X-API-Key` header or as a `key` query parameter. Administrators may also restrict forum access by specifying a comma-separated list of allowed forum IDs in the extension's ACP settings.

### Endpoints

| Method | Path | Description |
| ------ | ---- | ----------- |
| GET | `/booskit/phpbbapi/groups` | List all groups (no members). |
| GET | `/booskit/phpbbapi/group/{id}` | Details for a single group including members and leaders. |
| GET | `/booskit/phpbbapi/user/{id}` | Fetch a user by numeric ID with their groups. |
| GET | `/booskit/phpbbapi/user/username/{username}` | Fetch a user by username with their groups. |
| GET | `/booskit/phpbbapi/forum/{id}?limit=50` | Forum information with recent topics. `limit` is optional (default 50). |

Replace placeholders such as `{id}` and `{username}` with actual values when making requests.

The API Endpoint should be
https://domain.com/app.php/booskit/phpbbapi
