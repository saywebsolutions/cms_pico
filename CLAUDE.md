# CMS Pico for Nextcloud

## NO AI ATTRIBUTION
NEVER add Claude/AI attribution to commits, PRs, PR descriptions, or code.

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
- `search.twig` - Lunr.js search (used via `template: search` frontmatter)

## Deployment
```bash
rsync -avz --exclude='.git' --exclude='appdata_public' /home/user/repos/cms_pico/ do3:/tmp/cms_pico/
ssh do3 "sudo rsync -a /tmp/cms_pico/ /var/www/nextcloud/apps/cms_pico/ && sudo chown -R www-data:www-data /var/www/nextcloud/apps/cms_pico/"
ssh do3 "sudo -u www-data php /var/www/nextcloud/occ maintenance:repair"
```

Theme source: `appdata/themes/` → published to `appdata_public/themes/` by repair step.

## Remark42 Comments

**Server:** do3 (`ssh do3`)

### Initial Setup (already done)
```bash
# Download binary
wget https://github.com/umputun/remark42/releases/latest/download/remark42.linux-amd64.tar.gz -O /tmp/remark42.tar.gz
tar xzf /tmp/remark42.tar.gz -C /tmp
sudo mv /tmp/remark42.linux-amd64 /usr/local/bin/remark42
sudo chmod +x /usr/local/bin/remark42

# Create data dir
sudo mkdir -p /var/www/nextcloud/data/remark42
sudo chown www-data:www-data /var/www/nextcloud/data/remark42

# Enable Apache proxy
sudo a2enmod proxy proxy_http
sudo a2enconf remark42
sudo systemctl reload apache2

# Enable systemd service
sudo systemctl daemon-reload
sudo systemctl enable remark42
sudo systemctl start remark42
```

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
Environment=STORE_BOLT_PATH=/var/www/nextcloud/data/remark42
Environment=BACKUP_PATH=/var/www/nextcloud/data/remark42/backup
Environment=AUTH_ANON=true
Environment=AUTH_EMAIL_ENABLE=true
Environment="AUTH_EMAIL_FROM=Remark42 <remark42@saywebsolutions.com>"
Environment=ADMIN_PASSWD=[admin password for imports]
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

### Disqus Import

1. Export from Disqus admin (XML file emailed to you)
2. Create URL map (old URLs → new Pico URLs):
```bash
zcat export.xml.gz | grep -oP '<link>https://oldsite\.com/blog/[^<]+</link>' | \
  sed 's/<\/*link>//g' | while read url; do
  slug=$(echo "$url" | sed 's/?.*//' | sed 's|.*/blog/||')
  echo "$url https://nc.saywebsolutions.com/apps/cms_pico/pico/web/blog/${slug}"
done | sort -u > url_map.txt
```
3. Replace URLs in XML:
```bash
while read old new; do
  sed -i "s|$old|$new|g" export.xml
done < url_map.txt
```
4. Import:
```bash
REMARK_URL=https://nc.saywebsolutions.com/comments \
SECRET=[secret from service file] \
STORE_BOLT_PATH=/var/www/nextcloud/data/remark42 \
ADMIN_PASSWD=[admin password] \
remark42 import -p disqus -f export.xml -s web
```
