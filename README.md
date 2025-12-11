# phpBB Extensions Collection

This repository contains a collection of three phpBB extensions developed by Booskit.

## 1. GTA: World OAuth (`booskit/gtawoauth`)

Allows users to log in or link their phpBB accounts using their GTA: World UCP credentials.

### Features
*   **OAuth Login:** Sign in with GTA: World UCP.
*   **Account Linking:** Link existing phpBB accounts to GTA: World accounts via UCP.
*   **Registration Disabled:** Explicitly disables new account registration via OAuth (linking or login only).
*   **Unified Callback:** Handles both login and linking flows via a single callback URL.

### Usage
1.  Enable the extension in the ACP.
2.  Configure the **Client ID** and **Client Secret** in the extension settings.
3.  Set the **Base Website URL** if necessary.
4.  The "Login with GTAW" button will appear on the login page.
5.  Users can link accounts via their User Control Panel (UCP).

---

## 2. Data Collector (`booskit/datacollector`)

Acts as a "reverse API" that pushes user or thread data to an external URL via POST requests when triggered.

### Features
*   **Trigger Endpoint:** `app.php/datacollector/send`.
*   **Security:** Endpoint is protected and requires Administrator permissions (`acl_a_board`).
*   **User Export:** Pushes all users from a configured Group ID.
    *   Data: User ID, Username, Groups, Primary Group, Leader Status.
*   **Thread Export:** Pushes all threads from a configured Forum ID (using `?type=forum`).
    *   Data: Title, Author, Creation Date.

### Usage
1.  Enable the extension in the ACP.
2.  Configure the **POST API Link**, **Group ID**, and **Forum ID**.
3.  Trigger the export by visiting:
    *   `https://domain.com/app.php/datacollector/send` (for users).
    *   `https://domain.com/app.php/datacollector/send?type=forum` (for threads).

---

## 3. phpBB API (`booskit/phpbbapi`)

Exposes a JSON API to read data from the forum. Access is secured via an API Key.

### Features
*   **Authentication:** Requires `X-API-Key` header or `key` query parameter.
*   **Access Control:** Can restrict forum access to specific Forum IDs via ACP.
*   **Read-Only:** Provides read access to Groups, Users, and Forums.

### Endpoints
The base URL for the API is `https://domain.com/app.php/booskit/phpbbapi`.

| Method | Path | Description |
| ------ | ---- | ----------- |
| GET | `/groups` | List all groups (no members). |
| GET | `/group/{id}` | Details for a single group including members and leaders. |
| GET | `/user/{id}` | Fetch a user by numeric ID with their groups. |
| GET | `/user/username/{username}` | Fetch a user by username with their groups. |
| GET | `/forum/{id}?limit=50` | Forum information with recent topics. `limit` is optional (default 50). |

### Usage
1.  Enable the extension in the ACP.
2.  Configure the **API Key** in the extension settings.
3.  (Optional) Configure allowed **Forum IDs**.
4.  Make HTTP requests to the endpoints using the configured key.
