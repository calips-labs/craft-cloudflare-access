# Cloudflare Access

Cloudflare Access integration for Craft CMS.

This plugin makes it very easy to integrate [Cloudflare Access](https://www.cloudflare.com/products/zero-trust/access/)
with Craft CMS. Cloudflare Access is a modern, zero trust solution to protect applications or websites. You can use it
to protect the control panel of a Craft website, or even the complete website.

This plugin adds automatic logging in to either the control panel, the frontend or both using the identity provided by
Cloudflare. Cloudflare Access makes it easy to integrate various identity providers, like Okta, Microsoft Azure AD,
Google Workspace or social media accounts like Facebook, GitHub or Google accounts. Cloudflare Access is free up to 50
users. It requires your sites traffic to be proxied through Cloudflare.

## How does this work?

Each application protected by Cloudflare access is protected by a Cloudflare login page. This can be set for a full
domain or a part of it (e.g. only `/admin/`).

Cloudflare injects a [JWT](https://jwt.io/) header which contains a signature, expiry information, and the user's
identity. This plugin decodes the JWT, attempts to find a matching user in Craft CMS, and automatically signs in that
user when the user is not suspended or deactivated.

This way you enable single sign-on for your users, which reduces friction, relieves them from saving another password,
and you increase security when you rely on 2FA from external identity providers.

You may choose to enable this feature for control panel URLs, frontend URLs, or both. This plugin does not create new
users if they do not exist in Craft.

## Requirements

This plugin requires Craft CMS 4.3.5 or later, and PHP 8.0.2 or later.

It also requires a Cloudflare Access application. See below for configuration instructions.

## Installation

You can install this plugin from the Plugin Store or with Composer.

#### From the Plugin Store

Go to the Plugin Store in your project’s Control Panel and search for “Cloudflare Access”. Then press “Install”.

#### With Composer

Open your terminal and run the following commands:

```bash
# go to the project directory
cd /path/to/my-project.test

# tell Composer to load the plugin
composer require calips-labs/craft-cloudflare-access

# tell Craft to install the plugin
./craft plugin/install cloudflare-access
```

## Configuring Cloudflare Access

1. Go to the [Cloudflare Zero Trust dashboard](https://one.dash.cloudflare.com/).
2. Go to Access → Applications and click *Add an application*.
3. Pick *Self-hosted*.
4. Enter a name and set the domain, subdomain and optionally the path.
   If you want to protect the control panel only, enter `/admin/`.
5. Application appearance is only relevant for Cloudflare's app launcher.
6. Select which identity providers you accept. The default is to enable all identity providers.
   For testing, *One-time PIN* could be useful (you enter an e-mail address and then have to enter the PIN code
   sent to it).
7. Click *Next*.
8. You'll now have to create a policy. Enter a name and configure rules below. For testing, you might select *Everyone*
   which would allow everyone to log in. Better rules might check for the domain part of an e-mail address, or Azure
   Group ID's.
9. Click *Next*.
10. The CORS settings, Cookie settings and additional settings can be left unchanged. Click *Add application*.
11. In the applications overview, a new application is added. Click *Edit*.
12. Click *Overview*. Copy the *Application Audiance (AUD) Tag*.
13. Install the Cloudflare Access plugin to Craft, enable it and go to the plugin settings.
14. Enter the AUD tag.
15. You'll also have to enter the Team Domain. You can find this in the Cloudflare control panel under *Settings* →
    General. Copy the team domain including the last part containing `.cloudflareaccess.com`.
16. In the plugin settings, enable auto login for either the control panel and/or frontend.
17. Verify that your token is working as expected in Craft through Utilities → Cloudflare Access. It should show your
    Cloudflare login.

**Tip:**
You can logout from Cloudflare using the following URL:
`https://<team>.cloudflareaccess.com/cdn-cgi/access/logout`
This can be useful during testing.

