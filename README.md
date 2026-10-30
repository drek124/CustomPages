<div align="center">

# 🧩 CustomPages Joomla! Component

**Fully custom HTML or PHP pages for Joomla, with no template involved.**

Write a complete HTML document in CodeMirror, add `<?php ?>` wherever you need it, attach it to a menu item, and Joomla sends out the result with nothing added.

![Joomla](https://img.shields.io/badge/Joomla-5.x-5091cd?logo=joomla&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.1%2B-777bb4?logo=php&logoColor=white)
![Version](https://img.shields.io/badge/version-1.1.0-informational)
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

**com_custompages** stores the document in its own `MEDIUMTEXT` table (up to 16 MB). When the menu item is requested, it discards every output buffer, runs the stored page as PHP, sends headers, echoes the output, and closes the application. The template and the `Document` object never render.

## Features

- ✍️ **CodeMirror editor** with PHP + HTML syntax highlighting (`filter="raw"`, so nothing is stripped)
- 🐘 **PHP anywhere**: every page runs like a PHP template file, with `$app`, `$page` and `$params` in scope
- 🧾 **Byte-identical output** for anything outside `<?php ?>` tags, including `<!DOCTYPE>`, inline `<script>` and `<style>` blocks, and even text like `{loadposition}`, which content plugins never touch
- 📏 **Large pages**: `MEDIUMTEXT` storage instead of menu or module params
- 🔗 **Menu item type** "Custom Page" with a modal page picker (Select / Clear, like the article picker) and SEF URLs (`/your-menu-alias`, or `/component/custompages/page/12-alias` without a menu item)
- 🔐 **Joomla access levels**: unpublished pages return 404, and pages the user can't view return 403
- 🛡️ **ACL**: `core.admin`, `core.options`, `core.manage`, `core.create`, `core.edit`, `core.edit.state`, `core.delete`
- 🗄️ **Optional `Cache-Control` header** per menu item
- 🧱 Modern Joomla 4/5 architecture: namespaced MVC, `services/provider.php`, DI container, Dispatcher, RouterView

## Installation

Requires **Joomla 5.0+** and PHP 8.1+ (the installer refuses older versions).

1. Download `com_custompages-<version>.zip` from the [Releases](../../releases) page, or build it yourself with `python build.py`.
2. In the Joomla administrator, go to **System → Install → Extensions** and upload the ZIP.
3. Open **Components → Custom Pages**.


### Updates

CustomPages uses **Joomla's built-in update system**. New releases appear under **System → Update → Extensions** (and in the update notifications).

## Usage

1. **Components → Custom Pages → New.**
2. Enter a title (the alias is generated if left empty) and paste or write the full PHP document:



3. Set **Status** to *Published* and choose an **Access** level. Save.
4. **Menus → (your menu) → New**, then set **Menu Item Type → Custom Pages → Custom Page**.
5. Under **Select Page**, click **Select** and pick the page from the list (**Clear** removes it). Optionally, on the **HTTP** tab, set a `Cache-Control` value such as `public, max-age=300`.
6. Visit the menu item. *View source* shows exactly your document and nothing else.

> **Note:** The picker lists published and unpublished pages. Unpublished pages return 404 until you publish them.

### PHP pages

Every page is run like a PHP template file: it starts in HTML mode, so a page without `<?php ... ?>` tags is plain HTML and comes out unchanged. Wherever you add tags, that code runs on every request, and whatever the page prints is sent as the response.

```php
<?php
$user = $app->getIdentity();
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"><title><?php echo htmlspecialchars($page->title); ?></title></head>
<body>
  <h1>Hello, <?php echo $user->guest ? 'guest' : htmlspecialchars($user->name); ?></h1>
  <p>Generated at <?php echo date('H:i:s'); ?></p>
</body>
</html>
```

| Variable | What it is |
| --- | --- |
| `$app` | The site application (`Factory::getApplication()`) |
| `$page` | The page row: `id`, `title`, `alias`, `html`, `state`, `access` |
| `$params` | The menu item's params (`Joomla\Registry\Registry`) |

- The default `Content-Type` is `text/html; charset=utf-8`. If the script sets its own, through `header('Content-Type: ...')` or `$app->setHeader()`, that one is kept, so a page can return JSON, XML, CSV, etc.
- Uncaught exceptions and parse errors go to Joomla's error page.
- If your server has `short_open_tag` enabled, a literal `<?` (such as an `<?xml ...?>` declaration) is read as PHP. Print it instead: `<?php echo '<?xml version="1.0"?>'; ?>`.

## ⚠️ Security: this is a trusted-admin feature

Editing a page means **running arbitrary PHP on your server**, with full access to the database, files and configuration, and **writing arbitrary HTML and JavaScript that runs on your site's origin**. A page can read cookies that aren't `HttpOnly`, make authenticated requests as the visiting user (including an administrator), deface the site, or load third-party scripts.

- Grant **Create / Edit** on Custom Pages **only to Super Users or people you'd trust as one**. Anyone who can edit a page can take over the site.
- The default permissions inherit from Global Configuration. Review them under **Custom Pages → Options → Permissions**.
- Joomla's **Text Filters** do not apply to this field (`filter="raw"`, on purpose). That is the whole point of the component, and it is also the risk.
- Page **titles** are escaped in the admin list. Stored pages are never escaped or filtered on the frontend.

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


Licensed under the GNU General Public License version 2 or later.
