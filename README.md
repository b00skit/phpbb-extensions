# phpBB Extensions Collection

This repository contains a collection of phpBB extensions developed by Booskit.

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

---

## 4. User Awards (`booskit/awards`)

Manages user awards with support for multiple access levels and display on user profiles.

### Features
*   **Award Management:** Add, edit, and remove awards.
*   **Definitions:** Load award definitions from an external JSON URL or manage them locally in the database.
*   **Access Levels:** Configurable Group IDs for Level 1, Level 2, and Full Access permissions.
*   **Profile Display:** Displays awarded badges on user profiles.
*   **Notifications:** Sends notifications to users when they receive an award.
*   **Ruleset:** Configurable global message displayed on the management form.

### Usage
1.  Enable the extension in the ACP.
2.  Configure **Access Groups** for different levels.
3.  Choose **Definitions Source** (URL or Local) and configure accordingly.
4.  Authorized users can issue awards via the user profile.

---

## 5. Disciplinary Actions (`booskit/disciplinary`)

Manages disciplinary actions (records) for users, displayed on their profile.

### Features
*   **Action Management:** Add, edit, and remove disciplinary records.
*   **Definitions:** Load action definitions (e.g., Warning, Ban) from external JSON or local database.
*   **Hierarchy System:** Enforces a hierarchy where users can only target those with lower role levels.
*   **Access Control:** Configurable Group IDs for Level 1, Level 2, Level 3, and Full Access.
*   **Profile Display:** Lists disciplinary history on user profiles.
*   **Ruleset:** Configurable global message displayed on the management form.

### Usage
1.  Enable the extension in the ACP.
2.  Configure **Access Groups** for the 4 levels of access.
3.  Choose **Definitions Source** (URL or Local).
4.  Authorized users can add disciplinary records via the user profile.

---

## 6. User Career (`booskit/usercareer`)

Manages a career timeline for users, allowing tracking of their history and roles.

### Features
*   **Timeline:** Visual timeline of user career history on profiles (showing latest 5 entries).
*   **Definitions:** Load career types/roles from external JSON or local database.
*   **Access Control:** Granular permissions for Viewing (Local/Global) and Management (L1, L2, L3, Full).
*   **Rich Text:** Supports BBCode in career notes.
*   **Ruleset:** Configurable global message displayed on the management form.

### Usage
1.  Enable the extension in the ACP.
2.  Configure **Access Groups** for View and Management levels.
3.  Choose **Definitions Source** (URL or Local).
4.  Authorized users can add career notes via the user profile.

---

## 7. IC Disciplinary Records (`booskit/icdisciplinary`)

Manages In-Character disciplinary records for users' characters, displayed on their profile.

### Features
*   **Action Management:** Add, edit, and remove IC disciplinary records.
*   **Character System:** Actions are associated with specific characters.
*   **Profile Display:** Lists IC disciplinary history on user profiles with character filtering.

### Usage
1.  Enable the extension in the ACP.
2.  Authorized users can add IC disciplinary records via the user profile.
