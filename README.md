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

---

## 4. User Awards (`booskit/awards`)

Manages user awards with support for multiple access levels and display on user profiles.

### Features
*   **Award Management:** Add, edit, and remove awards.
*   **Definitions:** Load award definitions from an external JSON URL or manage them locally in the database.
*   **Access Levels:** Configurable Group IDs for Level 1, Level 2, and Full Access permissions.
*   **Profile Display:** Displays awarded badges on user profiles.

---

## 5. Disciplinary Actions (`booskit/disciplinary`)

Manages disciplinary records for users, displayed on their profile.

### Features
*   **Action Management:** Add, edit, and remove disciplinary records.
*   **Hierarchy System:** Enforces a hierarchy where users can only target those with lower role levels.
*   **Access Control:** Configurable Group IDs for Level 1, Level 2, Level 3, and Full Access.

---

## 6. User Career (`booskit/usercareer`)

Manages a career timeline for users, allowing tracking of their history and roles.

### Features
*   **Timeline:** Visual timeline of user career history on profiles.
*   **Access Control:** Granular permissions for Viewing and Management.
*   **Rich Text:** Supports BBCode in career notes.

---

## 7. IC Disciplinary Records (`booskit/icdisciplinary`)

Manages In-Character disciplinary records for users' characters, displayed on their profile.

### Features
*   **Action Management:** Add, edit, and remove IC disciplinary records.
*   **Character System:** Actions are associated with specific characters.

---

## 8. Custom Forms (`booskit/forms`)

A powerful and flexible extension that allows administrators to create custom forms. When a user submits a form, the extension automatically generates a forum post in a designated forum using a customizable template.

### Features
*   **Custom Field Types:** Support for Text, Textarea, Select, Checkbox, Radio, Number, and Date.
*   **Advanced Template Engine:** Robust `{{ variable }}` syntax with support for loops and system tags.
*   **Automatic Post Generation:** Posts are created automatically in a target forum upon submission.
*   **Summary Tags:** Use `{{ SUMMARY }}` to instantly dump all form data into the post.

### Template Logic
*   **Simple Tags:** `{{ my_field }}` resolves to the display label (human-readable).
*   **Field Loops:** `{{#my_field}} {{label}} ({{value}}) {{/my_field}}` iterates over selections (e.g. checkboxes).
*   **System Tags:** `{{ FORM_NAME }}`, `{{ USERNAME }}`, `{{ DATE }}`, `{{ TIME }}`.
*   **Global Loops:** `{{#fields}} [b]{{label}}:[/b] {{value}} {{/fields}}` loops through every field in the form.

---

## 9. User Commendations (`booskit/commendations`)

Allows authorized users to issue and manage commendations for other users, displayed on their profiles.

### Features
*   **Commendation Management:** Issue, edit, and remove commendations.
*   **Profile Display:** Displays commendations on the user's profile.
*   **Access Control:** Group-based permissions for managing commendations.

---

## 10. Forum Privacy (`booskit/forumprivacy`)

Adds advanced privacy controls to specific forums, allowing for "private" forum behavior.

### Features
*   **Self-Only View:** Users can only see their own topics in configured forums.
*   **Posting Controls:** Restricts users to only interacting with their own content.
*   **Search Isolation:** Users only find their own content in search results within private forums.

---

## 11. Post As (`booskit/postas`)

Allows users to post as alternative characters while maintaining their main account's underlying identity.

### Features
*   **Character Aliases:** Switch between different character identities when posting.
*   **Aesthetic Override:** Shows the alternate character's name color and rank image.
*   **Account Integrity:** Maintains the main user's underlying attributes and statistics.

---

## 12. GTA: World Tracker (`booskit/gtawtracker`)

Integrates with GTA: World OAuth to track and display character-specific data on the forum.

### Features
*   **Character Integration:** Links forum profiles with GTA: World character data.
*   **Dynamic Tracking:** Displays character stats, assets, or status directly on the forum.
*   **OAuth Dependent:** Requires `booskit/gtawoauth` to be installed and configured.

---

## 13. User Command Center (`booskit/usercommandcenter`)

An aggregated dashboard that provides a centralized interface for managing various Booskit extensions.

### Features
*   **Unified Dashboard:** A single location to view and manage data from multiple extensions (Awards, Careers, Disciplinary, etc.).
*   **Streamlined Workflow:** Reduces the need to navigate between multiple profile tabs or ACP pages.
