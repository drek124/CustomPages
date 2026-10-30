<div align="center">

# 🧩 CustomPages Joomla! Component

**Fully custom HTML pages for Joomla, with no template involved.**

Write a complete HTML document in CodeMirror, attach it to a menu item, and Joomla sends it out byte for byte.

![Joomla](https://img.shields.io/badge/Joomla-4.4%20%7C%205.x-5091cd?logo=joomla&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.1%2B-777bb4?logo=php&logoColor=white)
![Version](https://img.shields.io/badge/version-1.0.0-informational)
![License](https://img.shields.io/badge/license-GPL--2.0--or--later-blue)

</div>

---

## Why

Landing pages, one-off campaign pages, embedded apps, and "the designer sent us an HTML file" pages often don't fit the site template. Common workarounds fall short:

| Approach | Problem |
| --- | --- |
| Article with raw HTML | The editor and text filters strip `<script>`, `<style>`, `<head>`. The template still wraps the article. |
| `?tmpl=component` | Still loads Joomla's `<head>` assets and the template's `component.php`. |
| Custom module | Stored in a `TEXT` column, so it hits the ~64 KB limit. Also needs a template position. |

**com_custompages** stores the document in its own `MEDIUMTEXT` table (up to 16 MB). When the menu item is requested, it discards every output buffer, sends headers, echoes the stored HTML untouched, and closes the application. The template and the `Document` object never render.

## Features

- ✍️ **CodeMirror editor** with HTML syntax highlighting (`filter="raw"`, so nothing is stripped)
- 🧾 **Byte-identical output**, including `<!DOCTYPE>`, inline `<script>` and `<style>` blocks, and even text like `{loadposition}`, which content plugins never touch
- 📏 **Large pages**: `MEDIUMTEXT` storage instead of menu or module params
- 🔗 **Menu item type** "Custom Page" with a page picker and SEF URLs (`/your-menu-alias`, or `/component/custompages/page/12-alias` without a menu item)
- 🔐 **Joomla access levels**: unpublished pages return 404, and pages the user can't view return 403
- 🛡️ **ACL**: `core.admin`, `core.options`, `core.manage`, `core.create`, `core.edit`, `core.edit.state`, `core.delete`
- 🗄️ **Optional `Cache-Control` header** per menu item
- 🧱 Modern Joomla 4/5 architecture: namespaced MVC, `services/provider.php`, DI container, Dispatcher, RouterView

## Installation

1. Download `com_custompages-<version>.zip` from the [Releases](../../releases) page, or build it yourself with `python build.py`.
2. In the Joomla administrator, go to **System → Install → Extensions** and upload the ZIP.
3. Open **Components → Custom Pages**.

On the command line:

```bash
php cli/joomla.php extension:install --path=/path/to/com_custompages-1.0.0.zip
```

The package uses `method="upgrade"`, so you can install a newer ZIP over an existing one.

### Updates

Custom Pages uses **Joomla's built-in update system**. New releases appear under **System → Update → Extensions** (and in the update notifications), and you install them with one click like any other extension. Joomla verifies each download against the SHA-512 checksum published in the update feed.

## Usage

1. **Components → Custom Pages → New.**
2. Enter a title (the alias is generated if left empty) and paste or write the full HTML document:

   ```html
   <!DOCTYPE html>
   <html lang="en">
   <head>
     <meta charset="utf-8">
     <title>My landing page</title>
     <style>body { font-family: system-ui; }</style>
   </head>
   <body>
     <h1>Hello</h1>
     <script>console.log('runs as-is');</script>
   </body>
   </html>
   ```

3. Set **Status** to *Published* and choose an **Access** level. Save.
4. **Menus → (your menu) → New**, then set **Menu Item Type → Custom Pages → Custom Page**.
5. Pick the page under **Select Page**. Optionally, on the **HTTP** tab, set a `Cache-Control` value such as `public, max-age=300`.
6. Visit the menu item. *View source* shows exactly your document and nothing else.

> **Note:** The page picker only lists **published** pages.

## ⚠️ Security: this is a trusted-admin feature

Editing a page means **writing arbitrary HTML and JavaScript that runs on your site's origin**. A page can read cookies that aren't `HttpOnly`, make authenticated requests as the visiting user (including an administrator), deface the site, or load third-party scripts.

- Grant **Create / Edit** on Custom Pages **only to fully trusted administrators**. In practice this is equivalent to Super User–level trust.
- The default permissions inherit from Global Configuration. Review them under **Custom Pages → Options → Permissions**.
- Joomla's **Text Filters** do not apply to this field (`filter="raw"`, on purpose). That is the whole point of the component, and it is also the risk.
- Page **titles** are escaped in the admin list. Stored **HTML** is never escaped or filtered on the frontend.

## Caveats

Because the response is sent and the application is closed during the component's dispatch, the rest of Joomla's request lifecycle doesn't run:

| Not applied | Effect |
| --- | --- |
| `onAfterRender` / `onAfterRespond` system plugins | Page cache (`plg_system_cache`), SEF link rewriting (`plg_system_sef`), and any plugin that post-processes the HTML won't touch these pages. |
| Content plugins (`onContentPrepare`) | `{loadposition}`, `{loadmodule}`, email cloaking, etc. are output literally. |
| Joomla's own Gzip compression | Use web-server compression (`mod_deflate`, `brotli`, nginx `gzip`) instead. |
| Template, modules, `<head>` assets | None are loaded, so the page has to include everything it needs. |
| Debug console | The Joomla debug bar is not injected. |

Still applied:

- Headers that plugins set **before** dispatch, such as the HTTP Headers plugin (`X-Frame-Options`, `Referrer-Policy`, CSP, …).
- Session handling, access levels, and SEF **routing** (incoming URLs and building links).

Notes:

- **Line endings.** Browsers submit form fields with CRLF line breaks, while CodeMirror stores LF. The model converts CRLF back to LF on save, so the stored document is exactly what the editor showed. Nothing else is changed.
- **Errors** (404/403) are rendered by Joomla's normal error page, which *does* use the template.
- **Caching.** With no `Cache-Control` value set, the component sends no caching header. Pages whose access level isn't *Public* always get `Cache-Control: private, no-cache`, so a proxy can't cache restricted content.


## Releasing

1. Bump `<version>` in `com_custompages/custompages.xml` (and add `administrator/sql/updates/mysql/<version>.sql` if the schema changes).
2. Publish a GitHub release tagged `v<version>` (or `<version>`).
3. The **Release** workflow lints the code, builds `com_custompages-<version>.zip` and the Joomla update feed `updates.xml`, and attaches both to the release. It fails if the tag doesn't match the manifest version, or if the manifest's update server doesn't point at this repository.

Installed sites poll `https://github.com/drek124/CustomPages/releases/latest/download/updates.xml`, which always resolves to the newest **full** release. Pre-releases are never offered as updates, so publishing a release is what ships the update. If the repository is renamed or moved, update `<updateservers>` in the manifest to match.

The **CI** workflow lints the code on PHP 8.1–8.4 and builds the ZIP on every push to `main` and on every pull request. The ZIP is kept as a workflow artifact.

## The Roadmap B)
- Modal page picker (like com_content's article select) instead of the SQL list
- Frontend editing · versioning/history · multilingual associations · template variables/shortcodes
- PostgreSQL install scripts



Licensed under the GNU General Public License version 2 or later.
