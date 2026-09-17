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

---

## 14. Two-Factor Authentication (`booskit/twofactor`)

A comprehensive, production-grade Two-Factor Authentication (2FA) extension for phpBB using Time-based One-Time Passwords (RFC 6238 TOTP) and emergency backup keys.

### Features
*   **Sign-in Flow Interception:** Requires 2FA verification when logging in, logging in via OAuth (e.g. GTA:W OAuth), and accessing restricted areas (UCP, MCP, ACP).
*   **Group Policy Customization:**
    *   **Suggestion Prompt:** Configure groups that receive a 30-day skippable setup prompt banner.
    *   **Enforced Setup:** Configure groups strictly required to setup 2FA before being permitted to perform actions.
    *   **Granular Access Requirements:** Separate group rules for standard Login, UCP, MCP, ACP, and GTA:W OAuth login.
    *   **Conditional OAuth Settings:** Automatically detects whether `booskit/gtawoauth` is active.
*   **Standalone TOTP & QR Engine:** Pure PHP RFC 6238 implementation with inline vector SVG QR code generation (zero external APIs/dependencies).
*   **Emergency Backup Keys:** Generates single-use backup keys stored hashed with `password_hash()`, supporting quick clipboard copy, download (.txt), and printing.
*   **ACP User Administration:**
    *   View user 2FA status, last successful 2FA login timestamp, IP address, and method (TOTP vs Backup code).
    *   Admin capability to remotely disable 2FA for individual users.
    *   Admin capability to reset backup keys, prompting the user with their new backup keys upon next login.
*   **UCP Self-Management:** Users can self-enroll, view status and remaining backup keys, regenerate backup keys, or disconnect 2FA.

---

## 15. Dashboard (`booskit/dashboard`)

A modern, comprehensive command center and dashboard providing live oversight over forum activity, real-time user browsing sessions, and unified member profiles.

### Features
*   **Overview & Real-Time Stats:** Live cards displaying total members, members online, active today (past 24h), and topics/posts.
*   **Active Users & Live Browsing:** Real-time visibility into active members with pulsing online status, avatars, and permission-safe browsing location resolution (reading specific topics, browsing forums, writing posts, etc.).
*   **In-Dashboard Member Profiles:** Dedicated profile view aggregating all installed Booskit extensions (`disciplinary`, `icdisciplinary`, `awards`, `usercareer`, `commendations`, `gtawtracker`) strictly obeying their permission matrices.
*   **Group-to-Group Profile Access:** Flexible ACP matrix to configure which user groups can open and view profiles of which target user groups, with administrator override.
*   **Issued Actions Tracking:** Profile section and ACP settings controlling who can view what a user has issued (disciplinary actions, commendations, awards).
*   **Recently Visited Topics:** Tracks topics viewed by members and displays them on profiles based on configured group permissions and forum read ACLs.
*   **Trending & Hot Topics:** Ranked hot topics filterable across 24 hours, 7 days, and 30 days.
