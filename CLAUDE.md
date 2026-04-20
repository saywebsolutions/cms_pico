# CMS Pico for Nextcloud

## Blog Content

**Local path:** `/home/user/Nextcloud/CMS/web/`
- `content/blog/*.md` - Blog posts (synced to server via Nextcloud)
- `assets/images/` - Blog images

**Site URL:** `https://nc.saywebsolutions.com/apps/cms_pico/pico/web`

## Theme Structure

**Nav:** `appdata/themes/default/partials/nav.twig` - Edit to add/remove nav items.

**Templates:**
- `index.twig` - Default page template
- `blog-index.twig` - Blog listing (used via `template: blog-index` frontmatter)

## Deployment
```bash
rsync -avz --exclude='.git' --exclude='appdata_public' /home/user/repos/cms_pico/ do3:/tmp/cms_pico/
ssh do3 "sudo rsync -a /tmp/cms_pico/ /var/www/nextcloud/apps/cms_pico/ && sudo chown -R www-data:www-data /var/www/nextcloud/apps/cms_pico/"
ssh do3 "sudo -u www-data php /var/www/nextcloud/occ maintenance:repair"
```

Theme source: `appdata/themes/` → published to `appdata_public/themes/` by repair step.

## Remark42 Comments

**Server:** do3 (`ssh do3`)

**Paths:**
- Binary: `/usr/local/bin/remark42`
- Service: `/etc/systemd/system/remark42.service`
- Data: `/var/www/nextcloud/data/remark42/`
- Apache proxy: `/etc/apache2/conf-available/remark42.conf`

**Enable for Pico site:**
```bash
sudo -u www-data php /var/www/nextcloud/occ config:app:set cms_pico comments_url --value='https://nc.saywebsolutions.com/comments'
```

**Service config (`/etc/systemd/system/remark42.service`):**
```ini
[Service]
Environment=REMARK_URL=https://nc.saywebsolutions.com/comments
Environment=SITE=web
Environment=AUTH_ANON=true
Environment=ADMIN_SHARED_EMAIL=kyle@saywebsolutions.com
Environment=NOTIFY_ADMINS=email
Environment="NOTIFY_EMAIL_FROM=Remark42 <remark42@saywebsolutions.com>"
Environment=SMTP_HOST=smtp.zoho.com
Environment=SMTP_PORT=465
Environment=SMTP_TLS=true
Environment=SMTP_USERNAME=remark42@saywebsolutions.com
Environment=SMTP_PASSWORD=[zoho app password]
```

**Restart:** `sudo systemctl daemon-reload && sudo systemctl restart remark42`
